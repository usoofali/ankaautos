<?php

namespace App\Actions\Reservations;

use App\Enums\FinancialTransactionType;
use App\Enums\OwnershipType;
use App\Enums\PaymentType;
use App\Enums\ReservationStatus;
use App\Enums\RevenueBucket;
use App\Enums\TransactionDirection;
use App\Enums\VehicleStatus;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\AuditLogger;
use App\Services\FinancialLedgerService;
use App\Services\ReferenceNumberGenerator;
use App\Support\Money;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateReservationAction
{
    public function __construct(
        private ReferenceNumberGenerator $referenceGenerator,
        private FinancialLedgerService $ledger,
        private AuditLogger $auditLogger,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(array $data, User $actor): Reservation
    {
        $agreedPrice = Money::fromDecimal((string) $data['agreed_price']);
        $deposit = Money::fromDecimal((string) $data['deposit_amount']);

        if (! $deposit->isPositive()) {
            throw ValidationException::withMessages([
                'deposit_amount' => 'A mandatory deposit greater than ₦0.00 is required to reserve a vehicle.',
            ]);
        }

        if ($deposit->greaterThan($agreedPrice)) {
            throw ValidationException::withMessages([
                'deposit_amount' => 'Reservation deposit cannot exceed the agreed selling price.',
            ]);
        }

        return DB::transaction(function () use ($data, $actor, $agreedPrice, $deposit): Reservation {
            /** @var Vehicle $vehicle */
            $vehicle = Vehicle::query()->lockForUpdate()->findOrFail($data['vehicle_id']);

            if (in_array($vehicle->status, [VehicleStatus::Sold, VehicleStatus::Withdrawn, VehicleStatus::Archived], true)) {
                throw ValidationException::withMessages([
                    'vehicle_id' => "Vehicle {$vehicle->reference_number} is {$vehicle->status->label()} and cannot be reserved.",
                ]);
            }

            $existingActive = Reservation::query()
                ->where('vehicle_id', $vehicle->id)
                ->where('status', ReservationStatus::Active->value)
                ->exists();

            $overrideBy = null;
            if ($existingActive) {
                if (! $actor->can('reservations.override') || empty($data['override_conflict'])) {
                    throw ValidationException::withMessages([
                        'vehicle_id' => 'This vehicle already has an active reservation. A Manager override is required.',
                    ]);
                }
                $overrideBy = $actor->id;
            }

            /** @var Reservation $reservation */
            $reservation = Reservation::query()->create([
                'reference_number' => $this->referenceGenerator->generate('RES'),
                'vehicle_id' => $vehicle->id,
                'customer_id' => (int) $data['customer_id'],
                'agreed_price' => $agreedPrice->toDecimal(),
                'deposit_amount' => $deposit->toDecimal(),
                'status' => ReservationStatus::Active,
                'reserved_at' => now(),
                'notes' => $data['notes'] ?? null,
                'conflicting_override_by' => $overrideBy,
                'created_by' => $actor->id,
            ]);

            /** @var Payment $payment */
            $payment = $reservation->payments()->create([
                'reference_number' => $this->referenceGenerator->generate('PAY'),
                'idempotency_key' => $data['idempotency_key'] ?? null,
                'customer_id' => $reservation->customer_id,
                'payment_type' => PaymentType::ReservationDeposit,
                'payment_method_id' => (int) $data['payment_method_id'],
                'financial_account_id' => (int) $data['financial_account_id'],
                'amount' => $deposit->toDecimal(),
                'paid_at' => $data['paid_at'] ?? now()->toDateString(),
                'external_reference' => $data['external_reference'] ?? null,
                'notes' => "Reservation deposit for {$reservation->reference_number}",
                'recorded_by' => $actor->id,
                'created_at' => now(),
            ]);

            $bucket = $vehicle->ownership_type === OwnershipType::Consignment
                ? RevenueBucket::ThirdPartyOwner
                : RevenueBucket::Company;

            $this->ledger->post(
                type: FinancialTransactionType::ReservationDeposit,
                direction: TransactionDirection::Inflow,
                bucket: $bucket,
                amount: $deposit,
                description: "Reservation deposit {$payment->reference_number} for {$reservation->reference_number} ({$vehicle->reference_number})",
                financialAccountId: $payment->financial_account_id,
                paymentMethodId: $payment->payment_method_id,
                vehicleId: $vehicle->id,
                source: $payment,
                transactedAt: Carbon::parse((string) ($data['paid_at'] ?? now()->toDateString())),
                actor: $actor,
            );

            $previousStatus = $vehicle->status;
            if ($previousStatus !== VehicleStatus::Reserved) {
                $vehicle->update(['status' => VehicleStatus::Reserved]);
                $vehicle->statusHistories()->create([
                    'from_status' => $previousStatus,
                    'to_status' => VehicleStatus::Reserved,
                    'reason' => "Reserved under {$reservation->reference_number} with deposit {$deposit->format()}.",
                    'changed_by' => $actor->id,
                    'created_at' => now(),
                ]);
            }

            $this->auditLogger->audit(
                event: 'reservation.created',
                auditable: $reservation,
                newValues: [
                    'reference_number' => $reservation->reference_number,
                    'vehicle_id' => $vehicle->id,
                    'customer_id' => $reservation->customer_id,
                    'agreed_price' => $agreedPrice->toDecimal(),
                    'deposit_amount' => $deposit->toDecimal(),
                ],
                actor: $actor,
            );

            return $reservation;
        });
    }
}
