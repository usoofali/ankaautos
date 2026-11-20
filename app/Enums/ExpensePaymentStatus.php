<?php

namespace App\Enums;

enum ExpensePaymentStatus: string
{
    case Unpaid = 'unpaid';
    case PartiallyPaid = 'partially_paid';
    case Paid = 'paid';

    public function label(): string
    {
        return match ($this) {
            self::Unpaid => 'Unpaid',
            self::PartiallyPaid => 'Partially Paid',
            self::Paid => 'Paid',
        };
    }

    public function badgeVariant(): string
    {
        return match ($this) {
            self::Unpaid => 'danger',
            self::PartiallyPaid => 'warning',
            self::Paid => 'success',
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
