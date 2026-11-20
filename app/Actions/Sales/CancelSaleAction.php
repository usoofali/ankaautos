<?php

namespace App\Actions\Sales;

use App\Enums\ConsignmentStatus;
use App\Enums\FinancialTransactionType;
use App\Enums\OwnershipType;
use App\Enums\RevenueBucket;
use App\Enums\SaleStatus;
use App\Enums\SettlementStatus;
use App\Enums\TransactionDirection;
use App\Enums\VehicleStatus;
use App\Models\Consignment;
use App\Models\OwnerSettlement;
use App\Models\Refund;
use App\Models\Sale;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleExpense;
use App\Services\AuditLogger;
use App\Services\FinancialLedgerService;
use App\Services\ReferenceNumberGenerator;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CancelSaleAction
{
    public function __construct(
        private ReferenceNumberGenerator $referenceGenerator,
        private FinancialLedgerService $ledger,
        private AuditLogger $auditLogger,
    ) {}

    /**
     * Manager/Super Admin sale cancellation with explicit refunds and strict protection against reversing already-paid owner settlements.
     *
     * @param  array<string, mixed>  $data
     */
    public function execute(Sale $sale, array $data, User $actor): Sale
    {
        if (! $actor->can('sales.cancel')) {
            throw ValidationException::withMessages([
                'cancellation_reason' => 'Only a Manager or Super Administrator can cancel confirmed sales.',
            ]);
        }

        return DB::transaction(function () use ($sale, $data, $actor): Sale {
            /** @var Sale $locked */
            $locked = Sale::query()->lockForUpdate()->findOrFail($sale->id);

            if ($locked->status === SaleStatus::Cancelled) {
                throw ValidationException::withMessages([
                    'cancellation_reason' => 'This sale has already been cancelled.',
                ]);
            }

            /** @var OwnerSettlement|null $settlement */
            $settlement = $locked->ownerSettlement()->lockForUpdate()->first();
            if ($settlement !== null && $settlement->hasAnyDisbursement()) {
                throw ValidationException::withMessages([
                    'cancellation_reason' => "Cannot cancel sale {$locked->reference_number} because payout(s) have already been disbursed to the consignment owner ({$settlement->reference_number}). Record an authorized financial adjustment instead.",
                ]);
            }

            $previousStatus = $locked->status;
            $collected = Money::fromDecimal($locked->amount_paid);
            $reason = (string) $data['cancellation_reason'];

            if ($collected->isPositive()) {
                /** @var Refund $refund */
                $refund = Refund::query()->create([
                    'reference_number' => $this->referenceGenerator->generate('RFD'),
                    'refundable_type' => $locked->getMorphClass(),
                    'refundable_id' => $locked->id,
                    'customer_id' => $locked->customer_id,
                    'amount' => $collected->toDecimal(),
                    'payment_method_id' => (int) $data['payment_method_id'],
                    'financial_account_id' => (int) $data['financial_account_id'],
                    'reason' => "Sale cancellation ({$locked->reference_number}): {$reason}",
                    'refunded_at' => now()->toDateString(),
                    'approved_by' => $actor->id,
                    'created_at' => now(),
                ]);

                $bucket = $locked->ownership_type === OwnershipType::Consignment
                    ? RevenueBucket::ThirdPartyOwner
                    : RevenueBucket::Company;

                $this->ledger->post(
                    type: FinancialTransactionType::CustomerRefund,
                    direction: TransactionDirection::Outflow,
                    bucket: $bucket,
                    amount: $collected,
                    description: "Customer refund {$refund->reference_number} for cancelled sale {$locked->reference_number}",
                    financialAccountId: $refund->financial_account_id,
                    paymentMethodId: $refund->payment_method_id,
                    vehicleId: $locked->vehicle_id,
                    source: $refund,
                    actor: $actor,
                );
            }

            // Void unpaid owner settlement and release linked owner-deductible expenses so they can be settled on future sale/withdrawal
            if ($settlement !== null) {
                $settlement->update(['status' => SettlementStatus::Voided]);
                VehicleExpense::query()
                    ->where('settled_in_owner_settlement_id', $settlement->id)
                    ->update(['settled_in_owner_settlement_id' => null]);
            }

            // Revert vehicle back to Available
            /** @var Vehicle $vehicle */
            $vehicle = Vehicle::query()->lockForUpdate()->findOrFail($locked->vehicle_id);
            $vehicle->update(['status' => VehicleStatus::Available]);
            $vehicle->statusHistories()->create([
                'from_status' => VehicleStatus::Sold,
                'to_status' => VehicleStatus::Available,
                'reason' => "Sale {$locked->reference_number} cancelled: {$reason}",
                'changed_by' => $actor->id,
                'created_at' => now(),
            ]);

            // Revert consignment back to Active
            if ($locked->consignment_id !== null) {
                /** @var Consignment|null $consignment */
                $consignment = Consignment::query()->lockForUpdate()->find($locked->consignment_id);
                if ($consignment !== null) {
                    $prevCons = $consignment->status;
                    $consignment->update(['status' => ConsignmentStatus::Active]);
                    $consignment->statusHistories()->create([
                        'from_status' => $prevCons,
                        'to_status' => ConsignmentStatus::Active,
                        'reason' => "Sale {$locked->reference_number} cancelled: {$reason}",
                        'changed_by' => $actor->id,
                        'created_at' => now(),
                    ]);
                }
            }

            $locked->update([
                'status' => SaleStatus::Cancelled,
                'cancelled_at' => now(),
                'cancellation_reason' => $reason,
            ]);

            $locked->statusHistories()->create([
                'from_status' => $previousStatus,
                'to_status' => SaleStatus::Cancelled,
                'reason' => $reason,
                'changed_by' => $actor->id,
                'created_at' => now(),
            ]);

            $this->auditLogger->audit(
                event: 'sale.cancelled',
                auditable: $locked,
                oldValues: ['status' => $previousStatus->value, 'amount_paid' => $collected->toDecimal()],
                newValues: ['status' => SaleStatus::Cancelled->value, 'refund_issued' => $collected->toDecimal()],
                reason: $reason,
                actor: $actor,
            );

            return $locked;
        });
    }
}
