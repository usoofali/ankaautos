<?php

use App\Enums\AttendantActivityType;
use App\Enums\FuelType;
use App\Enums\OwnershipType;
use App\Enums\PhotoCategory;
use App\Enums\SteeringPosition;
use App\Enums\SystemRole;
use App\Enums\TransmissionType;
use App\Enums\VehicleStatus;
use App\Models\AuditLog;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleBrand;
use App\Models\VehicleCategory;
use App\Models\VehicleCondition;
use App\Models\VehicleModel;
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->seed([
        RolesAndPermissionsSeeder::class,
        MasterDataSeeder::class,
    ]);
});

function vehiclePayload(array $overrides = []): array
{
    $brand = VehicleBrand::query()->where('name', 'Toyota')->firstOrFail();
    $model = VehicleModel::query()->where('vehicle_brand_id', $brand->id)->firstOrFail();
    $category = VehicleCategory::query()->firstOrFail();
    $condition = VehicleCondition::query()->firstOrFail();

    return array_merge([
        'ownership_type' => OwnershipType::CompanyOwned->value,
        'vehicle_brand_id' => $brand->id,
        'vehicle_model_id' => $model->id,
        'vehicle_category_id' => $category->id,
        'vehicle_condition_id' => $condition->id,
        'year' => 2022,
        'vin' => '4T1B11HK5NU123456',
        'chassis_number' => 'CHASSIS-2022-001',
        'transmission' => TransmissionType::Automatic->value,
        'fuel_type' => FuelType::Petrol->value,
        'exterior_colour' => 'Pearl White',
        'steering_position' => SteeringPosition::LeftHand->value,
        'status' => VehicleStatus::Available->value,
        'received_at' => now()->toDateString(),
        'acquisition_cost' => '12000000.00',
        'advertised_price' => '15000000.00',
    ], $overrides);
}

test('inventory officer can register a vehicle and active duplicate VIN is rejected', function () {
    $officer = User::factory()->create();
    $officer->assignRole(SystemRole::InventoryOfficer->value);

    $this->actingAs($officer)
        ->post(route('vehicles.store'), vehiclePayload())
        ->assertRedirect();

    $vehicle = Vehicle::query()->where('vin', '4T1B11HK5NU123456')->firstOrFail();

    expect($vehicle->reference_number)->toStartWith('ANK-VEH-')
        ->and($vehicle->statusHistories()->count())->toBe(1)
        ->and($vehicle->conditionHistories()->count())->toBe(1);

    // Duplicate active VIN must be blocked even for a Manager
    $manager = User::factory()->create();
    $manager->assignRole(SystemRole::Manager->value);

    $this->actingAs($manager)
        ->post(route('vehicles.store'), vehiclePayload([
            'duplicate_vin_override_reason' => 'Attempting duplicate active VIN',
        ]))
        ->assertSessionHasErrors('vin');
});

test('returning sold vehicle VIN requires manager override and logged reason', function () {
    Vehicle::factory()->create([
        'vin' => 'JTMB1RFV9KD999999',
        'status' => VehicleStatus::Sold,
    ]);

    $officer = User::factory()->create();
    $officer->assignRole(SystemRole::InventoryOfficer->value);

    $this->actingAs($officer)
        ->post(route('vehicles.store'), vehiclePayload([
            'vin' => 'JTMB1RFV9KD999999',
            'chassis_number' => 'NEW-CHASSIS-999',
        ]))
        ->assertSessionHasErrors('vin');

    $manager = User::factory()->create();
    $manager->assignRole(SystemRole::Manager->value);

    // Without reason -> rejected
    $this->actingAs($manager)
        ->post(route('vehicles.store'), vehiclePayload([
            'vin' => 'JTMB1RFV9KD999999',
            'chassis_number' => 'NEW-CHASSIS-999',
        ]))
        ->assertSessionHasErrors('duplicate_vin_override_reason');

    // With reason -> allowed & audited
    $this->actingAs($manager)
        ->post(route('vehicles.store'), vehiclePayload([
            'vin' => 'JTMB1RFV9KD999999',
            'chassis_number' => 'NEW-CHASSIS-999',
            'duplicate_vin_override_reason' => 'Previous buyer traded this vehicle back in.',
        ]))
        ->assertRedirect();

    expect(Vehicle::query()->where('vin', 'JTMB1RFV9KD999999')->count())->toBe(2)
        ->and(AuditLog::query()->where('event', 'vehicle.registered_with_vin_override')->exists())->toBeTrue();
});

test('vehicle attendant can log physical care activities and cannot edit vehicle specs or view acquisition cost', function () {
    $attendant = User::factory()->create();
    $attendant->assignRole(SystemRole::VehicleAttendant->value);

    $vehicle = Vehicle::factory()->create([
        'acquisition_cost' => '13500000.00',
    ]);

    $this->actingAs($attendant)
        ->get(route('vehicles.show', $vehicle))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('vehicle.acquisition_cost', null)
        );

    $this->actingAs($attendant)
        ->get(route('vehicles.edit', $vehicle))
        ->assertForbidden();

    $this->actingAs($attendant)
        ->post(route('vehicles.attendant-logs.store', $vehicle), [
            'activity_type' => AttendantActivityType::Washing->value,
            'description' => 'Full interior & exterior detailing completed.',
            'location_from' => 'Wash Bay',
            'location_to' => 'Showroom Front Row',
            'update_vehicle_location' => true,
        ])
        ->assertRedirect();

    expect($vehicle->fresh()->location)->toBe('Showroom Front Row')
        ->and($vehicle->attendantLogs()->count())->toBe(1);
});

test('inventory officer can upload, promote primary, and delete vehicle photos', function () {
    Storage::fake('public');

    $officer = User::factory()->create();
    $officer->assignRole(SystemRole::InventoryOfficer->value);
    $vehicle = Vehicle::factory()->create();

    $this->actingAs($officer)
        ->post(route('vehicles.photos.store', $vehicle), [
            'photos' => [
                UploadedFile::fake()->image('front.jpg', 800, 600),
                UploadedFile::fake()->image('rear.jpg', 800, 600),
            ],
            'category' => PhotoCategory::Front->value,
            'caption' => 'Exterior shots',
        ])
        ->assertRedirect();

    $photos = $vehicle->photos()->orderBy('id')->get();
    expect($photos)->toHaveCount(2)
        ->and($photos[0]->is_primary)->toBeTrue()
        ->and($photos[1]->is_primary)->toBeFalse();

    $this->actingAs($officer)
        ->patch(route('vehicles.photos.primary', ['vehicle' => $vehicle, 'photo' => $photos[1]]))
        ->assertRedirect();

    expect($photos[0]->fresh()->is_primary)->toBeFalse()
        ->and($photos[1]->fresh()->is_primary)->toBeTrue();

    $this->actingAs($officer)
        ->delete(route('vehicles.photos.destroy', ['vehicle' => $vehicle, 'photo' => $photos[1]]))
        ->assertRedirect();

    expect($vehicle->photos()->count())->toBe(1)
        ->and($photos[0]->fresh()->is_primary)->toBeTrue();
});
