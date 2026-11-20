<?php

namespace App\Enums;

enum FinancialTransactionType: string
{
    case CompanySaleProceeds = 'company_sale_proceeds';
    case ConsignmentSaleProceeds = 'consignment_sale_proceeds';
    case ReservationDeposit = 'reservation_deposit';
    case DealershipCommission = 'dealership_commission';
    case ServiceChargeIncome = 'service_charge_income';
    case WithdrawalChargePayment = 'withdrawal_charge_payment';
    case ForfeitedDepositIncome = 'forfeited_deposit_income';
    case VehicleAcquisition = 'vehicle_acquisition';
    case CompanyExpense = 'company_expense';
    case OwnerDeductibleExpense = 'owner_deductible_expense';
    case OwnerSettlementPayout = 'owner_settlement_payout';
    case CustomerRefund = 'customer_refund';
    case FinancialAdjustment = 'financial_adjustment';

    public function label(): string
    {
        return match ($this) {
            self::CompanySaleProceeds => 'Company Sale Proceeds',
            self::ConsignmentSaleProceeds => 'Consignment Sale Proceeds',
            self::ReservationDeposit => 'Reservation Deposit',
            self::DealershipCommission => 'Dealership Commission',
            self::ServiceChargeIncome => 'Service Charge Income',
            self::WithdrawalChargePayment => 'Withdrawal Payment Received',
            self::ForfeitedDepositIncome => 'Forfeited Deposit Income',
            self::VehicleAcquisition => 'Vehicle Acquisition',
            self::CompanyExpense => 'Company Vehicle Expense',
            self::OwnerDeductibleExpense => 'Owner-Deductible Expense',
            self::OwnerSettlementPayout => 'Owner Settlement Payout',
            self::CustomerRefund => 'Customer Refund',
            self::FinancialAdjustment => 'Financial Adjustment',
        };
    }
}
