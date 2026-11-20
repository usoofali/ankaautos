<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $reference_number
 * @property string $handoverable_type
 * @property int $handoverable_id
 * @property int $vehicle_id
 * @property string $recipient_name
 * @property string $recipient_phone
 * @property string|null $recipient_id_number
 * @property int|null $odometer_reading
 * @property string|null $fuel_level
 * @property string|null $condition_summary
 * @property Carbon $handed_over_at
 * @property int|null $handed_over_by
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'reference_number',
    'handoverable_type',
    'handoverable_id',
    'vehicle_id',
    'recipient_name',
    'recipient_phone',
    'recipient_id_number',
    'odometer_reading',
    'fuel_level',
    'condition_summary',
    'handed_over_at',
    'handed_over_by',
    'notes',
])]
class Handover extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'odometer_reading' => 'integer',
            'handed_over_at' => 'datetime',
        ];
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function handoverable(): MorphTo
    {
        return $this->morphTo();
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
    public function officer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handed_over_by');
    }

    /**
     * @return HasMany<HandoverItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(HandoverItem::class);
    }
}
