<?php

namespace App\Services;

use App\Enums\OwnershipType;
use App\Enums\SaleStatus;
use App\Models\Consignment;
use App\Models\OwnerSettlement;
use App\Models\Sale;
use App\Models\Vehicle;
use App\Models\VehicleExpense;
use App\Models\WithdrawalRequest;
use App\Support\Money;

class ReportService
{
    /**
     * Catalog of all 8 SSD reports.
     *
     * @return list<array{slug: string, title: string, description: string, category: 'operational'|'financial'}>
     */
    public function catalog(): array
    {
        return [
            [
                'slug' => 'inventory',
                'title' => 'Vehicle Inventory Report',
                'description' => 'Complete listing of all dealership vehicles with ownership, status, and pricing.',
                'category' => 'operational',
            ],
            [
                'slug' => 'company-owned',
                'title' => 'Company-Owned Vehicles Report',
                'description' => 'Dealership-acquired stock with acquisition cost, advertised price, and current status.',
                'category' => 'operational',
            ],
            [
                'slug' => 'consignment',
                'title' => 'Consignment Vehicles Report',
                'description' => 'Third-party consigned vehicles with owner details, commission structure, and status.',
                'category' => 'operational',
            ],
            [
                'slug' => 'sales',
                'title' => 'Sales Transactions Report',
                'description' => 'All confirmed, completed, and cancelled sales with collections and receivables.',
                'category' => 'operational',
            ],
            [
                'slug' => 'expenses',
                'title' => 'Vehicle Expenses Report',
                'description' => 'Vehicle expenses segmented by Company vs. Owner-Deductible bearer and payment status.',
                'category' => 'financial',
            ],
            [
                'slug' => 'withdrawals',
                'title' => 'Consignment Withdrawals Report',
                'description' => 'Owner withdrawals showing expense recoveries, withdrawal charges, and clearance totals.',
                'category' => 'financial',
            ],
            [
                'slug' => 'settlements',
                'title' => 'Owner Settlements Report',
                'description' => 'Consignment settlements detailing commissions, deductions, disbursements, and balances.',
                'category' => 'financial',
            ],
            [
                'slug' => 'profitability',
                'title' => 'Vehicle Profitability & Margin Report',
                'description' => 'Net dealership margin on sold company vehicles (sale - purchase - company expenses) and consignment commissions.',
                'category' => 'financial',
            ],
        ];
    }

    /**
     * @return array{
     *     slug: string,
     *     title: string,
     *     description: string,
     *     category: 'operational'|'financial',
     *     headers: list<string>,
     *     rows: list<list<string>>,
     *     summary: list<array{label: string, value: string}>
     * }
     */
    public function generate(string $slug, ?string $from = null, ?string $to = null): array
    {
        return match ($slug) {
            'inventory' => $this->inventoryReport($from, $to),
            'company-owned' => $this->companyOwnedReport($from, $to),
            'consignment' => $this->consignmentReport($from, $to),
            'sales' => $this->salesReport($from, $to),
            'expenses' => $this->expensesReport($from, $to),
            'withdrawals' => $this->withdrawalsReport($from, $to),
            'settlements' => $this->settlementsReport($from, $to),
            'profitability' => $this->profitabilityReport($from, $to),
            default => abort(404),
        };
    }

