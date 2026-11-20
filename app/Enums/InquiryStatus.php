<?php

namespace App\Enums;

enum InquiryStatus: string
{
    case New = 'new';
    case Contacted = 'contacted';
    case Inspected = 'inspected';
    case Negotiating = 'negotiating';
    case Converted = 'converted';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::New => 'New Enquiry',
            self::Contacted => 'Contacted',
            self::Inspected => 'Vehicle Inspected',
            self::Negotiating => 'Negotiating',
            self::Converted => 'Converted',
            self::Closed => 'Closed',
        };
    }

    public function badgeVariant(): string
    {
        return match ($this) {
            self::New => 'info',
            self::Contacted, self::Inspected => 'pending',
            self::Negotiating => 'warning',
            self::Converted => 'success',
            self::Closed => 'neutral',
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
