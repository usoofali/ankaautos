<?php

namespace App\Models;

use App\Enums\WithdrawalStatus;
use App\Support\Money;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $reference_number
 * @property int $consignment_id
 * @property int $vehicle_id
 * @property int $vehicle_owner_id
 * @property WithdrawalStatus $status
 * @property string $reason
 * @property string $total_owner_expenses
 * @property string $total_withdrawal_charges
 * @property string $total_due
 * @property string $amount_paid
 * @property string $balance_due
 * @property Carbon $requested_at
 * @property Carbon|null $completed_at
 * @property int|null $initiated_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'reference_number',
    'consignment_id',
    'vehicle_id',
    'vehicle_owner_id',
    'status',
    'reason',
    'total_owner_expenses',
    'total_withdrawal_charges',
    'total_due',
    'amount_paid',
    'balance_due',
    'requested_at',
    'completed_at',
    'initiated_by',
])]
class WithdrawalRequest extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => WithdrawalStatus::class,
            'total_owner_expenses' => 'decimal:2',
            'total_withdrawal_charges' => 'decimal:2',
            'total_due' => 'decimal:2',
            'amount_paid' => 'decimal:2',
            'balance_due' => 'decimal:2',
            'requested_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function isClearedForHandover(): bool
    {
        return Money::fromDecimal($this->balance_due)->isZero()
            && $this->status === WithdrawalStatus::PaidReadyForHandover;
    }

    /**
     * @return BelongsTo<Consignment, $this>
     */
    public function consignment(): BelongsTo
    {
        return $this->belongsTo(Consignment::class);
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
     * @return BelongsTo<User, $this>
     */
    public function initiator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'initiated_by');
    }

    /**
     * @return MorphMany<TransactionCharge, $this>
     */
    public function charges(): MorphMany
    {
        return $this->morphMany(TransactionCharge::class, 'chargeable')->latest('created_at');
    }

    /**
     * @return MorphMany<Payment, $this>
     */
    public function payments(): MorphMany
    {
        return $this->morphMany(Payment::class, 'payable')->latest('paid_at');
    }

    /**
     * @return MorphOne<Handover, $this>
     */
    public function handover(): MorphOne
    {
        return $this->morphOne(Handover::class, 'handoverable');
    }
}
