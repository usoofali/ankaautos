<?php

namespace App\Models;

use App\Enums\OwnerCategory;
use Database\Factories\VehicleOwnerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $reference_number
 * @property OwnerCategory $owner_category
 * @property string $name
 * @property string $phone
 * @property string|null $alt_phone
 * @property string|null $email
 * @property string|null $address
 * @property string|null $id_type
 * @property string|null $id_number
 * @property string|null $bank_name
 * @property string|null $bank_account_name
 * @property string|null $bank_account_number
 * @property string|null $remarks
 * @property bool $is_active
 * @property int|null $created_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
#[Fillable([
    'reference_number',
    'owner_category',
    'name',
    'phone',
    'alt_phone',
    'email',
    'address',
    'id_type',
    'id_number',
    'bank_name',
    'bank_account_name',
    'bank_account_number',
    'remarks',
    'is_active',
    'created_by',
])]
class VehicleOwner extends Model
{
    /** @use HasFactory<VehicleOwnerFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'owner_category' => OwnerCategory::class,
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return HasMany<Consignment, $this>
     */
    public function consignments(): HasMany
    {
        return $this->hasMany(Consignment::class);
    }
}
