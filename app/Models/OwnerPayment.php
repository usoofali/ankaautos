<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $reference_number
 * @property string|null $idempotency_key
 * @property int $owner_settlement_id
 * @property int $vehicle_owner_id
 * @property int $payment_method_id
 * @property int $financial_account_id
 * @property string $amount
 * @property Carbon $paid_at
 * @property string|null $payment_reference
 * @property string|null $notes
 * @property int|null $recorded_by
 * @property Carbon|null $created_at
 */
#[Fillable([
    'reference_number',
    'idempotency_key',
    'owner_settlement_id',
    'vehicle_owner_id',
    'payment_method_id',
    'financial_account_id',
    'amount',
    'paid_at',
    'payment_reference',
    'notes',
    'recorded_by',
    'created_at',
])]
class OwnerPayment extends Model
{
    public const UPDATED_AT = null;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'paid_at' => 'date',
            'created_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<OwnerSettlement, $this>
     */
    public function settlement(): BelongsTo
    {
        return $this->belongsTo(OwnerSettlement::class, 'owner_settlement_id');
    }

    /**
     * @return BelongsTo<VehicleOwner, $this>
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(VehicleOwner::class, 'vehicle_owner_id');
    }

    /**
     * @return BelongsTo<PaymentMethod, $this>
     */
    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    /**
     * @return BelongsTo<FinancialAccount, $this>
     */
    public function financialAccount(): BelongsTo
    {
        return $this->belongsTo(FinancialAccount::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
