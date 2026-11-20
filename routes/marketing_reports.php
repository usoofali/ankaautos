<?php

use App\Http\Controllers\DocumentController;
use App\Http\Controllers\MarketingController;
use App\Http\Controllers\ReportController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'active'])->group(function (): void {
    // Marketing & Social Sharing
    Route::prefix('marketing')->name('marketing.')->group(function (): void {
        Route::get('/', [MarketingController::class, 'index'])
            ->middleware('permission:marketing.view')
            ->name('index');
        Route::get('/vehicles/{vehicle}/builder', [MarketingController::class, 'builder'])
            ->middleware('permission:marketing.view')
            ->name('builder');
        Route::post('/vehicles/{vehicle}/advertisement', [MarketingController::class, 'saveAdvertisement'])
            ->middleware('permission:marketing.manage')
            ->name('advertisement.save');
        Route::post('/vehicles/{vehicle}/share', [MarketingController::class, 'logShare'])
            ->middleware('permission:marketing.manage')
            ->name('share.log');
        Route::get('/vehicles/{vehicle}/photo-pack', [MarketingController::class, 'downloadPhotoPack'])
            ->middleware('permission:marketing.view')
            ->name('photo-pack');
    });

    // Private Document Management Vault
    Route::prefix('documents')->name('documents.')->group(function (): void {
        Route::get('/', [DocumentController::class, 'index'])
            ->middleware('permission:documents.view')
            ->name('index');
        Route::post('/', [DocumentController::class, 'store'])
            ->middleware('permission:documents.manage')
            ->name('store');
        Route::get('/{document}/download', [DocumentController::class, 'download'])
            ->middleware('permission:documents.view')
            ->name('download');
        Route::delete('/{document}', [DocumentController::class, 'destroy'])
            ->middleware('permission:documents.manage')
            ->name('destroy');
    });

    // 8 Exportable Reports (CSV + PDF)
    Route::prefix('reports')->name('reports.')->group(function (): void {
        Route::get('/', [ReportController::class, 'index'])
            ->middleware('permission:reports.view_operational|reports.view_financial')
            ->name('index');
        Route::get('/{slug}', [ReportController::class, 'show'])
            ->middleware('permission:reports.view_operational|reports.view_financial')
            ->name('show');
        Route::get('/{slug}/export-csv', [ReportController::class, 'exportCsv'])
            ->middleware('permission:reports.view_operational|reports.view_financial')
            ->name('export-csv');
        Route::get('/{slug}/export-pdf', [ReportController::class, 'exportPdf'])
            ->middleware('permission:reports.view_operational|reports.view_financial')
            ->name('export-pdf');
    });
});
