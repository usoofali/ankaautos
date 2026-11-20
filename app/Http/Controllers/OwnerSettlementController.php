<?php

namespace App\Http\Controllers;

use App\Actions\Settlements\RecordOwnerPayoutAction;
use App\Enums\SettlementStatus;
use App\Models\FinancialAccount;
use App\Models\OwnerSettlement;
use App\Models\PaymentMethod;
use App\Models\User;
use App\Services\BusinessSettingService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class OwnerSettlementController extends Controller
{
    public function __construct(
        private RecordOwnerPayoutAction $recordPayout,
        private BusinessSettingService $businessSettings,
    ) {}

    public function index(Request $request): Response
    {
        $search = trim((string) $request->string('search'));
        $status = trim((string) $request->string('status'));
        $perPage = in_array($request->integer('per_page', 15), [10, 15, 25, 50], true)
            ? $request->integer('per_page', 15)
            : 15;

        $settlements = OwnerSettlement::query()
            ->with(['vehicle.brand:id,name', 'vehicle.model:id,name', 'owner:id,name,phone', 'sale:id,reference_number,amount_paid,balance_due'])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($sub) use ($search): void {
                    $sub->where('reference_number', 'like', "%{$search}%")
                        ->orWhereHas('owner', fn ($q) => $q->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('vehicle', fn ($q) => $q->where('reference_number', 'like', "%{$search}%"));
                });
            })
            ->when($status !== '', fn ($q) => $q->where('status', $status))
            ->latest()
            ->paginate($perPage)
            ->withQueryString()
            ->through(static fn (OwnerSettlement $s): array => [
                'id' => $s->id,
                'reference_number' => $s->reference_number,
                'sale_reference' => $s->sale->reference_number ?? '—',
                'vehicle_title' => $s->vehicle?->displayName() ?? '—',
                'owner_name' => $s->owner->name ?? '—',
                'gross_sale_price' => $s->gross_sale_price,
                'commission_amount' => $s->commission_amount,
                'net_payable_to_owner' => $s->net_payable_to_owner,
                'amount_paid' => $s->amount_paid,
                'balance_due' => $s->balance_due,
                'status' => $s->status->value,
                'status_label' => $s->status->label(),
                'status_variant' => $s->status->badgeVariant(),
                'created_at' => $s->created_at?->toISOString(),
            ]);

        return Inertia::render('settlements/Index', [
            'settlements' => $settlements,
            'statusOptions' => SettlementStatus::options(),
            'summary' => [
                'total_payable' => (string) OwnerSettlement::query()->where('status', '!=', SettlementStatus::Voided->value)->sum('net_payable_to_owner'),
                'total_disbursed' => (string) OwnerSettlement::query()->where('status', '!=', SettlementStatus::Voided->value)->sum('amount_paid'),
                'outstanding_balance' => (string) OwnerSettlement::query()->whereNotIn('status', [SettlementStatus::Settled->value, SettlementStatus::Voided->value])->sum('balance_due'),
                'locked_count' => OwnerSettlement::query()->where('status', SettlementStatus::LockedAwaitingCustomerPayment->value)->count(),
            ],
            'filters' => [
                'search' => $search,
                'status' => $status,
                'per_page' => $perPage,
            ],
        ]);
    }

    public function show(Request $request, OwnerSettlement $settlement): Response
    {
        /** @var User $user */
        $user = $request->user();

        $settlement->load([
            'vehicle.brand:id,name',
            'vehicle.model:id,name',
            'owner',
            'consignment:id,reference_number',
            'sale:id,reference_number,final_price,amount_paid,balance_due,status',
            'items',
            'payments.paymentMethod:id,name',
            'payments.financialAccount:id,name',
            'payments.recorder:id,name',
        ]);

        $customerFullyPaid = $settlement->sale?->isFullyPaid() ?? false;

        return Inertia::render('settlements/Show', [
            'settlement' => [
                'id' => $settlement->id,
                'reference_number' => $settlement->reference_number,
                'consignment_id' => $settlement->consignment_id,
                'consignment_reference' => $settlement->consignment->reference_number ?? '—',
                'sale_id' => $settlement->sale_id,
                'sale_reference' => $settlement->sale->reference_number ?? '—',
                'customer_amount_paid' => $settlement->sale->amount_paid ?? '0.00',
                'customer_balance_due' => $settlement->sale->balance_due ?? '0.00',
                'customer_fully_paid' => $customerFullyPaid,
                'vehicle_title' => $settlement->vehicle?->displayName() ?? '—',
                'vehicle_reference' => $settlement->vehicle->reference_number ?? '—',
                'owner_name' => $settlement->owner->name ?? '—',
                'owner_phone' => $settlement->owner?->phone,
                'owner_bank_name' => $settlement->owner?->bank_name,
                'owner_bank_account_name' => $settlement->owner?->bank_account_name,
                'owner_bank_account_number' => $settlement->owner?->bank_account_number,
                'gross_sale_price' => $settlement->gross_sale_price,
                'commission_amount' => $settlement->commission_amount,
                'total_expense_deductions' => $settlement->total_expense_deductions,
                'total_charge_deductions' => $settlement->total_charge_deductions,
                'net_payable_to_owner' => $settlement->net_payable_to_owner,
                'amount_paid' => $settlement->amount_paid,
                'balance_due' => $settlement->balance_due,
                'status' => $settlement->status->value,
                'status_label' => $settlement->status->label(),
                'status_variant' => $settlement->status->badgeVariant(),
                'settled_at' => $settlement->settled_at?->toISOString(),
                'items' => $settlement->items->map(static fn ($i): array => [
                    'id' => $i->id,
                    'item_type' => $i->item_type->value,
                    'type_label' => $i->item_type->label(),
                    'label' => $i->label,
                    'amount' => $i->amount,
                ])->values()->all(),
                'payments' => $settlement->payments->map(static fn ($p): array => [
                    'id' => $p->id,
                    'reference_number' => $p->reference_number,
                    'amount' => $p->amount,
                    'paid_at' => $p->paid_at->toDateString(),
                    'payment_method' => $p->paymentMethod->name ?? '—',
                    'financial_account' => $p->financialAccount->name ?? '—',
                    'payment_reference' => $p->payment_reference,
                    'recorded_by' => $p->recorder->name ?? 'System',
                ])->values()->all(),
            ],
            'canDisburse' => $customerFullyPaid
                && ! in_array($settlement->status, [SettlementStatus::Settled, SettlementStatus::Voided], true)
                && $user->can('settlements.process'),
            'paymentMethods' => PaymentMethod::query()->active()->orderBy('name')->get(['id', 'name']),
            'financialAccounts' => FinancialAccount::query()->active()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function recordPayout(Request $request, OwnerSettlement $settlement): RedirectResponse
    {
        /** @var User $actor */
        $actor = $request->user();

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01', 'max:999999999999.99'],
            'payment_method_id' => ['required', 'integer', Rule::exists('payment_methods', 'id')],
            'financial_account_id' => ['required', 'integer', Rule::exists('financial_accounts', 'id')],
            'paid_at' => ['required', 'date'],
            'payment_reference' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:500'],
            'idempotency_key' => ['nullable', 'string', 'max:100'],
        ]);

        $payment = $this->recordPayout->execute($settlement, $validated, $actor);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "Owner payout {$payment->reference_number} disbursed successfully.",
        ]);

        return back();
    }

    public function statementPdf(OwnerSettlement $settlement): HttpResponse
    {
        $settlement->load(['vehicle.brand', 'vehicle.model', 'owner', 'consignment', 'sale', 'items', 'payments.paymentMethod']);

        $pdf = Pdf::loadView('pdfs.settlement-statement', [
            'settlement' => $settlement,
            'business' => $this->businessSettings->all(),
        ])->setPaper('a4');

        return $pdf->download("Owner-Settlement-{$settlement->reference_number}.pdf");
    }
}
