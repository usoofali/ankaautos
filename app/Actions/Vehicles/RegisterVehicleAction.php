<?php

namespace App\Actions\Vehicles;

use App\Enums\OwnershipType;
use App\Enums\VehicleStatus;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleBrand;
use App\Models\VehicleModel;
use App\Services\AuditLogger;
use App\Services\ReferenceNumberGenerator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class RegisterVehicleAction
{
    public function __construct(
        private ReferenceNumberGenerator $referenceGenerator,
        private AuditLogger $auditLogger,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(array $data, User $actor): Vehicle
    {
        $vin = isset($data['vin']) && trim((string) $data['vin']) !== ''
            ? strtoupper(trim((string) $data['vin']))
            : null;

        $chassis = isset($data['chassis_number']) && trim((string) $data['chassis_number']) !== ''
            ? strtoupper(trim((string) $data['chassis_number']))
            : null;

        $overrideReason = isset($data['duplicate_vin_override_reason']) && trim((string) $data['duplicate_vin_override_reason']) !== ''
            ? trim((string) $data['duplicate_vin_override_reason'])
            : null;

        $overrideApplied = false;

        if ($vin !== null || $chassis !== null) {
            $matches = Vehicle::query()
                ->where(function ($query) use ($vin, $chassis): void {
                    if ($vin !== null) {
                        $query->orWhere('vin', $vin);
                    }
                    if ($chassis !== null) {
                        $query->orWhere('chassis_number', $chassis);
                    }
                })
                ->get();

            if ($matches->isNotEmpty()) {
                $activeDuplicate = $matches->first(
                    static fn (Vehicle $existing): bool => $existing->status->isActiveInventory(),
                );

                if ($activeDuplicate !== null) {
                    throw ValidationException::withMessages([
                        'vin' => "A vehicle with this VIN/chassis is already active in inventory ({$activeDuplicate->reference_number}). Duplicate active registrations are not permitted.",
                    ]);
                }

                // Only Sold/Withdrawn/Archived historical matches exist: returning vehicle requires Manager/Super Admin override + reason.
                if (! $actor->can('vehicles.override_duplicate_vin')) {
                    throw ValidationException::withMessages([
                        'vin' => 'This VIN/chassis belongs to a previously sold or withdrawn vehicle. A Manager or Super Administrator must approve re-registering it.',
                    ]);
                }

                if ($overrideReason === null) {
                    throw ValidationException::withMessages([
                        'duplicate_vin_override_reason' => 'This VIN/chassis previously existed in inventory. Please provide an override reason confirming this returning vehicle.',
                    ]);
                }

                $overrideApplied = true;
            }
        }

        return DB::transaction(function () use ($data, $actor, $vin, $chassis, $overrideApplied, $overrideReason): Vehicle {
            $reference = $this->referenceGenerator->generate('VEH');

            /** @var VehicleBrand $brand */
            $brand = VehicleBrand::query()->findOrFail($data['vehicle_brand_id']);
            /** @var VehicleModel $model */
            $model = VehicleModel::query()->findOrFail($data['vehicle_model_id']);

            $year = (int) $data['year'];
            $slug = Str::slug("{$year}-{$brand->name}-{$model->name}-{$reference}");

            $ownershipType = OwnershipType::from((string) $data['ownership_type']);
            $status = isset($data['status'])
                ? VehicleStatus::from((string) $data['status'])
                : VehicleStatus::Available;

            /** @var Vehicle $vehicle */
            $vehicle = Vehicle::query()->create([
                'reference_number' => $reference,
                'slug' => $slug,
                'ownership_type' => $ownershipType,
                'vehicle_brand_id' => $brand->id,
                'vehicle_model_id' => $model->id,
                'vehicle_category_id' => (int) $data['vehicle_category_id'],
                'vehicle_condition_id' => (int) $data['vehicle_condition_id'],
                'year' => $year,
                'vin' => $vin,
                'chassis_number' => $chassis,
                'engine_number' => $data['engine_number'] ?? null,
                'registration_number' => isset($data['registration_number']) && trim((string) $data['registration_number']) !== ''
                    ? strtoupper(trim((string) $data['registration_number']))
                    : null,
                'transmission' => $data['transmission'],
                'fuel_type' => $data['fuel_type'],
                'drive_type' => $data['drive_type'] ?? null,
                'exterior_colour' => $data['exterior_colour'],
                'interior_colour' => $data['interior_colour'] ?? null,
                'mileage' => isset($data['mileage']) && $data['mileage'] !== '' ? (int) $data['mileage'] : null,
                'steering_position' => $data['steering_position'] ?? 'left_hand',
                'doors' => isset($data['doors']) && $data['doors'] !== '' ? (int) $data['doors'] : null,
                'status' => $status,
                'location' => $data['location'] ?? null,
                'received_at' => $data['received_at'],
                'registered_at' => now(),
                'received_by' => $actor->id,
                'acquisition_source' => $ownershipType === OwnershipType::CompanyOwned ? ($data['acquisition_source'] ?? null) : null,
                'acquisition_cost' => $ownershipType === OwnershipType::CompanyOwned && isset($data['acquisition_cost']) && $data['acquisition_cost'] !== ''
                    ? $data['acquisition_cost']
                    : null,
                'acquisition_reference' => $ownershipType === OwnershipType::CompanyOwned ? ($data['acquisition_reference'] ?? null) : null,
                'acquired_from' => $ownershipType === OwnershipType::CompanyOwned ? ($data['acquired_from'] ?? null) : null,
                'advertised_price' => isset($data['advertised_price']) && $data['advertised_price'] !== '' ? $data['advertised_price'] : null,
                'minimum_price' => isset($data['minimum_price']) && $data['minimum_price'] !== '' ? $data['minimum_price'] : null,
                'general_description' => $data['general_description'] ?? null,
                'condition_remarks' => $data['condition_remarks'] ?? null,
                'mechanical_issues' => $data['mechanical_issues'] ?? null,
                'bodywork_condition' => $data['bodywork_condition'] ?? null,
                'accessories' => $data['accessories'] ?? null,
                'internal_notes' => $data['internal_notes'] ?? null,
                'is_public_listing' => (bool) ($data['is_public_listing'] ?? true),
                'duplicate_vin_override_reason' => $overrideApplied ? $overrideReason : null,
                'duplicate_vin_overridden_by' => $overrideApplied ? $actor->id : null,
            ]);

            $vehicle->statusHistories()->create([
                'from_status' => null,
                'to_status' => $status,
                'reason' => $overrideApplied ? "Initial registration (Duplicate VIN override: {$overrideReason})" : 'Initial vehicle registration.',
                'changed_by' => $actor->id,
                'created_at' => now(),
            ]);

            $vehicle->conditionHistories()->create([
                'from_condition_id' => null,
                'to_condition_id' => $vehicle->vehicle_condition_id,
                'defects_noted' => $vehicle->mechanical_issues ?: $vehicle->condition_remarks,
                'changed_by' => $actor->id,
                'created_at' => now(),
            ]);

            $this->auditLogger->audit(
                event: $overrideApplied ? 'vehicle.registered_with_vin_override' : 'vehicle.registered',
                auditable: $vehicle,
                newValues: $vehicle->only([
                    'reference_number',
                    'ownership_type',
                    'year',
                    'vin',
                    'chassis_number',
                    'status',
                    'acquisition_cost',
                    'advertised_price',
                ]),
                reason: $overrideApplied ? $overrideReason : null,
                actor: $actor,
            );

            $this->auditLogger->activity(
                action: 'vehicle.registered',
                description: "Registered {$year} {$brand->name} {$model->name} ({$reference}) as {$ownershipType->label()}.",
                subject: $vehicle,
                actor: $actor,
            );

            return $vehicle;
        });
    }
}
