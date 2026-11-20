<?php

namespace App\Models;

use App\Enums\CommissionType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Immutable historical snapshot of consignment financial terms (SSD 10.5).
 *
 * @property int $id
 * @property int $consignment_agreement_id
 * @property int $version_number
 * @property string $agreed_selling_price
 * @property string|null $minimum_acceptable_price
 * @property CommissionType $commission_type
 * @property string $commission_value
 * @property string|null $commission_notes
 * @property string|null $expense_responsibility_notes
 * @property string|null $withdrawal_terms
 * @property string|null $settlement_terms
 * @property string|null $amendment_reason
 * @property int|null $created_by
 * @property Carbon|null $created_at
 */
#[Fillable([
    'consignment_agreement_id',
    'version_number',
    'agreed_selling_price',
    'minimum_acceptable_price',
    'commission_type',
    'commission_value',
    'commission_notes',
    'expense_responsibility_notes',
    'withdrawal_terms',
    'settlement_terms',
    'amendment_reason',
    'created_by',
    'created_at',
])]
class ConsignmentAgreementVersion extends Model
{
    public const UPDATED_AT = null;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'version_number' => 'integer',
            'commission_type' => CommissionType::class,
            'agreed_selling_price' => 'decimal:2',
            'minimum_acceptable_price' => 'decimal:2',
            'commission_value' => 'decimal:2',
            'created_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<ConsignmentAgreement, $this>
     */
    public function agreement(): BelongsTo
    {
        return $this->belongsTo(ConsignmentAgreement::class, 'consignment_agreement_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
