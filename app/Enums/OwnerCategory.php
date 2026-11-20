<?php

namespace App\Enums;

enum OwnerCategory: string
{
    case Individual = 'individual';
    case Organization = 'organization';

    public function label(): string
    {
        return match ($this) {
            self::Individual => 'Individual',
            self::Organization => 'Organization / Corporate',
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
