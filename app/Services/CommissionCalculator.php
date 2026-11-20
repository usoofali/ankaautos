<?php

namespace App\Services;

use App\Enums\CommissionType;
use App\Models\Consignment;
use App\Support\Money;

/**
 * Exact integer-kobo commission calculation for all 4 supported consignment models (SSD 10.4).
 */
class CommissionCalculator
{
    public function calculate(
        CommissionType $type,
        Money $finalSalePrice,
        string $commissionValue,
        ?string $minimumAcceptablePrice = null,
        ?Money $manualCustomAmount = null,
    ): Money {
        return match ($type) {
            CommissionType::Fixed => Money::fromDecimal($commissionValue)->min($finalSalePrice)->max(Money::zero()),
            CommissionType::Percentage => $finalSalePrice->percentage($commissionValue)->min($finalSalePrice)->max(Money::zero()),
            CommissionType::ExcessOverMinimum => $this->calculateExcess($finalSalePrice, $minimumAcceptablePrice),
            CommissionType::Custom => ($manualCustomAmount ?? Money::fromDecimal($commissionValue))->min($finalSalePrice)->max(Money::zero()),
        };
    }

    public function calculateForConsignment(
        Consignment $consignment,
        Money $finalSalePrice,
        ?Money $manualCustomAmount = null,
    ): Money {
        return $this->calculate(
            type: $consignment->commission_type,
            finalSalePrice: $finalSalePrice,
            commissionValue: $consignment->commission_value,
            minimumAcceptablePrice: $consignment->minimum_acceptable_price,
            manualCustomAmount: $manualCustomAmount,
        );
    }

    private function calculateExcess(Money $finalSalePrice, ?string $minimumAcceptablePrice): Money
    {
        if ($minimumAcceptablePrice === null || $minimumAcceptablePrice === '') {
            return Money::zero();
        }

        $floor = Money::fromDecimal($minimumAcceptablePrice);

        return $finalSalePrice->subtract($floor)->max(Money::zero());
    }
}
