<?php

namespace App\Models;

use App\Enums\SettlementStatus;
use App\Support\Money;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $reference_number
 * @property int $consignment_id
 * @property int $sale_id
 * @property int $vehicle_id
 * @property int $vehicle_owner_id
 * @property string $gross_sale_price
 * @property string $commission_amount
 * @property string $total_expense_deductions
 * @property string $total_charge_deductions
 * @property string $net_payable_to_owner
 * @property string $amount_paid
 * @property string $balance_due
 * @property SettlementStatus $status
 * @property Carbon|null $settled_at
 * @property string|null $notes
 * @property int|null $created_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'reference_number',
    'consignment_id',
    'sale_id',
    'vehicle_id',
    'vehicle_owner_id',
    'gross_sale_price',
    'commission_amount',
    'total_expense_deductions',
    'total_charge_deductions',
    'net_payable_to_owner',
    'amount_paid',
    'balance_due',
    'status',
    'settled_at',
    'notes',
    'created_by',
])]
class OwnerSettlement extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => SettlementStatus::class,
            'gross_sale_price' => 'decimal:2',
            'commission_amount' => 'decimal:2',
            'total_expense_deductions' => 'decimal:2',
            'total_charge_deductions' => 'decimal:2',
            'net_payable_to_owner' => 'decimal:2',
            'amount_paid' => 'decimal:2',
            'balance_due' => 'decimal:2',
            'settled_at' => 'datetime',
        ];
    }

    public function hasAnyDisbursement(): bool
    {
        return Money::fromDecimal($this->amount_paid)->isPositive();
    }

    /**
     * @return BelongsTo<Consignment, $this>
     */
    public function consignment(): BelongsTo
    {
        return $this->belongsTo(Consignment::class);
    }

    /**
     * @return BelongsTo<Sale, $this>
     */
    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    /**
     * @return BelongsTo<Vehicle, $this>
     */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    /**
     * @return BelongsTo<VehicleOwner, $this>
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(VehicleOwner::class, 'vehicle_owner_id');
    }

    /**
     * @return HasMany<OwnerSettlementItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(OwnerSettlementItem::class);
    }

    /**
     * @return HasMany<OwnerPayment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(OwnerPayment::class)->latest('paid_at');
    }

    /**
     * @return MorphMany<TransactionCharge, $this>
     */
    public function charges(): MorphMany
    {
        return $this->morphMany(TransactionCharge::class, 'chargeable')->latest('created_at');
    }
}
