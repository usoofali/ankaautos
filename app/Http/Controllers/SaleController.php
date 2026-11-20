<?php

namespace App\Http\Controllers;

use App\Actions\Handovers\RecordHandoverAction;
use App\Actions\Sales\CancelSaleAction;
use App\Actions\Sales\CreateSaleAction;
use App\Actions\Sales\RecordSalePaymentAction;
use App\Enums\ReservationStatus;
use App\Enums\SaleStatus;
use App\Enums\VehicleStatus;
use App\Models\ChargeCategory;
use App\Models\Customer;
use App\Models\FinancialAccount;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\Reservation;
use App\Models\Sale;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\BusinessSettingService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class SaleController extends Controller
{
    public function __construct(
        private CreateSaleAction $createSale,
        private RecordSalePaymentAction $recordPayment,
        private RecordHandoverAction $recordHandover,
        private CancelSaleAction $cancelSale,
        private BusinessSettingService $businessSettings,
    ) {}

    public function index(Request $request): Response
    {
        $search = trim((string) $request->string('search'));
        $status = trim((string) $request->string('status'));
        $perPage = in_array($request->integer('per_page', 15), [10, 15, 25, 50], true)
            ? $request->integer('per_page', 15)
            : 15;
        $sort = in_array($request->string('sort')->toString(), ['reference_number', 'final_price', 'balance_due', 'confirmed_at'], true)
            ? $request->string('sort')->toString()
            : 'confirmed_at';
        $direction = $request->string('direction')->toString() === 'asc' ? 'asc' : 'desc';

        $sales = Sale::query()
            ->with(['vehicle.brand:id,name', 'vehicle.model:id,name', 'customer:id,name,phone', 'handover:id,handoverable_type,handoverable_id,reference_number'])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($sub) use ($search): void {
                    $sub->where('reference_number', 'like', "%{$search}%")
                        ->orWhereHas('customer', fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('phone', 'like', "%{$search}%"))
                        ->orWhereHas('vehicle', fn ($q) => $q->where('reference_number', 'like', "%{$search}%"));
                });
            })
            ->when($status !== '', fn ($q) => $q->where('status', $status))
            ->orderBy($sort, $direction)
            ->paginate($perPage)
            ->withQueryString()
            ->through(static fn (Sale $sale): array => [
                'id' => $sale->id,
                'reference_number' => $sale->reference_number,
                'vehicle_id' => $sale->vehicle_id,
                'vehicle_reference' => $sale->vehicle->reference_number ?? '—',
                'vehicle_title' => $sale->vehicle?->displayName() ?? '—',
                'ownership_label' => $sale->ownership_type->label(),
                'customer_id' => $sale->customer_id,
                'customer_name' => $sale->customer->name ?? '—',
                'final_price' => $sale->final_price,
                'amount_paid' => $sale->amount_paid,
                'balance_due' => $sale->balance_due,
                'is_installment' => $sale->is_installment,
                'has_handover' => $sale->handover !== null,
                'status' => $sale->status->value,
                'status_label' => $sale->status->label(),
                'status_variant' => $sale->status->badgeVariant(),
                'confirmed_at' => $sale->confirmed_at->toISOString(),
            ]);

        return Inertia::render('sales/Index', [
            'sales' => $sales,
            'statusOptions' => SaleStatus::options(),
            'summary' => [
                'total_sales_value' => (string) Sale::query()->where('status', '!=', SaleStatus::Cancelled->value)->sum('final_price'),
                'total_collected' => (string) Sale::query()->where('status', '!=', SaleStatus::Cancelled->value)->sum('amount_paid'),
                'total_receivable' => (string) Sale::query()->where('status', '!=', SaleStatus::Cancelled->value)->sum('balance_due'),
                'completed_count' => Sale::query()->where('status', SaleStatus::Completed->value)->count(),
            ],
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
        /** @var User $user */
        $user = $request->user();

        $reservationId = $request->integer('reservation_id') ?: null;
        $preselectedReservation = null;
        if ($reservationId !== null) {
            $res = Reservation::query()->with(['vehicle.brand', 'vehicle.model', 'customer'])->find($reservationId);
            if ($res !== null && $res->status === ReservationStatus::Active) {
                $preselectedReservation = [
                    'id' => $res->id,
                    'reference_number' => $res->reference_number,
                    'vehicle_id' => $res->vehicle_id,
                    'customer_id' => $res->customer_id,
                    'agreed_price' => $res->agreed_price,
                    'deposit_amount' => $res->deposit_amount,
                ];
            }
        }

        $vehicles = Vehicle::query()
            ->with(['brand:id,name', 'model:id,name', 'activeConsignment:id,vehicle_id,commission_type'])
            ->whereNotIn('status', [VehicleStatus::Sold->value, VehicleStatus::Withdrawn->value, VehicleStatus::Archived->value])
            ->orderByDesc('id')
            ->get()
            ->map(static fn (Vehicle $v): array => [
                'id' => $v->id,
                'label' => "{$v->reference_number} — {$v->displayName()} ({$v->ownership_type->label()})",
                'advertised_price' => $v->advertised_price,
                'ownership_type' => $v->ownership_type->value,
                'commission_type' => $v->activeConsignment?->commission_type->value,
            ]);

        return Inertia::render('sales/Create', [
            'vehicles' => $vehicles,
            'customers' => Customer::query()->orderBy('name')->get(['id', 'reference_number', 'name', 'phone']),
            'paymentMethods' => PaymentMethod::query()->active()->orderBy('name')->get(['id', 'name', 'requires_reference']),
            'financialAccounts' => FinancialAccount::query()->active()->orderBy('name')->get(['id', 'name']),
            'chargeCategories' => ChargeCategory::query()->active()->orderBy('name')->get(['id', 'name', 'default_amount']),
            'canApproveDiscount' => $user->can('sales.approve_discount'),
            'preselectedReservation' => $preselectedReservation,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        /** @var User $actor */
        $actor = $request->user();

        $validated = $request->validate([
            'vehicle_id' => ['required', 'integer', Rule::exists('vehicles', 'id')],
            'customer_id' => ['required', 'integer', Rule::exists('customers', 'id')],
            'reservation_id' => ['nullable', 'integer', Rule::exists('reservations', 'id')],
            'list_price' => ['required', 'numeric', 'min:1', 'max:999999999999.99'],
            'discount_amount' => ['nullable', 'numeric', 'min:0', 'max:999999999999.99'],
            'discount_reason' => ['nullable', 'string', 'max:500'],
            'is_installment' => ['sometimes', 'boolean'],
            'installment_terms' => ['nullable', 'string', 'max:1500'],
            'initial_payment_amount' => ['nullable', 'numeric', 'min:0', 'max:999999999999.99'],
            'payment_method_id' => ['required_with:initial_payment_amount', 'nullable', 'integer', Rule::exists('payment_methods', 'id')],
            'financial_account_id' => ['required_with:initial_payment_amount', 'nullable', 'integer', Rule::exists('financial_accounts', 'id')],
            'paid_at' => ['nullable', 'date'],
            'external_reference' => ['nullable', 'string', 'max:120'],
            'custom_commission_amount' => ['nullable', 'numeric', 'min:0', 'max:999999999999.99'],
            'notes' => ['nullable', 'string', 'max:1500'],
            'idempotency_key' => ['nullable', 'string', 'max:100'],
            'charges' => ['nullable', 'array'],
            'charges.*.charge_category_id' => ['nullable', 'integer', Rule::exists('charge_categories', 'id')],
            'charges.*.custom_label' => ['nullable', 'string', 'max:150'],
            'charges.*.amount' => ['nullable', 'numeric', 'min:0.01'],
            'charges.*.notes' => ['nullable', 'string', 'max:300'],
        ]);

        $sale = $this->createSale->execute($validated, $actor);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "Sale {$sale->reference_number} confirmed successfully.",
        ]);

        return redirect()->route('sales.show', $sale);
    }

    public function show(Request $request, Sale $sale): Response
    {
        /** @var User $user */
        $user = $request->user();

        $sale->load([
            'vehicle.brand:id,name',
            'vehicle.model:id,name',
            'customer',
            'reservation:id,reference_number,deposit_amount',
            'consignment:id,reference_number',
            'salesOfficer:id,name',
            'payments.paymentMethod:id,name',
            'payments.financialAccount:id,name',
            'payments.recorder:id,name',
            'discounts.approver:id,name',
            'handover.items',
            'handover.officer:id,name',
            'ownerSettlement:id,sale_id,reference_number,status,net_payable_to_owner,balance_due',
        ]);

        return Inertia::render('sales/Show', [
            'sale' => [
                'id' => $sale->id,
                'reference_number' => $sale->reference_number,
                'vehicle_id' => $sale->vehicle_id,
                'vehicle_reference' => $sale->vehicle->reference_number ?? '—',
                'vehicle_title' => $sale->vehicle?->displayName() ?? '—',
                'ownership_label' => $sale->ownership_type->label(),
                'customer_id' => $sale->customer_id,
                'customer_name' => $sale->customer->name ?? '—',
                'customer_phone' => $sale->customer?->phone,
                'reservation_reference' => $sale->reservation?->reference_number,
                'reservation_deposit' => $sale->reservation?->deposit_amount,
                'consignment_id' => $sale->consignment_id,
                'consignment_reference' => $sale->consignment?->reference_number,
                'list_price' => $sale->list_price,
                'discount_amount' => $sale->discount_amount,
                'final_price' => $sale->final_price,
                'amount_paid' => $sale->amount_paid,
                'balance_due' => $sale->balance_due,
                'is_installment' => $sale->is_installment,
                'installment_terms' => $sale->installment_terms,
                'is_fully_paid' => $sale->isFullyPaid(),
                'status' => $sale->status->value,
                'status_label' => $sale->status->label(),
                'status_variant' => $sale->status->badgeVariant(),
                'confirmed_at' => $sale->confirmed_at->toISOString(),
                'completed_at' => $sale->completed_at?->toISOString(),
                'cancelled_at' => $sale->cancelled_at?->toISOString(),
                'cancellation_reason' => $sale->cancellation_reason,
                'early_handover_reason' => $sale->early_handover_reason,
                'sales_officer_name' => $sale->salesOfficer->name ?? 'System',
                'payments' => $sale->payments->map(static fn (Payment $p): array => [
                    'id' => $p->id,
                    'reference_number' => $p->reference_number,
                    'payment_type_label' => $p->payment_type->label(),
                    'amount' => $p->amount,
                    'paid_at' => $p->paid_at->toDateString(),
                    'payment_method' => $p->paymentMethod->name ?? '—',
                    'financial_account' => $p->financialAccount->name ?? '—',
                    'external_reference' => $p->external_reference,
                    'recorded_by' => $p->recorder->name ?? 'System',
                ])->values()->all(),
                'handover' => $sale->handover !== null ? [
                    'id' => $sale->handover->id,
                    'reference_number' => $sale->handover->reference_number,
                    'recipient_name' => $sale->handover->recipient_name,
                    'recipient_phone' => $sale->handover->recipient_phone,
                    'odometer_reading' => $sale->handover->odometer_reading,
                    'fuel_level' => $sale->handover->fuel_level,
                    'handed_over_at' => $sale->handover->handed_over_at->toISOString(),
                    'officer_name' => $sale->handover->officer->name ?? 'System',
                    'items' => $sale->handover->items->map(static fn ($i): array => [
                        'item_name' => $i->item_name,
                        'is_included' => $i->is_included,
                        'remarks' => $i->remarks,
                    ])->values()->all(),
                ] : null,
                'owner_settlement' => $sale->ownerSettlement !== null ? [
                    'id' => $sale->ownerSettlement->id,
                    'reference_number' => $sale->ownerSettlement->reference_number,
                    'status_label' => $sale->ownerSettlement->status->label(),
                    'status_variant' => $sale->ownerSettlement->status->badgeVariant(),
                    'net_payable_to_owner' => $sale->ownerSettlement->net_payable_to_owner,
                    'balance_due' => $sale->ownerSettlement->balance_due,
                ] : null,
            ],
            'canRecordPayment' => $sale->status === SaleStatus::Confirmed && $user->can('payments.record'),
            'canRecordHandover' => $sale->status !== SaleStatus::Cancelled && $sale->handover === null && $user->can('handovers.create'),
            'canApproveEarlyHandover' => $user->can('handovers.approve_early_release'),
            'canCancelSale' => $sale->status !== SaleStatus::Cancelled && $user->can('sales.cancel'),
            'paymentMethods' => PaymentMethod::query()->active()->orderBy('name')->get(['id', 'name', 'requires_reference']),
            'financialAccounts' => FinancialAccount::query()->active()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function recordPayment(Request $request, Sale $sale): RedirectResponse
    {
        /** @var User $actor */
        $actor = $request->user();

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01', 'max:999999999999.99'],
            'payment_method_id' => ['required', 'integer', Rule::exists('payment_methods', 'id')],
            'financial_account_id' => ['required', 'integer', Rule::exists('financial_accounts', 'id')],
            'paid_at' => ['required', 'date'],
            'external_reference' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:500'],
            'idempotency_key' => ['nullable', 'string', 'max:100'],
        ]);

        $payment = $this->recordPayment->execute($sale, $validated, $actor);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "Customer payment {$payment->reference_number} saved successfully.",
        ]);

        return back();
    }

    public function recordHandover(Request $request, Sale $sale): RedirectResponse
    {
        /** @var User $actor */
        $actor = $request->user();

        $validated = $request->validate([
            'recipient_name' => ['required', 'string', 'max:150'],
            'recipient_phone' => ['required', 'string', 'max:30'],
            'recipient_id_number' => ['nullable', 'string', 'max:80'],
            'odometer_reading' => ['nullable', 'integer', 'min:0'],
            'fuel_level' => ['nullable', 'string', 'max:40'],
            'condition_summary' => ['nullable', 'string', 'max:1000'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'early_handover_reason' => ['nullable', 'string', 'max:500'],
            'items' => ['nullable', 'array'],
            'items.*.item_name' => ['required', 'string', 'max:120'],
            'items.*.is_included' => ['sometimes', 'boolean'],
            'items.*.remarks' => ['nullable', 'string', 'max:255'],
        ]);

        $handover = $this->recordHandover->executeForSale($sale, $validated, $actor);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "Vehicle handover {$handover->reference_number} recorded successfully.",
        ]);

        return back();
    }

    public function cancel(Request $request, Sale $sale): RedirectResponse
    {
        /** @var User $actor */
        $actor = $request->user();

        $validated = $request->validate([
            'cancellation_reason' => ['required', 'string', 'min:5', 'max:1000'],
            'payment_method_id' => ['required', 'integer', Rule::exists('payment_methods', 'id')],
            'financial_account_id' => ['required', 'integer', Rule::exists('financial_accounts', 'id')],
        ]);

        $this->cancelSale->execute($sale, $validated, $actor);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "Sale {$sale->reference_number} cancelled, refund logged, and vehicle returned to Available.",
        ]);

        return back();
    }

    public function receiptPdf(Sale $sale, Payment $payment): HttpResponse
    {
        abort_unless($payment->payable_id === $sale->id && $payment->payable_type === $sale->getMorphClass(), 404);
        $sale->load(['vehicle.brand', 'vehicle.model', 'customer']);
        $payment->load(['paymentMethod', 'financialAccount', 'recorder']);

        $pdf = Pdf::loadView('pdfs.payment-receipt', [
            'sale' => $sale,
            'payment' => $payment,
            'business' => $this->businessSettings->all(),
        ])->setPaper('a4');

        return $pdf->download("Receipt-{$payment->reference_number}.pdf");
    }

    public function agreementPdf(Sale $sale): HttpResponse
    {
        $sale->load(['vehicle.brand', 'vehicle.model', 'customer', 'salesOfficer', 'payments.paymentMethod']);

        $pdf = Pdf::loadView('pdfs.sales-agreement', [
            'sale' => $sale,
            'business' => $this->businessSettings->all(),
        ])->setPaper('a4');

        return $pdf->download("Sales-Agreement-{$sale->reference_number}.pdf");
    }

    public function handoverPdf(Sale $sale): HttpResponse
    {
        $sale->load(['vehicle.brand', 'vehicle.model', 'customer', 'handover.items', 'handover.officer']);
        abort_if($sale->handover === null, 404);

        $pdf = Pdf::loadView('pdfs.handover-certificate', [
            'handover' => $sale->handover,
            'parentReference' => $sale->reference_number,
            'transactionLabel' => 'Vehicle Sale Handover',
            'business' => $this->businessSettings->all(),
        ])->setPaper('a4');

        return $pdf->download("Handover-{$sale->handover->reference_number}.pdf");
    }
}
