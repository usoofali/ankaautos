<?php

namespace App\Services;

use App\Enums\FinancialTransactionType;
use App\Enums\RevenueBucket;
use App\Enums\TransactionDirection;
use App\Models\FinancialTransaction;
use App\Models\User;
use App\Support\Money;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class FinancialLedgerService
{
    public function __construct(
        private ReferenceNumberGenerator $referenceGenerator,
    ) {}

    public function post(
        FinancialTransactionType $type,
        TransactionDirection $direction,
        RevenueBucket $bucket,
        Money $amount,
        string $description,
        ?int $financialAccountId = null,
        ?int $paymentMethodId = null,
        ?int $vehicleId = null,
        ?Model $source = null,
        ?Carbon $transactedAt = null,
        ?User $actor = null,
    ): FinancialTransaction {
        return FinancialTransaction::query()->create([
            'reference_number' => $this->referenceGenerator->generate('FIN'),
            'transaction_type' => $type,
            'direction' => $direction,
            'revenue_bucket' => $bucket,
            'amount' => $amount->toDecimal(),
            'financial_account_id' => $financialAccountId,
            'payment_method_id' => $paymentMethodId,
            'vehicle_id' => $vehicleId,
            'source_type' => $source?->getMorphClass(),
            'source_id' => $source?->getKey(),
            'description' => $description,
            'transacted_at' => $transactedAt ?? now(),
            'recorded_by' => $actor?->id,
            'created_at' => now(),
        ]);
    }
}
