<?php

namespace App\Http\Controllers;

use App\Enums\FinancialTransactionType;
use App\Enums\RevenueBucket;
use App\Enums\SaleStatus;
use App\Enums\SettlementStatus;
use App\Enums\TransactionDirection;
use App\Models\FinancialAccount;
use App\Models\FinancialAdjustment;
use App\Models\FinancialTransaction;
use App\Models\OwnerSettlement;
use App\Models\Sale;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\FinancialLedgerService;
use App\Services\ReferenceNumberGenerator;
use App\Support\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class FinanceController extends Controller
{
    public function __construct(
        private ReferenceNumberGenerator $referenceGenerator,
        private FinancialLedgerService $ledger,
        private AuditLogger $auditLogger,
    ) {}

    public function index(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();

        $search = trim((string) $request->string('search'));
        $bucket = trim((string) $request->string('bucket'));
        $direction = trim((string) $request->string('direction'));
        $perPage = in_array($request->integer('per_page', 15), [10, 15, 25, 50], true)
            ? $request->integer('per_page', 15)
            : 15;

        $transactions = FinancialTransaction::query()
            ->with(['financialAccount:id,name', 'paymentMethod:id,name', 'vehicle:id,reference_number', 'recorder:id,name'])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($sub) use ($search): void {
                    $sub->where('reference_number', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when($bucket !== '', fn ($q) => $q->where('revenue_bucket', $bucket))
            ->when($direction !== '', fn ($q) => $q->where('direction', $direction))
            ->latest('transacted_at')
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString()
            ->through(static fn (FinancialTransaction $tx): array => [
                'id' => $tx->id,
                'reference_number' => $tx->reference_number,
                'type_label' => $tx->transaction_type->label(),
                'direction' => $tx->direction->value,
                'direction_label' => $tx->direction->label(),
                'bucket' => $tx->revenue_bucket->value,
                'bucket_label' => $tx->revenue_bucket->label(),
                'bucket_variant' => $tx->revenue_bucket->badgeVariant(),
                'amount' => $tx->amount,
                'account_name' => $tx->financialAccount->name ?? '—',
                'method_name' => $tx->paymentMethod->name ?? '—',
                'vehicle_reference' => $tx->vehicle?->reference_number,
                'description' => $tx->description,
                'transacted_at' => $tx->transacted_at->toISOString(),
                'recorded_by' => $tx->recorder->name ?? 'System',
            ]);

        $companyInflow = Money::fromDecimal((string) FinancialTransaction::query()
            ->where('revenue_bucket', RevenueBucket::Company->value)
            ->where('direction', TransactionDirection::Inflow->value)
            ->sum('amount'));

        $companyOutflow = Money::fromDecimal((string) FinancialTransaction::query()
            ->where('revenue_bucket', RevenueBucket::Company->value)
            ->where('direction', TransactionDirection::Outflow->value)
            ->sum('amount'));

        $ownerHeldInflow = Money::fromDecimal((string) FinancialTransaction::query()
            ->where('revenue_bucket', RevenueBucket::ThirdPartyOwner->value)
            ->where('direction', TransactionDirection::Inflow->value)
            ->sum('amount'));

        $ownerHeldOutflow = Money::fromDecimal((string) FinancialTransaction::query()
            ->where('revenue_bucket', RevenueBucket::ThirdPartyOwner->value)
            ->where('direction', TransactionDirection::Outflow->value)
            ->sum('amount'));

        return Inertia::render('finance/Index', [
            'transactions' => $transactions,
            'summary' => [
                'company_inflow' => $companyInflow->toDecimal(),
                'company_outflow' => $companyOutflow->toDecimal(),
                'company_net' => $companyInflow->subtract($companyOutflow)->toDecimal(),
                'third_party_held_balance' => $ownerHeldInflow->subtract($ownerHeldOutflow)->toDecimal(),
                'customer_receivables' => (string) Sale::query()->where('status', SaleStatus::Confirmed->value)->sum('balance_due'),
                'owner_payables' => (string) OwnerSettlement::query()->whereNotIn('status', [SettlementStatus::Settled->value, SettlementStatus::Voided->value])->sum('balance_due'),
            ],
            'canAdjust' => $user->can('finance.adjust'),
            'financialAccounts' => FinancialAccount::query()->active()->orderBy('name')->get(['id', 'name']),
            'filters' => [
                'search' => $search,
                'bucket' => $bucket,
                'direction' => $direction,
                'per_page' => $perPage,
            ],
        ]);
    }

    public function storeAdjustment(Request $request): RedirectResponse
    {
        /** @var User $actor */
        $actor = $request->user();

        $validated = $request->validate([
            'revenue_bucket' => ['required', Rule::enum(RevenueBucket::class)],
            'direction' => ['required', Rule::enum(TransactionDirection::class)],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:999999999999.99'],
            'financial_account_id' => ['nullable', 'integer', Rule::exists('financial_accounts', 'id')],
            'adjusted_at' => ['required', 'date'],
            'reason' => ['required', 'string', 'min:5', 'max:1000'],
        ]);

        $amount = Money::fromDecimal((string) $validated['amount']);
        $bucket = RevenueBucket::from((string) $validated['revenue_bucket']);
        $direction = TransactionDirection::from((string) $validated['direction']);

        DB::transaction(function () use ($validated, $actor, $amount, $bucket, $direction): void {
            /** @var FinancialAdjustment $adjustment */
            $adjustment = FinancialAdjustment::query()->create([
                'reference_number' => $this->referenceGenerator->generate('ADJ'),
                'revenue_bucket' => $bucket,
                'direction' => $direction,
                'amount' => $amount->toDecimal(),
                'financial_account_id' => $validated['financial_account_id'] ?? null,
                'reason' => (string) $validated['reason'],
                'adjusted_at' => $validated['adjusted_at'],
                'approved_by' => $actor->id,
                'created_at' => now(),
            ]);

            $this->ledger->post(
                type: FinancialTransactionType::FinancialAdjustment,
                direction: $direction,
                bucket: $bucket,
                amount: $amount,
                description: "Financial adjustment {$adjustment->reference_number}: {$adjustment->reason}",
                financialAccountId: $adjustment->financial_account_id,
                source: $adjustment,
                transactedAt: Carbon::parse((string) $validated['adjusted_at']),
                actor: $actor,
            );

            $this->auditLogger->audit(
                event: 'finance.adjustment_recorded',
                auditable: $adjustment,
                newValues: $adjustment->toArray(),
                reason: $adjustment->reason,
                actor: $actor,
            );
        });

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Authorized financial adjustment posted to the ledger.',
        ]);

        return back();
    }
}
