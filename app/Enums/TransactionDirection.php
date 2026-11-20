<?php

namespace App\Enums;

enum TransactionDirection: string
{
    case Inflow = 'inflow';
    case Outflow = 'outflow';

    public function label(): string
    {
        return match ($this) {
            self::Inflow => 'Inflow',
            self::Outflow => 'Outflow',
        };
    }
}
