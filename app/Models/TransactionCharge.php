<?php

namespace App\Models;

use App\Enums\ChargeTransactionType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * Dynamic or exceptional one-time charge applied to a business transaction (SSD 8.6 & 18.4).
 *
 * @property int $id
 * @property int|null $charge_category_id
 * @property string|null $custom_label
 * @property string $chargeable_type
 * @property int $chargeable_id
 * @property ChargeTransactionType $transaction_type
 * @property string $amount
 * @property string|null $notes
 * @property int|null $applied_by
 * @property Carbon|null $created_at
 */
#[Fillable([
    'charge_category_id',
    'custom_label',
    'chargeable_type',
    'chargeable_id',
    'transaction_type',
    'amount',
    'notes',
    'applied_by',
    'created_at',
])]
class TransactionCharge extends Model
{
    public const UPDATED_AT = null;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'transaction_type' => ChargeTransactionType::class,
            'amount' => 'decimal:2',
            'created_at' => 'datetime',
        ];
    }

    public function displayLabel(): string
    {
        if ($this->relationLoaded('category') && $this->category !== null) {
            return $this->category->name;
        }

        return $this->custom_label ?: 'One-Time Charge';
    }

    /**
     * @return BelongsTo<ChargeCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ChargeCategory::class, 'charge_category_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function applier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'applied_by');
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function chargeable(): MorphTo
    {
        return $this->morphTo();
    }
}
