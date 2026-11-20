<?php

namespace App\Http\Controllers\Admin;

use App\Enums\SystemRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateRolePermissionsRequest;
use App\Models\Permission;
use App\Models\Role;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\PermissionRegistrar;

class RoleController extends Controller
{
    public function __construct(
        private AuditLogger $auditLogger,
    ) {}

    public function index(): Response
    {
        $roles = Role::query()
            ->withCount(['users', 'permissions'])
            ->orderBy('id')
            ->get()
            ->map(static fn (Role $role): array => [
                'id' => $role->id,
                'name' => $role->name,
                'display_name' => $role->display_name ?? $role->name,
                'description' => $role->description ?? '',
                'purpose' => $role->purpose ?? '',
                'responsibilities_summary' => $role->responsibilities_summary ?? '',
                'users_count' => $role->users_count,
                'permissions_count' => $role->permissions_count,
                'is_super_admin' => $role->name === SystemRole::SuperAdministrator->value,
            ]);

        return Inertia::render('admin/roles/Index', [
            'roles' => $roles,
        ]);
    }

    public function edit(Role $role): Response
    {
        $role->load('permissions');

        $groupedPermissions = Permission::query()
            ->orderBy('sort_order')
            ->get()
            ->groupBy('module_group')
            ->map(static fn ($items, string $group): array => [
                'group' => $group,
                'permissions' => $items->map(static fn (Permission $permission): array => [
                    'name' => $permission->name,
                    'display_name' => $permission->display_name ?? $permission->name,
                    'description' => $permission->description ?? '',
                ])->values()->all(),
            ])
            ->values()
            ->all();

        return Inertia::render('admin/roles/Edit', [
            'role' => [
                'id' => $role->id,
                'name' => $role->name,
                'display_name' => $role->display_name ?? $role->name,
                'description' => $role->description ?? '',
                'purpose' => $role->purpose ?? '',
                'responsibilities_summary' => $role->responsibilities_summary ?? '',
                'permissions' => $role->permissions->pluck('name')->values()->all(),
                'is_super_admin' => $role->name === SystemRole::SuperAdministrator->value,
            ],
            'permissionGroups' => $groupedPermissions,
        ]);
    }

    public function update(UpdateRolePermissionsRequest $request, Role $role): RedirectResponse
    {
        $validated = $request->validated();
        /** @var list<string> $permissions */
        $permissions = array_values(array_unique($validated['permissions']));

        if ($role->name === SystemRole::SuperAdministrator->value) {
            foreach (['users.manage', 'roles.manage', 'settings.manage'] as $protected) {
                if (! in_array($protected, $permissions, true)) {
                    $permissions[] = $protected;
                }
            }
        }

        $oldValues = [
            'display_name' => $role->display_name,
            'description' => $role->description,
            'permissions' => $role->permissions()->pluck('name')->sort()->values()->all(),
        ];

        $role->update([
            'display_name' => $validated['display_name'],
            'description' => $validated['description'],
            'purpose' => $validated['purpose'] ?? null,
            'responsibilities_summary' => $validated['responsibilities_summary'] ?? null,
        ]);

        $role->syncPermissions($permissions);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        sort($permissions);

        $this->auditLogger->audit(
            event: 'role.permissions_updated',
            auditable: $role,
            oldValues: $oldValues,
            newValues: [
                'display_name' => $role->display_name,
                'description' => $role->description,
                'permissions' => $permissions,
            ],
        );

        $this->auditLogger->activity(
            action: 'role.updated',
            description: "Updated role permissions and documentation for {$role->display_name}.",
            subject: $role,
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "User permissions for {$role->display_name} updated successfully.",
        ]);

        return redirect()->route('admin.roles.index');
    }
}
