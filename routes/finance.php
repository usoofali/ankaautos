<?php

use App\Http\Controllers\FinanceController;
use App\Http\Controllers\OwnerSettlementController;
use App\Http\Controllers\WithdrawalController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'active'])->group(function (): void {
    // Consignment Withdrawals
    Route::prefix('withdrawals')->name('withdrawals.')->group(function (): void {
        Route::get('/', [WithdrawalController::class, 'index'])
            ->middleware('permission:withdrawals.view')
            ->name('index');
        Route::get('/create', [WithdrawalController::class, 'create'])
            ->middleware('permission:withdrawals.initiate')
            ->name('create');
        Route::post('/', [WithdrawalController::class, 'store'])
            ->middleware('permission:withdrawals.initiate')
            ->name('store');
        Route::get('/{withdrawal}', [WithdrawalController::class, 'show'])
            ->middleware('permission:withdrawals.view')
            ->name('show');
        Route::post('/{withdrawal}/charges', [WithdrawalController::class, 'addCharge'])
            ->middleware('permission:charges.apply')
            ->name('charges.store');
        Route::post('/{withdrawal}/payments', [WithdrawalController::class, 'recordPayment'])
            ->middleware('permission:payments.record')
            ->name('payments.store');
        Route::post('/{withdrawal}/complete', [WithdrawalController::class, 'completeHandover'])
            ->middleware('permission:withdrawals.complete')
            ->name('complete');
        Route::get('/{withdrawal}/statement-pdf', [WithdrawalController::class, 'statementPdf'])
            ->middleware('permission:withdrawals.view')
            ->name('statement-pdf');
    });

    // Consignment Owner Settlements
    Route::prefix('settlements')->name('settlements.')->group(function (): void {
        Route::get('/', [OwnerSettlementController::class, 'index'])
            ->middleware('permission:settlements.view')
            ->name('index');
        Route::get('/{settlement}', [OwnerSettlementController::class, 'show'])
            ->middleware('permission:settlements.view')
            ->name('show');
        Route::post('/{settlement}/payouts', [OwnerSettlementController::class, 'recordPayout'])
            ->middleware('permission:settlements.process')
            ->name('payouts.store');
        Route::get('/{settlement}/statement-pdf', [OwnerSettlementController::class, 'statementPdf'])
            ->middleware('permission:settlements.view')
            ->name('statement-pdf');
    });

    // Financial Ledger & Adjustments
    Route::prefix('finance')->name('finance.')->group(function (): void {
        Route::get('/', [FinanceController::class, 'index'])
            ->middleware('permission:finance.view')
            ->name('index');
        Route::post('/adjustments', [FinanceController::class, 'storeAdjustment'])
            ->middleware('permission:finance.adjust')
            ->name('adjustments.store');
    });
});
