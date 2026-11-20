<?php

namespace App\Actions\Expenses;

use App\Enums\ExpenseBearer;
use App\Enums\ExpensePaymentStatus;
use App\Enums\FinancialTransactionType;
use App\Enums\RevenueBucket;
use App\Enums\TransactionDirection;
use App\Models\ExpensePayment;
use App\Models\User;
use App\Models\VehicleExpense;
use App\Services\AuditLogger;
use App\Services\FinancialLedgerService;
use App\Services\ReferenceNumberGenerator;
use App\Support\Money;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RecordExpensePaymentAction
{
    public function __construct(
        private ReferenceNumberGenerator $referenceGenerator,
        private FinancialLedgerService $ledger,
        private AuditLogger $auditLogger,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(VehicleExpense $expense, array $data, User $actor): ExpensePayment
    {
        $paymentAmount = Money::fromDecimal((string) $data['amount']);

        if (! $paymentAmount->isPositive()) {
            throw ValidationException::withMessages([
                'amount' => 'Payment amount must be greater than zero.',
            ]);
        }

        return DB::transaction(function () use ($expense, $data, $actor, $paymentAmount): ExpensePayment {
            /** @var VehicleExpense $locked */
            $locked = VehicleExpense::query()->lockForUpdate()->findOrFail($expense->id);
            $balance = $locked->balanceDue();

            if ($paymentAmount->greaterThan($balance)) {
                throw ValidationException::withMessages([
                    'amount' => "Payment exceeds remaining expense balance ({$balance->format()}).",
                ]);
            }

            /** @var ExpensePayment $payment */
            $payment = $locked->payments()->create([
                'reference_number' => $this->referenceGenerator->generate('EPY'),
                'payment_method_id' => (int) $data['payment_method_id'],
                'financial_account_id' => (int) $data['financial_account_id'],
                'amount' => $paymentAmount->toDecimal(),
                'paid_at' => $data['paid_at'],
                'payment_reference' => $data['payment_reference'] ?? null,
                'notes' => $data['notes'] ?? null,
                'recorded_by' => $actor->id,
            ]);

            $newPaid = Money::fromDecimal($locked->amount_paid)->add($paymentAmount);
            $total = Money::fromDecimal($locked->amount);
            $newStatus = $newPaid->greaterThanOrEqual($total)
                ? ExpensePaymentStatus::Paid
                : ExpensePaymentStatus::PartiallyPaid;

            $locked->update([
                'amount_paid' => $newPaid->toDecimal(),
                'payment_status' => $newStatus,
            ]);

            $this->ledger->post(
                type: $locked->borne_by === ExpenseBearer::OwnerDeductible
                    ? FinancialTransactionType::OwnerDeductibleExpense
                    : FinancialTransactionType::CompanyExpense,
                direction: TransactionDirection::Outflow,
                bucket: RevenueBucket::Company,
                amount: $paymentAmount,
                description: "Expense payment {$payment->reference_number} for {$locked->reference_number}: {$locked->description}",
                financialAccountId: $payment->financial_account_id,
                paymentMethodId: $payment->payment_method_id,
                vehicleId: $locked->vehicle_id,
                source: $payment,
                transactedAt: Carbon::parse((string) $data['paid_at']),
                actor: $actor,
            );

            $this->auditLogger->audit(
                event: 'expense.payment_recorded',
                auditable: $locked,
                newValues: [
                    'payment_reference' => $payment->reference_number,
                    'amount' => $paymentAmount->toDecimal(),
                    'new_amount_paid' => $newPaid->toDecimal(),
                    'payment_status' => $newStatus->value,
                ],
                actor: $actor,
            );

            return $payment;
        });
    }
}
