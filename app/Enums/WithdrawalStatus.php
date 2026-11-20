<?php

namespace App\Enums;

enum WithdrawalStatus: string
{
    case AwaitingPayment = 'awaiting_payment';
    case PaidReadyForHandover = 'paid_ready_for_handover';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::AwaitingPayment => 'Awaiting Payment',
            self::PaidReadyForHandover => 'Paid — Ready for Handover',
            self::Completed => 'Completed (Withdrawn)',
            self::Cancelled => 'Cancelled',
        };
    }

    public function badgeVariant(): string
    {
        return match ($this) {
            self::AwaitingPayment => 'warning',
            self::PaidReadyForHandover => 'info',
            self::Completed => 'success',
            self::Cancelled => 'neutral',
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
