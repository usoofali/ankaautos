<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $sale_id
 * @property string $previous_price
 * @property string $discount_amount
 * @property string $new_price
 * @property string $reason
 * @property int $approved_by
 * @property Carbon|null $created_at
 */
#[Fillable(['sale_id', 'previous_price', 'discount_amount', 'new_price', 'reason', 'approved_by', 'created_at'])]
class SaleDiscount extends Model
{
    public const UPDATED_AT = null;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'previous_price' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'new_price' => 'decimal:2',
            'created_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Sale, $this>
     */
    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
