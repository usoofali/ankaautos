<?php

namespace App\Actions\Handovers;

use App\Enums\SaleStatus;
use App\Models\Handover;
use App\Models\Sale;
use App\Models\User;
use App\Models\WithdrawalRequest;
use App\Services\AuditLogger;
use App\Services\ReferenceNumberGenerator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RecordHandoverAction
{
    public function __construct(
        private ReferenceNumberGenerator $referenceGenerator,
        private AuditLogger $auditLogger,
    ) {}

    /**
     * Record a vehicle handover for a Sale (requires 100% payment unless Manager approves early release)
     * or a WithdrawalRequest (strict cash-first: 100% paid required, no waivers).
     *
     * @param  array<string, mixed>  $data
     */
    public function executeForSale(Sale $sale, array $data, User $actor): Handover
    {
        return DB::transaction(function () use ($sale, $data, $actor): Handover {
            /** @var Sale $locked */
            $locked = Sale::query()->lockForUpdate()->findOrFail($sale->id);

            if ($locked->status === SaleStatus::Cancelled) {
                throw ValidationException::withMessages([
                    'recipient_name' => 'Cannot hand over a vehicle for a cancelled sale.',
                ]);
            }

            if ($locked->handover()->exists()) {
                throw ValidationException::withMessages([
                    'recipient_name' => 'A handover has already been recorded for this sale.',
                ]);
            }

            if (! $locked->isFullyPaid()) {
                if (! $actor->can('handovers.approve_early_release') || empty($data['early_handover_reason'])) {
                    throw ValidationException::withMessages([
                        'early_handover_reason' => 'Full customer payment is required before handover unless a Manager approves an early-release exception with a reason.',
                    ]);
                }

                $locked->update([
                    'early_handover_approved_by' => $actor->id,
                    'early_handover_reason' => (string) $data['early_handover_reason'],
                ]);
            }

            return $this->createHandoverRecord($locked, $locked->vehicle_id, $data, $actor);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function executeForWithdrawal(WithdrawalRequest $withdrawal, array $data, User $actor): Handover
    {
        if (! $withdrawal->isClearedForHandover()) {
            throw ValidationException::withMessages([
                'recipient_name' => 'All withdrawal charges and owner-deductible expenses must be paid 100% in cash/transfer before handover (no waivers permitted).',
            ]);
        }

        if ($withdrawal->handover()->exists()) {
            throw ValidationException::withMessages([
                'recipient_name' => 'A handover has already been recorded for this withdrawal.',
            ]);
        }

        return $this->createHandoverRecord($withdrawal, $withdrawal->vehicle_id, $data, $actor);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function createHandoverRecord(Sale|WithdrawalRequest $parent, int $vehicleId, array $data, User $actor): Handover
    {
        /** @var Handover $handover */
        $handover = $parent->handover()->create([
            'reference_number' => $this->referenceGenerator->generate('HND'),
            'vehicle_id' => $vehicleId,
            'recipient_name' => (string) $data['recipient_name'],
            'recipient_phone' => (string) $data['recipient_phone'],
            'recipient_id_number' => $data['recipient_id_number'] ?? null,
            'odometer_reading' => isset($data['odometer_reading']) && $data['odometer_reading'] !== '' ? (int) $data['odometer_reading'] : null,
            'fuel_level' => $data['fuel_level'] ?? null,
            'condition_summary' => $data['condition_summary'] ?? null,
            'handed_over_at' => now(),
            'handed_over_by' => $actor->id,
            'notes' => $data['notes'] ?? null,
        ]);

        /** @var list<array{item_name: string, is_included?: bool, remarks?: string|null}> $items */
        $items = is_array($data['items'] ?? null) ? $data['items'] : [];
        foreach ($items as $item) {
            if (trim($item['item_name']) !== '') {
                $handover->items()->create([
                    'item_name' => trim($item['item_name']),
                    'is_included' => (bool) ($item['is_included'] ?? true),
                    'remarks' => $item['remarks'] ?? null,
                ]);
            }
        }

        $this->auditLogger->audit(
            event: 'handover.recorded',
            auditable: $handover,
            newValues: [
                'reference_number' => $handover->reference_number,
                'parent' => $parent->reference_number,
                'recipient_name' => $handover->recipient_name,
            ],
            actor: $actor,
        );

        return $handover;
    }
}
