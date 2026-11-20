<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $vehicle_id
 * @property int|null $from_condition_id
 * @property int $to_condition_id
 * @property string|null $defects_noted
 * @property int|null $changed_by
 * @property Carbon|null $created_at
 */
#[Fillable(['vehicle_id', 'from_condition_id', 'to_condition_id', 'defects_noted', 'changed_by', 'created_at'])]
class VehicleConditionHistory extends Model
{
    public const UPDATED_AT = null;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
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
     * @return BelongsTo<VehicleCondition, $this>
     */
    public function fromCondition(): BelongsTo
    {
        return $this->belongsTo(VehicleCondition::class, 'from_condition_id');
    }

    /**
     * @return BelongsTo<VehicleCondition, $this>
     */
    public function toCondition(): BelongsTo
    {
        return $this->belongsTo(VehicleCondition::class, 'to_condition_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function changer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
