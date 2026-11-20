<?php

namespace App\Enums;

enum SettlementItemType: string
{
    case SaleProceeds = 'sale_proceeds';
    case Commission = 'commission';
    case OwnerExpenseDeduction = 'owner_expense_deduction';
    case ServiceChargeDeduction = 'service_charge_deduction';
    case Adjustment = 'adjustment';

    public function label(): string
    {
        return match ($this) {
            self::SaleProceeds => 'Gross Sale Proceeds',
            self::Commission => 'Dealership Commission',
            self::OwnerExpenseDeduction => 'Owner-Deductible Expense',
            self::ServiceChargeDeduction => 'Consignment Service Charge',
            self::Adjustment => 'Authorized Adjustment',
        };
    }
}
