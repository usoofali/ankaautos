<?php

namespace Database\Factories;

use App\Enums\OwnerCategory;
use App\Models\VehicleOwner;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VehicleOwner>
 */
class VehicleOwnerFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'reference_number' => 'ANK-OWN-2026-'.fake()->unique()->numerify('####'),
            'owner_category' => OwnerCategory::Individual,
            'name' => fake()->name(),
            'phone' => '+23480'.fake()->numerify('########'),
            'email' => fake()->safeEmail(),
            'address' => fake()->address(),
            'id_type' => 'NIN',
            'id_number' => fake()->numerify('###########'),
            'bank_name' => 'GTBank',
            'bank_account_name' => fake()->name(),
            'bank_account_number' => fake()->numerify('##########'),
            'is_active' => true,
        ];
    }
}
