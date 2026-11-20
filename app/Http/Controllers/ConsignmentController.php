<?php

namespace App\Http\Controllers;

use App\Actions\Consignments\AmendConsignmentAgreementAction;
use App\Actions\Consignments\CreateConsignmentAction;
use App\Enums\CommissionType;
use App\Enums\ConsignmentStatus;
use App\Enums\OwnershipType;
use App\Http\Requests\Consignments\AmendConsignmentRequest;
use App\Http\Requests\Consignments\StoreConsignmentRequest;
use App\Models\Consignment;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleOwner;
use App\Services\AuditLogger;
use App\Services\BusinessSettingService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ConsignmentController extends Controller
{
    public function __construct(
        private CreateConsignmentAction $createConsignment,
        private AmendConsignmentAgreementAction $amendAgreement,
        private BusinessSettingService $businessSettings,
        private AuditLogger $auditLogger,
    ) {}

    public function index(Request $request): Response
    {
        $search = trim((string) $request->string('search'));
        $status = trim((string) $request->string('status'));
        $perPage = in_array($request->integer('per_page', 15), [10, 15, 25, 50], true)
            ? $request->integer('per_page', 15)
            : 15;
        $sort = in_array($request->string('sort')->toString(), ['reference_number', 'agreed_selling_price', 'received_at', 'status'], true)
            ? $request->string('sort')->toString()
            : 'received_at';
        $direction = $request->string('direction')->toString() === 'asc' ? 'asc' : 'desc';

        $consignments = Consignment::query()
            ->with(['vehicle.brand:id,name', 'vehicle.model:id,name', 'owner:id,name,phone', 'agreement:id,consignment_id,current_version'])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($sub) use ($search): void {
                    $sub->where('reference_number', 'like', "%{$search}%")
                        ->orWhereHas('owner', fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('phone', 'like', "%{$search}%"))
                        ->orWhereHas('vehicle', fn ($q) => $q->where('reference_number', 'like', "%{$search}%")->orWhere('vin', 'like', "%{$search}%"));
                });
            })
            ->when($status !== '', fn ($q) => $q->where('status', $status))
            ->orderBy($sort, $direction)
            ->paginate($perPage)
            ->withQueryString()
            ->through(static fn (Consignment $consignment): array => [
                'id' => $consignment->id,
                'reference_number' => $consignment->reference_number,
                'vehicle_id' => $consignment->vehicle_id,
                'vehicle_reference' => $consignment->vehicle->reference_number ?? '—',
                'vehicle_title' => $consignment->vehicle?->displayName() ?? '—',
                'owner_id' => $consignment->vehicle_owner_id,
                'owner_name' => $consignment->owner->name ?? '—',
                'owner_phone' => $consignment->owner?->phone,
                'status' => $consignment->status->value,
                'status_label' => $consignment->status->label(),
                'status_variant' => $consignment->status->badgeVariant(),
                'agreed_selling_price' => $consignment->agreed_selling_price,
                'commission_type_label' => $consignment->commission_type->label(),
                'commission_value' => $consignment->commission_value,
                'agreement_version' => $consignment->agreement->current_version ?? 1,
                'received_at' => $consignment->received_at->toDateString(),
            ]);

        return Inertia::render('consignments/Index', [
            'consignments' => $consignments,
            'statusOptions' => ConsignmentStatus::options(),
            'filters' => [
                'search' => $search,
                'status' => $status,
                'sort' => $sort,
                'direction' => $direction,
                'per_page' => $perPage,
            ],
        ]);
    }

    public function create(Request $request): Response
    {
        $availableVehicles = Vehicle::query()
            ->with(['brand:id,name', 'model:id,name'])
            ->where('ownership_type', OwnershipType::Consignment->value)
            ->whereDoesntHave('consignments', function ($q): void {
                $q->whereNotIn('status', [ConsignmentStatus::Settled->value, ConsignmentStatus::Withdrawn->value]);
            })
            ->orderByDesc('id')
            ->get()
            ->map(static fn (Vehicle $vehicle): array => [
                'id' => $vehicle->id,
                'label' => "{$vehicle->reference_number} — {$vehicle->displayName()} ({$vehicle->exterior_colour})",
                'advertised_price' => $vehicle->advertised_price,
            ]);

        $owners = VehicleOwner::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'reference_number', 'name', 'phone']);

        return Inertia::render('consignments/Create', [
            'vehicles' => $availableVehicles,
            'owners' => $owners,
            'commissionTypes' => CommissionType::options(),
            'preselectedVehicleId' => $request->integer('vehicle_id') ?: null,
            'preselectedOwnerId' => $request->integer('owner_id') ?: null,
        ]);
    }

    public function store(StoreConsignmentRequest $request): RedirectResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $consignment = $this->createConsignment->execute($request->validated(), $actor);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "Consignment {$consignment->reference_number} registered successfully.",
        ]);

        return redirect()->route('consignments.show', $consignment);
    }

    public function show(Request $request, Consignment $consignment): Response
    {
        /** @var User $user */
        $user = $request->user();

        $consignment->load([
            'vehicle.brand:id,name',
            'vehicle.model:id,name',
            'vehicle.primaryPhoto',
            'owner',
            'receiver:id,name',
            'agreement.versions.creator:id,name',
            'statusHistories.changer:id,name',
        ]);

        $canAmend = $user->can('consignments.amend_terms') && ! in_array($consignment->status, [
            ConsignmentStatus::Sold,
            ConsignmentStatus::SettlementPending,
            ConsignmentStatus::Settled,
            ConsignmentStatus::Withdrawn,
        ], true);

        return Inertia::render('consignments/Show', [
            'consignment' => [
                'id' => $consignment->id,
                'reference_number' => $consignment->reference_number,
                'status' => $consignment->status->value,
                'status_label' => $consignment->status->label(),
                'status_variant' => $consignment->status->badgeVariant(),
                'received_at' => $consignment->received_at->toDateString(),
                'expected_review_date' => $consignment->expected_review_date?->toDateString(),
                'agreed_selling_price' => $consignment->agreed_selling_price,
                'minimum_acceptable_price' => $consignment->minimum_acceptable_price,
                'commission_type' => $consignment->commission_type->value,
                'commission_type_label' => $consignment->commission_type->label(),
                'commission_value' => $consignment->commission_value,
                'commission_notes' => $consignment->commission_notes,
                'expense_responsibility_notes' => $consignment->expense_responsibility_notes,
                'withdrawal_terms' => $consignment->withdrawal_terms,
                'settlement_terms' => $consignment->settlement_terms,
                'remarks' => $consignment->remarks,
                'received_by_name' => $consignment->receiver->name ?? '—',
                'vehicle' => $consignment->vehicle !== null ? [
                    'id' => $consignment->vehicle->id,
                    'reference_number' => $consignment->vehicle->reference_number,
                    'title' => $consignment->vehicle->displayName(),
                    'exterior_colour' => $consignment->vehicle->exterior_colour,
                    'status_label' => $consignment->vehicle->status->label(),
                    'status_variant' => $consignment->vehicle->status->badgeVariant(),
                    'photo_url' => $consignment->vehicle->primaryPhoto?->url(),
                ] : null,
                'owner' => $consignment->owner !== null ? [
                    'id' => $consignment->owner->id,
                    'reference_number' => $consignment->owner->reference_number,
                    'name' => $consignment->owner->name,
                    'phone' => $consignment->owner->phone,
                    'email' => $consignment->owner->email,
                    'bank_name' => $consignment->owner->bank_name,
                    'bank_account_number' => $consignment->owner->bank_account_number,
                ] : null,
                'agreement_version' => $consignment->agreement->current_version ?? 1,
                'duration_days' => $consignment->agreement?->duration_days,
                'versions' => $consignment->agreement?->versions->map(static fn ($v): array => [
                    'id' => $v->id,
                    'version_number' => $v->version_number,
                    'agreed_selling_price' => $v->agreed_selling_price,
                    'minimum_acceptable_price' => $v->minimum_acceptable_price,
                    'commission_type' => $v->commission_type->value,
                    'commission_type_label' => $v->commission_type->label(),
                    'commission_value' => $v->commission_value,
                    'amendment_reason' => $v->amendment_reason,
                    'created_by' => $v->creator->name ?? 'System',
                    'created_at' => $v->created_at?->toISOString(),
                ])->values()->all() ?? [],
                'status_histories' => $consignment->statusHistories->map(static fn ($h): array => [
                    'id' => $h->id,
                    'from_label' => $h->from_status?->label(),
                    'to_label' => $h->to_status->label(),
                    'to_variant' => $h->to_status->badgeVariant(),
                    'reason' => $h->reason,
                    'changed_by' => $h->changer->name ?? 'System',
                    'created_at' => $h->created_at?->toISOString(),
                ])->values()->all(),
            ],
            'canAmend' => $canAmend,
            'commissionTypes' => CommissionType::options(),
        ]);
    }

    public function amendTerms(AmendConsignmentRequest $request, Consignment $consignment): RedirectResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $this->amendAgreement->execute($consignment, $request->validated(), $actor);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Consignment financial terms amended and archived as a new agreement version.',
        ]);

        return back();
    }

    public function updateStatus(Request $request, Consignment $consignment): RedirectResponse
    {
        /** @var User $actor */
        $actor = $request->user();

        $validated = $request->validate([
            'status' => ['required', Rule::in([ConsignmentStatus::Active->value, ConsignmentStatus::OnHold->value])],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        if ($consignment->status->isTerminal() || in_array($consignment->status, [ConsignmentStatus::Sold, ConsignmentStatus::SettlementPending], true)) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => 'Cannot manually alter status of a sold, settled, or withdrawn consignment.',
            ]);

            return back();
        }

        $previous = $consignment->status;
        $target = ConsignmentStatus::from((string) $validated['status']);

        $consignment->update(['status' => $target]);
        $consignment->statusHistories()->create([
            'from_status' => $previous,
            'to_status' => $target,
            'reason' => $validated['reason'] ?? null,
            'changed_by' => $actor->id,
            'created_at' => now(),
        ]);

        $this->auditLogger->activity(
            action: 'consignment.status_updated',
            description: "Updated consignment {$consignment->reference_number} status from {$previous->label()} to {$target->label()}.",
            subject: $consignment,
            actor: $actor,
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "Consignment status updated to {$target->label()}.",
        ]);

        return back();
    }

    public function downloadAgreementPdf(Consignment $consignment): HttpResponse
    {
        $consignment->load(['vehicle.brand', 'vehicle.model', 'owner', 'receiver', 'agreement']);

        $pdf = Pdf::loadView('pdfs.consignment-agreement', [
            'consignment' => $consignment,
            'business' => $this->businessSettings->all(),
        ])->setPaper('a4');

        return $pdf->download("Consignment-Agreement-{$consignment->reference_number}.pdf");
    }
}
