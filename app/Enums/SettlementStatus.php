<?php

namespace App\Enums;

enum SettlementStatus: string
{
    case LockedAwaitingCustomerPayment = 'locked_awaiting_customer_payment';
    case PendingPayout = 'pending_payout';
    case PartiallyPaid = 'partially_paid';
    case Settled = 'settled';
    case Voided = 'voided';

    public function label(): string
    {
        return match ($this) {
            self::LockedAwaitingCustomerPayment => 'Locked (Awaiting Customer Full Payment)',
            self::PendingPayout => 'Ready for Payout',
            self::PartiallyPaid => 'Partially Disbursed',
            self::Settled => 'Settled in Full',
            self::Voided => 'Voided',
        };
    }

    public function badgeVariant(): string
    {
        return match ($this) {
            self::LockedAwaitingCustomerPayment => 'warning',
            self::PendingPayout => 'info',
            self::PartiallyPaid => 'pending',
            self::Settled => 'success',
            self::Voided => 'danger',
        };
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            static fn (self $case): array => ['value' => $case->value, 'label' => $case->label()],
            self::cases(),
        );
    }
}
