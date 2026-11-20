<?php

namespace App\Models;

use Illuminate\Support\Carbon;
use Spatie\Permission\Models\Role as SpatieRole;

/**
 * @property int $id
 * @property string $name
 * @property string|null $display_name
 * @property string|null $description
 * @property string|null $purpose
 * @property string|null $responsibilities_summary
 * @property string $guard_name
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Role extends SpatieRole
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'display_name',
        'description',
        'purpose',
        'responsibilities_summary',
        'guard_name',
    ];
}
