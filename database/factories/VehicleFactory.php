<?php

namespace Database\Factories;

use App\Enums\DriveType;
use App\Enums\FuelType;
use App\Enums\OwnershipType;
use App\Enums\SteeringPosition;
use App\Enums\TransmissionType;
use App\Enums\VehicleStatus;
use App\Models\Vehicle;
use App\Models\VehicleBrand;
use App\Models\VehicleCategory;
use App\Models\VehicleCondition;
use App\Models\VehicleModel;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Vehicle>
 */
class VehicleFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $brand = VehicleBrand::query()->first()
            ?? VehicleBrand::query()->create(['name' => 'Toyota', 'slug' => 'toyota', 'is_active' => true]);

        $model = VehicleModel::query()->where('vehicle_brand_id', $brand->id)->first()
            ?? VehicleModel::query()->create(['vehicle_brand_id' => $brand->id, 'name' => 'Camry', 'is_active' => true]);

        $category = VehicleCategory::query()->first()
            ?? VehicleCategory::query()->create(['name' => 'Sedan', 'slug' => 'sedan', 'is_active' => true]);

        $condition = VehicleCondition::query()->first()
            ?? VehicleCondition::query()->create(['name' => 'Very Good', 'slug' => 'very-good', 'severity_order' => 2, 'badge_variant' => 'success', 'is_active' => true]);

        $ref = 'ANK-VEH-2026-'.fake()->unique()->numerify('####');
        $year = fake()->numberBetween(2016, 2025);

        return [
            'reference_number' => $ref,
            'slug' => Str::slug("{$year}-{$brand->name}-{$model->name}-{$ref}"),
            'ownership_type' => OwnershipType::CompanyOwned,
            'vehicle_brand_id' => $brand->id,
            'vehicle_model_id' => $model->id,
            'vehicle_category_id' => $category->id,
            'vehicle_condition_id' => $condition->id,
            'year' => $year,
            'vin' => strtoupper(Str::random(17)),
            'chassis_number' => strtoupper(Str::random(17)),
            'transmission' => TransmissionType::Automatic,
            'fuel_type' => FuelType::Petrol,
            'drive_type' => DriveType::Fwd,
            'exterior_colour' => fake()->randomElement(['Pearl White', 'Obsidian Black', 'Silver Metallic', 'Graphite Grey', 'Midnight Blue']),
            'interior_colour' => fake()->randomElement(['Black Leather', 'Beige Leather', 'Tan Leather']),
            'mileage' => fake()->numberBetween(18000, 95000),
            'steering_position' => SteeringPosition::LeftHand,
            'doors' => 4,
            'status' => VehicleStatus::Available,
            'location' => 'Main Showroom Bay A',
            'received_at' => now()->subDays(fake()->numberBetween(1, 30))->toDateString(),
            'registered_at' => now(),
            'acquisition_cost' => '11500000.00',
            'advertised_price' => '14500000.00',
            'minimum_price' => '13500000.00',
            'is_public_listing' => true,
        ];
    }

    public function consignment(): static
    {
        return $this->state(fn (array $attributes): array => [
            'ownership_type' => OwnershipType::Consignment,
            'acquisition_cost' => null,
            'acquisition_source' => null,
            'acquired_from' => null,
        ]);
    }
}
