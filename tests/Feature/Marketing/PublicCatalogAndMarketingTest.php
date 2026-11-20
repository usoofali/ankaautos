<?php

use App\Enums\SystemRole;
use App\Enums\VehicleStatus;
use App\Models\Advertisement;
use App\Models\AdvertisementShare;
use App\Models\User;
use App\Models\Vehicle;
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function () {
    $this->seed([
        RolesAndPermissionsSeeder::class,
        MasterDataSeeder::class,
    ]);
});

test('public storefront shows publicly listed available vehicles without leaking internal costs or notes', function () {
    $publicVehicle = Vehicle::factory()->create([
        'status' => VehicleStatus::Available,
        'is_public_listing' => true,
        'acquisition_cost' => '11000000.00',
        'advertised_price' => '14500000.00',
        'internal_notes' => 'CONFIDENTIAL_SECRET_INTERNAL_NOTE',
        'vin' => 'SECRET_VIN_99887766',
    ]);

    $hiddenVehicle = Vehicle::factory()->create([
        'status' => VehicleStatus::Available,
        'is_public_listing' => false,
    ]);

    // Public home catalog returns 200 and includes public vehicle only
    $this->get(route('home'))
        ->assertOk()
        ->assertDontSee('CONFIDENTIAL_SECRET_INTERNAL_NOTE')
        ->assertDontSee('SECRET_VIN_99887766');

    // Public detail page includes OpenGraph tags and hides internal secrets
    $this->get(route('public.listings.show', $publicVehicle->slug))
        ->assertOk()
        ->assertSee('og:title', false)
        ->assertSee('og:image', false)
        ->assertDontSee('CONFIDENTIAL_SECRET_INTERNAL_NOTE')
        ->assertDontSee('SECRET_VIN_99887766');

    // Unlisted vehicle returns 404 publicly
    $this->get(route('public.listings.show', $hiddenVehicle->slug))
        ->assertNotFound();
});

test('sales officer can build vehicle advertisement, toggle public visibility, and log social shares', function () {
    $officer = User::factory()->create();
    $officer->assignRole(SystemRole::SalesOfficer->value);

    $vehicle = Vehicle::factory()->create([
        'status' => VehicleStatus::Available,
        'is_public_listing' => false,
    ]);

    $this->actingAs($officer)
        ->get(route('marketing.index'))
        ->assertOk();

    $this->actingAs($officer)
        ->get(route('marketing.builder', $vehicle))
        ->assertOk();

    $this->actingAs($officer)
        ->post(route('marketing.advertisement.save', $vehicle), [
            'headline' => 'Clean 2021 SUV Ready for Inspection',
            'caption' => 'Contact Anka Auto Sales today for inspection and test drive.',
            'is_publicly_listed' => true,
        ])
        ->assertRedirect();

    expect($vehicle->fresh()->is_public_listing)->toBeTrue()
        ->and(Advertisement::query()->where('vehicle_id', $vehicle->id)->count())->toBe(1);

    $this->actingAs($officer)
        ->post(route('marketing.share.log', $vehicle), [
            'platform' => 'whatsapp',
        ])
        ->assertRedirect();

    expect(AdvertisementShare::query()->where('vehicle_id', $vehicle->id)->where('platform', 'whatsapp')->count())->toBe(1);
});
