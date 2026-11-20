<?php

namespace Database\Factories;

use App\Enums\CommissionType;
use App\Enums\ConsignmentStatus;
use App\Models\Consignment;
use App\Models\Vehicle;
use App\Models\VehicleOwner;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Consignment>
 */
class ConsignmentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'reference_number' => 'ANK-CON-2026-'.fake()->unique()->numerify('####'),
            'vehicle_id' => Vehicle::factory()->consignment(),
            'vehicle_owner_id' => VehicleOwner::factory(),
            'status' => ConsignmentStatus::Active,
            'received_at' => now()->toDateString(),
            'expected_review_date' => now()->addDays(60)->toDateString(),
            'agreed_selling_price' => '16000000.00',
            'minimum_acceptable_price' => '14500000.00',
            'commission_type' => CommissionType::Percentage,
            'commission_value' => '5.00',
        ];
    }
}