    /**
     * @return array{slug: string, title: string, description: string, category: 'operational'|'financial', headers: list<string>, rows: list<list<string>>, summary: list<array{label: string, value: string}>}
     */
    private function inventoryReport(?string $from, ?string $to): array
    {
        $vehicles = Vehicle::query()
            ->with(['brand:id,name', 'model:id,name'])
            ->when($from, fn ($q) => $q->whereDate('created_at', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('created_at', '<=', $to))
            ->orderByDesc('id')
            ->get();

        $totalValue = Money::zero();
        $rows = [];
        foreach ($vehicles as $v) {
            $price = $v->advertised_price !== null ? Money::fromDecimal($v->advertised_price) : Money::zero();
            $totalValue = $totalValue->add($price);
            $rows[] = [
                $v->reference_number,
                $v->displayName(),
                $v->ownership_type->label(),
                $v->status->label(),
                $v->advertised_price !== null ? Money::fromDecimal($v->advertised_price)->format() : '—',
                $v->created_at?->toDateString() ?? '—',
            ];
        }

        return [
            'slug' => 'inventory',
            'title' => 'Vehicle Inventory Report',
            'description' => 'Complete listing of registered dealership vehicles.',
            'category' => 'operational',
            'headers' => ['Reference', 'Vehicle', 'Ownership', 'Status', 'Advertised Price', 'Registered'],
            'rows' => $rows,
            'summary' => [
                ['label' => 'Total Vehicles', 'value' => (string) count($rows)],
                ['label' => 'Total Advertised Value', 'value' => $totalValue->format()],
            ],
        ];
    }

    /**
     * @return array{slug: string, title: string, description: string, category: 'operational'|'financial', headers: list<string>, rows: list<list<string>>, summary: list<array{label: string, value: string}>}
     */
    private function companyOwnedReport(?string $from, ?string $to): array
    {
        $vehicles = Vehicle::query()
            ->with(['brand:id,name', 'model:id,name'])
            ->where('ownership_type', OwnershipType::CompanyOwned->value)
            ->when($from, fn ($q) => $q->whereDate('created_at', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('created_at', '<=', $to))
            ->orderByDesc('id')
            ->get();

        $totalCost = Money::zero();
        $totalAdvertised = Money::zero();
        $rows = [];

        foreach ($vehicles as $v) {
            $cost = $v->acquisition_cost !== null ? Money::fromDecimal($v->acquisition_cost) : Money::zero();
            $adv = $v->advertised_price !== null ? Money::fromDecimal($v->advertised_price) : Money::zero();
            $totalCost = $totalCost->add($cost);
            $totalAdvertised = $totalAdvertised->add($adv);

            $rows[] = [
                $v->reference_number,
                $v->displayName(),
                $v->status->label(),
                $cost->format(),
                $adv->format(),
                $v->created_at?->toDateString() ?? '—',
            ];
        }

        return [
            'slug' => 'company-owned',
            'title' => 'Company-Owned Vehicles Report',
            'description' => 'Dealership-owned stock acquisition cost vs. advertised pricing.',
            'category' => 'operational',
            'headers' => ['Reference', 'Vehicle', 'Status', 'Purchase Price', 'Advertised Price', 'Registered'],
            'rows' => $rows,
            'summary' => [
                ['label' => 'Company Vehicles', 'value' => (string) count($rows)],
                ['label' => 'Total Acquisition Cost', 'value' => $totalCost->format()],
                ['label' => 'Total Advertised Value', 'value' => $totalAdvertised->format()],
            ],
        ];
    }

    /**
     * @return array{slug: string, title: string, description: string, category: 'operational'|'financial', headers: list<string>, rows: list<list<string>>, summary: list<array{label: string, value: string}>}
     */
    private function consignmentReport(?string $from, ?string $to): array
    {
        $consignments = Consignment::query()
            ->with(['vehicle.brand:id,name', 'vehicle.model:id,name', 'owner:id,name,phone'])
            ->when($from, fn ($q) => $q->whereDate('received_at', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('received_at', '<=', $to))
            ->latest('received_at')
            ->get();

        $totalAgreed = Money::zero();
        $rows = [];
        foreach ($consignments as $c) {
            $agreed = Money::fromDecimal($c->agreed_selling_price);
            $totalAgreed = $totalAgreed->add($agreed);
            $rows[] = [
                $c->reference_number,
                $c->vehicle?->displayName() ?? '—',
                $c->owner->name ?? '—',
                $c->commission_type->label(),
                $agreed->format(),
                $c->status->label(),
                $c->received_at->toDateString(),
            ];
        }

        return [
            'slug' => 'consignment',
            'title' => 'Consignment Vehicles Report',
            'description' => 'Third-party consigned vehicles and agreed selling prices.',
            'category' => 'operational',
            'headers' => ['Reference', 'Vehicle', 'Owner', 'Commission Model', 'Agreed Price', 'Status', 'Received'],
            'rows' => $rows,
            'summary' => [
                ['label' => 'Total Consignments', 'value' => (string) count($rows)],
                ['label' => 'Total Agreed Listing Value', 'value' => $totalAgreed->format()],
            ],
        ];
    }

    /**
     * @return array{slug: string, title: string, description: string, category: 'operational'|'financial', headers: list<string>, rows: list<list<string>>, summary: list<array{label: string, value: string}>}
     */
    private function salesReport(?string $from, ?string $to): array
    {
        $sales = Sale::query()
            ->with(['vehicle.brand:id,name', 'vehicle.model:id,name', 'customer:id,name'])
            ->when($from, fn ($q) => $q->whereDate('confirmed_at', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('confirmed_at', '<=', $to))
            ->latest('confirmed_at')
            ->get();

        $totalFinal = Money::zero();
        $totalPaid = Money::zero();
        $totalBalance = Money::zero();
        $rows = [];

        foreach ($sales as $s) {
            if ($s->status !== SaleStatus::Cancelled) {
                $totalFinal = $totalFinal->add(Money::fromDecimal($s->final_price));
                $totalPaid = $totalPaid->add(Money::fromDecimal($s->amount_paid));
                $totalBalance = $totalBalance->add(Money::fromDecimal($s->balance_due));
            }
            $rows[] = [
                $s->reference_number,
                $s->vehicle?->displayName() ?? '—',
                $s->customer->name ?? '—',
                $s->ownership_type->label(),
                Money::fromDecimal($s->final_price)->format(),
                Money::fromDecimal($s->amount_paid)->format(),
                Money::fromDecimal($s->balance_due)->format(),
                $s->status->label(),
                $s->confirmed_at->toDateString(),
            ];
        }

        return [
            'slug' => 'sales',
            'title' => 'Sales Transactions Report',
            'description' => 'Confirmed, completed, and cancelled vehicle sales.',
            'category' => 'operational',
            'headers' => ['Reference', 'Vehicle', 'Customer', 'Ownership', 'Final Price', 'Paid', 'Balance', 'Status', 'Date'],
            'rows' => $rows,
            'summary' => [
                ['label' => 'Sales Count', 'value' => (string) count($rows)],
                ['label' => 'Net Active Sales Value', 'value' => $totalFinal->format()],
                ['label' => 'Total Collected', 'value' => $totalPaid->format()],
                ['label' => 'Outstanding Receivables', 'value' => $totalBalance->format()],
            ],
        ];
    }

    /**
     * @return array{slug: string, title: string, description: string, category: 'operational'|'financial', headers: list<string>, rows: list<list<string>>, summary: list<array{label: string, value: string}>}
     */
    private function expensesReport(?string $from, ?string $to): array
    {
        $expenses = VehicleExpense::query()
            ->with(['vehicle.brand:id,name', 'vehicle.model:id,name', 'category:id,name'])
            ->when($from, fn ($q) => $q->whereDate('expense_date', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('expense_date', '<=', $to))
            ->latest('expense_date')
            ->get();

        $totalAmount = Money::zero();
        $totalPaid = Money::zero();
        $rows = [];

        foreach ($expenses as $e) {
            $amt = Money::fromDecimal($e->amount);
            $paid = Money::fromDecimal($e->amount_paid);
            $totalAmount = $totalAmount->add($amt);
            $totalPaid = $totalPaid->add($paid);

            $rows[] = [
                $e->reference_number,
                $e->vehicle?->displayName() ?? '—',
                $e->category->name ?? '—',
                $e->borne_by->label(),
                $amt->format(),
                $paid->format(),
                $e->payment_status->label(),
                $e->expense_date->toDateString(),
            ];
        }

        return [
            'slug' => 'expenses',
            'title' => 'Vehicle Expenses Report',
            'description' => 'Vehicle costs categorized by bearer (Company vs. Owner-Deductible).',
            'category' => 'financial',
            'headers' => ['Reference', 'Vehicle', 'Category', 'Borne By', 'Amount', 'Paid', 'Status', 'Date'],
            'rows' => $rows,
            'summary' => [
                ['label' => 'Expense Records', 'value' => (string) count($rows)],
                ['label' => 'Total Expenses', 'value' => $totalAmount->format()],
                ['label' => 'Total Paid', 'value' => $totalPaid->format()],
            ],
        ];
    }

    /**
     * @return array{slug: string, title: string, description: string, category: 'operational'|'financial', headers: list<string>, rows: list<list<string>>, summary: list<array{label: string, value: string}>}
     */
    private function withdrawalsReport(?string $from, ?string $to): array
    {
        $withdrawals = WithdrawalRequest::query()
            ->with(['vehicle.brand:id,name', 'vehicle.model:id,name', 'owner:id,name'])
            ->when($from, fn ($q) => $q->whereDate('requested_at', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('requested_at', '<=', $to))
            ->latest('requested_at')
            ->get();

        $totalDue = Money::zero();
        $totalPaid = Money::zero();
        $rows = [];

        foreach ($withdrawals as $w) {
            $due = Money::fromDecimal($w->total_due);
            $paid = Money::fromDecimal($w->amount_paid);
            $totalDue = $totalDue->add($due);
            $totalPaid = $totalPaid->add($paid);

            $rows[] = [
                $w->reference_number,
                $w->vehicle?->displayName() ?? '—',
                $w->owner->name ?? '—',
                Money::fromDecimal($w->total_owner_expenses)->format(),
                Money::fromDecimal($w->total_withdrawal_charges)->format(),
                $due->format(),
                $paid->format(),
                $w->status->label(),
                $w->requested_at->toDateString(),
            ];
        }

        return [
            'slug' => 'withdrawals',
            'title' => 'Consignment Withdrawals Report',
            'description' => 'Owner withdrawals with expense recoveries and withdrawal charges.',
            'category' => 'financial',
            'headers' => ['Reference', 'Vehicle', 'Owner', 'Owner Expenses', 'Charges', 'Total Due', 'Paid', 'Status', 'Requested'],
            'rows' => $rows,
            'summary' => [
                ['label' => 'Withdrawals', 'value' => (string) count($rows)],
                ['label' => 'Total Clearance Due', 'value' => $totalDue->format()],
                ['label' => 'Total Collected', 'value' => $totalPaid->format()],
            ],
        ];
    }

    /**
     * @return array{slug: string, title: string, description: string, category: 'operational'|'financial', headers: list<string>, rows: list<list<string>>, summary: list<array{label: string, value: string}>}
     */
    private function settlementsReport(?string $from, ?string $to): array
    {
        $settlements = OwnerSettlement::query()
            ->with(['vehicle.brand:id,name', 'vehicle.model:id,name', 'owner:id,name'])
            ->when($from, fn ($q) => $q->whereDate('created_at', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('created_at', '<=', $to))
            ->latest()
            ->get();

        $totalComm = Money::zero();
        $totalNet = Money::zero();
        $totalPaid = Money::zero();
        $rows = [];

        foreach ($settlements as $s) {
            $comm = Money::fromDecimal($s->commission_amount);
            $net = Money::fromDecimal($s->net_payable_to_owner);
            $paid = Money::fromDecimal($s->amount_paid);
            $totalComm = $totalComm->add($comm);
            $totalNet = $totalNet->add($net);
            $totalPaid = $totalPaid->add($paid);

            $rows[] = [
                $s->reference_number,
                $s->vehicle?->displayName() ?? '—',
                $s->owner->name ?? '—',
                Money::fromDecimal($s->gross_sale_price)->format(),
                $comm->format(),
                Money::fromDecimal($s->total_expense_deductions)->format(),
                $net->format(),
                $paid->format(),
                $s->status->label(),
            ];
        }

        return [
            'slug' => 'settlements',
            'title' => 'Owner Settlements Report',
            'description' => 'Consignment settlements with commissions, expense deductions, and owner payouts.',
            'category' => 'financial',
            'headers' => ['Reference', 'Vehicle', 'Owner', 'Gross Sale', 'Commission', 'Expenses', 'Net Payable', 'Disbursed', 'Status'],
            'rows' => $rows,
            'summary' => [
                ['label' => 'Settlements', 'value' => (string) count($rows)],
                ['label' => 'Total Commissions', 'value' => $totalComm->format()],
                ['label' => 'Total Net Payable', 'value' => $totalNet->format()],
                ['label' => 'Total Disbursed', 'value' => $totalPaid->format()],
            ],
        ];
    }

    /**
     * @return array{slug: string, title: string, description: string, category: 'operational'|'financial', headers: list<string>, rows: list<list<string>>, summary: list<array{label: string, value: string}>}
     */
    private function profitabilityReport(?string $from, ?string $to): array
    {
        $sales = Sale::query()
            ->with(['vehicle.brand:id,name', 'vehicle.model:id,name', 'ownerSettlement'])
            ->where('status', '!=', SaleStatus::Cancelled->value)
            ->when($from, fn ($q) => $q->whereDate('confirmed_at', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('confirmed_at', '<=', $to))
            ->latest('confirmed_at')
            ->get();

        $totalRevenue = Money::zero();
        $totalDirectCost = Money::zero();
        $totalProfit = Money::zero();
        $rows = [];

        foreach ($sales as $s) {
            $vehicleExpenses = Money::fromDecimal((string) VehicleExpense::query()
                ->where('vehicle_id', $s->vehicle_id)
                ->where('borne_by', 'company')
                ->sum('amount'));

            if ($s->ownership_type === OwnershipType::CompanyOwned) {
                $saleRevenue = Money::fromDecimal($s->final_price);
                $purchaseCost = $s->vehicle?->acquisition_cost !== null
                    ? Money::fromDecimal($s->vehicle->acquisition_cost)
                    : Money::zero();
                $cost = $purchaseCost->add($vehicleExpenses);
                $profit = $saleRevenue->subtract($cost);
            } else {
                // Consignment: dealership revenue is commission; cost is any company-borne expense
                $saleRevenue = $s->ownerSettlement !== null
                    ? Money::fromDecimal($s->ownerSettlement->commission_amount)
                    : Money::zero();
                $cost = $vehicleExpenses;
                $profit = $saleRevenue->subtract($cost);
            }

            $totalRevenue = $totalRevenue->add($saleRevenue);
            $totalDirectCost = $totalDirectCost->add($cost);
            $totalProfit = $totalProfit->add($profit);

            $rows[] = [
                $s->reference_number,
                $s->vehicle?->displayName() ?? '—',
                $s->ownership_type->label(),
                Money::fromDecimal($s->final_price)->format(),
                $saleRevenue->format(),
                $cost->format(),
                $profit->format(),
                $s->confirmed_at->toDateString(),
            ];
        }

        return [
            'slug' => 'profitability',
            'title' => 'Vehicle Profitability & Margin Report',
            'description' => 'Dealership earned revenue (sale proceeds for company vehicles, commission for consignments) minus acquisition and company expenses.',
            'category' => 'financial',
            'headers' => ['Sale Ref', 'Vehicle', 'Ownership', 'Sale Price', 'Dealership Revenue', 'Direct Cost', 'Net Margin', 'Sale Date'],
            'rows' => $rows,
            'summary' => [
                ['label' => 'Sold Vehicles', 'value' => (string) count($rows)],
                ['label' => 'Dealership Revenue', 'value' => $totalRevenue->format()],
                ['label' => 'Direct Costs', 'value' => $totalDirectCost->format()],
                ['label' => 'Net Dealership Margin', 'value' => $totalProfit->format()],
            ],
        ];
    }
}
