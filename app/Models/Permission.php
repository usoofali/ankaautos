<?php

namespace App\Models;

use Illuminate\Support\Carbon;
use Spatie\Permission\Models\Permission as SpatiePermission;

/**
 * @property int $id
 * @property string $name
 * @property string|null $display_name
 * @property string|null $description
 * @property string $module_group
 * @property int $sort_order
 * @property string $guard_name
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Permission extends SpatiePermission
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'display_name',
        'description',
        'module_group',
        'sort_order',
        'guard_name',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }
}
