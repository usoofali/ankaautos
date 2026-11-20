<?php

namespace App\Actions\Consignments;

use App\Enums\CommissionType;
use App\Enums\ConsignmentStatus;
use App\Enums\OwnershipType;
use App\Enums\SystemRole;
use App\Models\Consignment;
use App\Models\ConsignmentAgreement;
use App\Models\User;
use App\Models\Vehicle;
use App\Notifications\BusinessEventNotification;
use App\Services\AuditLogger;
use App\Services\ReferenceNumberGenerator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

class CreateConsignmentAction
{
    public function __construct(
        private ReferenceNumberGenerator $referenceGenerator,
        private AuditLogger $auditLogger,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(array $data, User $actor): Consignment
    {
        /** @var Vehicle $vehicle */
        $vehicle = Vehicle::query()->findOrFail($data['vehicle_id']);

        if ($vehicle->ownership_type !== OwnershipType::Consignment) {
            throw ValidationException::withMessages([
                'vehicle_id' => 'Only vehicles classified as Third-Party Consignment can be linked to a consignment agreement.',
            ]);
        }

        $existingActive = $vehicle->consignments()
            ->whereNotIn('status', [ConsignmentStatus::Settled->value, ConsignmentStatus::Withdrawn->value])
            ->exists();

        if ($existingActive) {
            throw ValidationException::withMessages([
                'vehicle_id' => "Vehicle {$vehicle->reference_number} already has an open consignment record.",
            ]);
        }

        return DB::transaction(function () use ($data, $actor, $vehicle): Consignment {
            $reference = $this->referenceGenerator->generate('CON');
            $commissionType = CommissionType::from((string) $data['commission_type']);
            $status = isset($data['status'])
                ? ConsignmentStatus::from((string) $data['status'])
                : ConsignmentStatus::Active;

            /** @var Consignment $consignment */
            $consignment = Consignment::query()->create([
                'reference_number' => $reference,
                'vehicle_id' => $vehicle->id,
                'vehicle_owner_id' => (int) $data['vehicle_owner_id'],
                'status' => $status,
                'received_at' => $data['received_at'],
                'expected_review_date' => $data['expected_review_date'] ?? null,
                'agreed_selling_price' => $data['agreed_selling_price'],
                'minimum_acceptable_price' => $data['minimum_acceptable_price'] ?? null,
                'commission_type' => $commissionType,
                'commission_value' => $data['commission_value'] ?? '0.00',
                'commission_notes' => $data['commission_notes'] ?? null,
                'expense_responsibility_notes' => $data['expense_responsibility_notes'] ?? null,
                'withdrawal_terms' => $data['withdrawal_terms'] ?? null,
                'settlement_terms' => $data['settlement_terms'] ?? null,
                'remarks' => $data['remarks'] ?? null,
                'received_by' => $actor->id,
            ]);

            // Sync vehicle asking & minimum prices so sales and public catalog remain authoritative
            $vehicle->update([
                'advertised_price' => $consignment->agreed_selling_price,
                'minimum_price' => $consignment->minimum_acceptable_price,
            ]);

            /** @var ConsignmentAgreement $agreement */
            $agreement = $consignment->agreement()->create([
                'current_version' => 1,
                'commencement_date' => $data['received_at'],
                'duration_days' => isset($data['duration_days']) && $data['duration_days'] !== '' ? (int) $data['duration_days'] : null,
            ]);

            $agreement->versions()->create([
                'version_number' => 1,
                'agreed_selling_price' => $consignment->agreed_selling_price,
                'minimum_acceptable_price' => $consignment->minimum_acceptable_price,
                'commission_type' => $consignment->commission_type,
                'commission_value' => $consignment->commission_value,
                'commission_notes' => $consignment->commission_notes,
                'expense_responsibility_notes' => $consignment->expense_responsibility_notes,
                'withdrawal_terms' => $consignment->withdrawal_terms,
                'settlement_terms' => $consignment->settlement_terms,
                'amendment_reason' => 'Initial consignment agreement terms.',
                'created_by' => $actor->id,
                'created_at' => now(),
            ]);

            $consignment->statusHistories()->create([
                'from_status' => null,
                'to_status' => $status,
                'reason' => 'Initial consignment registration.',
                'changed_by' => $actor->id,
                'created_at' => now(),
            ]);

            $this->auditLogger->audit(
                event: 'consignment.created',
                auditable: $consignment,
                newValues: $consignment->only([
                    'reference_number',
                    'vehicle_id',
                    'vehicle_owner_id',
                    'agreed_selling_price',
                    'minimum_acceptable_price',
                    'commission_type',
                    'commission_value',
                ]),
                actor: $actor,
            );

            $this->auditLogger->activity(
                action: 'consignment.created',
                description: "Registered consignment {$reference} for vehicle {$vehicle->reference_number}.",
                subject: $consignment,
                actor: $actor,
            );

            $managers = User::role([SystemRole::SuperAdministrator->value, SystemRole::Manager->value])
                ->where('id', '!=', $actor->id)
                ->where('is_active', true)
                ->get();

            if ($managers->isNotEmpty()) {
                Notification::send($managers, new BusinessEventNotification(
                    title: 'New Consignment Registered',
                    message: "Consignment {$reference} for vehicle {$vehicle->reference_number} was registered by {$actor->name}.",
                    category: 'consignment',
                    actionUrl: route('consignments.show', $consignment),
                    severity: 'info',
                ));
            }

            return $consignment;
        });
    }
}
