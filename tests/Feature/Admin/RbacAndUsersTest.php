<?php

use App\Enums\SystemRole;
use App\Models\AuditLog;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('all seven system roles and permissions are seeded with human-readable descriptions', function () {
    expect(Role::query()->count())->toBe(7)
        ->and(Permission::query()->whereNull('display_name')->count())->toBe(0)
        ->and(Permission::query()->whereNull('description')->count())->toBe(0)
        ->and(Role::query()->whereNull('display_name')->count())->toBe(0)
        ->and(Role::query()->whereNull('purpose')->count())->toBe(0);
});

test('super administrator can create, update, and toggle staff accounts with audit trail', function () {
    $admin = User::factory()->create();
    $admin->assignRole(SystemRole::SuperAdministrator->value);

    $this->actingAs($admin)
        ->post(route('admin.users.store'), [
            'name' => 'Amina Bello',
            'email' => 'amina@ankaautos.com',
            'phone' => '+2348031112222',
            'role' => SystemRole::SalesOfficer->value,
            'password' => 'StrongPass123!',
            'password_confirmation' => 'StrongPass123!',
            'is_active' => true,
        ])
        ->assertRedirect(route('admin.users.index'));

    $staff = User::query()->where('email', 'amina@ankaautos.com')->firstOrFail();

    expect($staff->hasRole(SystemRole::SalesOfficer->value))->toBeTrue()
        ->and(AuditLog::query()->where('event', 'user.created')->where('auditable_id', $staff->id)->exists())->toBeTrue();

    $this->actingAs($admin)
        ->patch(route('admin.users.toggle-status', $staff))
        ->assertRedirect();

    expect($staff->fresh()->is_active)->toBeFalse()
        ->and(AuditLog::query()->where('event', 'user.deactivated')->where('auditable_id', $staff->id)->exists())->toBeTrue();
});

test('deactivated users cannot sign in and active sessions are terminated', function () {
    $inactive = User::factory()->inactive()->create();
    $inactive->assignRole(SystemRole::SalesOfficer->value);

    $this->post(route('login.store'), [
        'email' => $inactive->email,
        'password' => 'password',
    ]);

    $this->assertGuest();

    $this->actingAs($inactive)
        ->get(route('dashboard'))
        ->assertRedirect(route('login'));

    $this->assertGuest();
});

test('super administrator cannot deactivate their own account', function () {
    $admin = User::factory()->create();
    $admin->assignRole(SystemRole::SuperAdministrator->value);

    $this->actingAs($admin)
        ->patch(route('admin.users.toggle-status', $admin))
        ->assertRedirect();

    expect($admin->fresh()->is_active)->toBeTrue();
});

test('non-admin roles are forbidden from administrative endpoints', function (string $roleName) {
    $user = User::factory()->create();
    $user->assignRole($roleName);

    $this->actingAs($user)->get(route('admin.users.index'))->assertForbidden();
    $this->actingAs($user)->get(route('admin.roles.index'))->assertForbidden();
    $this->actingAs($user)->get(route('admin.business-settings.edit'))->assertForbidden();
})->with([
    SystemRole::SalesOfficer->value,
    SystemRole::InventoryOfficer->value,
    SystemRole::Accountant->value,
    SystemRole::VehicleAttendant->value,
    SystemRole::Viewer->value,
]);

test('super admin can update role permissions and documentation while protecting root admin access', function () {
    $admin = User::factory()->create();
    $admin->assignRole(SystemRole::SuperAdministrator->value);

    $viewerRole = Role::query()->where('name', SystemRole::Viewer->value)->firstOrFail();

    $this->actingAs($admin)
        ->put(route('admin.roles.update', $viewerRole), [
            'display_name' => 'Executive Viewer',
            'description' => 'Read-only monitoring access.',
            'purpose' => 'Oversight',
            'responsibilities_summary' => 'Review reports.',
            'permissions' => ['dashboard.view', 'vehicles.view'],
        ])
        ->assertRedirect(route('admin.roles.index'));

    expect($viewerRole->fresh()->display_name)->toBe('Executive Viewer')
        ->and($viewerRole->fresh()->permissions()->pluck('name')->all())->toEqualCanonicalizing(['dashboard.view', 'vehicles.view']);

    $superAdminRole = Role::query()->where('name', SystemRole::SuperAdministrator->value)->firstOrFail();

    $this->actingAs($admin)
        ->put(route('admin.roles.update', $superAdminRole), [
            'display_name' => 'Super Administrator',
            'description' => 'Root role.',
            'permissions' => ['dashboard.view'],
        ])
        ->assertRedirect(route('admin.roles.index'));

    expect($superAdminRole->fresh()->hasPermissionTo('users.manage'))->toBeTrue()
        ->and($superAdminRole->fresh()->hasPermissionTo('roles.manage'))->toBeTrue();
});
