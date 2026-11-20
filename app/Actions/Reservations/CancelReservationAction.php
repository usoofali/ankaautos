<?php

namespace App\Actions\Reservations;

use App\Enums\FinancialTransactionType;
use App\Enums\OwnershipType;
use App\Enums\ReservationStatus;
use App\Enums\RevenueBucket;
use App\Enums\TransactionDirection;
use App\Enums\VehicleStatus;
use App\Models\Refund;
use App\Models\Reservation;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\AuditLogger;
use App\Services\FinancialLedgerService;
use App\Services\ReferenceNumberGenerator;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CancelReservationAction
{
    public function __construct(
        private ReferenceNumberGenerator $referenceGenerator,
        private FinancialLedgerService $ledger,
        private AuditLogger $auditLogger,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(Reservation $reservation, array $data, User $actor): Reservation
    {
        if ($reservation->status !== ReservationStatus::Active) {
            throw ValidationException::withMessages([
                'status' => 'Only active reservations can be cancelled.',
            ]);
        }

        return DB::transaction(function () use ($reservation, $data, $actor): Reservation {
            /** @var Vehicle $vehicle */
            $vehicle = Vehicle::query()->lockForUpdate()->findOrFail($reservation->vehicle_id);
            $disposition = (string) $data['deposit_disposition']; // refunded | forfeited
            $deposit = Money::fromDecimal($reservation->deposit_amount);

            $reservation->update([
                'status' => ReservationStatus::Cancelled,
                'cancellation_reason' => (string) $data['cancellation_reason'],
                'deposit_disposition' => $disposition,
            ]);

            if ($disposition === 'refunded' && $deposit->isPositive()) {
                /** @var Refund $refund */
                $refund = Refund::query()->create([
                    'reference_number' => $this->referenceGenerator->generate('RFD'),
                    'refundable_type' => $reservation->getMorphClass(),
                    'refundable_id' => $reservation->id,
                    'customer_id' => $reservation->customer_id,
                    'amount' => $deposit->toDecimal(),
                    'payment_method_id' => (int) $data['payment_method_id'],
                    'financial_account_id' => (int) $data['financial_account_id'],
                    'reason' => (string) $data['cancellation_reason'],
                    'refunded_at' => now()->toDateString(),
                    'approved_by' => $actor->id,
                    'created_at' => now(),
                ]);

                $bucket = $vehicle->ownership_type === OwnershipType::Consignment
                    ? RevenueBucket::ThirdPartyOwner
                    : RevenueBucket::Company;

                $this->ledger->post(
                    type: FinancialTransactionType::CustomerRefund,
                    direction: TransactionDirection::Outflow,
                    bucket: $bucket,
                    amount: $deposit,
                    description: "Reservation deposit refund {$refund->reference_number} for {$reservation->reference_number}",
                    financialAccountId: $refund->financial_account_id,
                    paymentMethodId: $refund->payment_method_id,
                    vehicleId: $vehicle->id,
                    source: $refund,
                    actor: $actor,
                );
            } elseif ($disposition === 'forfeited' && $deposit->isPositive()) {
                $this->ledger->post(
                    type: FinancialTransactionType::ForfeitedDepositIncome,
                    direction: TransactionDirection::Inflow,
                    bucket: RevenueBucket::Company,
                    amount: $deposit,
                    description: "Forfeited reservation deposit recognized from {$reservation->reference_number}",
                    vehicleId: $vehicle->id,
                    source: $reservation,
                    actor: $actor,
                );
            }

            $hasOtherActive = Reservation::query()
                ->where('vehicle_id', $vehicle->id)
                ->where('status', ReservationStatus::Active->value)
                ->exists();

            if (! $hasOtherActive && $vehicle->status === VehicleStatus::Reserved) {
                $vehicle->update(['status' => VehicleStatus::Available]);
                $vehicle->statusHistories()->create([
                    'from_status' => VehicleStatus::Reserved,
                    'to_status' => VehicleStatus::Available,
                    'reason' => "Reservation {$reservation->reference_number} cancelled ({$disposition}).",
                    'changed_by' => $actor->id,
                    'created_at' => now(),
                ]);
            }

            $this->auditLogger->audit(
                event: 'reservation.cancelled',
                auditable: $reservation,
                oldValues: ['status' => ReservationStatus::Active->value],
                newValues: ['status' => ReservationStatus::Cancelled->value, 'deposit_disposition' => $disposition],
                reason: (string) $data['cancellation_reason'],
                actor: $actor,
            );

            return $reservation;
        });
    }
}
