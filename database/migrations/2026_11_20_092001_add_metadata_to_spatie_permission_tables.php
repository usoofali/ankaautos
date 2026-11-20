<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table): void {
            $table->string('display_name', 120)->nullable()->after('name');
            $table->text('description')->nullable()->after('display_name');
            $table->text('purpose')->nullable()->after('description');
            $table->text('responsibilities_summary')->nullable()->after('purpose');
        });

        Schema::table('permissions', function (Blueprint $table): void {
            $table->string('display_name', 120)->nullable()->after('name');
            $table->text('description')->nullable()->after('display_name');
            $table->string('module_group', 80)->default('General')->after('description')->index();
            $table->unsignedInteger('sort_order')->default(0)->after('module_group');
        });
    }

    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table): void {
            $table->dropColumn(['display_name', 'description', 'purpose', 'responsibilities_summary']);
        });

        Schema::table('permissions', function (Blueprint $table): void {
            $table->dropColumn(['display_name', 'description', 'module_group', 'sort_order']);
        });
    }
};
