<?php

namespace App\Actions\Charges;

use App\Enums\ChargeTransactionType;
use App\Models\ChargeCategory;
use App\Models\TransactionCharge;
use App\Models\User;
use App\Services\AuditLogger;
use App\Support\Money;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class ApplyTransactionChargeAction
{
    public function __construct(
        private AuditLogger $auditLogger,
    ) {}

    /**
     * Apply a dynamic charge category (with transaction-specific amount) OR an exceptional one-time charge.
     *
     * @param  array<string, mixed>  $data
     */
    public function execute(Model $chargeable, ChargeTransactionType $transactionType, array $data, User $actor): TransactionCharge
    {
        $amount = Money::fromDecimal((string) $data['amount']);
        if (! $amount->isPositive()) {
            throw ValidationException::withMessages([
                'amount' => 'Charge amount must be greater than zero.',
            ]);
        }

        $categoryId = isset($data['charge_category_id']) && $data['charge_category_id'] !== ''
            ? (int) $data['charge_category_id']
            : null;
        $customLabel = isset($data['custom_label']) && trim((string) $data['custom_label']) !== ''
            ? trim((string) $data['custom_label'])
            : null;

        if ($categoryId === null && $customLabel === null) {
            throw ValidationException::withMessages([
                'charge_category_id' => 'Select a charge category or enter a custom one-time charge label.',
            ]);
        }

        if ($categoryId !== null) {
            /** @var ChargeCategory $category */
            $category = ChargeCategory::query()->findOrFail($categoryId);
            if (! $category->is_active) {
                throw ValidationException::withMessages([
                    'charge_category_id' => 'Selected charge category is inactive and cannot be applied to new transactions.',
                ]);
            }
        }

        /** @var TransactionCharge $charge */
        $charge = TransactionCharge::query()->create([
            'charge_category_id' => $categoryId,
            'custom_label' => $categoryId === null ? $customLabel : null,
            'chargeable_type' => $chargeable->getMorphClass(),
            'chargeable_id' => (int) $chargeable->getKey(),
            'transaction_type' => $transactionType,
            'amount' => $amount->toDecimal(),
            'notes' => $data['notes'] ?? null,
            'applied_by' => $actor->id,
            'created_at' => now(),
        ]);

        $charge->load('category');

        $this->auditLogger->audit(
            event: 'charge.applied',
            auditable: $chargeable,
            newValues: [
                'charge_id' => $charge->id,
                'label' => $charge->displayLabel(),
                'transaction_type' => $transactionType->value,
                'amount' => $amount->toDecimal(),
            ],
            actor: $actor,
        );

        return $charge;
    }
}
