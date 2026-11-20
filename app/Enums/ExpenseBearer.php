<?php

namespace App\Enums;

enum ExpenseBearer: string
{
    case Company = 'company';
    case OwnerDeductible = 'owner_deductible';

    public function label(): string
    {
        return match ($this) {
            self::Company => 'Company-Borne',
            self::OwnerDeductible => 'Owner / Deductible',
        };
    }

    public function badgeVariant(): string
    {
        return match ($this) {
            self::Company => 'info',
            self::OwnerDeductible => 'warning',
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
