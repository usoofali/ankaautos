<?php

namespace App\Enums;

enum DriveType: string
{
    case Fwd = 'fwd';
    case Rwd = 'rwd';
    case Awd = 'awd';
    case FourWd = '4wd';

    public function label(): string
    {
        return match ($this) {
            self::Fwd => 'FWD (Front-Wheel Drive)',
            self::Rwd => 'RWD (Rear-Wheel Drive)',
            self::Awd => 'AWD (All-Wheel Drive)',
            self::FourWd => '4WD (Four-Wheel Drive)',
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
