<?php

namespace App\Enums;

enum VehicleStatus: string
{
    case PendingRegistration = 'pending_registration';
    case Available = 'available';
    case Reserved = 'reserved';
    case UnderPreparation = 'under_preparation';
    case UnderMaintenance = 'under_maintenance';
    case Sold = 'sold';
    case Withdrawn = 'withdrawn';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::PendingRegistration => 'Pending Registration',
            self::Available => 'Available',
            self::Reserved => 'Reserved',
            self::UnderPreparation => 'Under Preparation',
            self::UnderMaintenance => 'Under Maintenance',
            self::Sold => 'Sold',
            self::Withdrawn => 'Withdrawn',
            self::Archived => 'Archived',
        };
    }

    public function badgeVariant(): string
    {
        return match ($this) {
            self::Available => 'success',
            self::Reserved, self::PendingRegistration => 'pending',
            self::UnderPreparation, self::UnderMaintenance => 'warning',
            self::Sold => 'info',
            self::Withdrawn, self::Archived => 'neutral',
        };
    }

    /**
     * Whether a vehicle in this status counts as currently active on the stand
     * (blocks duplicate VIN registration without return-override).
     */
    public function isActiveInventory(): bool
    {
        return ! in_array($this, [self::Sold, self::Withdrawn, self::Archived], true);
    }

    /**
     * Allowed operational status transitions.
     *
     * @return list<self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::PendingRegistration => [self::UnderPreparation, self::Available, self::UnderMaintenance, self::Archived],
            self::UnderPreparation => [self::Available, self::UnderMaintenance, self::Withdrawn],
            self::UnderMaintenance => [self::UnderPreparation, self::Available, self::Withdrawn],
            self::Available => [self::UnderPreparation, self::UnderMaintenance, self::Reserved, self::Sold, self::Withdrawn],
            self::Reserved => [self::Available, self::Sold],
            self::Sold => [self::Available, self::Archived],
            self::Withdrawn => [self::Archived],
            self::Archived => [],
        };
    }

    public function canTransitionTo(self $next): bool
    {
        return in_array($next, $this->allowedTransitions(), true);
    }

    /**
     * Operational statuses that staff can manually toggle from the vehicle record
     * (Sold, Reserved, Withdrawn are driven by Sales/Reservations/Withdrawals workflows).
     *
     * @return list<self>
     */
    public function manuallySelectableTransitions(): array
    {
        return array_values(array_filter(
            $this->allowedTransitions(),
            static fn (self $status): bool => in_array($status, [
                self::PendingRegistration,
                self::UnderPreparation,
                self::UnderMaintenance,
                self::Available,
            ], true),
        ));
    }

    /**
     * @return list<array{value: string, label: string, variant: string}>
     */
    public static function options(): array
    {
        return array_map(
            static fn (self $case): array => [
                'value' => $case->value,
                'label' => $case->label(),
                'variant' => $case->badgeVariant(),
            ],
            self::cases(),
        );
    }
}
