<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table): void {
            $table->id();
            $table->string('reference_number', 40)->unique();
            $table->string('customer_type', 30)->default('individual')->index();
            $table->string('name', 150)->index();
            $table->string('phone', 30)->index();
            $table->string('alt_phone', 30)->nullable();
            $table->string('email', 150)->nullable();
            $table->text('address')->nullable();
            $table->string('id_type', 60)->nullable();
            $table->string('id_number', 80)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('customer_inquiries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->restrictOnDelete();
            $table->foreignId('vehicle_id')->constrained('vehicles')->restrictOnDelete();
            $table->string('channel', 40)->default('walk_in');
            $table->string('status', 30)->default('new')->index();
            $table->decimal('offered_price', 15, 2)->nullable();
            $table->date('inspection_date')->nullable();
            $table->text('inspection_notes')->nullable();
            $table->text('negotiation_notes')->nullable();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('reservations', function (Blueprint $table): void {
            $table->id();
            $table->string('reference_number', 40)->unique();
            $table->foreignId('vehicle_id')->constrained('vehicles')->restrictOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->restrictOnDelete();
            $table->decimal('agreed_price', 15, 2);
            $table->decimal('deposit_amount', 15, 2);
            $table->string('status', 30)->default('active')->index();
            $table->timestamp('reserved_at')->useCurrent()->index();
            $table->text('notes')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->string('deposit_disposition', 30)->nullable();
            $table->foreignId('conflicting_override_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('sales', function (Blueprint $table): void {
            $table->id();
            $table->string('reference_number', 40)->unique();
            $table->foreignId('vehicle_id')->constrained('vehicles')->restrictOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->restrictOnDelete();
            $table->foreignId('reservation_id')->nullable()->constrained('reservations')->restrictOnDelete();
            $table->foreignId('consignment_id')->nullable()->constrained('consignments')->restrictOnDelete();
            $table->string('ownership_type', 30)->index();
            $table->decimal('list_price', 15, 2);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('final_price', 15, 2);
            $table->decimal('amount_paid', 15, 2)->default(0);
            $table->decimal('balance_due', 15, 2);
            $table->boolean('is_installment')->default(false);
            $table->text('installment_terms')->nullable();
            $table->string('status', 30)->default('confirmed')->index();
            $table->timestamp('confirmed_at')->useCurrent()->index();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->foreignId('sales_officer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('discount_approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('early_handover_approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('early_handover_reason')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('sale_discounts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('sale_id')->constrained('sales')->cascadeOnDelete();
            $table->decimal('previous_price', 15, 2);
            $table->decimal('discount_amount', 15, 2);
            $table->decimal('new_price', 15, 2);
            $table->text('reason');
            $table->foreignId('approved_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('sale_status_histories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('sale_id')->constrained('sales')->cascadeOnDelete();
            $table->string('from_status', 30)->nullable();
            $table->string('to_status', 30);
            $table->text('reason')->nullable();
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('payments', function (Blueprint $table): void {
            $table->id();
            $table->string('reference_number', 40)->unique();
            $table->string('idempotency_key', 100)->nullable()->unique();
            $table->string('payable_type');
            $table->unsignedBigInteger('payable_id');
            $table->foreignId('customer_id')->nullable()->constrained('customers')->restrictOnDelete();
            $table->foreignId('vehicle_owner_id')->nullable()->constrained('vehicle_owners')->restrictOnDelete();
            $table->string('payment_type', 40)->index();
            $table->foreignId('payment_method_id')->constrained('payment_methods')->restrictOnDelete();
            $table->foreignId('financial_account_id')->constrained('financial_accounts')->restrictOnDelete();
            $table->decimal('amount', 15, 2);
            $table->date('paid_at')->index();
            $table->string('external_reference', 120)->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_voided')->default(false);
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['payable_type', 'payable_id']);
        });

        Schema::create('handovers', function (Blueprint $table): void {
            $table->id();
            $table->string('reference_number', 40)->unique();
            $table->string('handoverable_type');
            $table->unsignedBigInteger('handoverable_id');
            $table->foreignId('vehicle_id')->constrained('vehicles')->restrictOnDelete();
            $table->string('recipient_name', 150);
            $table->string('recipient_phone', 30);
            $table->string('recipient_id_number', 80)->nullable();
            $table->unsignedInteger('odometer_reading')->nullable();
            $table->string('fuel_level', 40)->nullable();
            $table->text('condition_summary')->nullable();
            $table->timestamp('handed_over_at')->useCurrent()->index();
            $table->foreignId('handed_over_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['handoverable_type', 'handoverable_id']);
        });

        Schema::create('handover_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('handover_id')->constrained('handovers')->cascadeOnDelete();
            $table->string('item_name', 120);
            $table->boolean('is_included')->default(true);
            $table->string('remarks', 255)->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('handover_items');
        Schema::dropIfExists('handovers');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('sale_status_histories');
        Schema::dropIfExists('sale_discounts');
        Schema::dropIfExists('sales');
        Schema::dropIfExists('reservations');
        Schema::dropIfExists('customer_inquiries');
        Schema::dropIfExists('customers');
    }
};
