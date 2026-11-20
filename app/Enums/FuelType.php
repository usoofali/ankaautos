<?php

namespace App\Enums;

enum FuelType: string
{
    case Petrol = 'petrol';
    case Diesel = 'diesel';
    case Hybrid = 'hybrid';
    case Electric = 'electric';
    case Cng = 'cng';

    public function label(): string
    {
        return match ($this) {
            self::Petrol => 'Petrol',
            self::Diesel => 'Diesel',
            self::Hybrid => 'Hybrid',
            self::Electric => 'Electric',
            self::Cng => 'CNG / Dual Fuel',
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
