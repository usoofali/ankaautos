<?php

namespace App\Models;

use App\Enums\AttendantActivityType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $vehicle_id
 * @property AttendantActivityType $activity_type
 * @property string $description
 * @property string|null $location_from
 * @property string|null $location_to
 * @property int|null $logged_by
 * @property Carbon $logged_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['vehicle_id', 'activity_type', 'description', 'location_from', 'location_to', 'logged_by', 'logged_at'])]
class VehicleAttendantLog extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'activity_type' => AttendantActivityType::class,
            'logged_at' => 'datetime',
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
    public function logger(): BelongsTo
    {
        return $this->belongsTo(User::class, 'logged_by');
    }
}
