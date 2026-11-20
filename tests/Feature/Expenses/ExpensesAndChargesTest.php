<?php

use App\Actions\Charges\ApplyTransactionChargeAction;
use App\Enums\ChargeTransactionType;
use App\Enums\ExpenseBearer;
use App\Enums\ExpensePaymentStatus;
use App\Enums\SystemRole;
use App\Models\ChargeCategory;
use App\Models\Consignment;
use App\Models\ExpenseCategory;
use App\Models\FinancialAccount;
use App\Models\FinancialTransaction;
use App\Models\PaymentMethod;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleExpense;
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

test('owner-deductible expenses are rejected on company-owned vehicles and permitted on consignment vehicles', function () {
    $accountant = User::factory()->create();
    $accountant->assignRole(SystemRole::Accountant->value);

    $companyVehicle = Vehicle::factory()->create();
    $consignment = Consignment::factory()->create();
    $category = ExpenseCategory::query()->firstOrFail();

    $this->actingAs($accountant)
        ->post(route('expenses.store'), [
            'vehicle_id' => $companyVehicle->id,
            'expense_category_id' => $category->id,
            'borne_by' => ExpenseBearer::OwnerDeductible->value,
            'description' => 'Invalid owner charge on company car',
            'amount' => '50000.00',
            'expense_date' => now()->toDateString(),
        ])
        ->assertSessionHasErrors('borne_by');

    Storage::fake('local');

    $this->actingAs($accountant)
        ->post(route('expenses.store'), [
            'vehicle_id' => $consignment->vehicle_id,
            'expense_category_id' => $category->id,
            'borne_by' => ExpenseBearer::OwnerDeductible->value,
            'description' => 'Battery replacement authorized by owner',
            'amount' => '120000.00',
            'expense_date' => now()->toDateString(),
            'receipt' => UploadedFile::fake()->create('receipt.pdf', 100, 'application/pdf'),
        ])
        ->assertRedirect();

    $expense = VehicleExpense::query()->where('vehicle_id', $consignment->vehicle_id)->firstOrFail();

    expect($expense->consignment_id)->toBe($consignment->id)
        ->and($expense->borne_by)->toBe(ExpenseBearer::OwnerDeductible)
        ->and($expense->receipt_path)->not->toBeNull();
});

test('expense partial and full payments update status, post to immutable ledger, and prevent overpayment', function () {
    $accountant = User::factory()->create();
    $accountant->assignRole(SystemRole::Accountant->value);

    $expense = VehicleExpense::factory()->create([
        'amount' => '100000.00',
        'amount_paid' => '0.00',
        'payment_status' => ExpensePaymentStatus::Unpaid,
    ]);

    $method = PaymentMethod::query()->firstOrFail();
    $account = FinancialAccount::query()->firstOrFail();

    // Partial payment
    $this->actingAs($accountant)
        ->post(route('expenses.payments.store', $expense), [
            'amount' => '40000.00',
            'payment_method_id' => $method->id,
            'financial_account_id' => $account->id,
            'paid_at' => now()->toDateString(),
        ])
        ->assertRedirect();

    expect($expense->fresh()->payment_status)->toBe(ExpensePaymentStatus::PartiallyPaid)
        ->and($expense->fresh()->balanceDue()->toDecimal())->toBe('60000.00');

    // Overpayment blocked
    $this->actingAs($accountant)
        ->post(route('expenses.payments.store', $expense), [
            'amount' => '65000.00',
            'payment_method_id' => $method->id,
            'financial_account_id' => $account->id,
            'paid_at' => now()->toDateString(),
        ])
        ->assertSessionHasErrors('amount');

    // Final payment
    $this->actingAs($accountant)
        ->post(route('expenses.payments.store', $expense), [
            'amount' => '60000.00',
            'payment_method_id' => $method->id,
            'financial_account_id' => $account->id,
            'paid_at' => now()->toDateString(),
        ])
        ->assertRedirect();

    expect($expense->fresh()->payment_status)->toBe(ExpensePaymentStatus::Paid)
        ->and(FinancialTransaction::query()->count())->toBe(2);

    // FinancialTransaction is immutable
    $tx = FinancialTransaction::query()->firstOrFail();
    expect(fn () => $tx->update(['amount' => '1.00']))->toThrow(LogicException::class)
        ->and(fn () => $tx->delete())->toThrow(LogicException::class);
});

test('referenced expense and charge categories cannot be hard deleted', function () {
    $admin = User::factory()->create();
    $admin->assignRole(SystemRole::SuperAdministrator->value);

    $expense = VehicleExpense::factory()->create();

    $this->actingAs($admin)
        ->delete(route('admin.master-data.destroy', ['type' => 'expense_categories', 'id' => $expense->expense_category_id]))
        ->assertRedirect();

    expect(ExpenseCategory::query()->whereKey($expense->expense_category_id)->exists())->toBeTrue();

    $chargeCategory = ChargeCategory::query()->firstOrFail();
    app(ApplyTransactionChargeAction::class)->execute(
        chargeable: $expense->vehicle,
        transactionType: ChargeTransactionType::VehicleService,
        data: [
            'charge_category_id' => $chargeCategory->id,
            'amount' => '25000.00',
        ],
        actor: $admin,
    );

    $this->actingAs($admin)
        ->delete(route('admin.master-data.destroy', ['type' => 'charge_categories', 'id' => $chargeCategory->id]))
        ->assertRedirect();

    expect(ChargeCategory::query()->whereKey($chargeCategory->id)->exists())->toBeTrue();
});
