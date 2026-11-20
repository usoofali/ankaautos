<?php

namespace App\Models;

use App\Enums\OwnershipType;
use App\Enums\SaleStatus;
use App\Support\Money;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $reference_number
 * @property int $vehicle_id
 * @property int $customer_id
 * @property int|null $reservation_id
 * @property int|null $consignment_id
 * @property OwnershipType $ownership_type
 * @property string $list_price
 * @property string $discount_amount
 * @property string $final_price
 * @property string $amount_paid
 * @property string $balance_due
 * @property bool $is_installment
 * @property string|null $installment_terms
 * @property SaleStatus $status
 * @property Carbon $confirmed_at
 * @property Carbon|null $completed_at
 * @property Carbon|null $cancelled_at
 * @property string|null $cancellation_reason
 * @property int|null $sales_officer_id
 * @property int|null $discount_approved_by
 * @property int|null $early_handover_approved_by
 * @property string|null $early_handover_reason
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
#[Fillable([
    'reference_number',
    'vehicle_id',
    'customer_id',
    'reservation_id',
    'consignment_id',
    'ownership_type',
    'list_price',
    'discount_amount',
    'final_price',
    'amount_paid',
    'balance_due',
    'is_installment',
    'installment_terms',
    'status',
    'confirmed_at',
    'completed_at',
    'cancelled_at',
    'cancellation_reason',
    'sales_officer_id',
    'discount_approved_by',
    'early_handover_approved_by',
    'early_handover_reason',
    'notes',
])]
class Sale extends Model
{
    use SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'ownership_type' => OwnershipType::class,
            'status' => SaleStatus::class,
            'list_price' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'final_price' => 'decimal:2',
            'amount_paid' => 'decimal:2',
            'balance_due' => 'decimal:2',
            'is_installment' => 'boolean',
            'confirmed_at' => 'datetime',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function isFullyPaid(): bool
    {
        return Money::fromDecimal($this->balance_due)->isZero();
    }

    /**
     * @return BelongsTo<Vehicle, $this>
     */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    /**
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * @return BelongsTo<Reservation, $this>
     */
    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    /**
     * @return BelongsTo<Consignment, $this>
     */
    public function consignment(): BelongsTo
    {
        return $this->belongsTo(Consignment::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function salesOfficer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sales_officer_id');
    }

    /**
     * @return HasMany<SaleDiscount, $this>
     */
    public function discounts(): HasMany
    {
        return $this->hasMany(SaleDiscount::class)->latest('created_at');
    }

    /**
     * @return HasMany<SaleStatusHistory, $this>
     */
    public function statusHistories(): HasMany
    {
        return $this->hasMany(SaleStatusHistory::class)->latest('created_at');
    }

    /**
     * @return MorphMany<Payment, $this>
     */
    public function payments(): MorphMany
    {
        return $this->morphMany(Payment::class, 'payable')->latest('paid_at');
    }

    /**
     * @return MorphMany<TransactionCharge, $this>
     */
    public function charges(): MorphMany
    {
        return $this->morphMany(TransactionCharge::class, 'chargeable')->latest('created_at');
    }

    /**
     * @return MorphOne<Handover, $this>
     */
    public function handover(): MorphOne
    {
        return $this->morphOne(Handover::class, 'handoverable');
    }

    /**
     * @return HasOne<OwnerSettlement, $this>
     */
    public function ownerSettlement(): HasOne
    {
        return $this->hasOne(OwnerSettlement::class);
    }
}
