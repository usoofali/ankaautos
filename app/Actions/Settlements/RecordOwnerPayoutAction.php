<?php

namespace App\Actions\Settlements;

use App\Enums\ConsignmentStatus;
use App\Enums\FinancialTransactionType;
use App\Enums\RevenueBucket;
use App\Enums\SettlementStatus;
use App\Enums\TransactionDirection;
use App\Models\Consignment;
use App\Models\OwnerPayment;
use App\Models\OwnerSettlement;
use App\Models\Sale;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\FinancialLedgerService;
use App\Services\ReferenceNumberGenerator;
use App\Support\Money;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RecordOwnerPayoutAction
{
    public function __construct(
        private ReferenceNumberGenerator $referenceGenerator,
        private FinancialLedgerService $ledger,
        private AuditLogger $auditLogger,
    ) {}

    /**
     * Strict cash-first owner payout: locked until customer has paid 100% of sale price.
     * Supports full or partial payouts via owner_payments.
     *
     * @param  array<string, mixed>  $data
     */
    public function execute(OwnerSettlement $settlement, array $data, User $actor): OwnerPayment
    {
        $idempotencyKey = isset($data['idempotency_key']) && trim((string) $data['idempotency_key']) !== ''
            ? trim((string) $data['idempotency_key'])
            : null;

        if ($idempotencyKey !== null) {
            /** @var OwnerPayment|null $existing */
            $existing = OwnerPayment::query()->where('idempotency_key', $idempotencyKey)->first();
            if ($existing !== null) {
                return $existing;
            }
        }

        $payoutAmount = Money::fromDecimal((string) $data['amount']);
        if (! $payoutAmount->isPositive()) {
            throw ValidationException::withMessages([
                'amount' => 'Payout amount must be greater than zero.',
            ]);
        }

        return DB::transaction(function () use ($settlement, $data, $actor, $payoutAmount, $idempotencyKey): OwnerPayment {
            /** @var OwnerSettlement $locked */
            $locked = OwnerSettlement::query()->lockForUpdate()->findOrFail($settlement->id);
            /** @var Sale $sale */
            $sale = Sale::query()->lockForUpdate()->findOrFail($locked->sale_id);

            if ($locked->status === SettlementStatus::Voided || $locked->status === SettlementStatus::Settled) {
                throw ValidationException::withMessages([
                    'amount' => "Cannot disburse payout for a settlement in {$locked->status->label()} state.",
                ]);
            }

            // Strict cash-first invariant
            if (! $sale->isFullyPaid()) {
                throw ValidationException::withMessages([
                    'amount' => "Owner settlement payouts are locked until the customer has paid 100% of sale {$sale->reference_number} (remaining customer balance: ".Money::fromDecimal($sale->balance_due)->format().').',
                ]);
            }

            $balance = Money::fromDecimal($locked->balance_due);
            if ($payoutAmount->greaterThan($balance)) {
                throw ValidationException::withMessages([
                    'amount' => "Payout amount exceeds remaining owner balance ({$balance->format()}).",
                ]);
            }

            /** @var OwnerPayment $ownerPayment */
            $ownerPayment = $locked->payments()->create([
                'reference_number' => $this->referenceGenerator->generate('OPY'),
                'idempotency_key' => $idempotencyKey,
                'vehicle_owner_id' => $locked->vehicle_owner_id,
                'payment_method_id' => (int) $data['payment_method_id'],
                'financial_account_id' => (int) $data['financial_account_id'],
                'amount' => $payoutAmount->toDecimal(),
                'paid_at' => $data['paid_at'],
                'payment_reference' => $data['payment_reference'] ?? null,
                'notes' => $data['notes'] ?? null,
                'recorded_by' => $actor->id,
                'created_at' => now(),
            ]);

            $newPaid = Money::fromDecimal($locked->amount_paid)->add($payoutAmount);
            $newBalance = Money::fromDecimal($locked->net_payable_to_owner)->subtract($newPaid)->max(Money::zero());
            $settledNow = $newBalance->isZero();

            $locked->update([
                'amount_paid' => $newPaid->toDecimal(),
                'balance_due' => $newBalance->toDecimal(),
                'status' => $settledNow ? SettlementStatus::Settled : SettlementStatus::PartiallyPaid,
                'settled_at' => $settledNow ? now() : null,
            ]);

            $this->ledger->post(
                type: FinancialTransactionType::OwnerSettlementPayout,
                direction: TransactionDirection::Outflow,
                bucket: RevenueBucket::ThirdPartyOwner,
                amount: $payoutAmount,
                description: "Consignment owner payout {$ownerPayment->reference_number} for settlement {$locked->reference_number}",
                financialAccountId: $ownerPayment->financial_account_id,
                paymentMethodId: $ownerPayment->payment_method_id,
                vehicleId: $locked->vehicle_id,
                source: $ownerPayment,
                transactedAt: Carbon::parse((string) $data['paid_at']),
                actor: $actor,
            );

            if ($settledNow) {
                $commission = Money::fromDecimal($locked->commission_amount);
                if ($commission->isPositive()) {
                    $this->ledger->post(
                        type: FinancialTransactionType::DealershipCommission,
                        direction: TransactionDirection::Inflow,
                        bucket: RevenueBucket::Company,
                        amount: $commission,
                        description: "Dealership commission recognized from settled consignment {$locked->reference_number}",
                        vehicleId: $locked->vehicle_id,
                        source: $locked,
                        transactedAt: Carbon::parse((string) $data['paid_at']),
                        actor: $actor,
                    );
                }

                $chargeDeductions = Money::fromDecimal($locked->total_charge_deductions);
                if ($chargeDeductions->isPositive()) {
                    $this->ledger->post(
                        type: FinancialTransactionType::ServiceChargeIncome,
                        direction: TransactionDirection::Inflow,
                        bucket: RevenueBucket::Company,
                        amount: $chargeDeductions,
                        description: "Consignment service charge income recognized from {$locked->reference_number}",
                        vehicleId: $locked->vehicle_id,
                        source: $locked,
                        transactedAt: Carbon::parse((string) $data['paid_at']),
                        actor: $actor,
                    );
                }

                /** @var Consignment $consignment */
                $consignment = Consignment::query()->lockForUpdate()->findOrFail($locked->consignment_id);
                $prev = $consignment->status;
                $consignment->update(['status' => ConsignmentStatus::Settled]);
                $consignment->statusHistories()->create([
                    'from_status' => $prev,
                    'to_status' => ConsignmentStatus::Settled,
                    'reason' => "Owner settlement {$locked->reference_number} disbursed in full.",
                    'changed_by' => $actor->id,
                    'created_at' => now(),
                ]);
            }

            $this->auditLogger->audit(
                event: 'settlement.payout_recorded',
                auditable: $locked,
                newValues: [
                    'owner_payment_reference' => $ownerPayment->reference_number,
                    'amount' => $payoutAmount->toDecimal(),
                    'balance_due' => $newBalance->toDecimal(),
                    'status' => $locked->status->value,
                ],
                actor: $actor,
            );

            return $ownerPayment;
        });
    }
}
