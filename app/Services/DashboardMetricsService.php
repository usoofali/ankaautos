<?php

namespace App\Services;

use App\Enums\ConsignmentStatus;
use App\Enums\RevenueBucket;
use App\Enums\SaleStatus;
use App\Enums\SettlementStatus;
use App\Enums\TransactionDirection;
use App\Enums\VehicleStatus;
use App\Enums\WithdrawalStatus;
use App\Models\ActivityLog;
use App\Models\Consignment;
use App\Models\FinancialTransaction;
use App\Models\OwnerSettlement;
use App\Models\Sale;
use App\Models\Vehicle;
use App\Models\VehicleExpense;
use App\Models\WithdrawalRequest;
use App\Support\Money;

class DashboardMetricsService
{
    /**
     * @return array{
     *     total_vehicles: int,
     *     available_vehicles: int,
     *     reserved_vehicles: int,
     *     active_consignments: int,
     *     monthly_sales_count: int,
     *     pending_withdrawals: int,
     *     recent_sales: list<array{id: int, reference_number: string, vehicle_title: string, customer_name: string, final_price: string, status_label: string, status_variant: string, confirmed_at: string}>,
     *     recent_activities: list<array{id: int, action: string, description: string, actor: string, created_at: string|null}>
     * }
     */
    public function operational(): array
    {
        $monthStart = now()->startOfMonth();

        $recentSales = array_values(Sale::query()
            ->with(['vehicle.brand:id,name', 'vehicle.model:id,name', 'customer:id,name'])
            ->latest('confirmed_at')
            ->limit(6)
            ->get()
            ->map(static fn (Sale $s): array => [
                'id' => $s->id,
                'reference_number' => $s->reference_number,
                'vehicle_title' => $s->vehicle?->displayName() ?? '—',
                'customer_name' => $s->customer->name ?? '—',
                'final_price' => $s->final_price,
                'status_label' => $s->status->label(),
                'status_variant' => $s->status->badgeVariant(),
                'confirmed_at' => (string) $s->confirmed_at->toISOString(),
            ])
            ->all());

        $recentActivities = array_values(ActivityLog::query()
            ->with('user:id,name')
            ->latest('created_at')
            ->limit(8)
            ->get()
            ->map(static fn (ActivityLog $a): array => [
                'id' => $a->id,
                'action' => $a->action,
                'description' => $a->description,
                'actor' => $a->user->name ?? 'System',
                'created_at' => $a->created_at?->toISOString(),
            ])
            ->all());

        return [
            'total_vehicles' => Vehicle::query()->where('status', '!=', VehicleStatus::Archived->value)->count(),
            'available_vehicles' => Vehicle::query()->where('status', VehicleStatus::Available->value)->count(),
            'reserved_vehicles' => Vehicle::query()->where('status', VehicleStatus::Reserved->value)->count(),
            'active_consignments' => Consignment::query()->where('status', ConsignmentStatus::Active->value)->count(),
            'monthly_sales_count' => Sale::query()
                ->where('status', '!=', SaleStatus::Cancelled->value)
                ->where('confirmed_at', '>=', $monthStart)
                ->count(),
            'pending_withdrawals' => WithdrawalRequest::query()
                ->whereIn('status', [WithdrawalStatus::AwaitingPayment->value, WithdrawalStatus::PaidReadyForHandover->value])
                ->count(),
            'recent_sales' => $recentSales,
            'recent_activities' => $recentActivities,
        ];
    }

    /**
     * @return array{
     *     monthly_sales_value: string,
     *     company_net_cashflow: string,
     *     customer_receivables: string,
     *     owner_payables: string,
     *     monthly_expenses: string,
     *     third_party_held: string,
     *     locked_settlements_count: int
     * }
     */
    public function financial(): array
    {
        $monthStart = now()->startOfMonth();

        $companyInflow = Money::fromDecimal((string) FinancialTransaction::query()
            ->where('revenue_bucket', RevenueBucket::Company->value)
            ->where('direction', TransactionDirection::Inflow->value)
            ->sum('amount'));

        $companyOutflow = Money::fromDecimal((string) FinancialTransaction::query()
            ->where('revenue_bucket', RevenueBucket::Company->value)
            ->where('direction', TransactionDirection::Outflow->value)
            ->sum('amount'));

        $ownerInflow = Money::fromDecimal((string) FinancialTransaction::query()
            ->where('revenue_bucket', RevenueBucket::ThirdPartyOwner->value)
            ->where('direction', TransactionDirection::Inflow->value)
            ->sum('amount'));

        $ownerOutflow = Money::fromDecimal((string) FinancialTransaction::query()
            ->where('revenue_bucket', RevenueBucket::ThirdPartyOwner->value)
            ->where('direction', TransactionDirection::Outflow->value)
            ->sum('amount'));

        return [
            'monthly_sales_value' => (string) Sale::query()
                ->where('status', '!=', SaleStatus::Cancelled->value)
                ->where('confirmed_at', '>=', $monthStart)
                ->sum('final_price'),
            'company_net_cashflow' => $companyInflow->subtract($companyOutflow)->toDecimal(),
            'customer_receivables' => (string) Sale::query()
                ->where('status', SaleStatus::Confirmed->value)
                ->sum('balance_due'),
            'owner_payables' => (string) OwnerSettlement::query()
                ->whereNotIn('status', [SettlementStatus::Settled->value, SettlementStatus::Voided->value])
                ->sum('balance_due'),
            'monthly_expenses' => (string) VehicleExpense::query()
                ->where('expense_date', '>=', $monthStart->toDateString())
                ->sum('amount'),
            'third_party_held' => $ownerInflow->subtract($ownerOutflow)->toDecimal(),
            'locked_settlements_count' => OwnerSettlement::query()
                ->where('status', SettlementStatus::LockedAwaitingCustomerPayment->value)
                ->count(),
        ];
    }
}
