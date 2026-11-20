<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_owners', function (Blueprint $table): void {
            $table->id();
            $table->string('reference_number', 40)->unique();
            $table->string('owner_category', 30)->default('individual')->index();
            $table->string('name', 150)->index();
            $table->string('phone', 30)->index();
            $table->string('alt_phone', 30)->nullable();
            $table->string('email', 150)->nullable();
            $table->text('address')->nullable();
            $table->string('id_type', 60)->nullable();
            $table->string('id_number', 80)->nullable();
            $table->string('bank_name', 120)->nullable();
            $table->string('bank_account_name', 150)->nullable();
            $table->string('bank_account_number', 40)->nullable();
            $table->text('remarks')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_owners');
    }
};
