<?php

namespace App\Actions\Sales;

use App\Enums\FinancialTransactionType;
use App\Enums\OwnershipType;
use App\Enums\PaymentType;
use App\Enums\RevenueBucket;
use App\Enums\SaleStatus;
use App\Enums\SettlementStatus;
use App\Enums\TransactionDirection;
use App\Models\OwnerSettlement;
use App\Models\Payment;
use App\Models\Sale;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\FinancialLedgerService;
use App\Services\ReferenceNumberGenerator;
use App\Support\Money;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RecordSalePaymentAction
{
    public function __construct(
        private ReferenceNumberGenerator $referenceGenerator,
        private FinancialLedgerService $ledger,
        private AuditLogger $auditLogger,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(Sale $sale, array $data, User $actor): Payment
    {
        $idempotencyKey = isset($data['idempotency_key']) && trim((string) $data['idempotency_key']) !== ''
            ? trim((string) $data['idempotency_key'])
            : null;

        if ($idempotencyKey !== null) {
            /** @var Payment|null $existing */
            $existing = Payment::query()->where('idempotency_key', $idempotencyKey)->first();
            if ($existing !== null) {
                return $existing;
            }
        }

        $paymentAmount = Money::fromDecimal((string) $data['amount']);
        if (! $paymentAmount->isPositive()) {
            throw ValidationException::withMessages([
                'amount' => 'Payment amount must be greater than zero.',
            ]);
        }

        return DB::transaction(function () use ($sale, $data, $actor, $paymentAmount, $idempotencyKey): Payment {
            /** @var Sale $locked */
            $locked = Sale::query()->lockForUpdate()->findOrFail($sale->id);

            if ($locked->status === SaleStatus::Cancelled) {
                throw ValidationException::withMessages([
                    'amount' => 'Cannot record payments against a cancelled sale.',
                ]);
            }

            $balance = Money::fromDecimal($locked->balance_due);
            if ($paymentAmount->greaterThan($balance)) {
                throw ValidationException::withMessages([
                    'amount' => "Payment exceeds remaining customer balance ({$balance->format()}).",
                ]);
            }

            $paymentType = isset($data['payment_type'])
                ? PaymentType::from((string) $data['payment_type'])
                : ($locked->is_installment ? PaymentType::Installment : PaymentType::SalePayment);

            /** @var Payment $payment */
            $payment = $locked->payments()->create([
                'reference_number' => $this->referenceGenerator->generate('PAY'),
                'idempotency_key' => $idempotencyKey,
                'customer_id' => $locked->customer_id,
                'payment_type' => $paymentType,
                'payment_method_id' => (int) $data['payment_method_id'],
                'financial_account_id' => (int) $data['financial_account_id'],
                'amount' => $paymentAmount->toDecimal(),
                'paid_at' => $data['paid_at'],
                'external_reference' => $data['external_reference'] ?? null,
                'notes' => $data['notes'] ?? null,
                'recorded_by' => $actor->id,
                'created_at' => now(),
            ]);

            $newPaid = Money::fromDecimal($locked->amount_paid)->add($paymentAmount);
            $newBalance = Money::fromDecimal($locked->final_price)->subtract($newPaid)->max(Money::zero());
            $completedNow = $newBalance->isZero();

            $locked->update([
                'amount_paid' => $newPaid->toDecimal(),
                'balance_due' => $newBalance->toDecimal(),
                'status' => $completedNow ? SaleStatus::Completed : SaleStatus::Confirmed,
                'completed_at' => $completedNow ? now() : null,
            ]);

            if ($completedNow) {
                $locked->statusHistories()->create([
                    'from_status' => SaleStatus::Confirmed,
                    'to_status' => SaleStatus::Completed,
                    'reason' => "Final payment {$payment->reference_number} cleared full balance.",
                    'changed_by' => $actor->id,
                    'created_at' => now(),
                ]);

                // Strict cash-first rule: unlock OwnerSettlement now that customer paid 100%
                /** @var OwnerSettlement|null $settlement */
                $settlement = $locked->ownerSettlement()->lockForUpdate()->first();
                if ($settlement !== null && $settlement->status === SettlementStatus::LockedAwaitingCustomerPayment) {
                    $settlement->update(['status' => SettlementStatus::PendingPayout]);
                }
            }

            $isConsignment = $locked->ownership_type === OwnershipType::Consignment;

            $this->ledger->post(
                type: $isConsignment
                    ? FinancialTransactionType::ConsignmentSaleProceeds
                    : FinancialTransactionType::CompanySaleProceeds,
                direction: TransactionDirection::Inflow,
                bucket: $isConsignment ? RevenueBucket::ThirdPartyOwner : RevenueBucket::Company,
                amount: $paymentAmount,
                description: "Customer payment {$payment->reference_number} for sale {$locked->reference_number}",
                financialAccountId: $payment->financial_account_id,
                paymentMethodId: $payment->payment_method_id,
                vehicleId: $locked->vehicle_id,
                source: $payment,
                transactedAt: Carbon::parse((string) $data['paid_at']),
                actor: $actor,
            );

            $this->auditLogger->audit(
                event: 'sale.payment_recorded',
                auditable: $locked,
                newValues: [
                    'payment_reference' => $payment->reference_number,
                    'amount' => $paymentAmount->toDecimal(),
                    'balance_due' => $newBalance->toDecimal(),
                ],
                actor: $actor,
            );

            return $payment;
        });
    }
}
