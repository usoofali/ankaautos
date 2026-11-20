<?php

namespace App\Http\Controllers;

use App\Actions\Reservations\CancelReservationAction;
use App\Actions\Reservations\CreateReservationAction;
use App\Enums\ReservationStatus;
use App\Enums\VehicleStatus;
use App\Models\Customer;
use App\Models\FinancialAccount;
use App\Models\PaymentMethod;
use App\Models\Reservation;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ReservationController extends Controller
{
    public function __construct(
        private CreateReservationAction $createReservation,
        private CancelReservationAction $cancelReservation,
    ) {}

    public function index(Request $request): Response
    {
        $search = trim((string) $request->string('search'));
        $status = trim((string) $request->string('status'));
        $perPage = in_array($request->integer('per_page', 15), [10, 15, 25, 50], true)
            ? $request->integer('per_page', 15)
            : 15;

        $reservations = Reservation::query()
            ->with(['vehicle.brand:id,name', 'vehicle.model:id,name', 'customer:id,name,phone'])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($sub) use ($search): void {
                    $sub->where('reference_number', 'like', "%{$search}%")
                        ->orWhereHas('customer', fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('phone', 'like', "%{$search}%"))
                        ->orWhereHas('vehicle', fn ($q) => $q->where('reference_number', 'like', "%{$search}%"));
                });
            })
            ->when($status !== '', fn ($q) => $q->where('status', $status))
            ->latest('reserved_at')
            ->paginate($perPage)
            ->withQueryString()
            ->through(static fn (Reservation $res): array => [
                'id' => $res->id,
                'reference_number' => $res->reference_number,
                'vehicle_id' => $res->vehicle_id,
                'vehicle_reference' => $res->vehicle->reference_number ?? '—',
                'vehicle_title' => $res->vehicle?->displayName() ?? '—',
                'customer_id' => $res->customer_id,
                'customer_name' => $res->customer->name ?? '—',
                'customer_phone' => $res->customer?->phone,
                'agreed_price' => $res->agreed_price,
                'deposit_amount' => $res->deposit_amount,
                'status' => $res->status->value,
                'status_label' => $res->status->label(),
                'status_variant' => $res->status->badgeVariant(),
                'reserved_at' => $res->reserved_at->toISOString(),
            ]);

        return Inertia::render('reservations/Index', [
            'reservations' => $reservations,
            'statusOptions' => ReservationStatus::options(),
            'filters' => [
                'search' => $search,
                'status' => $status,
                'per_page' => $perPage,
            ],
        ]);
    }

    public function create(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();

        $vehicles = Vehicle::query()
            ->with(['brand:id,name', 'model:id,name'])
            ->whereNotIn('status', [VehicleStatus::Sold->value, VehicleStatus::Withdrawn->value, VehicleStatus::Archived->value])
            ->orderByDesc('id')
            ->get()
            ->map(static fn (Vehicle $v): array => [
                'id' => $v->id,
                'label' => "{$v->reference_number} — {$v->displayName()} ({$v->status->label()})",
                'advertised_price' => $v->advertised_price,
                'is_reserved' => $v->status === VehicleStatus::Reserved,
            ]);

        return Inertia::render('reservations/Create', [
            'vehicles' => $vehicles,
            'customers' => Customer::query()->orderBy('name')->get(['id', 'reference_number', 'name', 'phone']),
            'paymentMethods' => PaymentMethod::query()->active()->orderBy('name')->get(['id', 'name', 'requires_reference']),
            'financialAccounts' => FinancialAccount::query()->active()->orderBy('name')->get(['id', 'name']),
            'canOverrideConflict' => $user->can('reservations.override'),
            'preselectedVehicleId' => $request->integer('vehicle_id') ?: null,
            'preselectedCustomerId' => $request->integer('customer_id') ?: null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        /** @var User $actor */
        $actor = $request->user();

        $validated = $request->validate([
            'vehicle_id' => ['required', 'integer', Rule::exists('vehicles', 'id')],
            'customer_id' => ['required', 'integer', Rule::exists('customers', 'id')],
            'agreed_price' => ['required', 'numeric', 'min:1', 'max:999999999999.99'],
            'deposit_amount' => ['required', 'numeric', 'min:0.01', 'max:999999999999.99'],
            'payment_method_id' => ['required', 'integer', Rule::exists('payment_methods', 'id')],
            'financial_account_id' => ['required', 'integer', Rule::exists('financial_accounts', 'id')],
            'paid_at' => ['required', 'date'],
            'external_reference' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'override_conflict' => ['sometimes', 'boolean'],
            'idempotency_key' => ['nullable', 'string', 'max:100'],
        ]);

        $reservation = $this->createReservation->execute($validated, $actor);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "Reservation {$reservation->reference_number} confirmed with deposit.",
        ]);

        return redirect()->route('reservations.show', $reservation);
    }

    public function show(Request $request, Reservation $reservation): Response
    {
        /** @var User $user */
        $user = $request->user();

        $reservation->load([
            'vehicle.brand:id,name',
            'vehicle.model:id,name',
            'customer',
            'creator:id,name',
            'sale:id,reservation_id,reference_number',
            'payments.paymentMethod:id,name',
            'payments.financialAccount:id,name',
        ]);

        return Inertia::render('reservations/Show', [
            'reservation' => [
                'id' => $reservation->id,
                'reference_number' => $reservation->reference_number,
                'vehicle_id' => $reservation->vehicle_id,
                'vehicle_reference' => $reservation->vehicle->reference_number ?? '—',
                'vehicle_title' => $reservation->vehicle?->displayName() ?? '—',
                'customer_id' => $reservation->customer_id,
                'customer_name' => $reservation->customer->name ?? '—',
                'customer_phone' => $reservation->customer?->phone,
                'agreed_price' => $reservation->agreed_price,
                'deposit_amount' => $reservation->deposit_amount,
                'status' => $reservation->status->value,
                'status_label' => $reservation->status->label(),
                'status_variant' => $reservation->status->badgeVariant(),
                'reserved_at' => $reservation->reserved_at->toISOString(),
                'notes' => $reservation->notes,
                'cancellation_reason' => $reservation->cancellation_reason,
                'deposit_disposition' => $reservation->deposit_disposition,
                'created_by_name' => $reservation->creator->name ?? 'System',
                'converted_sale_id' => $reservation->sale?->id,
                'converted_sale_reference' => $reservation->sale?->reference_number,
                'payments' => $reservation->payments->map(static fn ($p): array => [
                    'id' => $p->id,
                    'reference_number' => $p->reference_number,
                    'amount' => $p->amount,
                    'paid_at' => $p->paid_at->toDateString(),
                    'payment_method' => $p->paymentMethod->name ?? '—',
                    'financial_account' => $p->financialAccount->name ?? '—',
                    'external_reference' => $p->external_reference,
                ])->values()->all(),
            ],
            'canConvert' => $reservation->status === ReservationStatus::Active && $user->can('sales.create'),
            'canCancel' => $reservation->status === ReservationStatus::Active && $user->can('reservations.override'),
            'paymentMethods' => PaymentMethod::query()->active()->orderBy('name')->get(['id', 'name']),
            'financialAccounts' => FinancialAccount::query()->active()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function cancel(Request $request, Reservation $reservation): RedirectResponse
    {
        /** @var User $actor */
        $actor = $request->user();

        $validated = $request->validate([
            'deposit_disposition' => ['required', Rule::in(['refunded', 'forfeited'])],
            'cancellation_reason' => ['required', 'string', 'min:5', 'max:1000'],
            'payment_method_id' => ['required_if:deposit_disposition,refunded', 'nullable', 'integer', Rule::exists('payment_methods', 'id')],
            'financial_account_id' => ['required_if:deposit_disposition,refunded', 'nullable', 'integer', Rule::exists('financial_accounts', 'id')],
        ]);

        $this->cancelReservation->execute($reservation, $validated, $actor);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "Reservation {$reservation->reference_number} cancelled and vehicle released to Available.",
        ]);

        return back();
    }
}
