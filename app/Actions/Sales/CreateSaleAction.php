<?php

namespace App\Actions\Sales;

use App\Actions\Charges\ApplyTransactionChargeAction;
use App\Actions\Settlements\GenerateOwnerSettlementAction;
use App\Enums\ChargeTransactionType;
use App\Enums\ConsignmentStatus;
use App\Enums\OwnershipType;
use App\Enums\PaymentType;
use App\Enums\ReservationStatus;
use App\Enums\SaleStatus;
use App\Enums\VehicleStatus;
use App\Models\Consignment;
use App\Models\Reservation;
use App\Models\Sale;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\AuditLogger;
use App\Services\ReferenceNumberGenerator;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateSaleAction
{
    public function __construct(
        private ReferenceNumberGenerator $referenceGenerator,
        private RecordSalePaymentAction $recordPayment,
        private ApplyTransactionChargeAction $applyCharge,
        private GenerateOwnerSettlementAction $generateSettlement,
        private AuditLogger $auditLogger,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(array $data, User $actor): Sale
    {
        $listPrice = Money::fromDecimal((string) $data['list_price']);
        $discount = Money::fromDecimal((string) ($data['discount_amount'] ?? '0'));

        if ($discount->isPositive()) {
            if (! $actor->can('sales.approve_discount')) {
                throw ValidationException::withMessages([
                    'discount_amount' => 'Only a Manager or Super Administrator can approve sales discounts.',
                ]);
            }
            if (empty($data['discount_reason'])) {
                throw ValidationException::withMessages([
                    'discount_reason' => 'Please provide a reason for the approved discount.',
                ]);
            }
        }

        if ($discount->greaterThanOrEqual($listPrice)) {
            throw ValidationException::withMessages([
                'discount_amount' => 'Discount must be less than the list price.',
            ]);
        }

        $finalPrice = $listPrice->subtract($discount);
        $initialPayment = isset($data['initial_payment_amount']) && $data['initial_payment_amount'] !== ''
            ? Money::fromDecimal((string) $data['initial_payment_amount'])
            : Money::zero();
        $isInstallment = (bool) ($data['is_installment'] ?? false);

        return DB::transaction(function () use ($data, $actor, $listPrice, $discount, $finalPrice, $initialPayment, $isInstallment): Sale {
            /** @var Vehicle $vehicle */
            $vehicle = Vehicle::query()->with('activeConsignment')->lockForUpdate()->findOrFail($data['vehicle_id']);

            if (in_array($vehicle->status, [VehicleStatus::Sold, VehicleStatus::Withdrawn, VehicleStatus::Archived], true)) {
                throw ValidationException::withMessages([
                    'vehicle_id' => "Vehicle {$vehicle->reference_number} is already {$vehicle->status->label()} and cannot be sold again.",
                ]);
            }

            /** @var Reservation|null $reservation */
            $reservation = null;
            $carriedDeposit = Money::zero();

            if (! empty($data['reservation_id'])) {
                $reservation = Reservation::query()->lockForUpdate()->findOrFail($data['reservation_id']);
                if ($reservation->status !== ReservationStatus::Active || $reservation->vehicle_id !== $vehicle->id) {
                    throw ValidationException::withMessages([
                        'reservation_id' => 'Selected reservation is not active for this vehicle.',
                    ]);
                }
                $carriedDeposit = Money::fromDecimal($reservation->deposit_amount);
            }

            $totalUpfront = $carriedDeposit->add($initialPayment);
            if ($totalUpfront->greaterThan($finalPrice)) {
                throw ValidationException::withMessages([
                    'initial_payment_amount' => 'Total upfront payment (including carried deposit) exceeds the final sale price.',
                ]);
            }

            if (! $isInstallment && $totalUpfront->lessThan($finalPrice)) {
                throw ValidationException::withMessages([
                    'initial_payment_amount' => 'Full payment is required unless this sale is marked as an approved installment plan.',
                ]);
            }

            /** @var Consignment|null $consignment */
            $consignment = $vehicle->ownership_type === OwnershipType::Consignment
                ? $vehicle->activeConsignment
                : null;

            $initialBalance = $finalPrice->subtract($carriedDeposit);
            $initialStatus = $initialBalance->isZero() ? SaleStatus::Completed : SaleStatus::Confirmed;

            /** @var Sale $sale */
            $sale = Sale::query()->create([
                'reference_number' => $this->referenceGenerator->generate('SAL'),
                'vehicle_id' => $vehicle->id,
                'customer_id' => $reservation !== null ? $reservation->customer_id : (int) $data['customer_id'],
                'reservation_id' => $reservation?->id,
                'consignment_id' => $consignment?->id,
                'ownership_type' => $vehicle->ownership_type,
                'list_price' => $listPrice->toDecimal(),
                'discount_amount' => $discount->toDecimal(),
                'final_price' => $finalPrice->toDecimal(),
                'amount_paid' => $carriedDeposit->toDecimal(),
                'balance_due' => $initialBalance->toDecimal(),
                'is_installment' => $isInstallment,
                'installment_terms' => $isInstallment ? ($data['installment_terms'] ?? null) : null,
                'status' => $initialStatus,
                'confirmed_at' => now(),
                'completed_at' => $initialBalance->isZero() ? now() : null,
                'sales_officer_id' => $actor->id,
                'discount_approved_by' => $discount->isPositive() ? $actor->id : null,
                'notes' => $data['notes'] ?? null,
            ]);

            if ($discount->isPositive()) {
                $sale->discounts()->create([
                    'previous_price' => $listPrice->toDecimal(),
                    'discount_amount' => $discount->toDecimal(),
                    'new_price' => $finalPrice->toDecimal(),
                    'reason' => (string) $data['discount_reason'],
                    'approved_by' => $actor->id,
                    'created_at' => now(),
                ]);
            }

            if ($reservation !== null) {
                $reservation->update([
                    'status' => ReservationStatus::Converted,
                    'deposit_disposition' => 'applied_to_sale',
                ]);
            }

            // Apply any consignment sale service charges included on creation
            /** @var list<array<string, mixed>> $charges */
            $charges = is_array($data['charges'] ?? null) ? $data['charges'] : [];
            foreach ($charges as $chargeRow) {
                if (! empty($chargeRow['amount'])) {
                    $this->applyCharge->execute(
                        chargeable: $sale,
                        transactionType: $consignment !== null
                            ? ChargeTransactionType::ConsignmentSale
                            : ChargeTransactionType::Sale,
                        data: $chargeRow,
                        actor: $actor,
                    );
                }
            }

            $sale->statusHistories()->create([
                'from_status' => null,
                'to_status' => $initialStatus,
                'reason' => $isInstallment ? 'Confirmed as installment sale.' : 'Confirmed as full-payment sale.',
                'changed_by' => $actor->id,
                'created_at' => now(),
            ]);

            // Update vehicle to Sold
            $prevVehicleStatus = $vehicle->status;
            $vehicle->update(['status' => VehicleStatus::Sold]);
            $vehicle->statusHistories()->create([
                'from_status' => $prevVehicleStatus,
                'to_status' => VehicleStatus::Sold,
                'reason' => "Confirmed sale {$sale->reference_number}.",
                'changed_by' => $actor->id,
                'created_at' => now(),
            ]);

            // If consignment, transition consignment and auto-generate itemized owner settlement
            if ($consignment !== null) {
                $prevConsStatus = $consignment->status;
                $consignment->update(['status' => ConsignmentStatus::SettlementPending]);
                $consignment->statusHistories()->create([
                    'from_status' => $prevConsStatus,
                    'to_status' => ConsignmentStatus::SettlementPending,
                    'reason' => "Vehicle sold under {$sale->reference_number}.",
                    'changed_by' => $actor->id,
                    'created_at' => now(),
                ]);

                $customCommission = isset($data['custom_commission_amount']) && $data['custom_commission_amount'] !== ''
                    ? Money::fromDecimal((string) $data['custom_commission_amount'])
                    : null;

                $this->generateSettlement->execute($sale, $consignment, $actor, $customCommission);
            }

            // Record initial payment collected at sale closing (if any)
            if ($initialPayment->isPositive()) {
                $this->recordPayment->execute($sale, [
                    'amount' => $initialPayment->toDecimal(),
                    'payment_type' => $isInstallment ? PaymentType::Installment->value : PaymentType::SalePayment->value,
                    'payment_method_id' => $data['payment_method_id'],
                    'financial_account_id' => $data['financial_account_id'],
                    'paid_at' => $data['paid_at'] ?? now()->toDateString(),
                    'external_reference' => $data['external_reference'] ?? null,
                    'notes' => 'Initial payment recorded at sale confirmation.',
                    'idempotency_key' => $data['idempotency_key'] ?? null,
                ], $actor);
            }

            $this->auditLogger->audit(
                event: 'sale.created',
                auditable: $sale,
                newValues: $sale->only([
                    'reference_number',
                    'vehicle_id',
                    'customer_id',
                    'final_price',
                    'discount_amount',
                    'is_installment',
                ]),
                actor: $actor,
            );

            return $sale->fresh() ?? $sale;
        });
    }
}
