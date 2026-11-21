<?php

use App\Enums\SystemRole;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('all listing index pages return ok with empty datasets', function () {
    $superAdmin = User::factory()->create();
    $superAdmin->assignRole(SystemRole::SuperAdministrator->value);

    $paginatedListingRoutes = [
        ['route' => 'vehicles.index', 'component' => 'vehicles/Index', 'prop' => 'vehicles.data'],
        ['route' => 'owners.index', 'component' => 'owners/Index', 'prop' => 'owners.data'],
        ['route' => 'consignments.index', 'component' => 'consignments/Index', 'prop' => 'consignments.data'],
        ['route' => 'expenses.index', 'component' => 'expenses/Index', 'prop' => 'expenses.data'],
        ['route' => 'customers.index', 'component' => 'customers/Index', 'prop' => 'customers.data'],
        ['route' => 'reservations.index', 'component' => 'reservations/Index', 'prop' => 'reservations.data'],
        ['route' => 'sales.index', 'component' => 'sales/Index', 'prop' => 'sales.data'],
        ['route' => 'withdrawals.index', 'component' => 'withdrawals/Index', 'prop' => 'withdrawals.data'],
        ['route' => 'settlements.index', 'component' => 'settlements/Index', 'prop' => 'settlements.data'],
        ['route' => 'finance.index', 'component' => 'finance/Index', 'prop' => 'transactions.data'],
        ['route' => 'marketing.index', 'component' => 'marketing/Index', 'prop' => 'vehicles.data'],
        ['route' => 'documents.index', 'component' => 'documents/Index', 'prop' => 'documents.data'],
        ['route' => 'notifications.index', 'component' => 'notifications/Index', 'prop' => 'notifications.data'],
        ['route' => 'admin.audit-logs.index', 'component' => 'admin/audit/Index', 'prop' => 'logs.data'],
        ['route' => 'admin.master-data.index', 'component' => 'admin/master-data/Index', 'prop' => 'items.data'],
    ];

    foreach ($paginatedListingRoutes as $case) {
        $this->actingAs($superAdmin)
            ->get(route($case['route']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component($case['component'])
                ->where($case['prop'], [])
            );
    }

    $this->actingAs($superAdmin)
        ->get(route('admin.users.index', ['search' => '__no_matching_user__']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/users/Index')
            ->where('users.data', [])
        );

    $this->actingAs($superAdmin)
        ->get(route('admin.roles.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('admin/roles/Index'));

    $this->actingAs($superAdmin)
        ->get(route('reports.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('reports/Index'));
});
