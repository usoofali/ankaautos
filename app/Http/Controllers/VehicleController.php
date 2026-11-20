<?php

namespace App\Http\Controllers;

use App\Actions\Vehicles\RegisterVehicleAction;
use App\Actions\Vehicles\TransitionVehicleStatusAction;
use App\Enums\AttendantActivityType;
use App\Enums\DriveType;
use App\Enums\FuelType;
use App\Enums\OwnershipType;
use App\Enums\PhotoCategory;
use App\Enums\SteeringPosition;
use App\Enums\TransmissionType;
use App\Enums\VehicleStatus;
use App\Http\Requests\Vehicles\StoreAttendantLogRequest;
use App\Http\Requests\Vehicles\StoreVehicleRequest;
use App\Http\Requests\Vehicles\UpdateVehicleRequest;
use App\Http\Requests\Vehicles\UploadVehiclePhotosRequest;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleBrand;
use App\Models\VehicleCategory;
use App\Models\VehicleCondition;
use App\Models\VehicleModel;
use App\Models\VehiclePhoto;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class VehicleController extends Controller
{
    public function __construct(
        private RegisterVehicleAction $registerVehicle,
        private TransitionVehicleStatusAction $transitionStatus,
        private AuditLogger $auditLogger,
    ) {}

    public function index(Request $request): Response
    {
        $search = trim((string) $request->string('search'));
        $ownership = trim((string) $request->string('ownership'));
        $status = trim((string) $request->string('status'));
        $conditionId = $request->integer('condition_id') ?: null;
        $perPage = in_array($request->integer('per_page', 15), [10, 15, 25, 50], true)
            ? $request->integer('per_page', 15)
            : 15;
        $sort = in_array($request->string('sort')->toString(), ['reference_number', 'year', 'advertised_price', 'received_at', 'status'], true)
            ? $request->string('sort')->toString()
            : 'received_at';
        $direction = $request->string('direction')->toString() === 'asc' ? 'asc' : 'desc';

        $vehicles = Vehicle::query()
            ->with(['brand:id,name', 'model:id,name', 'category:id,name', 'condition:id,name,badge_variant', 'primaryPhoto', 'activeConsignment.owner:id,name'])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($sub) use ($search): void {
                    $sub->where('reference_number', 'like', "%{$search}%")
                        ->orWhere('vin', 'like', "%{$search}%")
                        ->orWhere('chassis_number', 'like', "%{$search}%")
                        ->orWhere('registration_number', 'like', "%{$search}%")
                        ->orWhere('year', 'like', "%{$search}%")
                        ->orWhereHas('brand', fn ($q) => $q->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('model', fn ($q) => $q->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('activeConsignment.owner', fn ($q) => $q->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($ownership !== '', fn ($q) => $q->where('ownership_type', $ownership))
            ->when($status !== '', fn ($q) => $q->where('status', $status))
            ->when($conditionId !== null, fn ($q) => $q->where('vehicle_condition_id', $conditionId))
            ->orderBy($sort, $direction)
            ->paginate($perPage)
            ->withQueryString()
            ->through(static fn (Vehicle $vehicle): array => [
                'id' => $vehicle->id,
                'reference_number' => $vehicle->reference_number,
                'title' => $vehicle->displayName(),
                'year' => $vehicle->year,
                'exterior_colour' => $vehicle->exterior_colour,
                'ownership_type' => $vehicle->ownership_type->value,
                'ownership_label' => $vehicle->ownership_type->label(),
                'owner_name' => $vehicle->activeConsignment?->owner?->name,
                'status' => $vehicle->status->value,
                'status_label' => $vehicle->status->label(),
                'status_variant' => $vehicle->status->badgeVariant(),
                'condition_name' => $vehicle->condition->name ?? '—',
                'condition_variant' => $vehicle->condition->badge_variant ?? 'neutral',
                'category_name' => $vehicle->category->name ?? '—',
                'advertised_price' => $vehicle->advertised_price,
                'location' => $vehicle->location,
                'received_at' => $vehicle->received_at->toDateString(),
                'primary_photo_url' => $vehicle->primaryPhoto?->url(),
            ]);

        return Inertia::render('vehicles/Index', [
            'vehicles' => $vehicles,
            'ownershipOptions' => OwnershipType::options(),
            'statusOptions' => VehicleStatus::options(),
            'conditions' => VehicleCondition::query()->active()->orderBy('severity_order')->get(['id', 'name']),
            'summary' => [
                'total' => Vehicle::query()->count(),
                'available' => Vehicle::query()->where('status', VehicleStatus::Available->value)->count(),
                'company_owned' => Vehicle::query()->where('ownership_type', OwnershipType::CompanyOwned->value)->count(),
                'consignment' => Vehicle::query()->where('ownership_type', OwnershipType::Consignment->value)->count(),
            ],
            'filters' => [
                'search' => $search,
                'ownership' => $ownership,
                'status' => $status,
                'condition_id' => $conditionId ? (string) $conditionId : '',
                'sort' => $sort,
                'direction' => $direction,
                'per_page' => $perPage,
            ],
        ]);
    }

    public function create(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();

        return Inertia::render('vehicles/Create', [
            ...$this->formLookups(),
            'canOverrideDuplicateVin' => $user->can('vehicles.override_duplicate_vin'),
        ]);
    }

    public function store(StoreVehicleRequest $request): RedirectResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $vehicle = $this->registerVehicle->execute($request->validated(), $actor);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "Vehicle {$vehicle->reference_number} registered successfully.",
        ]);

        return redirect()->route('vehicles.show', $vehicle);
    }

    public function show(Request $request, Vehicle $vehicle): Response
    {
        /** @var User $user */
        $user = $request->user();

        $vehicle->load([
            'brand:id,name',
            'model:id,name',
            'category:id,name',
            'condition:id,name,badge_variant',
            'receiver:id,name',
            'duplicateVinOverrider:id,name',
            'photos.uploader:id,name',
            'statusHistories.changer:id,name',
            'conditionHistories.fromCondition:id,name',
            'conditionHistories.toCondition:id,name',
            'conditionHistories.changer:id,name',
            'attendantLogs.logger:id,name',
            'activeConsignment.owner:id,reference_number,name,phone',
        ]);

        $canSeeAcquisitionCost = $user->can('dashboard.view_financials') || $user->can('vehicles.edit');

        return Inertia::render('vehicles/Show', [
            'vehicle' => [
                'id' => $vehicle->id,
                'reference_number' => $vehicle->reference_number,
                'slug' => $vehicle->slug,
                'title' => $vehicle->displayName(),
                'ownership_type' => $vehicle->ownership_type->value,
                'ownership_label' => $vehicle->ownership_type->label(),
                'brand_name' => $vehicle->brand->name ?? '—',
                'model_name' => $vehicle->model->name ?? '—',
                'category_name' => $vehicle->category->name ?? '—',
                'condition_id' => $vehicle->vehicle_condition_id,
                'condition_name' => $vehicle->condition->name ?? '—',
                'condition_variant' => $vehicle->condition->badge_variant ?? 'neutral',
                'year' => $vehicle->year,
                'vin' => $vehicle->vin,
                'chassis_number' => $vehicle->chassis_number,
                'engine_number' => $vehicle->engine_number,
                'registration_number' => $vehicle->registration_number,
                'transmission_label' => $vehicle->transmission->label(),
                'fuel_type_label' => $vehicle->fuel_type->label(),
                'drive_type_label' => $vehicle->drive_type?->label(),
                'exterior_colour' => $vehicle->exterior_colour,
                'interior_colour' => $vehicle->interior_colour,
                'mileage' => $vehicle->mileage,
                'steering_position_label' => $vehicle->steering_position->label(),
                'doors' => $vehicle->doors,
                'status' => $vehicle->status->value,
                'status_label' => $vehicle->status->label(),
                'status_variant' => $vehicle->status->badgeVariant(),
                'location' => $vehicle->location,
                'received_at' => $vehicle->received_at->toDateString(),
                'registered_at' => $vehicle->registered_at?->toISOString(),
                'received_by_name' => $vehicle->receiver->name ?? '—',
                'acquisition_source' => $canSeeAcquisitionCost ? $vehicle->acquisition_source : null,
                'acquisition_cost' => $canSeeAcquisitionCost ? $vehicle->acquisition_cost : null,
                'acquisition_reference' => $canSeeAcquisitionCost ? $vehicle->acquisition_reference : null,
                'acquired_from' => $canSeeAcquisitionCost ? $vehicle->acquired_from : null,
                'advertised_price' => $vehicle->advertised_price,
                'minimum_price' => $canSeeAcquisitionCost ? $vehicle->minimum_price : null,
                'general_description' => $vehicle->general_description,
                'condition_remarks' => $vehicle->condition_remarks,
                'mechanical_issues' => $vehicle->mechanical_issues,
                'bodywork_condition' => $vehicle->bodywork_condition,
                'accessories' => $vehicle->accessories,
                'internal_notes' => $vehicle->internal_notes,
                'is_public_listing' => $vehicle->is_public_listing,
                'duplicate_vin_override_reason' => $vehicle->duplicate_vin_override_reason,
                'duplicate_vin_overridden_by_name' => $vehicle->duplicateVinOverrider?->name,
                'photos' => $vehicle->photos->map(static fn (VehiclePhoto $photo): array => [
                    'id' => $photo->id,
                    'url' => $photo->url(),
                    'original_name' => $photo->original_name,
                    'caption' => $photo->caption,
                    'category' => $photo->category->value,
                    'category_label' => $photo->category->label(),
                    'is_primary' => $photo->is_primary,
                    'uploaded_by' => $photo->uploader->name ?? 'System',
                    'created_at' => $photo->created_at?->toISOString(),
                ])->values()->all(),
                'status_histories' => $vehicle->statusHistories->map(static fn ($h): array => [
                    'id' => $h->id,
                    'from_label' => $h->from_status?->label(),
                    'to_label' => $h->to_status->label(),
                    'to_variant' => $h->to_status->badgeVariant(),
                    'reason' => $h->reason,
                    'changed_by' => $h->changer->name ?? 'System',
                    'created_at' => $h->created_at?->toISOString(),
                ])->values()->all(),
                'condition_histories' => $vehicle->conditionHistories->map(static fn ($h): array => [
                    'id' => $h->id,
                    'from_name' => $h->fromCondition?->name,
                    'to_name' => $h->toCondition->name ?? '—',
                    'defects_noted' => $h->defects_noted,
                    'changed_by' => $h->changer->name ?? 'System',
                    'created_at' => $h->created_at?->toISOString(),
                ])->values()->all(),
                'attendant_logs' => $vehicle->attendantLogs->map(static fn ($log): array => [
                    'id' => $log->id,
                    'activity_label' => $log->activity_type->label(),
                    'description' => $log->description,
                    'location_from' => $log->location_from,
                    'location_to' => $log->location_to,
                    'logged_by' => $log->logger->name ?? 'System',
                    'logged_at' => $log->logged_at->toISOString(),
                ])->values()->all(),
                'active_consignment' => $vehicle->activeConsignment !== null ? [
                    'id' => $vehicle->activeConsignment->id,
                    'reference_number' => $vehicle->activeConsignment->reference_number,
                    'status_label' => $vehicle->activeConsignment->status->label(),
                    'status_variant' => $vehicle->activeConsignment->status->badgeVariant(),
                    'owner_id' => $vehicle->activeConsignment->vehicle_owner_id,
                    'owner_name' => $vehicle->activeConsignment->owner->name ?? '—',
                    'owner_phone' => $vehicle->activeConsignment->owner?->phone,
                    'agreed_selling_price' => $vehicle->activeConsignment->agreed_selling_price,
                    'commission_label' => $vehicle->activeConsignment->commission_type->label(),
                ] : null,
            ],
            'allowedManualStatuses' => array_map(
                static fn (VehicleStatus $s): array => ['value' => $s->value, 'label' => $s->label()],
                $vehicle->status->manuallySelectableTransitions(),
            ),
            'canArchive' => $user->can('vehicles.archive') && $vehicle->status->canTransitionTo(VehicleStatus::Archived),
            'conditions' => VehicleCondition::query()->active()->orderBy('severity_order')->get(['id', 'name']),
            'attendantActivityTypes' => AttendantActivityType::options(),
            'photoCategories' => PhotoCategory::options(),
        ]);
    }

    public function edit(Vehicle $vehicle): Response
    {
        return Inertia::render('vehicles/Edit', [
            'vehicle' => [
                'id' => $vehicle->id,
                'reference_number' => $vehicle->reference_number,
                'ownership_type' => $vehicle->ownership_type->value,
                'ownership_label' => $vehicle->ownership_type->label(),
                'vehicle_brand_id' => $vehicle->vehicle_brand_id,
                'vehicle_model_id' => $vehicle->vehicle_model_id,
                'vehicle_category_id' => $vehicle->vehicle_category_id,
                'vehicle_condition_id' => $vehicle->vehicle_condition_id,
                'year' => $vehicle->year,
                'vin' => $vehicle->vin ?? '',
                'chassis_number' => $vehicle->chassis_number ?? '',
                'engine_number' => $vehicle->engine_number ?? '',
                'registration_number' => $vehicle->registration_number ?? '',
                'transmission' => $vehicle->transmission->value,
                'fuel_type' => $vehicle->fuel_type->value,
                'drive_type' => $vehicle->drive_type->value ?? '',
                'exterior_colour' => $vehicle->exterior_colour,
                'interior_colour' => $vehicle->interior_colour ?? '',
                'mileage' => $vehicle->mileage ?? '',
                'steering_position' => $vehicle->steering_position->value,
                'doors' => $vehicle->doors ?? '',
                'location' => $vehicle->location ?? '',
                'received_at' => $vehicle->received_at->toDateString(),
                'acquisition_source' => $vehicle->acquisition_source ?? '',
                'acquisition_cost' => $vehicle->acquisition_cost ?? '',
                'acquisition_reference' => $vehicle->acquisition_reference ?? '',
                'acquired_from' => $vehicle->acquired_from ?? '',
                'advertised_price' => $vehicle->advertised_price ?? '',
                'minimum_price' => $vehicle->minimum_price ?? '',
                'general_description' => $vehicle->general_description ?? '',
                'condition_remarks' => $vehicle->condition_remarks ?? '',
                'mechanical_issues' => $vehicle->mechanical_issues ?? '',
                'bodywork_condition' => $vehicle->bodywork_condition ?? '',
                'accessories' => $vehicle->accessories ?? '',
                'internal_notes' => $vehicle->internal_notes ?? '',
                'is_public_listing' => $vehicle->is_public_listing,
            ],
            ...$this->formLookups(),
        ]);
    }

    public function update(UpdateVehicleRequest $request, Vehicle $vehicle): RedirectResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $validated = $request->validated();

        $oldValues = $vehicle->only([
            'vin',
            'chassis_number',
            'registration_number',
            'vehicle_condition_id',
            'advertised_price',
            'minimum_price',
            'acquisition_cost',
            'location',
        ]);

        $previousConditionId = $vehicle->vehicle_condition_id;

        if ($vehicle->ownership_type === OwnershipType::Consignment) {
            unset($validated['acquisition_cost'], $validated['acquisition_source'], $validated['acquisition_reference'], $validated['acquired_from']);
        }

        if (isset($validated['vin']) && trim((string) $validated['vin']) !== '') {
            $validated['vin'] = strtoupper(trim((string) $validated['vin']));
        }
        if (isset($validated['chassis_number']) && trim((string) $validated['chassis_number']) !== '') {
            $validated['chassis_number'] = strtoupper(trim((string) $validated['chassis_number']));
        }

        DB::transaction(function () use ($vehicle, $validated, $previousConditionId, $oldValues, $actor): void {
            $vehicle->update($validated);

            if ($previousConditionId !== $vehicle->vehicle_condition_id) {
                $vehicle->conditionHistories()->create([
                    'from_condition_id' => $previousConditionId,
                    'to_condition_id' => $vehicle->vehicle_condition_id,
                    'defects_noted' => $vehicle->mechanical_issues ?: $vehicle->condition_remarks,
                    'changed_by' => $actor->id,
                    'created_at' => now(),
                ]);
            }

            $this->auditLogger->audit(
                event: 'vehicle.updated',
                auditable: $vehicle,
                oldValues: $oldValues,
                newValues: $vehicle->only(array_keys($oldValues)),
                actor: $actor,
            );

            $this->auditLogger->activity(
                action: 'vehicle.updated',
                description: "Updated specifications for vehicle {$vehicle->reference_number}.",
                subject: $vehicle,
                actor: $actor,
            );
        });

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "Vehicle {$vehicle->reference_number} updated successfully.",
        ]);

        return redirect()->route('vehicles.show', $vehicle);
    }

    public function updateStatus(Request $request, Vehicle $vehicle): RedirectResponse
    {
        /** @var User $actor */
        $actor = $request->user();

        $validated = $request->validate([
            'status' => [
                'required',
                Rule::in([
                    VehicleStatus::PendingRegistration->value,
                    VehicleStatus::UnderPreparation->value,
                    VehicleStatus::UnderMaintenance->value,
                    VehicleStatus::Available->value,
                ]),
            ],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $target = VehicleStatus::from((string) $validated['status']);
        $this->transitionStatus->execute($vehicle, $target, $actor, $validated['reason'] ?? null);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "Vehicle status updated to {$target->label()}.",
        ]);

        return back();
    }

    public function updateCondition(Request $request, Vehicle $vehicle): RedirectResponse
    {
        /** @var User $actor */
        $actor = $request->user();

        $validated = $request->validate([
            'vehicle_condition_id' => ['required', 'integer', Rule::exists('vehicle_conditions', 'id')],
            'defects_noted' => ['nullable', 'string', 'max:2000'],
        ]);

        $previousId = $vehicle->vehicle_condition_id;
        $newId = (int) $validated['vehicle_condition_id'];

        DB::transaction(function () use ($vehicle, $previousId, $newId, $validated, $actor): void {
            $vehicle->update([
                'vehicle_condition_id' => $newId,
                'condition_remarks' => $validated['defects_noted'] ?: $vehicle->condition_remarks,
            ]);

            $vehicle->conditionHistories()->create([
                'from_condition_id' => $previousId,
                'to_condition_id' => $newId,
                'defects_noted' => $validated['defects_noted'] ?? null,
                'changed_by' => $actor->id,
                'created_at' => now(),
            ]);

            $this->auditLogger->activity(
                action: 'vehicle.condition_updated',
                description: "Updated physical condition for vehicle {$vehicle->reference_number}.",
                subject: $vehicle,
                actor: $actor,
            );
        });

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Vehicle condition updated and logged.',
        ]);

        return back();
    }

    public function logAttendantActivity(StoreAttendantLogRequest $request, Vehicle $vehicle): RedirectResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $validated = $request->validated();

        DB::transaction(function () use ($vehicle, $validated, $actor): void {
            $vehicle->attendantLogs()->create([
                'activity_type' => $validated['activity_type'],
                'description' => $validated['description'],
                'location_from' => $validated['location_from'] ?? $vehicle->location,
                'location_to' => $validated['location_to'] ?? null,
                'logged_by' => $actor->id,
                'logged_at' => now(),
            ]);

            if (! empty($validated['update_vehicle_location']) && ! empty($validated['location_to'])) {
                $vehicle->update(['location' => $validated['location_to']]);
            }

            $this->auditLogger->activity(
                action: 'vehicle.attendant_activity',
                description: "Logged {$validated['activity_type']} activity on vehicle {$vehicle->reference_number}.",
                subject: $vehicle,
                actor: $actor,
            );
        });

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Vehicle activity logged successfully.',
        ]);

        return back();
    }

    public function uploadPhotos(UploadVehiclePhotosRequest $request, Vehicle $vehicle): RedirectResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $validated = $request->validated();
        /** @var list<UploadedFile> $files */
        $files = $request->file('photos', []);

        $hasPrimary = $vehicle->photos()->where('is_primary', true)->exists();
        $nextOrder = (int) $vehicle->photos()->max('sort_order') + 1;

        foreach ($files as $index => $file) {
            $path = $file->store("vehicles/{$vehicle->id}/photos", 'public');

            $vehicle->photos()->create([
                'disk' => 'public',
                'path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'caption' => $validated['caption'] ?? null,
                'category' => $validated['category'],
                'is_primary' => ! $hasPrimary && $index === 0,
                'sort_order' => $nextOrder + $index,
                'uploaded_by' => $actor->id,
            ]);
        }

        $this->auditLogger->activity(
            action: 'vehicle.photos_uploaded',
            description: 'Uploaded '.count($files)." photograph(s) to vehicle {$vehicle->reference_number}.",
            subject: $vehicle,
            actor: $actor,
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => count($files).' vehicle photograph(s) uploaded successfully.',
        ]);

        return back();
    }

    public function setPrimaryPhoto(Vehicle $vehicle, VehiclePhoto $photo): RedirectResponse
    {
        abort_unless($photo->vehicle_id === $vehicle->id, 404);

        DB::transaction(function () use ($vehicle, $photo): void {
            $vehicle->photos()->update(['is_primary' => false]);
            $photo->update(['is_primary' => true]);
        });

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Primary vehicle photograph updated.',
        ]);

        return back();
    }

    public function deletePhoto(Vehicle $vehicle, VehiclePhoto $photo): RedirectResponse
    {
        abort_unless($photo->vehicle_id === $vehicle->id, 404);

        $wasPrimary = $photo->is_primary;
        Storage::disk($photo->disk)->delete($photo->path);
        $photo->delete();

        if ($wasPrimary) {
            $nextPhoto = $vehicle->photos()->first();
            if ($nextPhoto !== null) {
                $nextPhoto->update(['is_primary' => true]);
            }
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Photograph removed.',
        ]);

        return back();
    }

    public function archive(Request $request, Vehicle $vehicle): RedirectResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $this->transitionStatus->execute(
            $vehicle,
            VehicleStatus::Archived,
            $actor,
            $validated['reason'] ?? 'Archived from active inventory.',
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "Vehicle {$vehicle->reference_number} has been archived.",
        ]);

        return back();
    }

    /**
     * @return array<string, mixed>
     */
    private function formLookups(): array
    {
        return [
            'brands' => VehicleBrand::query()->active()->orderBy('name')->get(['id', 'name']),
            'models' => VehicleModel::query()->active()->orderBy('name')->get(['id', 'vehicle_brand_id', 'name']),
            'categories' => VehicleCategory::query()->active()->orderBy('name')->get(['id', 'name']),
            'conditions' => VehicleCondition::query()->active()->orderBy('severity_order')->get(['id', 'name']),
            'ownershipOptions' => OwnershipType::options(),
            'transmissionOptions' => TransmissionType::options(),
            'fuelOptions' => FuelType::options(),
            'driveOptions' => DriveType::options(),
            'steeringOptions' => SteeringPosition::options(),
            'initialStatusOptions' => [
                ['value' => VehicleStatus::Available->value, 'label' => VehicleStatus::Available->label()],
                ['value' => VehicleStatus::UnderPreparation->value, 'label' => VehicleStatus::UnderPreparation->label()],
                ['value' => VehicleStatus::UnderMaintenance->value, 'label' => VehicleStatus::UnderMaintenance->label()],
                ['value' => VehicleStatus::PendingRegistration->value, 'label' => VehicleStatus::PendingRegistration->label()],
            ],
        ];
    }
}
