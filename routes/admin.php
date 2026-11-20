<?php

use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\BusinessSettingController;
use App\Http\Controllers\Admin\MasterDataController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'active'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function (): void {
        // Staff & User Management
        Route::middleware('permission:users.manage')->group(function (): void {
            Route::get('users', [UserController::class, 'index'])->name('users.index');
            Route::get('users/create', [UserController::class, 'create'])->name('users.create');
            Route::post('users', [UserController::class, 'store'])->name('users.store');
            Route::get('users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
            Route::put('users/{user}', [UserController::class, 'update'])->name('users.update');
            Route::patch('users/{user}/toggle-status', [UserController::class, 'toggleStatus'])->name('users.toggle-status');
        });

        // Roles & Permissions Documentation / Management
        Route::middleware('permission:roles.manage')->group(function (): void {
            Route::get('roles', [RoleController::class, 'index'])->name('roles.index');
            Route::get('roles/{role}/edit', [RoleController::class, 'edit'])->name('roles.edit');
            Route::put('roles/{role}', [RoleController::class, 'update'])->name('roles.update');
        });

        // Business Settings
        Route::middleware('permission:settings.manage')->group(function (): void {
            Route::get('business-settings', [BusinessSettingController::class, 'edit'])->name('business-settings.edit');
            Route::put('business-settings', [BusinessSettingController::class, 'update'])->name('business-settings.update');
        });

        // Master Data & Dynamic Charge Categories
        Route::middleware('permission:master_data.manage|charges.manage_categories')->group(function (): void {
            Route::get('master-data', [MasterDataController::class, 'index'])->name('master-data.index');
            Route::post('master-data/{type}', [MasterDataController::class, 'store'])->name('master-data.store');
            Route::put('master-data/{type}/{id}', [MasterDataController::class, 'update'])->name('master-data.update');
            Route::patch('master-data/{type}/{id}/toggle-active', [MasterDataController::class, 'toggleActive'])->name('master-data.toggle-active');
            Route::delete('master-data/{type}/{id}', [MasterDataController::class, 'destroy'])->name('master-data.destroy');
        });

        // Activity & Audit Logs
        Route::middleware('permission:audit.view')->group(function (): void {
            Route::get('audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');
        });
    });
