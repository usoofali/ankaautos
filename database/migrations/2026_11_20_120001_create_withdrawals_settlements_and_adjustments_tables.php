<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('refunds', function (Blueprint $table): void {
            $table->id();
            $table->string('reference_number', 40)->unique();
            $table->string('refundable_type');
            $table->unsignedBigInteger('refundable_id');
            $table->foreignId('customer_id')->constrained('customers')->restrictOnDelete();
            $table->decimal('amount', 15, 2);
            $table->foreignId('payment_method_id')->constrained('payment_methods')->restrictOnDelete();
            $table->foreignId('financial_account_id')->constrained('financial_accounts')->restrictOnDelete();
            $table->text('reason');
            $table->date('refunded_at')->index();
            $table->foreignId('approved_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['refundable_type', 'refundable_id']);
        });

        Schema::create('financial_adjustments', function (Blueprint $table): void {
            $table->id();
            $table->string('reference_number', 40)->unique();
            $table->string('revenue_bucket', 30)->index();
            $table->string('direction', 20);
            $table->decimal('amount', 15, 2);
            $table->foreignId('vehicle_id')->nullable()->constrained('vehicles')->restrictOnDelete();
            $table->foreignId('financial_account_id')->nullable()->constrained('financial_accounts')->restrictOnDelete();
            $table->text('reason');
            $table->date('adjusted_at')->index();
            $table->foreignId('approved_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('withdrawal_requests', function (Blueprint $table): void {
            $table->id();
            $table->string('reference_number', 40)->unique();
            $table->foreignId('consignment_id')->constrained('consignments')->restrictOnDelete();
            $table->foreignId('vehicle_id')->constrained('vehicles')->restrictOnDelete();
            $table->foreignId('vehicle_owner_id')->constrained('vehicle_owners')->restrictOnDelete();
            $table->string('status', 40)->default('awaiting_payment')->index();
            $table->text('reason');
            $table->decimal('total_owner_expenses', 15, 2)->default(0);
            $table->decimal('total_withdrawal_charges', 15, 2)->default(0);
            $table->decimal('total_due', 15, 2)->default(0);
            $table->decimal('amount_paid', 15, 2)->default(0);
            $table->decimal('balance_due', 15, 2)->default(0);
            $table->timestamp('requested_at')->useCurrent()->index();
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('initiated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('owner_settlements', function (Blueprint $table): void {
            $table->id();
            $table->string('reference_number', 40)->unique();
            $table->foreignId('consignment_id')->constrained('consignments')->restrictOnDelete();
            $table->foreignId('sale_id')->constrained('sales')->restrictOnDelete();
            $table->foreignId('vehicle_id')->constrained('vehicles')->restrictOnDelete();
            $table->foreignId('vehicle_owner_id')->constrained('vehicle_owners')->restrictOnDelete();
            $table->decimal('gross_sale_price', 15, 2);
            $table->decimal('commission_amount', 15, 2)->default(0);
            $table->decimal('total_expense_deductions', 15, 2)->default(0);
            $table->decimal('total_charge_deductions', 15, 2)->default(0);
            $table->decimal('net_payable_to_owner', 15, 2)->default(0);
            $table->decimal('amount_paid', 15, 2)->default(0);
            $table->decimal('balance_due', 15, 2)->default(0);
            $table->string('status', 50)->default('locked_awaiting_customer_payment')->index();
            $table->timestamp('settled_at')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('owner_settlement_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('owner_settlement_id')->constrained('owner_settlements')->cascadeOnDelete();
            $table->string('item_type', 40);
            $table->string('label', 255);
            $table->decimal('amount', 15, 2);
            $table->string('source_type')->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
        });

        Schema::create('owner_payments', function (Blueprint $table): void {
            $table->id();
            $table->string('reference_number', 40)->unique();
            $table->string('idempotency_key', 100)->nullable()->unique();
            $table->foreignId('owner_settlement_id')->constrained('owner_settlements')->restrictOnDelete();
            $table->foreignId('vehicle_owner_id')->constrained('vehicle_owners')->restrictOnDelete();
            $table->foreignId('payment_method_id')->constrained('payment_methods')->restrictOnDelete();
            $table->foreignId('financial_account_id')->constrained('financial_accounts')->restrictOnDelete();
            $table->decimal('amount', 15, 2);
            $table->date('paid_at')->index();
            $table->string('payment_reference', 120)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('owner_payments');
        Schema::dropIfExists('owner_settlement_items');
        Schema::dropIfExists('owner_settlements');
        Schema::dropIfExists('withdrawal_requests');
        Schema::dropIfExists('financial_adjustments');
        Schema::dropIfExists('refunds');
    }
};
