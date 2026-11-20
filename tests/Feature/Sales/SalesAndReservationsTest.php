<?php

use App\Enums\CommissionType;
use App\Enums\ConsignmentStatus;
use App\Enums\ReservationStatus;
use App\Enums\SaleStatus;
use App\Enums\SettlementStatus;
use App\Enums\SystemRole;
use App\Enums\VehicleStatus;
use App\Models\Consignment;
use App\Models\Customer;
use App\Models\FinancialAccount;
use App\Models\OwnerSettlement;
use App\Models\PaymentMethod;
use App\Models\Reservation;
use App\Models\Sale;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleExpense;
use App\Models\VehicleOwner;
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function () {
    $this->seed([
        RolesAndPermissionsSeeder::class,
        MasterDataSeeder::class,
    ]);
});

test('reservations require positive deposit, mark vehicle reserved, and carry deposit into converted sale', function () {
    $salesOfficer = User::factory()->create();
    $salesOfficer->assignRole(SystemRole::SalesOfficer->value);

    $vehicle = Vehicle::factory()->create([
        'status' => VehicleStatus::Available,
        'advertised_price' => '15000000.00',
    ]);
    $customer = Customer::factory()->create();
    $method = PaymentMethod::query()->firstOrFail();
    $account = FinancialAccount::query()->firstOrFail();

    // Deposit must be > 0
    $this->actingAs($salesOfficer)
        ->post(route('reservations.store'), [
            'vehicle_id' => $vehicle->id,
            'customer_id' => $customer->id,
            'agreed_price' => '15000000.00',
            'deposit_amount' => '0',
            'payment_method_id' => $method->id,
            'financial_account_id' => $account->id,
            'paid_at' => now()->toDateString(),
        ])
        ->assertSessionHasErrors('deposit_amount');

    // Valid reservation with ₦3,000,000 deposit
    $this->actingAs($salesOfficer)
        ->post(route('reservations.store'), [
            'vehicle_id' => $vehicle->id,
            'customer_id' => $customer->id,
            'agreed_price' => '15000000.00',
            'deposit_amount' => '3000000.00',
            'payment_method_id' => $method->id,
            'financial_account_id' => $account->id,
            'paid_at' => now()->toDateString(),
        ])
        ->assertRedirect();

    $reservation = Reservation::query()->where('vehicle_id', $vehicle->id)->firstOrFail();
    expect($reservation->status)->toBe(ReservationStatus::Active)
        ->and($vehicle->fresh()->status)->toBe(VehicleStatus::Reserved);

    // Convert reservation to sale paying the remaining ₦12,000,000
    $this->actingAs($salesOfficer)
        ->post(route('sales.store'), [
            'vehicle_id' => $vehicle->id,
            'customer_id' => $customer->id,
            'reservation_id' => $reservation->id,
            'list_price' => '15000000.00',
            'initial_payment_amount' => '12000000.00',
            'payment_method_id' => $method->id,
            'financial_account_id' => $account->id,
            'paid_at' => now()->toDateString(),
            'idempotency_key' => 'test-sale-convert-001',
        ])
        ->assertRedirect();

    $sale = Sale::query()->where('vehicle_id', $vehicle->id)->firstOrFail();

    expect($sale->status)->toBe(SaleStatus::Completed)
        ->and($sale->amount_paid)->toBe('15000000.00')
        ->and($sale->balance_due)->toBe('0.00')
        ->and($reservation->fresh()->status)->toBe(ReservationStatus::ConvertedToSale)
        ->and($vehicle->fresh()->status)->toBe(VehicleStatus::Sold);

    // Download sales agreement and receipt PDFs
    $this->actingAs($salesOfficer)
        ->get(route('sales.agreement-pdf', $sale))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');

    $payment = $sale->payments()->firstOrFail();
    $this->actingAs($salesOfficer)
        ->get(route('sales.receipt-pdf', ['sale' => $sale, 'payment' => $payment]))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');
});

