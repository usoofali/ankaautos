<?php

namespace App\Models;

use App\Enums\ReservationStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $reference_number
 * @property int $vehicle_id
 * @property int $customer_id
 * @property string $agreed_price
 * @property string $deposit_amount
 * @property ReservationStatus $status
 * @property Carbon $reserved_at
 * @property string|null $notes
 * @property string|null $cancellation_reason
 * @property string|null $deposit_disposition
 * @property int|null $conflicting_override_by
 * @property int|null $created_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'reference_number',
    'vehicle_id',
    'customer_id',
    'agreed_price',
    'deposit_amount',
    'status',
    'reserved_at',
    'notes',
    'cancellation_reason',
    'deposit_disposition',
    'conflicting_override_by',
    'created_by',
])]
class Reservation extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ReservationStatus::class,
            'agreed_price' => 'decimal:2',
            'deposit_amount' => 'decimal:2',
            'reserved_at' => 'datetime',
        ];
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
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return HasOne<Sale, $this>
     */
    public function sale(): HasOne
    {
        return $this->hasOne(Sale::class);
    }

    /**
     * @return MorphMany<Payment, $this>
     */
    public function payments(): MorphMany
    {
        return $this->morphMany(Payment::class, 'payable')->latest('paid_at');
    }
}
