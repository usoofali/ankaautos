<?php

namespace App\Http\Controllers;

use App\Enums\InquiryStatus;
use App\Enums\OwnerCategory;
use App\Enums\VehicleStatus;
use App\Models\Customer;
use App\Models\CustomerInquiry;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\AuditLogger;
use App\Services\ReferenceNumberGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class CustomerController extends Controller
{
    public function __construct(
        private ReferenceNumberGenerator $referenceGenerator,
        private AuditLogger $auditLogger,
    ) {}

    public function index(Request $request): Response
    {
        $search = trim((string) $request->string('search'));
        $perPage = in_array($request->integer('per_page', 15), [10, 15, 25, 50], true)
            ? $request->integer('per_page', 15)
            : 15;
        $sort = in_array($request->string('sort')->toString(), ['reference_number', 'name', 'created_at'], true)
            ? $request->string('sort')->toString()
            : 'name';
        $direction = $request->string('direction')->toString() === 'desc' ? 'desc' : 'asc';

        $customers = Customer::query()
            ->withCount(['inquiries', 'reservations', 'sales'])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($sub) use ($search): void {
                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhere('reference_number', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->orderBy($sort, $direction)
            ->paginate($perPage)
            ->withQueryString()
            ->through(static fn (Customer $customer): array => [
                'id' => $customer->id,
                'reference_number' => $customer->reference_number,
                'name' => $customer->name,
                'category_label' => $customer->customer_type->label(),
                'phone' => $customer->phone,
                'email' => $customer->email,
                'inquiries_count' => $customer->inquiries_count,
                'reservations_count' => $customer->reservations_count,
                'sales_count' => $customer->sales_count,
                'created_at' => $customer->created_at?->toISOString(),
            ]);

        return Inertia::render('customers/Index', [
            'customers' => $customers,
            'filters' => [
                'search' => $search,
                'sort' => $sort,
                'direction' => $direction,
                'per_page' => $perPage,
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('customers/Create', [
            'categoryOptions' => OwnerCategory::options(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        /** @var User $actor */
        $actor = $request->user();

        $validated = $request->validate([
            'customer_type' => ['required', Rule::enum(OwnerCategory::class)],
            'name' => ['required', 'string', 'max:150'],
            'phone' => ['required', 'string', 'max:30'],
            'alt_phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:150'],
            'address' => ['nullable', 'string', 'max:500'],
            'id_type' => ['nullable', 'string', 'max:60'],
            'id_number' => ['nullable', 'string', 'max:80'],
            'notes' => ['nullable', 'string', 'max:1500'],
        ]);

        /** @var Customer $customer */
        $customer = Customer::query()->create([
            ...$validated,
            'reference_number' => $this->referenceGenerator->generate('CUS'),
            'created_by' => $actor->id,
        ]);

        $this->auditLogger->activity(
            action: 'customer.created',
            description: "Registered customer {$customer->name} ({$customer->reference_number}).",
            subject: $customer,
            actor: $actor,
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "Customer {$customer->name} created successfully.",
        ]);

        return redirect()->route('customers.show', $customer);
    }

    public function show(Customer $customer): Response
    {
        $customer->load([
            'inquiries.vehicle.brand:id,name',
            'inquiries.vehicle.model:id,name',
            'inquiries.assignee:id,name',
            'reservations.vehicle.brand:id,name',
            'reservations.vehicle.model:id,name',
            'sales.vehicle.brand:id,name',
            'sales.vehicle.model:id,name',
        ]);

        $availableVehicles = Vehicle::query()
            ->with(['brand:id,name', 'model:id,name'])
            ->whereIn('status', [VehicleStatus::Available->value, VehicleStatus::UnderPreparation->value, VehicleStatus::UnderMaintenance->value])
            ->orderByDesc('id')
            ->get()
            ->map(static fn (Vehicle $v): array => [
                'id' => $v->id,
                'label' => "{$v->reference_number} — {$v->displayName()}",
            ]);

        return Inertia::render('customers/Show', [
            'customer' => [
                'id' => $customer->id,
                'reference_number' => $customer->reference_number,
                'name' => $customer->name,
                'category_label' => $customer->customer_type->label(),
                'phone' => $customer->phone,
                'alt_phone' => $customer->alt_phone,
                'email' => $customer->email,
                'address' => $customer->address,
                'id_type' => $customer->id_type,
                'id_number' => $customer->id_number,
                'notes' => $customer->notes,
                'inquiries' => $customer->inquiries->map(static fn (CustomerInquiry $inq): array => [
                    'id' => $inq->id,
                    'vehicle_id' => $inq->vehicle_id,
                    'vehicle_title' => $inq->vehicle?->displayName() ?? '—',
                    'channel' => $inq->channel,
                    'status' => $inq->status->value,
                    'status_label' => $inq->status->label(),
                    'status_variant' => $inq->status->badgeVariant(),
                    'offered_price' => $inq->offered_price,
                    'inspection_date' => $inq->inspection_date?->toDateString(),
                    'inspection_notes' => $inq->inspection_notes,
                    'negotiation_notes' => $inq->negotiation_notes,
                    'assigned_to' => $inq->assignee->name ?? 'Unassigned',
                    'created_at' => $inq->created_at?->toISOString(),
                ])->values()->all(),
                'reservations' => $customer->reservations->map(static fn ($res): array => [
                    'id' => $res->id,
                    'reference_number' => $res->reference_number,
                    'vehicle_title' => $res->vehicle?->displayName() ?? '—',
                    'deposit_amount' => $res->deposit_amount,
                    'agreed_price' => $res->agreed_price,
                    'status_label' => $res->status->label(),
                    'status_variant' => $res->status->badgeVariant(),
                    'reserved_at' => $res->reserved_at->toISOString(),
                ])->values()->all(),
                'sales' => $customer->sales->map(static fn ($sale): array => [
                    'id' => $sale->id,
                    'reference_number' => $sale->reference_number,
                    'vehicle_title' => $sale->vehicle?->displayName() ?? '—',
                    'final_price' => $sale->final_price,
                    'amount_paid' => $sale->amount_paid,
                    'balance_due' => $sale->balance_due,
                    'status_label' => $sale->status->label(),
                    'status_variant' => $sale->status->badgeVariant(),
                    'confirmed_at' => $sale->confirmed_at->toISOString(),
                ])->values()->all(),
            ],
            'availableVehicles' => $availableVehicles,
            'inquiryStatuses' => InquiryStatus::options(),
        ]);
    }

    public function edit(Customer $customer): Response
    {
        return Inertia::render('customers/Edit', [
            'customer' => [
                'id' => $customer->id,
                'reference_number' => $customer->reference_number,
                'customer_type' => $customer->customer_type->value,
                'name' => $customer->name,
                'phone' => $customer->phone,
                'alt_phone' => $customer->alt_phone ?? '',
                'email' => $customer->email ?? '',
                'address' => $customer->address ?? '',
                'id_type' => $customer->id_type ?? '',
                'id_number' => $customer->id_number ?? '',
                'notes' => $customer->notes ?? '',
            ],
            'categoryOptions' => OwnerCategory::options(),
        ]);
    }

    public function update(Request $request, Customer $customer): RedirectResponse
    {
        $validated = $request->validate([
            'customer_type' => ['required', Rule::enum(OwnerCategory::class)],
            'name' => ['required', 'string', 'max:150'],
            'phone' => ['required', 'string', 'max:30'],
            'alt_phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:150'],
            'address' => ['nullable', 'string', 'max:500'],
            'id_type' => ['nullable', 'string', 'max:60'],
            'id_number' => ['nullable', 'string', 'max:80'],
            'notes' => ['nullable', 'string', 'max:1500'],
        ]);

        $customer->update($validated);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "Customer {$customer->name} updated successfully.",
        ]);

        return redirect()->route('customers.show', $customer);
    }

    public function storeInquiry(Request $request, Customer $customer): RedirectResponse
    {
        /** @var User $actor */
        $actor = $request->user();

        $validated = $request->validate([
            'vehicle_id' => ['required', 'integer', Rule::exists('vehicles', 'id')],
            'channel' => ['required', 'string', 'max:40'],
            'status' => ['required', Rule::enum(InquiryStatus::class)],
            'offered_price' => ['nullable', 'numeric', 'min:0', 'max:999999999999.99'],
            'inspection_date' => ['nullable', 'date'],
            'inspection_notes' => ['nullable', 'string', 'max:1500'],
            'negotiation_notes' => ['nullable', 'string', 'max:1500'],
        ]);

        $customer->inquiries()->create([
            ...$validated,
            'assigned_to' => $actor->id,
        ]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Customer enquiry and inspection notes logged.',
        ]);

        return back();
    }
}
