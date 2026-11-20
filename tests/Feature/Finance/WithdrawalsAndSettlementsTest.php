<?php

use App\Enums\CommissionType;
use App\Enums\ConsignmentStatus;
use App\Enums\FinancialTransactionType;
use App\Enums\RevenueBucket;
use App\Enums\SettlementStatus;
use App\Enums\SystemRole;
use App\Enums\VehicleStatus;
use App\Enums\WithdrawalStatus;
use App\Models\ChargeCategory;
use App\Models\Consignment;
use App\Models\Customer;
use App\Models\FinancialAccount;
use App\Models\FinancialTransaction;
use App\Models\OwnerSettlement;
use App\Models\PaymentMethod;
use App\Models\Sale;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleExpense;
use App\Models\VehicleOwner;
use App\Models\WithdrawalRequest;
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function () {
    $this->seed([
        RolesAndPermissionsSeeder::class,
        MasterDataSeeder::class,
    ]);
});

test('consignment withdrawal enforces strict 100 percent cash-first payment before vehicle release', function () {
    $manager = User::factory()->create();
    $manager->assignRole(SystemRole::Manager->value);

    $owner = VehicleOwner::factory()->create();
    $vehicle = Vehicle::factory()->consignment()->create(['status' => VehicleStatus::Available]);
    $consignment = Consignment::factory()->create([
        'vehicle_id' => $vehicle->id,
        'vehicle_owner_id' => $owner->id,
        'status' => ConsignmentStatus::Active,
    ]);
    $expense = VehicleExpense::factory()->ownerDeductible($consignment)->create([
        'vehicle_id' => $vehicle->id,
        'amount' => '150000.00',
    ]);

    $chargeCategory = ChargeCategory::query()->firstOrFail();
    $method = PaymentMethod::query()->firstOrFail();
    $account = FinancialAccount::query()->firstOrFail();

    // Initiate withdrawal with a ₦50,000 withdrawal charge -> Total Due = ₦200,000
    $this->actingAs($manager)
        ->post(route('withdrawals.store'), [
            'consignment_id' => $consignment->id,
            'reason' => 'Owner decided to retain vehicle for family use.',
            'charges' => [
                [
                    'charge_category_id' => $chargeCategory->id,
                    'amount' => '50000.00',
                ],
            ],
        ])
        ->assertRedirect();

    $withdrawal = WithdrawalRequest::query()->where('consignment_id', $consignment->id)->firstOrFail();

    expect($withdrawal->total_due)->toBe('200000.00')
        ->and($withdrawal->balance_due)->toBe('200000.00')
        ->and($withdrawal->status)->toBe(WithdrawalStatus::AwaitingPayment);

    // Partial payment (₦100,000) -> handover must STILL be strictly blocked
    $this->actingAs($manager)
        ->post(route('withdrawals.payments.store', $withdrawal), [
            'amount' => '100000.00',
            'payment_method_id' => $method->id,
            'financial_account_id' => $account->id,
            'paid_at' => now()->toDateString(),
        ])
        ->assertRedirect();

    $this->actingAs($manager)
        ->post(route('withdrawals.complete', $withdrawal), [
            'recipient_name' => $owner->name,
            'recipient_phone' => $owner->phone,
        ])
        ->assertSessionHasErrors('withdrawal');

    expect($withdrawal->fresh()->status)->toBe(WithdrawalStatus::AwaitingPayment);

    // Pay remaining ₦100,000 -> status becomes ClearedForHandover
    $this->actingAs($manager)
        ->post(route('withdrawals.payments.store', $withdrawal), [
            'amount' => '100000.00',
            'payment_method_id' => $method->id,
            'financial_account_id' => $account->id,
            'paid_at' => now()->toDateString(),
        ])
        ->assertRedirect();

    expect($withdrawal->fresh()->status)->toBe(WithdrawalStatus::ClearedForHandover)
        ->and($withdrawal->fresh()->balance_due)->toBe('0.00');

    // Complete handover -> succeeds, locks expense, marks consignment & vehicle Withdrawn
    $this->actingAs($manager)
        ->post(route('withdrawals.complete', $withdrawal), [
            'recipient_name' => $owner->name,
            'recipient_phone' => $owner->phone,
        ])
        ->assertRedirect();

    expect($withdrawal->fresh()->status)->toBe(WithdrawalStatus::Completed)
        ->and($consignment->fresh()->status)->toBe(ConsignmentStatus::Withdrawn)
        ->and($vehicle->fresh()->status)->toBe(VehicleStatus::Withdrawn)
        ->and($expense->fresh()->settled_in_withdrawal_id)->toBe($withdrawal->id);

    $this->actingAs($manager)
        ->get(route('withdrawals.statement-pdf', $withdrawal))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');
});

