<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_brands', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 100)->unique();
            $table->string('slug', 120)->unique();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('vehicle_models', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('vehicle_brand_id')->constrained('vehicle_brands')->restrictOnDelete();
            $table->string('name', 100);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();

            $table->unique(['vehicle_brand_id', 'name']);
        });

        Schema::create('vehicle_categories', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 100)->unique();
            $table->string('slug', 120)->unique();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('vehicle_conditions', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 100)->unique();
            $table->string('slug', 120)->unique();
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('severity_order')->default(1);
            $table->string('badge_variant', 30)->default('neutral');
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('expense_categories', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 100)->unique();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('charge_categories', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 100)->unique();
            $table->text('description')->nullable();
            $table->json('applicable_types')->nullable();
            $table->decimal('default_amount', 15, 2)->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('payment_methods', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 100)->unique();
            $table->string('code', 40)->unique();
            $table->boolean('requires_reference')->default(false);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('financial_accounts', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 120)->unique();
            $table->string('bank_name', 120)->nullable();
            $table->string('account_number', 40)->nullable();
            $table->string('account_type', 40)->default('bank');
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('document_categories', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 100)->unique();
            $table->text('description')->nullable();
            $table->boolean('is_private')->default(true);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_categories');
        Schema::dropIfExists('financial_accounts');
        Schema::dropIfExists('payment_methods');
        Schema::dropIfExists('charge_categories');
        Schema::dropIfExists('expense_categories');
        Schema::dropIfExists('vehicle_conditions');
        Schema::dropIfExists('vehicle_categories');
        Schema::dropIfExists('vehicle_models');
        Schema::dropIfExists('vehicle_brands');
    }
};
