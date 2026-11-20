<?php

namespace App\Enums;

enum SteeringPosition: string
{
    case LeftHand = 'left_hand';
    case RightHand = 'right_hand';

    public function label(): string
    {
        return match ($this) {
            self::LeftHand => 'Left-Hand Drive',
            self::RightHand => 'Right-Hand Drive',
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
