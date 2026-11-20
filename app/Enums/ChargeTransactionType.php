<?php

namespace App\Enums;

enum ChargeTransactionType: string
{
    case ConsignmentSale = 'consignment_sale';
    case ConsignmentWithdrawal = 'consignment_withdrawal';
    case VehicleService = 'vehicle_service';
    case Sale = 'sale';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::ConsignmentSale => 'Consignment Sale',
            self::ConsignmentWithdrawal => 'Consignment Withdrawal',
            self::VehicleService => 'Vehicle Preparation / Service',
            self::Sale => 'Vehicle Sale',
            self::Other => 'Other Business Service',
        };
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            static fn (self $case): array => [
                'value' => $case->value,
                'label' => $case->label(),
            ],
            self::cases(),
        );
    }
}
