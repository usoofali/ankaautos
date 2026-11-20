<?php

namespace App\Models;

use App\Enums\PaymentType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $reference_number
 * @property string|null $idempotency_key
 * @property string $payable_type
 * @property int $payable_id
 * @property int|null $customer_id
 * @property int|null $vehicle_owner_id
 * @property PaymentType $payment_type
 * @property int $payment_method_id
 * @property int $financial_account_id
 * @property string $amount
 * @property Carbon $paid_at
 * @property string|null $external_reference
 * @property string|null $notes
 * @property bool $is_voided
 * @property int|null $recorded_by
 * @property Carbon|null $created_at
 */
#[Fillable([
    'reference_number',
    'idempotency_key',
    'payable_type',
    'payable_id',
    'customer_id',
    'vehicle_owner_id',
    'payment_type',
    'payment_method_id',
    'financial_account_id',
    'amount',
    'paid_at',
    'external_reference',
    'notes',
    'is_voided',
    'recorded_by',
    'created_at',
])]
class Payment extends Model
{
    public const UPDATED_AT = null;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'payment_type' => PaymentType::class,
            'amount' => 'decimal:2',
            'paid_at' => 'date',
            'is_voided' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function payable(): MorphTo
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
