<?php

namespace App\Actions\Consignments;

use App\Enums\CommissionType;
use App\Enums\ConsignmentStatus;
use App\Models\Consignment;
use App\Models\ConsignmentAgreement;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AmendConsignmentAgreementAction
{
    public function __construct(
        private AuditLogger $auditLogger,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(Consignment $consignment, array $data, User $actor): Consignment
    {
        if (in_array($consignment->status, [
            ConsignmentStatus::Sold,
            ConsignmentStatus::SettlementPending,
            ConsignmentStatus::Settled,
            ConsignmentStatus::Withdrawn,
        ], true)) {
            throw ValidationException::withMessages([
                'amendment_reason' => 'Financial terms cannot be amended after a consignment has been sold or withdrawn.',
            ]);
        }

        return DB::transaction(function () use ($consignment, $data, $actor): Consignment {
            $oldValues = $consignment->only([
                'agreed_selling_price',
                'minimum_acceptable_price',
                'commission_type',
                'commission_value',
                'commission_notes',
                'expense_responsibility_notes',
                'withdrawal_terms',
                'settlement_terms',
            ]);

            $commissionType = CommissionType::from((string) $data['commission_type']);

            $consignment->update([
                'agreed_selling_price' => $data['agreed_selling_price'],
                'minimum_acceptable_price' => $data['minimum_acceptable_price'] ?? null,
                'commission_type' => $commissionType,
                'commission_value' => $data['commission_value'] ?? '0.00',
                'commission_notes' => $data['commission_notes'] ?? null,
                'expense_responsibility_notes' => $data['expense_responsibility_notes'] ?? null,
                'withdrawal_terms' => $data['withdrawal_terms'] ?? null,
                'settlement_terms' => $data['settlement_terms'] ?? null,
            ]);

            $consignment->vehicle()->update([
                'advertised_price' => $consignment->agreed_selling_price,
                'minimum_price' => $consignment->minimum_acceptable_price,
            ]);

            /** @var ConsignmentAgreement $agreement */
            $agreement = $consignment->agreement()->firstOrCreate(
                ['consignment_id' => $consignment->id],
                ['current_version' => 0, 'commencement_date' => $consignment->received_at],
            );

            $nextVersion = $agreement->current_version + 1;
            $agreement->update(['current_version' => $nextVersion]);

            $agreement->versions()->create([
                'version_number' => $nextVersion,
                'agreed_selling_price' => $consignment->agreed_selling_price,
                'minimum_acceptable_price' => $consignment->minimum_acceptable_price,
                'commission_type' => $consignment->commission_type,
                'commission_value' => $consignment->commission_value,
                'commission_notes' => $consignment->commission_notes,
                'expense_responsibility_notes' => $consignment->expense_responsibility_notes,
                'withdrawal_terms' => $consignment->withdrawal_terms,
                'settlement_terms' => $consignment->settlement_terms,
                'amendment_reason' => (string) $data['amendment_reason'],
                'created_by' => $actor->id,
                'created_at' => now(),
            ]);

            $this->auditLogger->audit(
                event: 'consignment.agreement_amended',
                auditable: $consignment,
                oldValues: $oldValues,
                newValues: [
                    'version' => $nextVersion,
                    'agreed_selling_price' => $consignment->agreed_selling_price,
                    'minimum_acceptable_price' => $consignment->minimum_acceptable_price,
                    'commission_type' => $consignment->commission_type->value,
                    'commission_value' => $consignment->commission_value,
                ],
                reason: (string) $data['amendment_reason'],
                actor: $actor,
            );

            $this->auditLogger->activity(
                action: 'consignment.agreement_amended',
                description: "Amended consignment {$consignment->reference_number} terms to version v{$nextVersion}.",
                subject: $consignment,
                actor: $actor,
            );

            return $consignment;
        });
    }
}
