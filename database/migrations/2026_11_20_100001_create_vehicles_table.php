<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicles', function (Blueprint $table): void {
            $table->id();
            $table->string('reference_number', 40)->unique();
            $table->string('slug', 180)->unique();
            $table->string('ownership_type', 30)->index();
            $table->foreignId('vehicle_brand_id')->constrained('vehicle_brands')->restrictOnDelete();
            $table->foreignId('vehicle_model_id')->constrained('vehicle_models')->restrictOnDelete();
            $table->foreignId('vehicle_category_id')->constrained('vehicle_categories')->restrictOnDelete();
            $table->foreignId('vehicle_condition_id')->constrained('vehicle_conditions')->restrictOnDelete();
            $table->unsignedSmallInteger('year')->index();
            $table->string('vin', 60)->nullable()->index();
            $table->string('chassis_number', 80)->nullable()->index();
            $table->string('engine_number', 80)->nullable();
            $table->string('registration_number', 40)->nullable()->index();
            $table->string('transmission', 30)->default('automatic');
            $table->string('fuel_type', 30)->default('petrol');
            $table->string('drive_type', 20)->nullable();
            $table->string('exterior_colour', 60);
            $table->string('interior_colour', 60)->nullable();
            $table->unsignedInteger('mileage')->nullable();
            $table->string('steering_position', 30)->default('left_hand');
            $table->unsignedTinyInteger('doors')->nullable();
            $table->string('status', 40)->default('available')->index();
            $table->string('location', 120)->nullable();
            $table->date('received_at')->index();
            $table->timestamp('registered_at')->useCurrent();
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('acquisition_source', 150)->nullable();
            $table->decimal('acquisition_cost', 15, 2)->nullable();
            $table->string('acquisition_reference', 80)->nullable();
            $table->string('acquired_from', 150)->nullable();
            $table->decimal('advertised_price', 15, 2)->nullable()->index();
            $table->decimal('minimum_price', 15, 2)->nullable();
            $table->text('general_description')->nullable();
            $table->text('condition_remarks')->nullable();
            $table->text('mechanical_issues')->nullable();
            $table->text('bodywork_condition')->nullable();
            $table->text('accessories')->nullable();
            $table->text('internal_notes')->nullable();
            $table->boolean('is_public_listing')->default(true)->index();
            $table->text('duplicate_vin_override_reason')->nullable();
            $table->foreignId('duplicate_vin_overridden_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('vehicle_photos', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('vehicle_id')->constrained('vehicles')->cascadeOnDelete();
            $table->string('disk', 30)->default('public');
            $table->string('path', 255);
            $table->string('original_name', 255);
            $table->string('caption', 200)->nullable();
            $table->string('category', 40)->default('other');
            $table->boolean('is_primary')->default(false)->index();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('vehicle_status_histories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('vehicle_id')->constrained('vehicles')->cascadeOnDelete();
            $table->string('from_status', 40)->nullable();
            $table->string('to_status', 40);
            $table->text('reason')->nullable();
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent()->index();
        });

        Schema::create('vehicle_condition_histories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('vehicle_id')->constrained('vehicles')->cascadeOnDelete();
            $table->foreignId('from_condition_id')->nullable()->constrained('vehicle_conditions')->nullOnDelete();
            $table->foreignId('to_condition_id')->constrained('vehicle_conditions')->restrictOnDelete();
            $table->text('defects_noted')->nullable();
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent()->index();
        });

        Schema::create('vehicle_attendant_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('vehicle_id')->constrained('vehicles')->cascadeOnDelete();
            $table->string('activity_type', 40)->index();
            $table->text('description');
            $table->string('location_from', 120)->nullable();
            $table->string('location_to', 120)->nullable();
            $table->foreignId('logged_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('logged_at')->useCurrent()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_attendant_logs');
        Schema::dropIfExists('vehicle_condition_histories');
        Schema::dropIfExists('vehicle_status_histories');
        Schema::dropIfExists('vehicle_photos');
        Schema::dropIfExists('vehicles');
    }
};