test('owner settlement stays locked until customer pays 100 percent, then unlocks for partial and full disbursements', function () {
    $manager = User::factory()->create();
    $manager->assignRole(SystemRole::Manager->value);

    $owner = VehicleOwner::factory()->create();
    $vehicle = Vehicle::factory()->consignment()->create([
        'status' => VehicleStatus::Available,
        'advertised_price' => '12000000.00',
    ]);
    $consignment = Consignment::factory()->create([
        'vehicle_id' => $vehicle->id,
        'vehicle_owner_id' => $owner->id,
        'agreed_selling_price' => '12000000.00',
        'commission_type' => CommissionType::Percentage,
        'commission_value' => '5.00', // 5% = ₦600,000
        'status' => ConsignmentStatus::Active,
    ]);
    VehicleExpense::factory()->ownerDeductible($consignment)->create([
        'vehicle_id' => $vehicle->id,
        'amount' => '400000.00',
    ]);

    $customer = Customer::factory()->create();
    $method = PaymentMethod::query()->firstOrFail();
    $account = FinancialAccount::query()->firstOrFail();

    // Installment sale: ₦12,000,000 final price, ₦8,000,000 paid upfront (₦4,000,000 still due)
    $this->actingAs($manager)
        ->post(route('sales.store'), [
            'vehicle_id' => $vehicle->id,
            'customer_id' => $customer->id,
            'list_price' => '12000000.00',
            'is_installment' => true,
            'initial_payment_amount' => '8000000.00',
            'payment_method_id' => $method->id,
            'financial_account_id' => $account->id,
            'paid_at' => now()->toDateString(),
        ])
        ->assertRedirect();

    $sale = Sale::query()->where('vehicle_id', $vehicle->id)->firstOrFail();
    $settlement = OwnerSettlement::query()->where('sale_id', $sale->id)->firstOrFail();

    // Net payable = 12,000,000 - 600,000 commission - 400,000 expense = 11,000,000
    expect($settlement->commission_amount)->toBe('600000.00')
        ->and($settlement->total_expense_deductions)->toBe('400000.00')
        ->and($settlement->net_payable_to_owner)->toBe('11000000.00')
        ->and($settlement->status)->toBe(SettlementStatus::LockedAwaitingCustomerPayment);

    // Owner payout MUST BE BLOCKED while customer owes ₦4,000,000
    $this->actingAs($manager)
        ->post(route('settlements.payouts.store', $settlement), [
            'amount' => '5000000.00',
            'payment_method_id' => $method->id,
            'financial_account_id' => $account->id,
            'paid_at' => now()->toDateString(),
        ])
        ->assertSessionHasErrors('settlement');

    // Customer pays final ₦4,000,000 -> settlement automatically unlocks!
    $this->actingAs($manager)
        ->post(route('sales.payments.store', $sale), [
            'amount' => '4000000.00',
            'payment_method_id' => $method->id,
            'financial_account_id' => $account->id,
            'paid_at' => now()->toDateString(),
        ])
        ->assertRedirect();

    expect($settlement->fresh()->status)->toBe(SettlementStatus::ReadyForPayout);

    // Partial owner payout (₦6,000,000)
    $this->actingAs($manager)
        ->post(route('settlements.payouts.store', $settlement), [
            'amount' => '6000000.00',
            'payment_method_id' => $method->id,
            'financial_account_id' => $account->id,
            'paid_at' => now()->toDateString(),
            'idempotency_key' => 'owner-payout-part-1',
        ])
        ->assertRedirect();

    expect($settlement->fresh()->status)->toBe(SettlementStatus::PartiallyPaid)
        ->and($settlement->fresh()->balance_due)->toBe('5000000.00');

    // Final owner payout (₦5,000,000) -> Settled + commission recognized in Company bucket
    $this->actingAs($manager)
        ->post(route('settlements.payouts.store', $settlement), [
            'amount' => '5000000.00',
            'payment_method_id' => $method->id,
            'financial_account_id' => $account->id,
            'paid_at' => now()->toDateString(),
            'idempotency_key' => 'owner-payout-part-2',
        ])
        ->assertRedirect();

    expect($settlement->fresh()->status)->toBe(SettlementStatus::Settled)
        ->and($settlement->fresh()->balance_due)->toBe('0.00')
        ->and($consignment->fresh()->status)->toBe(ConsignmentStatus::Settled);

    $commissionLedger = FinancialTransaction::query()
        ->where('transaction_type', FinancialTransactionType::DealershipCommission->value)
        ->where('revenue_bucket', RevenueBucket::Company->value)
        ->first();

    expect($commissionLedger)->not->toBeNull()
        ->and($commissionLedger->amount)->toBe('600000.00');

    // Settlement PDF downloads cleanly
    $this->actingAs($manager)
        ->get(route('settlements.statement-pdf', $settlement))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');

    // Manager can post authorized financial adjustment
    $this->actingAs($manager)
        ->post(route('finance.adjustments.store'), [
            'revenue_bucket' => 'company',
            'direction' => 'outflow',
            'amount' => '15000.00',
            'financial_account_id' => $account->id,
            'adjusted_at' => now()->toDateString(),
            'reason' => 'Bank transfer stamp duty reconciliation.',
        ])
        ->assertRedirect();

    $this->actingAs($manager)
        ->get(route('finance.index'))
        ->assertOk();
});
