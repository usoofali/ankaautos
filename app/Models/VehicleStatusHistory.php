<?php

namespace App\Models;

use App\Enums\VehicleStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $vehicle_id
 * @property VehicleStatus|null $from_status
 * @property VehicleStatus $to_status
 * @property string|null $reason
 * @property int|null $changed_by
 * @property Carbon|null $created_at
 */
#[Fillable(['vehicle_id', 'from_status', 'to_status', 'reason', 'changed_by', 'created_at'])]
class VehicleStatusHistory extends Model
{
    public const UPDATED_AT = null;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'from_status' => VehicleStatus::class,
            'to_status' => VehicleStatus::class,
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
    public function changer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
