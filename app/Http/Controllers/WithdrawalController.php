<?php

namespace App\Http\Controllers;

use App\Actions\Charges\ApplyTransactionChargeAction;
use App\Actions\Withdrawals\CompleteWithdrawalHandoverAction;
use App\Actions\Withdrawals\InitiateWithdrawalAction;
use App\Actions\Withdrawals\RecordWithdrawalPaymentAction;
use App\Enums\ChargeTransactionType;
use App\Enums\ConsignmentStatus;
use App\Enums\ExpenseBearer;
use App\Enums\WithdrawalStatus;
use App\Models\ChargeCategory;
use App\Models\Consignment;
use App\Models\FinancialAccount;
use App\Models\PaymentMethod;
use App\Models\User;
use App\Models\VehicleExpense;
use App\Models\WithdrawalRequest;
use App\Services\BusinessSettingService;
use App\Support\Money;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class WithdrawalController extends Controller
{
    public function __construct(
        private InitiateWithdrawalAction $initiateWithdrawal,
        private ApplyTransactionChargeAction $applyCharge,
        private RecordWithdrawalPaymentAction $recordPayment,
        private CompleteWithdrawalHandoverAction $completeHandover,
        private BusinessSettingService $businessSettings,
    ) {}

    public function index(Request $request): Response
    {
        $search = trim((string) $request->string('search'));
        $status = trim((string) $request->string('status'));
        $perPage = in_array($request->integer('per_page', 15), [10, 15, 25, 50], true)
            ? $request->integer('per_page', 15)
            : 15;

        $withdrawals = WithdrawalRequest::query()
            ->with(['vehicle.brand:id,name', 'vehicle.model:id,name', 'owner:id,name,phone', 'consignment:id,reference_number'])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($sub) use ($search): void {
                    $sub->where('reference_number', 'like', "%{$search}%")
                        ->orWhereHas('owner', fn ($q) => $q->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('vehicle', fn ($q) => $q->where('reference_number', 'like', "%{$search}%"));
                });
            })
            ->when($status !== '', fn ($q) => $q->where('status', $status))
            ->latest('requested_at')
            ->paginate($perPage)
            ->withQueryString()
            ->through(static fn (WithdrawalRequest $w): array => [
                'id' => $w->id,
                'reference_number' => $w->reference_number,
                'consignment_reference' => $w->consignment->reference_number ?? '—',
                'vehicle_title' => $w->vehicle?->displayName() ?? '—',
                'owner_name' => $w->owner->name ?? '—',
                'total_due' => $w->total_due,
                'amount_paid' => $w->amount_paid,
                'balance_due' => $w->balance_due,
                'status' => $w->status->value,
                'status_label' => $w->status->label(),
                'status_variant' => $w->status->badgeVariant(),
                'requested_at' => $w->requested_at->toISOString(),
            ]);

        $eligibleConsignments = Consignment::query()
            ->with(['vehicle.brand:id,name', 'vehicle.model:id,name', 'owner:id,name,phone'])
            ->whereIn('status', [ConsignmentStatus::Active->value, ConsignmentStatus::OnHold->value])
            ->latest('received_at')
            ->limit(10)
            ->get()
            ->map(static fn (Consignment $c): array => [
                'id' => $c->id,
                'reference_number' => $c->reference_number,
                'vehicle_title' => $c->vehicle?->displayName() ?? '—',
                'vehicle_reference' => $c->vehicle->reference_number ?? '—',
                'owner_name' => $c->owner->name ?? '—',
                'status_label' => $c->status->label(),
                'status_variant' => $c->status->badgeVariant(),
            ])
            ->values()
            ->all();

        return Inertia::render('withdrawals/Index', [
            'withdrawals' => $withdrawals,
            'statusOptions' => WithdrawalStatus::options(),
            'summary' => [
                'awaiting_payment_count' => WithdrawalRequest::query()->where('status', WithdrawalStatus::AwaitingPayment->value)->count(),
                'ready_for_handover_count' => WithdrawalRequest::query()->where('status', WithdrawalStatus::PaidReadyForHandover->value)->count(),
                'completed_count' => WithdrawalRequest::query()->where('status', WithdrawalStatus::Completed->value)->count(),
                'total_collected' => (string) WithdrawalRequest::query()->where('status', '!=', WithdrawalStatus::Cancelled->value)->sum('amount_paid'),
            ],
            'eligibleConsignments' => $eligibleConsignments,
            'filters' => [
                'search' => $search,
                'status' => $status,
                'per_page' => $perPage,
            ],
        ]);
    }

    public function create(Request $request): Response
    {
        $consignments = Consignment::query()
            ->with(['vehicle.brand:id,name', 'vehicle.model:id,name', 'owner:id,name'])
            ->whereIn('status', [ConsignmentStatus::Active->value, ConsignmentStatus::OnHold->value])
            ->orderByDesc('id')
            ->get()
            ->map(static function (Consignment $c): array {
                $unsettledOwnerExpenses = VehicleExpense::query()
                    ->where('consignment_id', $c->id)
                    ->where('borne_by', ExpenseBearer::OwnerDeductible->value)
                    ->whereNull('settled_in_owner_settlement_id')
                    ->whereNull('settled_in_withdrawal_id')
                    ->sum('amount');

                return [
                    'id' => $c->id,
                    'label' => "{$c->reference_number} — {$c->vehicle?->displayName()} ({$c->owner?->name})",
                    'owner_expenses_total' => (string) $unsettledOwnerExpenses,
                ];
            });

        return Inertia::render('withdrawals/Create', [
            'consignments' => $consignments,
            'chargeCategories' => ChargeCategory::query()->active()->orderBy('name')->get(['id', 'name', 'default_amount']),
            'preselectedConsignmentId' => $request->integer('consignment_id') ?: null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        /** @var User $actor */
        $actor = $request->user();

        $validated = $request->validate([
            'consignment_id' => ['required', 'integer', Rule::exists('consignments', 'id')],
            'reason' => ['required', 'string', 'min:5', 'max:1000'],
            'charges' => ['nullable', 'array'],
            'charges.*.charge_category_id' => ['nullable', 'integer', Rule::exists('charge_categories', 'id')],
            'charges.*.custom_label' => ['nullable', 'string', 'max:150'],
            'charges.*.amount' => ['nullable', 'numeric', 'min:0.01'],
            'charges.*.notes' => ['nullable', 'string', 'max:300'],
        ]);

        /** @var Consignment $consignment */
        $consignment = Consignment::query()->findOrFail($validated['consignment_id']);
        $withdrawal = $this->initiateWithdrawal->execute($consignment, $validated, $actor);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "Withdrawal request {$withdrawal->reference_number} initiated.",
        ]);

        return redirect()->route('withdrawals.show', $withdrawal);
    }

    public function show(Request $request, WithdrawalRequest $withdrawal): Response
    {
        /** @var User $user */
        $user = $request->user();

        $withdrawal->load([
            'vehicle.brand:id,name',
            'vehicle.model:id,name',
            'owner',
            'consignment:id,reference_number',
            'charges.category:id,name',
            'payments.paymentMethod:id,name',
            'payments.financialAccount:id,name',
            'handover.items',
            'handover.officer:id,name',
        ]);

        $ownerExpenses = VehicleExpense::query()
            ->where('consignment_id', $withdrawal->consignment_id)
            ->where('borne_by', ExpenseBearer::OwnerDeductible->value)
            ->where(function ($q) use ($withdrawal): void {
                $q->whereNull('settled_in_withdrawal_id')
                    ->orWhere('settled_in_withdrawal_id', $withdrawal->id);
            })
            ->whereNull('settled_in_owner_settlement_id')
            ->get(['id', 'reference_number', 'description', 'amount', 'expense_date']);

        return Inertia::render('withdrawals/Show', [
            'withdrawal' => [
                'id' => $withdrawal->id,
                'reference_number' => $withdrawal->reference_number,
                'consignment_id' => $withdrawal->consignment_id,
                'consignment_reference' => $withdrawal->consignment->reference_number ?? '—',
                'vehicle_id' => $withdrawal->vehicle_id,
                'vehicle_reference' => $withdrawal->vehicle->reference_number ?? '—',
                'vehicle_title' => $withdrawal->vehicle?->displayName() ?? '—',
                'owner_name' => $withdrawal->owner->name ?? '—',
                'owner_phone' => $withdrawal->owner?->phone,
                'reason' => $withdrawal->reason,
                'total_owner_expenses' => $withdrawal->total_owner_expenses,
                'total_withdrawal_charges' => $withdrawal->total_withdrawal_charges,
                'total_due' => $withdrawal->total_due,
                'amount_paid' => $withdrawal->amount_paid,
                'balance_due' => $withdrawal->balance_due,
                'is_cleared_for_handover' => $withdrawal->isClearedForHandover(),
                'status' => $withdrawal->status->value,
                'status_label' => $withdrawal->status->label(),
                'status_variant' => $withdrawal->status->badgeVariant(),
                'requested_at' => $withdrawal->requested_at->toISOString(),
                'completed_at' => $withdrawal->completed_at?->toISOString(),
                'owner_expenses' => $ownerExpenses,
                'charges' => $withdrawal->charges->map(static fn ($c): array => [
                    'id' => $c->id,
                    'label' => $c->displayLabel(),
                    'amount' => $c->amount,
                    'notes' => $c->notes,
                ])->values()->all(),
                'payments' => $withdrawal->payments->map(static fn ($p): array => [
                    'id' => $p->id,
                    'reference_number' => $p->reference_number,
                    'amount' => $p->amount,
                    'paid_at' => $p->paid_at->toDateString(),
                    'payment_method' => $p->paymentMethod->name ?? '—',
                    'financial_account' => $p->financialAccount->name ?? '—',
                ])->values()->all(),
                'handover' => $withdrawal->handover !== null ? [
                    'reference_number' => $withdrawal->handover->reference_number,
                    'recipient_name' => $withdrawal->handover->recipient_name,
                    'handed_over_at' => $withdrawal->handover->handed_over_at->toISOString(),
                ] : null,
            ],
            'canAddCharge' => $withdrawal->status === WithdrawalStatus::AwaitingPayment && $user->can('charges.apply'),
            'canRecordPayment' => $withdrawal->status === WithdrawalStatus::AwaitingPayment && $user->can('payments.record'),
            'canCompleteHandover' => $withdrawal->isClearedForHandover() && $user->can('withdrawals.complete'),
            'chargeCategories' => ChargeCategory::query()->active()->orderBy('name')->get(['id', 'name', 'default_amount']),
            'paymentMethods' => PaymentMethod::query()->active()->orderBy('name')->get(['id', 'name']),
            'financialAccounts' => FinancialAccount::query()->active()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function addCharge(Request $request, WithdrawalRequest $withdrawal): RedirectResponse
    {
        /** @var User $actor */
        $actor = $request->user();

        if ($withdrawal->status !== WithdrawalStatus::AwaitingPayment) {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'Cannot add charges after payment clearance or completion.']);

            return back();
        }

        $validated = $request->validate([
            'charge_category_id' => ['nullable', 'integer', Rule::exists('charge_categories', 'id')],
            'custom_label' => ['nullable', 'string', 'max:150'],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:999999999999.99'],
            'notes' => ['nullable', 'string', 'max:300'],
        ]);

        $charge = $this->applyCharge->execute($withdrawal, ChargeTransactionType::ConsignmentWithdrawal, $validated, $actor);

        $newCharges = Money::fromDecimal($withdrawal->total_withdrawal_charges)->add(Money::fromDecimal($charge->amount));
        $newTotal = Money::fromDecimal($withdrawal->total_owner_expenses)->add($newCharges);
        $newBalance = $newTotal->subtract(Money::fromDecimal($withdrawal->amount_paid))->max(Money::zero());

        $withdrawal->update([
            'total_withdrawal_charges' => $newCharges->toDecimal(),
            'total_due' => $newTotal->toDecimal(),
            'balance_due' => $newBalance->toDecimal(),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Withdrawal charge applied.']);

        return back();
    }

    public function recordPayment(Request $request, WithdrawalRequest $withdrawal): RedirectResponse
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
        ]);

        $payment = $this->recordPayment->execute($withdrawal, $validated, $actor);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "Withdrawal payment {$payment->reference_number} recorded.",
        ]);

        return back();
    }

    public function completeHandover(Request $request, WithdrawalRequest $withdrawal): RedirectResponse
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
            'items' => ['nullable', 'array'],
            'items.*.item_name' => ['required', 'string', 'max:120'],
            'items.*.is_included' => ['sometimes', 'boolean'],
            'items.*.remarks' => ['nullable', 'string', 'max:255'],
        ]);

        $handover = $this->completeHandover->execute($withdrawal, $validated, $actor);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "Withdrawal completed and handover {$handover->reference_number} recorded.",
        ]);

        return back();
    }

    public function statementPdf(WithdrawalRequest $withdrawal): HttpResponse
    {
        $withdrawal->load(['vehicle.brand', 'vehicle.model', 'owner', 'consignment', 'charges.category', 'payments.paymentMethod']);

        $pdf = Pdf::loadView('pdfs.withdrawal-statement', [
            'withdrawal' => $withdrawal,
            'business' => $this->businessSettings->all(),
        ])->setPaper('a4');

        return $pdf->download("Withdrawal-Statement-{$withdrawal->reference_number}.pdf");
    }
}
