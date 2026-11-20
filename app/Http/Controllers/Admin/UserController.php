<?php

namespace App\Http\Controllers\Admin;

use App\Enums\SystemRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\Role;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function __construct(
        private AuditLogger $auditLogger,
    ) {}

    public function index(Request $request): Response
    {
        $search = trim((string) $request->string('search'));
        $roleFilter = trim((string) $request->string('role'));
        $statusFilter = trim((string) $request->string('status'));
        $perPage = in_array($request->integer('per_page', 15), [10, 15, 25, 50], true)
            ? $request->integer('per_page', 15)
            : 15;
        $sort = in_array($request->string('sort')->toString(), ['name', 'email', 'is_active', 'last_login_at', 'created_at'], true)
            ? $request->string('sort')->toString()
            : 'name';
        $direction = $request->string('direction')->toString() === 'desc' ? 'desc' : 'asc';

        $users = User::query()
            ->with('roles')
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($sub) use ($search): void {
                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->when($roleFilter !== '', function ($query) use ($roleFilter): void {
                $query->whereHas('roles', fn ($q) => $q->where('name', $roleFilter));
            })
            ->when($statusFilter === 'active', fn ($query) => $query->where('is_active', true))
            ->when($statusFilter === 'inactive', fn ($query) => $query->where('is_active', false))
            ->orderBy($sort, $direction)
            ->paginate($perPage)
            ->withQueryString()
            ->through(static function (User $user): array {
                $role = $user->roles->first();

                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'is_active' => $user->is_active,
                    'role_name' => $role instanceof Role ? $role->name : null,
                    'role_display_name' => $role instanceof Role ? ($role->display_name ?? $role->name) : 'Unassigned',
                    'last_login_at' => $user->last_login_at?->toISOString(),
                    'created_at' => $user->created_at?->toISOString(),
                ];
            });

        $roles = Role::query()
            ->orderBy('id')
            ->get()
            ->map(static fn (Role $role): array => [
                'name' => $role->name,
                'display_name' => $role->display_name ?? $role->name,
                'description' => $role->description,
            ]);

        return Inertia::render('admin/users/Index', [
            'users' => $users,
            'roles' => $roles,
            'filters' => [
                'search' => $search,
                'role' => $roleFilter,
                'status' => $statusFilter,
                'sort' => $sort,
                'direction' => $direction,
                'per_page' => $perPage,
            ],
        ]);
    }

    public function create(): Response
    {
        $roles = Role::query()
            ->orderBy('id')
            ->get()
            ->map(static fn (Role $role): array => [
                'name' => $role->name,
                'display_name' => $role->display_name ?? $role->name,
                'description' => $role->description,
            ]);

        return Inertia::render('admin/users/Create', [
            'roles' => $roles,
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        /** @var User $user */
        $user = User::query()->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'password' => Hash::make($validated['password']),
            'is_active' => (bool) ($validated['is_active'] ?? true),
            'email_verified_at' => now(),
        ]);

        $user->syncRoles([$validated['role']]);

        $this->auditLogger->audit(
            event: 'user.created',
            auditable: $user,
            newValues: [
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'role' => $validated['role'],
                'is_active' => $user->is_active,
            ],
        );

        $this->auditLogger->activity(
            action: 'user.created',
            description: "Created staff account for {$user->name} ({$user->email}).",
            subject: $user,
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "Staff account for {$user->name} created successfully.",
        ]);

        return redirect()->route('admin.users.index');
    }

    public function edit(User $user): Response
    {
        $user->load('roles');
        $role = $user->roles->first();

        $roles = Role::query()
            ->orderBy('id')
            ->get()
            ->map(static fn (Role $r): array => [
                'name' => $r->name,
                'display_name' => $r->display_name ?? $r->name,
                'description' => $r->description,
            ]);

        return Inertia::render('admin/users/Edit', [
            'managedUser' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'is_active' => $user->is_active,
                'role' => $role instanceof Role ? $role->name : '',
                'last_login_at' => $user->last_login_at?->toISOString(),
            ],
            'roles' => $roles,
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $validated = $request->validated();
        /** @var User $actor */
        $actor = $request->user();

        if ($actor->id === $user->id && (! (bool) $validated['is_active'] || $validated['role'] !== SystemRole::SuperAdministrator->value)) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => 'You cannot deactivate your own account or remove your own Super Administrator role.',
            ]);

            return back();
        }

        $oldValues = [
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'role' => $user->getRoleNames()->first(),
            'is_active' => $user->is_active,
        ];

        $attributes = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'is_active' => (bool) $validated['is_active'],
        ];

        if (! empty($validated['password'])) {
            $attributes['password'] = Hash::make($validated['password']);
        }

        $user->update($attributes);
        $user->syncRoles([$validated['role']]);

        $this->auditLogger->audit(
            event: 'user.updated',
            auditable: $user,
            oldValues: $oldValues,
            newValues: [
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'role' => $validated['role'],
                'is_active' => $user->is_active,
                'password_changed' => ! empty($validated['password']),
            ],
        );

        $this->auditLogger->activity(
            action: 'user.updated',
            description: "Updated staff account for {$user->name}.",
            subject: $user,
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "Staff account for {$user->name} updated successfully.",
        ]);

        return redirect()->route('admin.users.index');
    }

    public function toggleStatus(Request $request, User $user): RedirectResponse
    {
        /** @var User $actor */
        $actor = $request->user();

        if ($actor->id === $user->id) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => 'You cannot deactivate your own active session account.',
            ]);

            return back();
        }

        $previous = $user->is_active;
        $user->update(['is_active' => ! $previous]);

        $statusLabel = $user->is_active ? 'activated' : 'deactivated';

        $this->auditLogger->audit(
            event: $user->is_active ? 'user.activated' : 'user.deactivated',
            auditable: $user,
            oldValues: ['is_active' => $previous],
            newValues: ['is_active' => $user->is_active],
        );

        $this->auditLogger->activity(
            action: "user.{$statusLabel}",
            description: ucfirst($statusLabel)." user account for {$user->name}.",
            subject: $user,
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "{$user->name}'s account has been {$statusLabel}.",
        ]);

        return back();
    }
}
