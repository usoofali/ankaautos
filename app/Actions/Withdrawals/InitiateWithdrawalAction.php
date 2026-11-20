<?php

namespace App\Actions\Withdrawals;

use App\Actions\Charges\ApplyTransactionChargeAction;
use App\Enums\ChargeTransactionType;
use App\Enums\ConsignmentStatus;
use App\Enums\ExpenseBearer;
use App\Enums\WithdrawalStatus;
use App\Models\Consignment;
use App\Models\User;
use App\Models\VehicleExpense;
use App\Models\WithdrawalRequest;
use App\Services\AuditLogger;
use App\Services\ReferenceNumberGenerator;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InitiateWithdrawalAction
{
    public function __construct(
        private ReferenceNumberGenerator $referenceGenerator,
        private ApplyTransactionChargeAction $applyCharge,
        private AuditLogger $auditLogger,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(Consignment $consignment, array $data, User $actor): WithdrawalRequest
    {
        if (in_array($consignment->status, [
            ConsignmentStatus::Sold,
            ConsignmentStatus::SettlementPending,
            ConsignmentStatus::Settled,
            ConsignmentStatus::Withdrawn,
        ], true)) {
            throw ValidationException::withMessages([
                'consignment_id' => 'This consignment is already sold, settled, or withdrawn.',
            ]);
        }

        $hasOpenWithdrawal = WithdrawalRequest::query()
            ->where('consignment_id', $consignment->id)
            ->whereIn('status', [WithdrawalStatus::AwaitingPayment->value, WithdrawalStatus::PaidReadyForHandover->value])
            ->exists();

        if ($hasOpenWithdrawal) {
            throw ValidationException::withMessages([
                'consignment_id' => 'An active withdrawal request already exists for this consignment.',
            ]);
        }

        return DB::transaction(function () use ($consignment, $data, $actor): WithdrawalRequest {
            $ownerExpenses = VehicleExpense::query()
                ->where('consignment_id', $consignment->id)
                ->where('borne_by', ExpenseBearer::OwnerDeductible->value)
                ->whereNull('settled_in_owner_settlement_id')
                ->whereNull('settled_in_withdrawal_id')
                ->get();

            $totalExpenses = Money::zero();
            foreach ($ownerExpenses as $expense) {
                $totalExpenses = $totalExpenses->add(Money::fromDecimal($expense->amount));
            }

            /** @var WithdrawalRequest $withdrawal */
            $withdrawal = WithdrawalRequest::query()->create([
                'reference_number' => $this->referenceGenerator->generate('WDR'),
                'consignment_id' => $consignment->id,
                'vehicle_id' => $consignment->vehicle_id,
                'vehicle_owner_id' => $consignment->vehicle_owner_id,
                'status' => WithdrawalStatus::AwaitingPayment,
                'reason' => (string) $data['reason'],
                'total_owner_expenses' => $totalExpenses->toDecimal(),
                'total_withdrawal_charges' => '0.00',
                'total_due' => $totalExpenses->toDecimal(),
                'amount_paid' => '0.00',
                'balance_due' => $totalExpenses->toDecimal(),
                'requested_at' => now(),
                'initiated_by' => $actor->id,
            ]);

            $totalCharges = Money::zero();
            /** @var list<array<string, mixed>> $charges */
            $charges = is_array($data['charges'] ?? null) ? $data['charges'] : [];
            foreach ($charges as $chargeRow) {
                if (! empty($chargeRow['amount'])) {
                    $applied = $this->applyCharge->execute(
                        chargeable: $withdrawal,
                        transactionType: ChargeTransactionType::ConsignmentWithdrawal,
                        data: $chargeRow,
                        actor: $actor,
                    );
                    $totalCharges = $totalCharges->add(Money::fromDecimal($applied->amount));
                }
            }

            $totalDue = $totalExpenses->add($totalCharges);
            $status = $totalDue->isZero()
                ? WithdrawalStatus::PaidReadyForHandover
                : WithdrawalStatus::AwaitingPayment;

            $withdrawal->update([
                'total_withdrawal_charges' => $totalCharges->toDecimal(),
                'total_due' => $totalDue->toDecimal(),
                'balance_due' => $totalDue->toDecimal(),
                'status' => $status,
            ]);

            $prevConsStatus = $consignment->status;
            $consignment->update(['status' => ConsignmentStatus::WithdrawalRequested]);
            $consignment->statusHistories()->create([
                'from_status' => $prevConsStatus,
                'to_status' => ConsignmentStatus::WithdrawalRequested,
                'reason' => "Withdrawal {$withdrawal->reference_number} requested: {$withdrawal->reason}",
                'changed_by' => $actor->id,
                'created_at' => now(),
            ]);

            $this->auditLogger->audit(
                event: 'withdrawal.initiated',
                auditable: $withdrawal,
                newValues: [
                    'reference_number' => $withdrawal->reference_number,
                    'total_owner_expenses' => $totalExpenses->toDecimal(),
                    'total_withdrawal_charges' => $totalCharges->toDecimal(),
                    'total_due' => $totalDue->toDecimal(),
                ],
                reason: $withdrawal->reason,
                actor: $actor,
            );

            return $withdrawal;
        });
    }
}
