<?php

namespace App\Enums;

enum RevenueBucket: string
{
    case Company = 'company';
    case ThirdPartyOwner = 'third_party_owner';

    public function label(): string
    {
        return match ($this) {
            self::Company => 'Dealership / Company',
            self::ThirdPartyOwner => 'Third-Party Owner Funds',
        };
    }

    public function badgeVariant(): string
    {
        return match ($this) {
            self::Company => 'info',
            self::ThirdPartyOwner => 'pending',
        };
    }
}
