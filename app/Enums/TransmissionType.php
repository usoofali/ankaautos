<?php

namespace App\Enums;

enum TransmissionType: string
{
    case Automatic = 'automatic';
    case Manual = 'manual';
    case Cvt = 'cvt';
    case SemiAutomatic = 'semi_automatic';

    public function label(): string
    {
        return match ($this) {
            self::Automatic => 'Automatic',
            self::Manual => 'Manual',
            self::Cvt => 'CVT',
            self::SemiAutomatic => 'Semi-Automatic',
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
