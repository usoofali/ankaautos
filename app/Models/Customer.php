<?php

namespace App\Models;

use App\Enums\OwnerCategory;
use Database\Factories\CustomerFactory;
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
 * @property OwnerCategory $customer_type
 * @property string $name
 * @property string $phone
 * @property string|null $alt_phone
 * @property string|null $email
 * @property string|null $address
 * @property string|null $id_type
 * @property string|null $id_number
 * @property string|null $notes
 * @property int|null $created_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
#[Fillable([
    'reference_number',
    'customer_type',
    'name',
    'phone',
    'alt_phone',
    'email',
    'address',
    'id_type',
    'id_number',
    'notes',
    'created_by',
])]
class Customer extends Model
{
    /** @use HasFactory<CustomerFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'customer_type' => OwnerCategory::class,
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
     * @return HasMany<CustomerInquiry, $this>
     */
    public function inquiries(): HasMany
    {
        return $this->hasMany(CustomerInquiry::class)->latest();
    }

    /**
     * @return HasMany<Reservation, $this>
     */
    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class)->latest('reserved_at');
    }

    /**
     * @return HasMany<Sale, $this>
     */
    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class)->latest('confirmed_at');
    }
}
