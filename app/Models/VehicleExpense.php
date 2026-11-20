<?php

namespace App\Models;

use App\Enums\ExpenseBearer;
use App\Enums\ExpensePaymentStatus;
use App\Support\Money;
use Database\Factories\VehicleExpenseFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $reference_number
 * @property int $vehicle_id
 * @property int|null $consignment_id
 * @property int $expense_category_id
 * @property ExpenseBearer $borne_by
 * @property string $description
 * @property string $amount
 * @property string $amount_paid
 * @property ExpensePaymentStatus $payment_status
 * @property Carbon $expense_date
 * @property string|null $vendor_name
 * @property string|null $receipt_path
 * @property string|null $receipt_original_name
 * @property int|null $settled_in_owner_settlement_id
 * @property int|null $settled_in_withdrawal_id
 * @property int|null $recorded_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
#[Fillable([
    'reference_number',
    'vehicle_id',
    'consignment_id',
    'expense_category_id',
    'borne_by',
    'description',
    'amount',
    'amount_paid',
    'payment_status',
    'expense_date',
    'vendor_name',
    'receipt_path',
    'receipt_original_name',
    'settled_in_owner_settlement_id',
    'settled_in_withdrawal_id',
    'recorded_by',
])]
class VehicleExpense extends Model
{
    /** @use HasFactory<VehicleExpenseFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'borne_by' => ExpenseBearer::class,
            'payment_status' => ExpensePaymentStatus::class,
            'amount' => 'decimal:2',
            'amount_paid' => 'decimal:2',
            'expense_date' => 'date',
        ];
    }

    public function balanceDue(): Money
    {
        return Money::fromDecimal($this->amount)->subtract(Money::fromDecimal($this->amount_paid))->max(Money::zero());
    }

    public function isDeductedFromOwner(): bool
    {
        return $this->settled_in_owner_settlement_id !== null || $this->settled_in_withdrawal_id !== null;
    }

    /**
     * @return BelongsTo<Vehicle, $this>
     */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    /**
     * @return BelongsTo<Consignment, $this>
     */
    public function consignment(): BelongsTo
    {
        return $this->belongsTo(Consignment::class);
    }

    /**
     * @return BelongsTo<ExpenseCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class, 'expense_category_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    /**
     * @return HasMany<ExpensePayment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(ExpensePayment::class)->latest('paid_at');
    }
}
