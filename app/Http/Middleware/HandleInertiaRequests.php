<?php

namespace App\Http\Middleware;

use App\Models\Role;
use App\Models\User;
use App\Services\BusinessSettingService;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Schema;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    public function __construct(
        private BusinessSettingService $businessSettings,
    ) {}

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        /** @var User|null $user */
        $user = $request->user();
        $business = $this->businessSettings->publicProfile();

        return [
            ...parent::share($request),
            'name' => $business['company_name'] ?: config('app.name'),
            'business' => $business,
            'auth' => [
                'user' => $user !== null ? $this->serializeUser($user) : null,
                'permissions' => $user !== null ? $user->getAllPermissions()->pluck('name')->values()->all() : [],
                'roles' => $user !== null ? $user->roles->map(static fn ($role): array => [
                    'name' => $role instanceof Role ? $role->name : (string) $role->getAttribute('name'),
                    'display_name' => $role instanceof Role ? ($role->display_name ?? $role->name) : (string) $role->getAttribute('name'),
                ])->values()->all() : [],
            ],
            'notifications' => $user !== null ? $this->serializeNotifications($user) : [
                'unread_count' => 0,
                'recent' => [],
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeUser(User $user): array
    {
        $user->loadMissing('roles');
        $primaryRole = $user->roles->first();

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'is_active' => $user->is_active,
            'email_verified_at' => $user->email_verified_at?->toISOString(),
            'last_login_at' => $user->last_login_at?->toISOString(),
            'primary_role' => $primaryRole instanceof Role ? ($primaryRole->display_name ?? $primaryRole->name) : null,
            'created_at' => $user->created_at?->toISOString(),
            'updated_at' => $user->updated_at?->toISOString(),
        ];
    }

    /**
     * @return array{unread_count: int, recent: list<array<string, mixed>>}
     */
    private function serializeNotifications(User $user): array
    {
        if (! Schema::hasTable('notifications')) {
            return ['unread_count' => 0, 'recent' => []];
        }

        $unreadCount = $user->unreadNotifications()->count();
        $recent = $user->notifications()
            ->latest()
            ->limit(5)
            ->get()
            ->map(static function (DatabaseNotification $notification): array {
                /** @var array<string, mixed> $data */
                $data = $notification->data;

                return [
                    'id' => $notification->id,
                    'title' => (string) ($data['title'] ?? 'Notification'),
                    'message' => (string) ($data['message'] ?? ''),
                    'category' => (string) ($data['category'] ?? 'general'),
                    'action_url' => isset($data['action_url']) ? (string) $data['action_url'] : null,
                    'severity' => (string) ($data['severity'] ?? 'info'),
                    'read_at' => $notification->read_at?->toISOString(),
                    'created_at' => $notification->created_at?->toISOString(),
                ];
            })
            ->values()
            ->all();

        return [
            'unread_count' => $unreadCount,
            'recent' => array_values($recent),
        ];
    }
}
