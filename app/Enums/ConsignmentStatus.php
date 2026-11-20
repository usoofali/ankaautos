<?php

namespace App\Enums;

enum ConsignmentStatus: string
{
    case Draft = 'draft';
    case Active = 'active';
    case OnHold = 'on_hold';
    case SalePending = 'sale_pending';
    case Sold = 'sold';
    case WithdrawalRequested = 'withdrawal_requested';
    case SettlementPending = 'settlement_pending';
    case Settled = 'settled';
    case Withdrawn = 'withdrawn';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Active => 'Active',
            self::OnHold => 'On Hold',
            self::SalePending => 'Sale Pending',
            self::Sold => 'Sold',
            self::WithdrawalRequested => 'Withdrawal Requested',
            self::SettlementPending => 'Settlement Pending',
            self::Settled => 'Settled',
            self::Withdrawn => 'Withdrawn',
        };
    }

    public function badgeVariant(): string
    {
        return match ($this) {
            self::Active, self::Settled => 'success',
            self::Draft, self::OnHold, self::WithdrawalRequested => 'warning',
            self::SalePending, self::SettlementPending => 'pending',
            self::Sold => 'info',
            self::Withdrawn => 'neutral',
        };
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::Settled, self::Withdrawn], true);
    }

    /**
     * @return list<array{value: string, label: string, variant: string}>
     */
    public static function options(): array
    {
        return array_map(
            static fn (self $case): array => [
                'value' => $case->value,
                'label' => $case->label(),
                'variant' => $case->badgeVariant(),
            ],
            self::cases(),
        );
    }
}
