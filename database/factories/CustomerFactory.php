<?php

namespace Database\Factories;

use App\Enums\OwnerCategory;
use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Customer>
 */
class CustomerFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'reference_number' => 'ANK-CUS-2026-'.fake()->unique()->numerify('####'),
            'customer_type' => OwnerCategory::Individual,
            'name' => fake()->name(),
            'phone' => '+23480'.fake()->numerify('########'),
            'email' => fake()->safeEmail(),
            'address' => fake()->address(),
            'id_type' => 'NIN',
            'id_number' => fake()->numerify('###########'),
        ];
    }
}
