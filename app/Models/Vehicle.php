<?php

namespace App\Models;

use App\Enums\DriveType;
use App\Enums\FuelType;
use App\Enums\OwnershipType;
use App\Enums\SteeringPosition;
use App\Enums\TransmissionType;
use App\Enums\VehicleStatus;
use Database\Factories\VehicleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $reference_number
 * @property string $slug
 * @property OwnershipType $ownership_type
 * @property int $vehicle_brand_id
 * @property int $vehicle_model_id
 * @property int $vehicle_category_id
 * @property int $vehicle_condition_id
 * @property int $year
 * @property string|null $vin
 * @property string|null $chassis_number
 * @property string|null $engine_number
 * @property string|null $registration_number
 * @property TransmissionType $transmission
 * @property FuelType $fuel_type
 * @property DriveType|null $drive_type
 * @property string $exterior_colour
 * @property string|null $interior_colour
 * @property int|null $mileage
 * @property SteeringPosition $steering_position
 * @property int|null $doors
 * @property VehicleStatus $status
 * @property string|null $location
 * @property Carbon $received_at
 * @property Carbon|null $registered_at
 * @property int|null $received_by
 * @property string|null $acquisition_source
 * @property string|null $acquisition_cost
 * @property string|null $acquisition_reference
 * @property string|null $acquired_from
 * @property string|null $advertised_price
 * @property string|null $minimum_price
 * @property string|null $general_description
 * @property string|null $condition_remarks
 * @property string|null $mechanical_issues
 * @property string|null $bodywork_condition
 * @property string|null $accessories
 * @property string|null $internal_notes
 * @property bool $is_public_listing
 * @property string|null $duplicate_vin_override_reason
 * @property int|null $duplicate_vin_overridden_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
#[Fillable([
    'reference_number',
    'slug',
    'ownership_type',
    'vehicle_brand_id',
    'vehicle_model_id',
    'vehicle_category_id',
    'vehicle_condition_id',
    'year',
    'vin',
    'chassis_number',
    'engine_number',
    'registration_number',
    'transmission',
    'fuel_type',
    'drive_type',
    'exterior_colour',
    'interior_colour',
    'mileage',
    'steering_position',
    'doors',
    'status',
    'location',
    'received_at',
    'registered_at',
    'received_by',
    'acquisition_source',
    'acquisition_cost',
    'acquisition_reference',
    'acquired_from',
    'advertised_price',
    'minimum_price',
    'general_description',
    'condition_remarks',
    'mechanical_issues',
    'bodywork_condition',
    'accessories',
    'internal_notes',
    'is_public_listing',
    'duplicate_vin_override_reason',
    'duplicate_vin_overridden_by',
])]
class Vehicle extends Model
{
    /** @use HasFactory<VehicleFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'ownership_type' => OwnershipType::class,
            'transmission' => TransmissionType::class,
            'fuel_type' => FuelType::class,
            'drive_type' => DriveType::class,
            'steering_position' => SteeringPosition::class,
            'status' => VehicleStatus::class,
            'year' => 'integer',
            'mileage' => 'integer',
            'doors' => 'integer',
            'received_at' => 'date',
            'registered_at' => 'datetime',
            'acquisition_cost' => 'decimal:2',
            'advertised_price' => 'decimal:2',
            'minimum_price' => 'decimal:2',
            'is_public_listing' => 'boolean',
        ];
    }

    public function displayName(): string
    {
        $brand = $this->relationLoaded('brand') && $this->brand !== null ? $this->brand->name : '';
        $model = $this->relationLoaded('model') && $this->model !== null ? $this->model->name : '';

        return trim("{$this->year} {$brand} {$model}");
    }

    /**
     * @return BelongsTo<VehicleBrand, $this>
     */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(VehicleBrand::class, 'vehicle_brand_id');
    }

    /**
     * @return BelongsTo<VehicleModel, $this>
     */
    public function model(): BelongsTo
    {
        return $this->belongsTo(VehicleModel::class, 'vehicle_model_id');
    }

    /**
     * @return BelongsTo<VehicleCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(VehicleCategory::class, 'vehicle_category_id');
    }

    /**
     * @return BelongsTo<VehicleCondition, $this>
     */
    public function condition(): BelongsTo
    {
        return $this->belongsTo(VehicleCondition::class, 'vehicle_condition_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function duplicateVinOverrider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'duplicate_vin_overridden_by');
    }

    /**
     * @return HasMany<VehiclePhoto, $this>
     */
    public function photos(): HasMany
    {
        return $this->hasMany(VehiclePhoto::class)->orderByDesc('is_primary')->orderBy('sort_order')->orderBy('id');
    }

    /**
     * @return HasOne<VehiclePhoto, $this>
     */
    public function primaryPhoto(): HasOne
    {
        return $this->hasOne(VehiclePhoto::class)->orderByDesc('is_primary')->orderBy('sort_order')->orderBy('id');
    }

    /**
     * @return HasMany<VehicleStatusHistory, $this>
     */
    public function statusHistories(): HasMany
    {
        return $this->hasMany(VehicleStatusHistory::class)->latest('created_at');
    }

    /**
     * @return HasMany<VehicleConditionHistory, $this>
     */
    public function conditionHistories(): HasMany
    {
        return $this->hasMany(VehicleConditionHistory::class)->latest('created_at');
    }

    /**
     * @return HasMany<VehicleAttendantLog, $this>
     */
    public function attendantLogs(): HasMany
    {
        return $this->hasMany(VehicleAttendantLog::class)->latest('logged_at');
    }

    /**
     * @return HasMany<Consignment, $this>
     */
    public function consignments(): HasMany
    {
        return $this->hasMany(Consignment::class)->latest('id');
    }

    /**
     * @return HasOne<Consignment, $this>
     */
    public function activeConsignment(): HasOne
    {
        return $this->hasOne(Consignment::class)->latestOfMany();
    }

    /**
     * @return HasMany<VehicleExpense, $this>
     */
    public function expenses(): HasMany
    {
        return $this->hasMany(VehicleExpense::class)->latest('expense_date');
    }

    /**
     * @return MorphMany<TransactionCharge, $this>
     */
    public function charges(): MorphMany
    {
        return $this->morphMany(TransactionCharge::class, 'chargeable')->latest('created_at');
    }
}
