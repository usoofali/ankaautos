<?php

use App\Http\Controllers\VehicleExpenseController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'active'])
    ->prefix('expenses')
    ->name('expenses.')
    ->group(function (): void {
        Route::get('/', [VehicleExpenseController::class, 'index'])
            ->middleware('permission:expenses.view')
            ->name('index');
        Route::get('/create', [VehicleExpenseController::class, 'create'])
            ->middleware('permission:expenses.create')
            ->name('create');
        Route::post('/', [VehicleExpenseController::class, 'store'])
            ->middleware('permission:expenses.create')
            ->name('store');
        Route::get('/{expense}', [VehicleExpenseController::class, 'show'])
            ->middleware('permission:expenses.view')
            ->name('show');
        Route::post('/{expense}/payments', [VehicleExpenseController::class, 'recordPayment'])
            ->middleware('permission:expenses.pay')
            ->name('payments.store');
        Route::get('/{expense}/receipt', [VehicleExpenseController::class, 'downloadReceipt'])
            ->middleware('permission:expenses.view')
            ->name('receipt');
    });
