<?php

namespace App\Actions\Settlements;

use App\Enums\ChargeTransactionType;
use App\Enums\ExpenseBearer;
use App\Enums\SettlementItemType;
use App\Enums\SettlementStatus;
use App\Models\Consignment;
use App\Models\OwnerSettlement;
use App\Models\Sale;
use App\Models\TransactionCharge;
use App\Models\User;
use App\Models\VehicleExpense;
use App\Services\AuditLogger;
use App\Services\CommissionCalculator;
use App\Services\ReferenceNumberGenerator;
use App\Support\Money;
use Illuminate\Support\Facades\DB;

class GenerateOwnerSettlementAction
{
    public function __construct(
        private ReferenceNumberGenerator $referenceGenerator,
        private CommissionCalculator $commissionCalculator,
        private AuditLogger $auditLogger,
    ) {}

    public function execute(
        Sale $sale,
        Consignment $consignment,
        User $actor,
        ?Money $customCommissionOverride = null,
    ): OwnerSettlement {
        return DB::transaction(function () use ($sale, $consignment, $actor, $customCommissionOverride): OwnerSettlement {
            $grossSale = Money::fromDecimal($sale->final_price);
            $commission = $this->commissionCalculator->calculateForConsignment(
                consignment: $consignment,
                finalSalePrice: $grossSale,
                manualCustomAmount: $customCommissionOverride,
            );

            // Pull all unsettled owner-deductible expenses for this consignment (prevents double-deduction)
            $ownerExpenses = VehicleExpense::query()
                ->where('consignment_id', $consignment->id)
                ->where('borne_by', ExpenseBearer::OwnerDeductible->value)
                ->whereNull('settled_in_owner_settlement_id')
                ->whereNull('settled_in_withdrawal_id')
                ->lockForUpdate()
                ->get();

            $totalExpenses = Money::zero();
            foreach ($ownerExpenses as $expense) {
                $totalExpenses = $totalExpenses->add(Money::fromDecimal($expense->amount));
            }

            // Pull consignment sale charges applied to the sale
            $saleCharges = TransactionCharge::query()
                ->with('category')
                ->where('chargeable_type', $sale->getMorphClass())
                ->where('chargeable_id', $sale->id)
                ->where('transaction_type', ChargeTransactionType::ConsignmentSale->value)
                ->get();

            $totalCharges = Money::zero();
            foreach ($saleCharges as $charge) {
                $totalCharges = $totalCharges->add(Money::fromDecimal($charge->amount));
            }

            $netPayable = $grossSale
                ->subtract($commission)
                ->subtract($totalExpenses)
                ->subtract($totalCharges)
                ->max(Money::zero());

            $initialStatus = $sale->isFullyPaid()
                ? SettlementStatus::PendingPayout
                : SettlementStatus::LockedAwaitingCustomerPayment;

            /** @var OwnerSettlement $settlement */
            $settlement = OwnerSettlement::query()->create([
                'reference_number' => $this->referenceGenerator->generate('SET'),
                'consignment_id' => $consignment->id,
                'sale_id' => $sale->id,
                'vehicle_id' => $sale->vehicle_id,
                'vehicle_owner_id' => $consignment->vehicle_owner_id,
                'gross_sale_price' => $grossSale->toDecimal(),
                'commission_amount' => $commission->toDecimal(),
                'total_expense_deductions' => $totalExpenses->toDecimal(),
                'total_charge_deductions' => $totalCharges->toDecimal(),
                'net_payable_to_owner' => $netPayable->toDecimal(),
                'amount_paid' => '0.00',
                'balance_due' => $netPayable->toDecimal(),
                'status' => $initialStatus,
                'created_by' => $actor->id,
            ]);

            $settlement->items()->create([
                'item_type' => SettlementItemType::SaleProceeds,
                'label' => "Gross Sale Proceeds ({$sale->reference_number})",
                'amount' => $grossSale->toDecimal(),
                'source_type' => $sale->getMorphClass(),
                'source_id' => $sale->id,
            ]);

            if ($commission->isPositive()) {
                $settlement->items()->create([
                    'item_type' => SettlementItemType::Commission,
                    'label' => "Dealership Commission ({$consignment->commission_type->label()})",
                    'amount' => $commission->toDecimal(),
                    'source_type' => $consignment->getMorphClass(),
                    'source_id' => $consignment->id,
                ]);
            }

            foreach ($ownerExpenses as $expense) {
                $settlement->items()->create([
                    'item_type' => SettlementItemType::OwnerExpenseDeduction,
                    'label' => "{$expense->reference_number}: {$expense->description}",
                    'amount' => $expense->amount,
                    'source_type' => $expense->getMorphClass(),
                    'source_id' => $expense->id,
                ]);

                $expense->update(['settled_in_owner_settlement_id' => $settlement->id]);
            }

            foreach ($saleCharges as $charge) {
                $settlement->items()->create([
                    'item_type' => SettlementItemType::ServiceChargeDeduction,
                    'label' => $charge->displayLabel(),
                    'amount' => $charge->amount,
                    'source_type' => $charge->getMorphClass(),
                    'source_id' => $charge->id,
                ]);
            }

            $this->auditLogger->audit(
                event: 'settlement.generated',
                auditable: $settlement,
                newValues: [
                    'reference_number' => $settlement->reference_number,
                    'gross_sale_price' => $grossSale->toDecimal(),
                    'commission_amount' => $commission->toDecimal(),
                    'total_expense_deductions' => $totalExpenses->toDecimal(),
                    'total_charge_deductions' => $totalCharges->toDecimal(),
                    'net_payable_to_owner' => $netPayable->toDecimal(),
                    'status' => $initialStatus->value,
                ],
                actor: $actor,
            );

            return $settlement;
        });
    }
}
