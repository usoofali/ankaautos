<?php

namespace App\Models;

use App\Enums\CommissionType;
use App\Enums\ConsignmentStatus;
use Database\Factories\ConsignmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $reference_number
 * @property int $vehicle_id
 * @property int $vehicle_owner_id
 * @property ConsignmentStatus $status
 * @property Carbon $received_at
 * @property Carbon|null $expected_review_date
 * @property string $agreed_selling_price
 * @property string|null $minimum_acceptable_price
 * @property CommissionType $commission_type
 * @property string $commission_value
 * @property string|null $commission_notes
 * @property string|null $expense_responsibility_notes
 * @property string|null $withdrawal_terms
 * @property string|null $settlement_terms
 * @property string|null $remarks
 * @property int|null $received_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
#[Fillable([
    'reference_number',
    'vehicle_id',
    'vehicle_owner_id',
    'status',
    'received_at',
    'expected_review_date',
    'agreed_selling_price',
    'minimum_acceptable_price',
    'commission_type',
    'commission_value',
    'commission_notes',
    'expense_responsibility_notes',
    'withdrawal_terms',
    'settlement_terms',
    'remarks',
    'received_by',
])]
class Consignment extends Model
{
    /** @use HasFactory<ConsignmentFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ConsignmentStatus::class,
            'commission_type' => CommissionType::class,
            'received_at' => 'date',
            'expected_review_date' => 'date',
            'agreed_selling_price' => 'decimal:2',
            'minimum_acceptable_price' => 'decimal:2',
            'commission_value' => 'decimal:2',
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
     * @return BelongsTo<VehicleOwner, $this>
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(VehicleOwner::class, 'vehicle_owner_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    /**
     * @return HasOne<ConsignmentAgreement, $this>
     */
    public function agreement(): HasOne
    {
        return $this->hasOne(ConsignmentAgreement::class);
    }

    /**
     * @return HasMany<ConsignmentStatusHistory, $this>
     */
    public function statusHistories(): HasMany
    {
        return $this->hasMany(ConsignmentStatusHistory::class)->latest('created_at');
    }

    /**
     * @return HasMany<VehicleExpense, $this>
     */
    public function expenses(): HasMany
    {
        return $this->hasMany(VehicleExpense::class)->latest('expense_date');
    }
}
