<?php

namespace App\Http\Controllers;

use App\Actions\Expenses\RecordExpensePaymentAction;
use App\Actions\Expenses\RecordVehicleExpenseAction;
use App\Enums\ExpenseBearer;
use App\Enums\ExpensePaymentStatus;
use App\Http\Requests\Expenses\RecordExpensePaymentRequest;
use App\Http\Requests\Expenses\StoreVehicleExpenseRequest;
use App\Models\ExpenseCategory;
use App\Models\FinancialAccount;
use App\Models\PaymentMethod;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleExpense;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class VehicleExpenseController extends Controller
{
    public function __construct(
        private RecordVehicleExpenseAction $recordExpense,
        private RecordExpensePaymentAction $recordPayment,
    ) {}

    public function index(Request $request): Response
    {
        $search = trim((string) $request->string('search'));
        $bearer = trim((string) $request->string('borne_by'));
        $status = trim((string) $request->string('payment_status'));
        $categoryId = $request->integer('category_id') ?: null;
        $perPage = in_array($request->integer('per_page', 15), [10, 15, 25, 50], true)
            ? $request->integer('per_page', 15)
            : 15;
        $sort = in_array($request->string('sort')->toString(), ['reference_number', 'amount', 'expense_date', 'payment_status'], true)
            ? $request->string('sort')->toString()
            : 'expense_date';
        $direction = $request->string('direction')->toString() === 'asc' ? 'asc' : 'desc';

        $expenses = VehicleExpense::query()
            ->with(['vehicle.brand:id,name', 'vehicle.model:id,name', 'category:id,name', 'recorder:id,name'])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($sub) use ($search): void {
                    $sub->where('reference_number', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhere('vendor_name', 'like', "%{$search}%")
                        ->orWhereHas('vehicle', fn ($q) => $q->where('reference_number', 'like', "%{$search}%"));
                });
            })
            ->when($bearer !== '', fn ($q) => $q->where('borne_by', $bearer))
            ->when($status !== '', fn ($q) => $q->where('payment_status', $status))
            ->when($categoryId !== null, fn ($q) => $q->where('expense_category_id', $categoryId))
            ->orderBy($sort, $direction)
            ->paginate($perPage)
            ->withQueryString()
            ->through(static fn (VehicleExpense $expense): array => [
                'id' => $expense->id,
                'reference_number' => $expense->reference_number,
                'vehicle_id' => $expense->vehicle_id,
                'vehicle_reference' => $expense->vehicle->reference_number ?? '—',
                'vehicle_title' => $expense->vehicle?->displayName() ?? '—',
                'category_name' => $expense->category->name ?? '—',
                'borne_by' => $expense->borne_by->value,
                'borne_by_label' => $expense->borne_by->label(),
                'borne_by_variant' => $expense->borne_by->badgeVariant(),
                'description' => $expense->description,
                'amount' => $expense->amount,
                'amount_paid' => $expense->amount_paid,
                'balance_due' => $expense->balanceDue()->toDecimal(),
                'payment_status' => $expense->payment_status->value,
                'payment_status_label' => $expense->payment_status->label(),
                'payment_status_variant' => $expense->payment_status->badgeVariant(),
                'expense_date' => $expense->expense_date->toDateString(),
                'vendor_name' => $expense->vendor_name,
                'is_deducted' => $expense->isDeductedFromOwner(),
            ]);

        return Inertia::render('expenses/Index', [
            'expenses' => $expenses,
            'categories' => ExpenseCategory::query()->active()->orderBy('name')->get(['id', 'name']),
            'bearerOptions' => ExpenseBearer::options(),
            'statusOptions' => ExpensePaymentStatus::options(),
            'summary' => [
                'total_amount' => (string) VehicleExpense::query()->sum('amount'),
                'company_total' => (string) VehicleExpense::query()->where('borne_by', ExpenseBearer::Company->value)->sum('amount'),
                'owner_deductible_total' => (string) VehicleExpense::query()->where('borne_by', ExpenseBearer::OwnerDeductible->value)->sum('amount'),
                'unpaid_count' => VehicleExpense::query()->where('payment_status', '!=', ExpensePaymentStatus::Paid->value)->count(),
            ],
            'filters' => [
                'search' => $search,
                'borne_by' => $bearer,
                'payment_status' => $status,
                'category_id' => $categoryId ? (string) $categoryId : '',
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

        $vehicles = Vehicle::query()
            ->with(['brand:id,name', 'model:id,name', 'activeConsignment:id,vehicle_id'])
            ->orderByDesc('id')
            ->get()
            ->map(static fn (Vehicle $vehicle): array => [
                'id' => $vehicle->id,
                'label' => "{$vehicle->reference_number} — {$vehicle->displayName()}",
                'ownership_type' => $vehicle->ownership_type->value,
                'has_consignment' => $vehicle->activeConsignment !== null,
            ]);

        return Inertia::render('expenses/Create', [
            'vehicles' => $vehicles,
            'categories' => ExpenseCategory::query()->active()->orderBy('name')->get(['id', 'name']),
            'bearerOptions' => ExpenseBearer::options(),
            'paymentMethods' => PaymentMethod::query()->active()->orderBy('name')->get(['id', 'name', 'requires_reference']),
            'financialAccounts' => FinancialAccount::query()->active()->orderBy('name')->get(['id', 'name']),
            'canPayImmediately' => $user->can('expenses.pay'),
            'preselectedVehicleId' => $request->integer('vehicle_id') ?: null,
        ]);
    }

    public function store(StoreVehicleExpenseRequest $request): RedirectResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $expense = $this->recordExpense->execute(
            $request->validated(),
            $actor,
            $request->file('receipt'),
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "Expense {$expense->reference_number} recorded successfully.",
        ]);

        return redirect()->route('expenses.show', $expense);
    }

    public function show(Request $request, VehicleExpense $expense): Response
    {
        /** @var User $user */
        $user = $request->user();

        $expense->load([
            'vehicle.brand:id,name',
            'vehicle.model:id,name',
            'consignment:id,reference_number',
            'category:id,name',
            'recorder:id,name',
            'payments.paymentMethod:id,name',
            'payments.financialAccount:id,name',
            'payments.recorder:id,name',
        ]);

        return Inertia::render('expenses/Show', [
            'expense' => [
                'id' => $expense->id,
                'reference_number' => $expense->reference_number,
                'vehicle_id' => $expense->vehicle_id,
                'vehicle_reference' => $expense->vehicle->reference_number ?? '—',
                'vehicle_title' => $expense->vehicle?->displayName() ?? '—',
                'consignment_id' => $expense->consignment_id,
                'consignment_reference' => $expense->consignment?->reference_number,
                'category_name' => $expense->category->name ?? '—',
                'borne_by' => $expense->borne_by->value,
                'borne_by_label' => $expense->borne_by->label(),
                'borne_by_variant' => $expense->borne_by->badgeVariant(),
                'description' => $expense->description,
                'amount' => $expense->amount,
                'amount_paid' => $expense->amount_paid,
                'balance_due' => $expense->balanceDue()->toDecimal(),
                'payment_status' => $expense->payment_status->value,
                'payment_status_label' => $expense->payment_status->label(),
                'payment_status_variant' => $expense->payment_status->badgeVariant(),
                'expense_date' => $expense->expense_date->toDateString(),
                'vendor_name' => $expense->vendor_name,
                'has_receipt' => $expense->receipt_path !== null,
                'receipt_original_name' => $expense->receipt_original_name,
                'is_deducted' => $expense->isDeductedFromOwner(),
                'recorded_by_name' => $expense->recorder->name ?? 'System',
                'payments' => $expense->payments->map(static fn ($p): array => [
                    'id' => $p->id,
                    'reference_number' => $p->reference_number,
                    'amount' => $p->amount,
                    'paid_at' => $p->paid_at->toDateString(),
                    'payment_method' => $p->paymentMethod->name ?? '—',
                    'financial_account' => $p->financialAccount->name ?? '—',
                    'payment_reference' => $p->payment_reference,
                    'notes' => $p->notes,
                    'recorded_by' => $p->recorder->name ?? 'System',
                ])->values()->all(),
            ],
            'canRecordPayment' => $user->can('expenses.pay') && $expense->balanceDue()->isPositive(),
            'paymentMethods' => PaymentMethod::query()->active()->orderBy('name')->get(['id', 'name', 'requires_reference']),
            'financialAccounts' => FinancialAccount::query()->active()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function recordPayment(RecordExpensePaymentRequest $request, VehicleExpense $expense): RedirectResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $payment = $this->recordPayment->execute($expense, $request->validated(), $actor);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "Expense payment {$payment->reference_number} saved successfully.",
        ]);

        return back();
    }

    public function downloadReceipt(VehicleExpense $expense): StreamedResponse
    {
        if ($expense->receipt_path === null || ! Storage::disk('local')->exists($expense->receipt_path)) {
            abort(404);
        }

        return Storage::disk('local')->download(
            $expense->receipt_path,
            $expense->receipt_original_name ?? "receipt-{$expense->reference_number}",
        );
    }
}
