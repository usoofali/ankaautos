<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consignments', function (Blueprint $table): void {
            $table->id();
            $table->string('reference_number', 40)->unique();
            $table->foreignId('vehicle_id')->constrained('vehicles')->restrictOnDelete();
            $table->foreignId('vehicle_owner_id')->constrained('vehicle_owners')->restrictOnDelete();
            $table->string('status', 40)->default('active')->index();
            $table->date('received_at')->index();
            $table->date('expected_review_date')->nullable();
            $table->decimal('agreed_selling_price', 15, 2);
            $table->decimal('minimum_acceptable_price', 15, 2)->nullable();
            $table->string('commission_type', 40);
            $table->decimal('commission_value', 15, 2)->default(0);
            $table->text('commission_notes')->nullable();
            $table->text('expense_responsibility_notes')->nullable();
            $table->text('withdrawal_terms')->nullable();
            $table->text('settlement_terms')->nullable();
            $table->text('remarks')->nullable();
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('consignment_agreements', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('consignment_id')->unique()->constrained('consignments')->cascadeOnDelete();
            $table->unsignedInteger('current_version')->default(1);
            $table->date('commencement_date');
            $table->unsignedSmallInteger('duration_days')->nullable();
            $table->string('signed_document_path', 255)->nullable();
            $table->timestamp('signed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('consignment_agreement_versions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('consignment_agreement_id')->constrained('consignment_agreements')->cascadeOnDelete();
            $table->unsignedInteger('version_number');
            $table->decimal('agreed_selling_price', 15, 2);
            $table->decimal('minimum_acceptable_price', 15, 2)->nullable();
            $table->string('commission_type', 40);
            $table->decimal('commission_value', 15, 2)->default(0);
            $table->text('commission_notes')->nullable();
            $table->text('expense_responsibility_notes')->nullable();
            $table->text('withdrawal_terms')->nullable();
            $table->text('settlement_terms')->nullable();
            $table->text('amendment_reason')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['consignment_agreement_id', 'version_number'], 'consignment_agreement_version_unique');
        });

        Schema::create('consignment_status_histories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('consignment_id')->constrained('consignments')->cascadeOnDelete();
            $table->string('from_status', 40)->nullable();
            $table->string('to_status', 40);
            $table->text('reason')->nullable();
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consignment_status_histories');
        Schema::dropIfExists('consignment_agreement_versions');
        Schema::dropIfExists('consignment_agreements');
        Schema::dropIfExists('consignments');
    }
};
