<?php

namespace App\Actions\Vehicles;

use App\Enums\VehicleStatus;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TransitionVehicleStatusAction
{
    public function __construct(
        private AuditLogger $auditLogger,
    ) {}

    public function execute(
        Vehicle $vehicle,
        VehicleStatus $targetStatus,
        User $actor,
        ?string $reason = null,
    ): Vehicle {
        if ($vehicle->status === $targetStatus) {
            return $vehicle;
        }

        if (! $vehicle->status->canTransitionTo($targetStatus)) {
            throw ValidationException::withMessages([
                'status' => "Cannot transition vehicle status from {$vehicle->status->label()} to {$targetStatus->label()}.",
            ]);
        }

        return DB::transaction(function () use ($vehicle, $targetStatus, $actor, $reason): Vehicle {
            $previousStatus = $vehicle->status;

            $vehicle->update(['status' => $targetStatus]);

            $vehicle->statusHistories()->create([
                'from_status' => $previousStatus,
                'to_status' => $targetStatus,
                'reason' => $reason,
                'changed_by' => $actor->id,
                'created_at' => now(),
            ]);

            $this->auditLogger->audit(
                event: 'vehicle.status_changed',
                auditable: $vehicle,
                oldValues: ['status' => $previousStatus->value],
                newValues: ['status' => $targetStatus->value],
                reason: $reason,
                actor: $actor,
            );

            $this->auditLogger->activity(
                action: 'vehicle.status_changed',
                description: "Updated vehicle {$vehicle->reference_number} status from {$previousStatus->label()} to {$targetStatus->label()}.",
                subject: $vehicle,
                actor: $actor,
            );

            return $vehicle;
        });
    }
}
