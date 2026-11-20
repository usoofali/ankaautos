<?php

use App\Enums\CommissionType;
use App\Enums\SystemRole;
use App\Models\Consignment;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleOwner;
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function () {
    $this->seed([
        RolesAndPermissionsSeeder::class,
        MasterDataSeeder::class,
    ]);
});

test('inventory officer can register owner and create versioned consignment agreement', function () {
    $officer = User::factory()->create();
    $officer->assignRole(SystemRole::InventoryOfficer->value);

    $this->actingAs($officer)
        ->post(route('owners.store'), [
            'owner_category' => 'individual',
            'name' => 'Alhaji Sani Danladi',
            'phone' => '+2348095556666',
            'bank_name' => 'Zenith Bank',
            'bank_account_name' => 'Sani Danladi',
            'bank_account_number' => '2001234567',
        ])
        ->assertRedirect();

    $owner = VehicleOwner::query()->where('name', 'Alhaji Sani Danladi')->firstOrFail();
    $consignmentVehicle = Vehicle::factory()->consignment()->create();
    $companyVehicle = Vehicle::factory()->create();

    // Reject linking company-owned vehicle
    $this->actingAs($officer)
        ->post(route('consignments.store'), [
            'vehicle_id' => $companyVehicle->id,
            'vehicle_owner_id' => $owner->id,
            'received_at' => now()->toDateString(),
            'agreed_selling_price' => '18000000.00',
            'commission_type' => CommissionType::Percentage->value,
            'commission_value' => '5',
        ])
        ->assertSessionHasErrors('vehicle_id');

    // Valid consignment creation
    $this->actingAs($officer)
        ->post(route('consignments.store'), [
            'vehicle_id' => $consignmentVehicle->id,
            'vehicle_owner_id' => $owner->id,
            'received_at' => now()->toDateString(),
            'agreed_selling_price' => '18000000.00',
            'minimum_acceptable_price' => '16500000.00',
            'commission_type' => CommissionType::ExcessOverMinimum->value,
        ])
        ->assertRedirect();

    $consignment = Consignment::query()->where('vehicle_id', $consignmentVehicle->id)->firstOrFail();

    expect($consignment->reference_number)->toStartWith('ANK-CON-')
        ->and($consignment->agreement?->current_version)->toBe(1)
        ->and($consignment->agreement?->versions()->count())->toBe(1)
        ->and($consignmentVehicle->fresh()->advertised_price)->toBe('18000000.00');

    // Download generated PDF agreement
    $this->actingAs($officer)
        ->get(route('consignments.agreement-pdf', $consignment))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');
});

test('only manager or super admin can amend consignment financial terms and previous version is preserved', function () {
    $officer = User::factory()->create();
    $officer->assignRole(SystemRole::InventoryOfficer->value);

    $manager = User::factory()->create();
    $manager->assignRole(SystemRole::Manager->value);

    $consignment = Consignment::factory()->create([
        'agreed_selling_price' => '16000000.00',
        'commission_type' => CommissionType::Percentage,
        'commission_value' => '5.00',
    ]);
    $agreement = $consignment->agreement()->create([
        'current_version' => 1,
        'commencement_date' => now()->toDateString(),
    ]);
    $agreement->versions()->create([
        'version_number' => 1,
        'agreed_selling_price' => '16000000.00',
        'commission_type' => CommissionType::Percentage,
        'commission_value' => '5.00',
        'amendment_reason' => 'Initial terms',
        'created_by' => $officer->id,
        'created_at' => now(),
    ]);

    // Inventory officer is forbidden from amending financial terms
    $this->actingAs($officer)
        ->post(route('consignments.amend-terms', $consignment), [
            'agreed_selling_price' => '15500000.00',
            'commission_type' => CommissionType::Fixed->value,
            'commission_value' => '600000.00',
            'amendment_reason' => 'Owner requested price reduction.',
        ])
        ->assertForbidden();

    // Manager succeeds and creates v2
    $this->actingAs($manager)
        ->post(route('consignments.amend-terms', $consignment), [
            'agreed_selling_price' => '15500000.00',
            'commission_type' => CommissionType::Fixed->value,
            'commission_value' => '600000.00',
            'amendment_reason' => 'Owner approved ₦500k price reduction for faster sale.',
        ])
        ->assertRedirect();

    expect($consignment->fresh()->agreed_selling_price)->toBe('15500000.00')
        ->and($consignment->agreement->fresh()->current_version)->toBe(2)
        ->and($consignment->agreement->versions()->count())->toBe(2);
});
