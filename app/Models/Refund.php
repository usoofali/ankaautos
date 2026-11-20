<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $reference_number
 * @property string $refundable_type
 * @property int $refundable_id
 * @property int $customer_id
 * @property string $amount
 * @property int $payment_method_id
 * @property int $financial_account_id
 * @property string $reason
 * @property Carbon $refunded_at
 * @property int $approved_by
 * @property Carbon|null $created_at
 */
#[Fillable([
    'reference_number',
    'refundable_type',
    'refundable_id',
    'customer_id',
    'amount',
    'payment_method_id',
    'financial_account_id',
    'reason',
    'refunded_at',
    'approved_by',
    'created_at',
])]
class Refund extends Model
{
    public const UPDATED_AT = null;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'refunded_at' => 'date',
            'created_at' => 'datetime',
        ];
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function refundable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
