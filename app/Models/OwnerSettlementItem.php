<?php

namespace App\Models;

use App\Enums\SettlementItemType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $owner_settlement_id
 * @property SettlementItemType $item_type
 * @property string $label
 * @property string $amount
 * @property string|null $source_type
 * @property int|null $source_id
 */
#[Fillable(['owner_settlement_id', 'item_type', 'label', 'amount', 'source_type', 'source_id'])]
class OwnerSettlementItem extends Model
{
    public $timestamps = false;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'item_type' => SettlementItemType::class,
            'amount' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<OwnerSettlement, $this>
     */
    public function settlement(): BelongsTo
    {
        return $this->belongsTo(OwnerSettlement::class, 'owner_settlement_id');
    }
}
