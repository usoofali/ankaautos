<?php

namespace App\Models;

use App\Enums\ConsignmentStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $consignment_id
 * @property ConsignmentStatus|null $from_status
 * @property ConsignmentStatus $to_status
 * @property string|null $reason
 * @property int|null $changed_by
 * @property Carbon|null $created_at
 */
#[Fillable(['consignment_id', 'from_status', 'to_status', 'reason', 'changed_by', 'created_at'])]
class ConsignmentStatusHistory extends Model
{
    public const UPDATED_AT = null;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'from_status' => ConsignmentStatus::class,
            'to_status' => ConsignmentStatus::class,
            'created_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Consignment, $this>
     */
    public function consignment(): BelongsTo
    {
        return $this->belongsTo(Consignment::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function changer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
