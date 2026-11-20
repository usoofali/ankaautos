<?php

namespace App\Actions\Expenses;

use App\Enums\ExpenseBearer;
use App\Enums\ExpensePaymentStatus;
use App\Enums\OwnershipType;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleExpense;
use App\Services\AuditLogger;
use App\Services\ReferenceNumberGenerator;
use App\Support\Money;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RecordVehicleExpenseAction
{
    public function __construct(
        private ReferenceNumberGenerator $referenceGenerator,
        private AuditLogger $auditLogger,
        private RecordExpensePaymentAction $recordPayment,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(array $data, User $actor, ?UploadedFile $receipt = null): VehicleExpense
    {
        /** @var Vehicle $vehicle */
        $vehicle = Vehicle::query()->with('activeConsignment')->findOrFail($data['vehicle_id']);
        $bearer = ExpenseBearer::from((string) $data['borne_by']);

        if ($bearer === ExpenseBearer::OwnerDeductible && $vehicle->ownership_type !== OwnershipType::Consignment) {
            throw ValidationException::withMessages([
                'borne_by' => 'Owner-deductible expenses can only be recorded on Third-Party Consignment vehicles.',
            ]);
        }

        if ($bearer === ExpenseBearer::OwnerDeductible && $vehicle->activeConsignment === null) {
            throw ValidationException::withMessages([
                'borne_by' => 'Link a consignment agreement to this vehicle before recording owner-deductible expenses.',
            ]);
        }

        $amount = Money::fromDecimal((string) $data['amount']);
        if (! $amount->isPositive()) {
            throw ValidationException::withMessages([
                'amount' => 'Expense amount must be greater than zero.',
            ]);
        }

        return DB::transaction(function () use ($data, $actor, $vehicle, $bearer, $amount, $receipt): VehicleExpense {
            $receiptPath = null;
            $receiptOriginalName = null;

            if ($receipt !== null) {
                $receiptPath = $receipt->store("private/expenses/{$vehicle->id}", 'local');
                $receiptOriginalName = $receipt->getClientOriginalName();
            }

            /** @var VehicleExpense $expense */
            $expense = VehicleExpense::query()->create([
                'reference_number' => $this->referenceGenerator->generate('EXP'),
                'vehicle_id' => $vehicle->id,
                'consignment_id' => $vehicle->ownership_type === OwnershipType::Consignment
                    ? $vehicle->activeConsignment?->id
                    : null,
                'expense_category_id' => (int) $data['expense_category_id'],
                'borne_by' => $bearer,
                'description' => (string) $data['description'],
                'amount' => $amount->toDecimal(),
                'amount_paid' => '0.00',
                'payment_status' => ExpensePaymentStatus::Unpaid,
                'expense_date' => $data['expense_date'],
                'vendor_name' => $data['vendor_name'] ?? null,
                'receipt_path' => $receiptPath,
                'receipt_original_name' => $receiptOriginalName,
                'recorded_by' => $actor->id,
            ]);

            $this->auditLogger->audit(
                event: 'expense.recorded',
                auditable: $expense,
                newValues: $expense->only([
                    'reference_number',
                    'vehicle_id',
                    'consignment_id',
                    'borne_by',
                    'amount',
                    'expense_date',
                ]),
                actor: $actor,
            );

            $this->auditLogger->activity(
                action: 'expense.recorded',
                description: "Recorded {$bearer->label()} expense {$expense->reference_number} ({$amount->format()}) on {$vehicle->reference_number}.",
                subject: $expense,
                actor: $actor,
            );

            if (! empty($data['pay_immediately']) && $actor->can('expenses.pay')) {
                $this->recordPayment->execute($expense, [
                    'amount' => $amount->toDecimal(),
                    'payment_method_id' => $data['payment_method_id'],
                    'financial_account_id' => $data['financial_account_id'],
                    'paid_at' => $data['expense_date'],
                    'payment_reference' => $data['payment_reference'] ?? null,
                    'notes' => 'Paid immediately at expense recording.',
                ], $actor);
            }

            return $expense->fresh() ?? $expense;
        });
    }
}