test('installment sale blocks unpaid handover unless manager approves early release, and payment idempotency prevents duplicate charges', function () {
    $salesOfficer = User::factory()->create();
    $salesOfficer->assignRole(SystemRole::SalesOfficer->value);

    $manager = User::factory()->create();
    $manager->assignRole(SystemRole::Manager->value);

    $vehicle = Vehicle::factory()->create([
        'status' => VehicleStatus::Available,
        'advertised_price' => '10000000.00',
    ]);
    $customer = Customer::factory()->create();
    $method = PaymentMethod::query()->firstOrFail();
    $account = FinancialAccount::query()->firstOrFail();

    // Non-manager cannot apply discount
    $this->actingAs($salesOfficer)
        ->post(route('sales.store'), [
            'vehicle_id' => $vehicle->id,
            'customer_id' => $customer->id,
            'list_price' => '10000000.00',
            'discount_amount' => '500000.00',
            'discount_reason' => 'Loyalty discount',
            'is_installment' => true,
            'initial_payment_amount' => '5000000.00',
            'payment_method_id' => $method->id,
            'financial_account_id' => $account->id,
            'paid_at' => now()->toDateString(),
        ])
        ->assertSessionHasErrors('discount_amount');

    // Manager creates installment sale with approved discount
    $this->actingAs($manager)
        ->post(route('sales.store'), [
            'vehicle_id' => $vehicle->id,
            'customer_id' => $customer->id,
            'list_price' => '10000000.00',
            'discount_amount' => '500000.00',
            'discount_reason' => 'Approved repeat client discount',
            'is_installment' => true,
            'installment_terms' => '50% upfront, balance in 30 days',
            'initial_payment_amount' => '5000000.00',
            'payment_method_id' => $method->id,
            'financial_account_id' => $account->id,
            'paid_at' => now()->toDateString(),
        ])
        ->assertRedirect();

    $sale = Sale::query()->where('vehicle_id', $vehicle->id)->firstOrFail();
    expect($sale->final_price)->toBe('9500000.00')
        ->and($sale->balance_due)->toBe('4500000.00')
        ->and($sale->status)->toBe(SaleStatus::Confirmed);

    // Sales Officer blocked from handing over unpaid vehicle
    $this->actingAs($salesOfficer)
        ->post(route('sales.handover.store', $sale), [
            'recipient_name' => $customer->name,
            'recipient_phone' => $customer->phone,
        ])
        ->assertSessionHasErrors('early_handover_reason');

    // Duplicate idempotency key does not double-charge payment
    $payload = [
        'amount' => '2000000.00',
        'payment_method_id' => $method->id,
        'financial_account_id' => $account->id,
        'paid_at' => now()->toDateString(),
        'idempotency_key' => 'idem-pay-999',
    ];
    $this->actingAs($manager)->post(route('sales.payments.store', $sale), $payload)->assertRedirect();
    $this->actingAs($manager)->post(route('sales.payments.store', $sale), $payload)->assertRedirect();

    expect($sale->fresh()->amount_paid)->toBe('7000000.00')
        ->and($sale->fresh()->balance_due)->toBe('2500000.00');

    // Manager approves early release with mandatory reason
    $this->actingAs($manager)
        ->post(route('sales.handover.store', $sale), [
            'recipient_name' => $customer->name,
            'recipient_phone' => $customer->phone,
            'early_handover_reason' => 'Verified corporate guarantee letter on file.',
            'items' => [
                ['item_name' => 'Keys', 'is_included' => true],
            ],
        ])
        ->assertRedirect();

    expect($sale->fresh()->handover)->not->toBeNull();

    $this->actingAs($manager)
        ->get(route('sales.handover-pdf', $sale))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');
});

