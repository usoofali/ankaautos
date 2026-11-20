<?php

namespace Database\Factories;

use App\Enums\ExpenseBearer;
use App\Enums\ExpensePaymentStatus;
use App\Models\Consignment;
use App\Models\ExpenseCategory;
use App\Models\Vehicle;
use App\Models\VehicleExpense;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VehicleExpense>
 */
class VehicleExpenseFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $category = ExpenseCategory::query()->first()
            ?? ExpenseCategory::query()->create(['name' => 'Routine Servicing', 'is_active' => true]);

        return [
            'reference_number' => 'ANK-EXP-2026-'.fake()->unique()->numerify('####'),
            'vehicle_id' => Vehicle::factory(),
            'consignment_id' => null,
            'expense_category_id' => $category->id,
            'borne_by' => ExpenseBearer::Company,
            'description' => fake()->sentence(5),
            'amount' => '85000.00',
            'amount_paid' => '0.00',
            'payment_status' => ExpensePaymentStatus::Unpaid,
            'expense_date' => now()->toDateString(),
            'vendor_name' => fake()->company(),
        ];
    }

    public function ownerDeductible(Consignment $consignment): static
    {
        return $this->state(fn (): array => [
            'vehicle_id' => $consignment->vehicle_id,
            'consignment_id' => $consignment->id,
            'borne_by' => ExpenseBearer::OwnerDeductible,
        ]);
    }
}
