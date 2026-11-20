<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $handover_id
 * @property string $item_name
 * @property bool $is_included
 * @property string|null $remarks
 */
#[Fillable(['handover_id', 'item_name', 'is_included', 'remarks'])]
class HandoverItem extends Model
{
    public $timestamps = false;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_included' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Handover, $this>
     */
    public function handover(): BelongsTo
    {
        return $this->belongsTo(Handover::class);
    }
}
