<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_expenses', function (Blueprint $table): void {
            $table->id();
            $table->string('reference_number', 40)->unique();
            $table->foreignId('vehicle_id')->constrained('vehicles')->restrictOnDelete();
            $table->foreignId('consignment_id')->nullable()->constrained('consignments')->restrictOnDelete();
            $table->foreignId('expense_category_id')->constrained('expense_categories')->restrictOnDelete();
            $table->string('borne_by', 30)->default('company')->index();
            $table->string('description', 500);
            $table->decimal('amount', 15, 2);
            $table->decimal('amount_paid', 15, 2)->default(0);
            $table->string('payment_status', 30)->default('unpaid')->index();
            $table->date('expense_date')->index();
            $table->string('vendor_name', 150)->nullable();
            $table->string('receipt_path', 255)->nullable();
            $table->string('receipt_original_name', 255)->nullable();
            $table->unsignedBigInteger('settled_in_owner_settlement_id')->nullable()->index();
            $table->unsignedBigInteger('settled_in_withdrawal_id')->nullable()->index();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('expense_payments', function (Blueprint $table): void {
            $table->id();
            $table->string('reference_number', 40)->unique();
            $table->foreignId('vehicle_expense_id')->constrained('vehicle_expenses')->restrictOnDelete();
            $table->foreignId('payment_method_id')->constrained('payment_methods')->restrictOnDelete();
            $table->foreignId('financial_account_id')->constrained('financial_accounts')->restrictOnDelete();
            $table->decimal('amount', 15, 2);
            $table->date('paid_at')->index();
            $table->string('payment_reference', 100)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('transaction_charges', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('charge_category_id')->nullable()->constrained('charge_categories')->restrictOnDelete();
            $table->string('custom_label', 150)->nullable();
            $table->string('chargeable_type');
            $table->unsignedBigInteger('chargeable_id');
            $table->string('transaction_type', 40)->index();
            $table->decimal('amount', 15, 2);
            $table->text('notes')->nullable();
            $table->foreignId('applied_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['chargeable_type', 'chargeable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaction_charges');
        Schema::dropIfExists('expense_payments');
        Schema::dropIfExists('vehicle_expenses');
    }
};
