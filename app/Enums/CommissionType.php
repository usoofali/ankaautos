<?php

namespace App\Enums;

enum CommissionType: string
{
    case Fixed = 'fixed';
    case Percentage = 'percentage';
    case ExcessOverMinimum = 'excess_over_minimum';
    case Custom = 'custom';

    public function label(): string
    {
        return match ($this) {
            self::Fixed => 'Fixed Amount (₦)',
            self::Percentage => 'Percentage of Sale Price (%)',
            self::ExcessOverMinimum => 'Excess Over Minimum / Owner-Net Price',
            self::Custom => 'Custom / Manual Agreement',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Fixed => 'Dealership earns a fixed Naira commission regardless of final sale price.',
            self::Percentage => 'Dealership earns a percentage of the final confirmed sale price.',
            self::ExcessOverMinimum => 'Owner receives the agreed minimum floor price; dealership keeps any excess margin sold above it.',
            self::Custom => 'Special negotiated commission entered manually at settlement with notes.',
        };
    }

    /**
     * @return list<array{value: string, label: string, description: string}>
     */
    public static function options(): array
    {
        return array_map(
            static fn (self $case): array => [
                'value' => $case->value,
                'label' => $case->label(),
                'description' => $case->description(),
            ],
            self::cases(),
        );
    }
}
