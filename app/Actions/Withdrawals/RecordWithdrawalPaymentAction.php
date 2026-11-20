<?php

namespace App\Actions\Withdrawals;

use App\Enums\FinancialTransactionType;
use App\Enums\PaymentType;
use App\Enums\RevenueBucket;
use App\Enums\TransactionDirection;
use App\Enums\WithdrawalStatus;
use App\Models\Payment;
use App\Models\User;
use App\Models\WithdrawalRequest;
use App\Services\AuditLogger;
use App\Services\FinancialLedgerService;
use App\Services\ReferenceNumberGenerator;
use App\Support\Money;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RecordWithdrawalPaymentAction
{
    public function __construct(
        private ReferenceNumberGenerator $referenceGenerator,
        private FinancialLedgerService $ledger,
        private AuditLogger $auditLogger,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(WithdrawalRequest $withdrawal, array $data, User $actor): Payment
    {
        $amount = Money::fromDecimal((string) $data['amount']);
        if (! $amount->isPositive()) {
            throw ValidationException::withMessages([
                'amount' => 'Payment amount must be greater than zero.',
            ]);
        }

        return DB::transaction(function () use ($withdrawal, $data, $actor, $amount): Payment {
            /** @var WithdrawalRequest $locked */
            $locked = WithdrawalRequest::query()->lockForUpdate()->findOrFail($withdrawal->id);

            if ($locked->status !== WithdrawalStatus::AwaitingPayment) {
                throw ValidationException::withMessages([
                    'amount' => 'This withdrawal request is not awaiting payment.',
                ]);
            }

            $balance = Money::fromDecimal($locked->balance_due);
            if ($amount->greaterThan($balance)) {
                throw ValidationException::withMessages([
                    'amount' => "Payment exceeds remaining withdrawal balance ({$balance->format()}).",
                ]);
            }

            /** @var Payment $payment */
            $payment = $locked->payments()->create([
                'reference_number' => $this->referenceGenerator->generate('PAY'),
                'idempotency_key' => $data['idempotency_key'] ?? null,
                'vehicle_owner_id' => $locked->vehicle_owner_id,
                'payment_type' => PaymentType::WithdrawalPayment,
                'payment_method_id' => (int) $data['payment_method_id'],
                'financial_account_id' => (int) $data['financial_account_id'],
                'amount' => $amount->toDecimal(),
                'paid_at' => $data['paid_at'],
                'external_reference' => $data['external_reference'] ?? null,
                'notes' => $data['notes'] ?? null,
                'recorded_by' => $actor->id,
                'created_at' => now(),
            ]);

            $newPaid = Money::fromDecimal($locked->amount_paid)->add($amount);
            $newBalance = Money::fromDecimal($locked->total_due)->subtract($newPaid)->max(Money::zero());

            $locked->update([
                'amount_paid' => $newPaid->toDecimal(),
                'balance_due' => $newBalance->toDecimal(),
                'status' => $newBalance->isZero()
                    ? WithdrawalStatus::PaidReadyForHandover
                    : WithdrawalStatus::AwaitingPayment,
            ]);

            $this->ledger->post(
                type: FinancialTransactionType::WithdrawalChargePayment,
                direction: TransactionDirection::Inflow,
                bucket: RevenueBucket::Company,
                amount: $amount,
                description: "Consignment withdrawal payment {$payment->reference_number} for {$locked->reference_number}",
                financialAccountId: $payment->financial_account_id,
                paymentMethodId: $payment->payment_method_id,
                vehicleId: $locked->vehicle_id,
                source: $payment,
                transactedAt: Carbon::parse((string) $data['paid_at']),
                actor: $actor,
            );

            $this->auditLogger->audit(
                event: 'withdrawal.payment_recorded',
                auditable: $locked,
                newValues: [
                    'payment_reference' => $payment->reference_number,
                    'amount' => $amount->toDecimal(),
                    'balance_due' => $newBalance->toDecimal(),
                ],
                actor: $actor,
            );

            return $payment;
        });
    }
}
