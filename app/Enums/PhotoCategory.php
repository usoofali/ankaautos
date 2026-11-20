<?php

namespace App\Enums;

enum PhotoCategory: string
{
    case Front = 'front';
    case Rear = 'rear';
    case LeftSide = 'left_side';
    case RightSide = 'right_side';
    case Interior = 'interior';
    case Dashboard = 'dashboard';
    case Engine = 'engine';
    case Defect = 'defect';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Front => 'Front View',
            self::Rear => 'Rear View',
            self::LeftSide => 'Left Side',
            self::RightSide => 'Right Side',
            self::Interior => 'Interior',
            self::Dashboard => 'Dashboard & Odometer',
            self::Engine => 'Engine Compartment',
            self::Defect => 'Visible Defect / Damage',
            self::Other => 'Other',
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
