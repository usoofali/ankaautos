<?php

namespace App\Http\Controllers;

use App\Enums\OwnerCategory;
use App\Http\Requests\Owners\StoreVehicleOwnerRequest;
use App\Http\Requests\Owners\UpdateVehicleOwnerRequest;
use App\Models\User;
use App\Models\VehicleOwner;
use App\Services\AuditLogger;
use App\Services\ReferenceNumberGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class VehicleOwnerController extends Controller
{
    public function __construct(
        private ReferenceNumberGenerator $referenceGenerator,
        private AuditLogger $auditLogger,
    ) {}

    public function index(Request $request): Response
    {
        $search = trim((string) $request->string('search'));
        $category = trim((string) $request->string('category'));
        $perPage = in_array($request->integer('per_page', 15), [10, 15, 25, 50], true)
            ? $request->integer('per_page', 15)
            : 15;
        $sort = in_array($request->string('sort')->toString(), ['reference_number', 'name', 'created_at'], true)
            ? $request->string('sort')->toString()
            : 'name';
        $direction = $request->string('direction')->toString() === 'desc' ? 'desc' : 'asc';

        $owners = VehicleOwner::query()
            ->withCount('consignments')
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($sub) use ($search): void {
                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhere('reference_number', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($category !== '', fn ($q) => $q->where('owner_category', $category))
            ->orderBy($sort, $direction)
            ->paginate($perPage)
            ->withQueryString()
            ->through(static fn (VehicleOwner $owner): array => [
                'id' => $owner->id,
                'reference_number' => $owner->reference_number,
                'name' => $owner->name,
                'owner_category' => $owner->owner_category->value,
                'category_label' => $owner->owner_category->label(),
                'phone' => $owner->phone,
                'email' => $owner->email,
                'bank_name' => $owner->bank_name,
                'consignments_count' => $owner->consignments_count,
                'is_active' => $owner->is_active,
                'created_at' => $owner->created_at?->toISOString(),
            ]);

        return Inertia::render('owners/Index', [
            'owners' => $owners,
            'categoryOptions' => OwnerCategory::options(),
            'filters' => [
                'search' => $search,
                'category' => $category,
                'sort' => $sort,
                'direction' => $direction,
                'per_page' => $perPage,
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('owners/Create', [
            'categoryOptions' => OwnerCategory::options(),
        ]);
    }

    public function store(StoreVehicleOwnerRequest $request): RedirectResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $validated = $request->validated();

        /** @var VehicleOwner $owner */
        $owner = VehicleOwner::query()->create([
            ...$validated,
            'reference_number' => $this->referenceGenerator->generate('OWN'),
            'is_active' => (bool) ($validated['is_active'] ?? true),
            'created_by' => $actor->id,
        ]);

        $this->auditLogger->audit(
            event: 'vehicle_owner.created',
            auditable: $owner,
            newValues: $owner->only(['reference_number', 'name', 'phone', 'owner_category']),
            actor: $actor,
        );

        $this->auditLogger->activity(
            action: 'vehicle_owner.created',
            description: "Registered vehicle owner {$owner->name} ({$owner->reference_number}).",
            subject: $owner,
            actor: $actor,
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "Vehicle owner {$owner->name} registered successfully.",
        ]);

        return redirect()->route('owners.show', $owner);
    }

    public function show(VehicleOwner $owner): Response
    {
        $owner->load(['consignments.vehicle.brand', 'consignments.vehicle.model']);

        return Inertia::render('owners/Show', [
            'owner' => [
                'id' => $owner->id,
                'reference_number' => $owner->reference_number,
                'name' => $owner->name,
                'owner_category' => $owner->owner_category->value,
                'category_label' => $owner->owner_category->label(),
                'phone' => $owner->phone,
                'alt_phone' => $owner->alt_phone,
                'email' => $owner->email,
                'address' => $owner->address,
                'id_type' => $owner->id_type,
                'id_number' => $owner->id_number,
                'bank_name' => $owner->bank_name,
                'bank_account_name' => $owner->bank_account_name,
                'bank_account_number' => $owner->bank_account_number,
                'remarks' => $owner->remarks,
                'is_active' => $owner->is_active,
                'created_at' => $owner->created_at?->toISOString(),
                'consignments' => $owner->consignments->map(static fn ($consignment): array => [
                    'id' => $consignment->id,
                    'reference_number' => $consignment->reference_number,
                    'vehicle_id' => $consignment->vehicle_id,
                    'vehicle_reference' => $consignment->vehicle->reference_number ?? '—',
                    'vehicle_title' => $consignment->vehicle?->displayName() ?? '—',
                    'status_label' => $consignment->status->label(),
                    'status_variant' => $consignment->status->badgeVariant(),
                    'agreed_selling_price' => $consignment->agreed_selling_price,
                    'commission_label' => $consignment->commission_type->label(),
                    'received_at' => $consignment->received_at->toDateString(),
                ])->values()->all(),
            ],
        ]);
    }

    public function edit(VehicleOwner $owner): Response
    {
        return Inertia::render('owners/Edit', [
            'owner' => [
                'id' => $owner->id,
                'reference_number' => $owner->reference_number,
                'owner_category' => $owner->owner_category->value,
                'name' => $owner->name,
                'phone' => $owner->phone,
                'alt_phone' => $owner->alt_phone ?? '',
                'email' => $owner->email ?? '',
                'address' => $owner->address ?? '',
                'id_type' => $owner->id_type ?? '',
                'id_number' => $owner->id_number ?? '',
                'bank_name' => $owner->bank_name ?? '',
                'bank_account_name' => $owner->bank_account_name ?? '',
                'bank_account_number' => $owner->bank_account_number ?? '',
                'remarks' => $owner->remarks ?? '',
                'is_active' => $owner->is_active,
            ],
            'categoryOptions' => OwnerCategory::options(),
        ]);
    }

    public function update(UpdateVehicleOwnerRequest $request, VehicleOwner $owner): RedirectResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $oldValues = $owner->only(['name', 'phone', 'email', 'bank_name', 'bank_account_number', 'is_active']);

        $owner->update($request->validated());

        $this->auditLogger->audit(
            event: 'vehicle_owner.updated',
            auditable: $owner,
            oldValues: $oldValues,
            newValues: $owner->only(array_keys($oldValues)),
            actor: $actor,
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "Vehicle owner {$owner->name} updated successfully.",
        ]);

        return redirect()->route('owners.show', $owner);
    }
}
