<?php

namespace App\Models;

use App\Enums\PhotoCategory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * @property int $id
 * @property int $vehicle_id
 * @property string $disk
 * @property string $path
 * @property string $original_name
 * @property string|null $caption
 * @property PhotoCategory $category
 * @property bool $is_primary
 * @property int $sort_order
 * @property int|null $uploaded_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['vehicle_id', 'disk', 'path', 'original_name', 'caption', 'category', 'is_primary', 'sort_order', 'uploaded_by'])]
class VehiclePhoto extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'category' => PhotoCategory::class,
            'is_primary' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function url(): string
    {
        return Storage::disk($this->disk)->url($this->path);
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
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