test('cancelling consignment sale refunds customer, reverts vehicle and consignment, voids unpaid settlement and unlocks expenses, but blocks if owner paid', function () {
    $manager = User::factory()->create();
    $manager->assignRole(SystemRole::Manager->value);

    $owner = VehicleOwner::factory()->create();
    $vehicle = Vehicle::factory()->consignment()->create([
        'status' => VehicleStatus::Available,
        'advertised_price' => '20000000.00',
    ]);
    $consignment = Consignment::factory()->create([
        'vehicle_id' => $vehicle->id,
        'vehicle_owner_id' => $owner->id,
        'agreed_selling_price' => '20000000.00',
        'commission_type' => CommissionType::Fixed,
        'commission_value' => '1000000.00',
        'status' => ConsignmentStatus::Active,
    ]);
    $expense = VehicleExpense::factory()->ownerDeductible($consignment)->create([
        'vehicle_id' => $vehicle->id,
        'amount' => '250000.00',
    ]);

    $customer = Customer::factory()->create();
    $method = PaymentMethod::query()->firstOrFail();
    $account = FinancialAccount::query()->firstOrFail();

    // Sell consignment vehicle in full
    $this->actingAs($manager)
        ->post(route('sales.store'), [
            'vehicle_id' => $vehicle->id,
            'customer_id' => $customer->id,
            'list_price' => '20000000.00',
            'initial_payment_amount' => '20000000.00',
            'payment_method_id' => $method->id,
            'financial_account_id' => $account->id,
            'paid_at' => now()->toDateString(),
        ])
        ->assertRedirect();

    $sale = Sale::query()->where('vehicle_id', $vehicle->id)->firstOrFail();
    $settlement = OwnerSettlement::query()->where('sale_id', $sale->id)->firstOrFail();

    expect($settlement->status)->toBe(SettlementStatus::ReadyForPayout)
        ->and($expense->fresh()->settled_in_owner_settlement_id)->toBe($settlement->id);

    // Cancel sale before owner payout succeeds
    $this->actingAs($manager)
        ->post(route('sales.cancel', $sale), [
            'cancellation_reason' => 'Buyer failed final document verification.',
            'payment_method_id' => $method->id,
            'financial_account_id' => $account->id,
        ])
        ->assertRedirect();

    expect($sale->fresh()->status)->toBe(SaleStatus::Cancelled)
        ->and($vehicle->fresh()->status)->toBe(VehicleStatus::Available)
        ->and($consignment->fresh()->status)->toBe(ConsignmentStatus::Active)
        ->and($settlement->fresh()->status)->toBe(SettlementStatus::Voided)
        ->and($expense->fresh()->settled_in_owner_settlement_id)->toBeNull();

    // Resell and disburse a partial payout to the owner -> cancellation must now be BLOCKED
    $this->actingAs($manager)
        ->post(route('sales.store'), [
            'vehicle_id' => $vehicle->id,
            'customer_id' => $customer->id,
            'list_price' => '20000000.00',
            'initial_payment_amount' => '20000000.00',
            'payment_method_id' => $method->id,
            'financial_account_id' => $account->id,
            'paid_at' => now()->toDateString(),
        ])
        ->assertRedirect();

    $sale2 = Sale::query()->where('vehicle_id', $vehicle->id)->latest('id')->firstOrFail();
    $settlement2 = OwnerSettlement::query()->where('sale_id', $sale2->id)->firstOrFail();

    $this->actingAs($manager)
        ->post(route('settlements.payouts.store', $settlement2), [
            'amount' => '5000000.00',
            'payment_method_id' => $method->id,
            'financial_account_id' => $account->id,
            'paid_at' => now()->toDateString(),
        ])
        ->assertRedirect();

    $this->actingAs($manager)
        ->post(route('sales.cancel', $sale2), [
            'cancellation_reason' => 'Attempted cancellation after owner payout.',
            'payment_method_id' => $method->id,
            'financial_account_id' => $account->id,
        ])
        ->assertSessionHasErrors('sale');

    expect($sale2->fresh()->status)->toBe(SaleStatus::Completed);
});
