<?php

namespace App\Enums;

enum AttendantActivityType: string
{
    case Washing = 'washing';
    case Maintenance = 'maintenance';
    case Movement = 'movement';
    case Inspection = 'inspection';
    case Preparation = 'preparation';
    case HandoverAssist = 'handover_assist';

    public function label(): string
    {
        return match ($this) {
            self::Washing => 'Vehicle Washing / Detailing',
            self::Maintenance => 'Maintenance / Workshop',
            self::Movement => 'Location Movement',
            self::Inspection => 'Condition Inspection',
            self::Preparation => 'Preparation Update',
            self::HandoverAssist => 'Handover Assistance',
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
