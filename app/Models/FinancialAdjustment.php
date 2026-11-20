<?php

namespace App\Models;

use App\Enums\RevenueBucket;
use App\Enums\TransactionDirection;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $reference_number
 * @property RevenueBucket $revenue_bucket
 * @property TransactionDirection $direction
 * @property string $amount
 * @property int|null $vehicle_id
 * @property int|null $financial_account_id
 * @property string $reason
 * @property Carbon $adjusted_at
 * @property int $approved_by
 * @property Carbon|null $created_at
 */
#[Fillable([
    'reference_number',
    'revenue_bucket',
    'direction',
    'amount',
    'vehicle_id',
    'financial_account_id',
    'reason',
    'adjusted_at',
    'approved_by',
    'created_at',
])]
class FinancialAdjustment extends Model
{
    public const UPDATED_AT = null;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'revenue_bucket' => RevenueBucket::class,
            'direction' => TransactionDirection::class,
            'amount' => 'decimal:2',
            'adjusted_at' => 'date',
            'created_at' => 'datetime',
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
     * @return BelongsTo<User, $this>
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
