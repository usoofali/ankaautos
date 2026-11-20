<?php

namespace App\Models;

use App\Enums\FinancialTransactionType;
use App\Enums\RevenueBucket;
use App\Enums\TransactionDirection;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;
use LogicException;

/**
 * Append-only financial ledger entry separating company revenue from third-party owner funds (SSD 18.4 & 18.8).
 *
 * @property int $id
 * @property string $reference_number
 * @property FinancialTransactionType $transaction_type
 * @property TransactionDirection $direction
 * @property RevenueBucket $revenue_bucket
 * @property string $amount
 * @property int|null $financial_account_id
 * @property int|null $payment_method_id
 * @property int|null $vehicle_id
 * @property string|null $source_type
 * @property int|null $source_id
 * @property string $description
 * @property Carbon $transacted_at
 * @property int|null $recorded_by
 * @property Carbon|null $created_at
 */
#[Fillable([
    'reference_number',
    'transaction_type',
    'direction',
    'revenue_bucket',
    'amount',
    'financial_account_id',
    'payment_method_id',
    'vehicle_id',
    'source_type',
    'source_id',
    'description',
    'transacted_at',
    'recorded_by',
    'created_at',
])]
class FinancialTransaction extends Model
{
    public const UPDATED_AT = null;

    protected static function booted(): void
    {
        static::updating(static function (): never {
            throw new LogicException('Financial transactions are immutable. Record a refund or financial adjustment instead.');
        });

        static::deleting(static function (): never {
            throw new LogicException('Financial transactions cannot be deleted.');
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'transaction_type' => FinancialTransactionType::class,
            'direction' => TransactionDirection::class,
            'revenue_bucket' => RevenueBucket::class,
            'amount' => 'decimal:2',
            'transacted_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<FinancialAccount, $this>
     */
    public function financialAccount(): BelongsTo
    {
        return $this->belongsTo(FinancialAccount::class);
    }

    /**
     * @return BelongsTo<PaymentMethod, $this>
     */
    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
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
    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function source(): MorphTo
    {
        return $this->morphTo();
    }
}
