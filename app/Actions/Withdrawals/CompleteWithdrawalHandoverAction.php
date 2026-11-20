<?php

namespace App\Actions\Withdrawals;

use App\Actions\Handovers\RecordHandoverAction;
use App\Enums\ConsignmentStatus;
use App\Enums\ExpenseBearer;
use App\Enums\VehicleStatus;
use App\Enums\WithdrawalStatus;
use App\Models\Consignment;
use App\Models\Handover;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleExpense;
use App\Models\WithdrawalRequest;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CompleteWithdrawalHandoverAction
{
    public function __construct(
        private RecordHandoverAction $recordHandover,
        private AuditLogger $auditLogger,
    ) {}

    /**
     * Strictly cash-first completion: requires 100% payment (no waivers) before handover and vehicle release.
     *
     * @param  array<string, mixed>  $handoverData
     */
    public function execute(WithdrawalRequest $withdrawal, array $handoverData, User $actor): Handover
    {
        return DB::transaction(function () use ($withdrawal, $handoverData, $actor): Handover {
            /** @var WithdrawalRequest $locked */
            $locked = WithdrawalRequest::query()->lockForUpdate()->findOrFail($withdrawal->id);

            if (! $locked->isClearedForHandover()) {
                throw ValidationException::withMessages([
                    'recipient_name' => 'Withdrawal handover is blocked until 100% of withdrawal charges and owner-deductible expenses are paid in full (no waivers allowed).',
                ]);
            }

            $handover = $this->recordHandover->executeForWithdrawal($locked, $handoverData, $actor);

            // Lock all unsettled owner-deductible expenses into this withdrawal
            VehicleExpense::query()
                ->where('consignment_id', $locked->consignment_id)
                ->where('borne_by', ExpenseBearer::OwnerDeductible->value)
                ->whereNull('settled_in_owner_settlement_id')
                ->whereNull('settled_in_withdrawal_id')
                ->update(['settled_in_withdrawal_id' => $locked->id]);

            $locked->update([
                'status' => WithdrawalStatus::Completed,
                'completed_at' => now(),
            ]);

            /** @var Consignment $consignment */
            $consignment = Consignment::query()->lockForUpdate()->findOrFail($locked->consignment_id);
            $prevCons = $consignment->status;
            $consignment->update(['status' => ConsignmentStatus::Withdrawn]);
            $consignment->statusHistories()->create([
                'from_status' => $prevCons,
                'to_status' => ConsignmentStatus::Withdrawn,
                'reason' => "Completed withdrawal {$locked->reference_number} with handover {$handover->reference_number}.",
                'changed_by' => $actor->id,
                'created_at' => now(),
            ]);

            /** @var Vehicle $vehicle */
            $vehicle = Vehicle::query()->lockForUpdate()->findOrFail($locked->vehicle_id);
            $prevVeh = $vehicle->status;
            $vehicle->update(['status' => VehicleStatus::Withdrawn]);
            $vehicle->statusHistories()->create([
                'from_status' => $prevVeh,
                'to_status' => VehicleStatus::Withdrawn,
                'reason' => "Withdrawn by owner under {$locked->reference_number}.",
                'changed_by' => $actor->id,
                'created_at' => now(),
            ]);

            $this->auditLogger->audit(
                event: 'withdrawal.completed',
                auditable: $locked,
                newValues: [
                    'status' => WithdrawalStatus::Completed->value,
                    'handover_reference' => $handover->reference_number,
                ],
                actor: $actor,
            );

            return $handover;
        });
    }
}
