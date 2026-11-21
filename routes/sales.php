<?php

use App\Http\Controllers\CustomerController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\SaleController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'active'])->group(function (): void {
    // Customers & Enquiries
    Route::prefix('customers')->name('customers.')->group(function (): void {
        Route::get('/', [CustomerController::class, 'index'])
            ->middleware('permission:customers.view')
            ->name('index');
        Route::get('/create', [CustomerController::class, 'create'])
            ->middleware('permission:customers.manage')
            ->name('create');
        Route::post('/', [CustomerController::class, 'store'])
            ->middleware('permission:customers.manage')
            ->name('store');
        Route::post('/quick', [CustomerController::class, 'quickStore'])
            ->middleware('permission:customers.manage')
            ->name('quick-store');
        Route::get('/{customer}', [CustomerController::class, 'show'])
            ->middleware('permission:customers.view')
            ->name('show');
        Route::get('/{customer}/edit', [CustomerController::class, 'edit'])
            ->middleware('permission:customers.manage')
            ->name('edit');
        Route::put('/{customer}', [CustomerController::class, 'update'])
            ->middleware('permission:customers.manage')
            ->name('update');
        Route::post('/{customer}/inquiries', [CustomerController::class, 'storeInquiry'])
            ->middleware('permission:customers.manage')
            ->name('inquiries.store');
    });

    // Reservations
    Route::prefix('reservations')->name('reservations.')->group(function (): void {
        Route::get('/', [ReservationController::class, 'index'])
            ->middleware('permission:reservations.view')
            ->name('index');
        Route::get('/create', [ReservationController::class, 'create'])
            ->middleware('permission:reservations.manage')
            ->name('create');
        Route::post('/', [ReservationController::class, 'store'])
            ->middleware('permission:reservations.manage')
            ->name('store');
        Route::get('/{reservation}', [ReservationController::class, 'show'])
            ->middleware('permission:reservations.view')
            ->name('show');
        Route::post('/{reservation}/cancel', [ReservationController::class, 'cancel'])
            ->middleware('permission:reservations.override')
            ->name('cancel');
    });

    // Vehicle Sales, Payments & Handovers
    Route::prefix('sales')->name('sales.')->group(function (): void {
        Route::get('/', [SaleController::class, 'index'])
            ->middleware('permission:sales.view')
            ->name('index');
        Route::get('/create', [SaleController::class, 'create'])
            ->middleware('permission:sales.create')
            ->name('create');
        Route::post('/', [SaleController::class, 'store'])
            ->middleware('permission:sales.create')
            ->name('store');
        Route::get('/{sale}', [SaleController::class, 'show'])
            ->middleware('permission:sales.view')
            ->name('show');
        Route::post('/{sale}/payments', [SaleController::class, 'recordPayment'])
            ->middleware('permission:payments.record')
            ->name('payments.store');
        Route::post('/{sale}/handover', [SaleController::class, 'recordHandover'])
            ->middleware('permission:handovers.create')
            ->name('handover.store');
        Route::post('/{sale}/cancel', [SaleController::class, 'cancel'])
            ->middleware('permission:sales.cancel')
            ->name('cancel');
        Route::get('/{sale}/payments/{payment}/receipt-pdf', [SaleController::class, 'receiptPdf'])
            ->middleware('permission:sales.view')
            ->name('receipt-pdf');
        Route::get('/{sale}/invoice-pdf', [SaleController::class, 'invoicePdf'])
            ->middleware('permission:sales.view')
            ->name('invoice-pdf');
        Route::get('/{sale}/agreement-pdf', [SaleController::class, 'agreementPdf'])
            ->middleware('permission:sales.view')
            ->name('agreement-pdf');
        Route::get('/{sale}/handover-pdf', [SaleController::class, 'handoverPdf'])
            ->middleware('permission:handovers.view')
            ->name('handover-pdf');
    });
});
