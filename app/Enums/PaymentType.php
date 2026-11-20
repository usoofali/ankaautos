<?php

namespace App\Enums;

enum PaymentType: string
{
    case ReservationDeposit = 'reservation_deposit';
    case SalePayment = 'sale_payment';
    case Installment = 'installment';
    case WithdrawalPayment = 'withdrawal_payment';

    public function label(): string
    {
        return match ($this) {
            self::ReservationDeposit => 'Reservation Deposit',
            self::SalePayment => 'Sale Payment',
            self::Installment => 'Installment Payment',
            self::WithdrawalPayment => 'Withdrawal Charge Payment',
        };
    }
}
