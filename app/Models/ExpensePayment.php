<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $reference_number
 * @property int $vehicle_expense_id
 * @property int $payment_method_id
 * @property int $financial_account_id
 * @property string $amount
 * @property Carbon $paid_at
 * @property string|null $payment_reference
 * @property string|null $notes
 * @property int|null $recorded_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'reference_number',
    'vehicle_expense_id',
    'payment_method_id',
    'financial_account_id',
    'amount',
    'paid_at',
    'payment_reference',
    'notes',
    'recorded_by',
])]
class ExpensePayment extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'paid_at' => 'date',
        ];
    }

    /**
     * @return BelongsTo<VehicleExpense, $this>
     */
    public function expense(): BelongsTo
    {
        return $this->belongsTo(VehicleExpense::class, 'vehicle_expense_id');
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
