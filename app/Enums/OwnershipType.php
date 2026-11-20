<?php

namespace App\Enums;

enum OwnershipType: string
{
    case CompanyOwned = 'company_owned';
    case Consignment = 'consignment';

    public function label(): string
    {
        return match ($this) {
            self::CompanyOwned => 'Company-Owned',
            self::Consignment => 'Third-Party Consignment',
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
