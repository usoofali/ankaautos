<?php

use App\Http\Controllers\ConsignmentController;
use App\Http\Controllers\VehicleController;
use App\Http\Controllers\VehicleOwnerController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'active'])->group(function (): void {
    // Vehicle Inventory Management
    Route::prefix('vehicles')->name('vehicles.')->group(function (): void {
        Route::get('/', [VehicleController::class, 'index'])
            ->middleware('permission:vehicles.view')
            ->name('index');
        Route::get('/create', [VehicleController::class, 'create'])
            ->middleware('permission:vehicles.create')
            ->name('create');
        Route::post('/', [VehicleController::class, 'store'])
            ->middleware('permission:vehicles.create')
            ->name('store');
        Route::get('/{vehicle}', [VehicleController::class, 'show'])
            ->middleware('permission:vehicles.view')
            ->name('show');
        Route::get('/{vehicle}/edit', [VehicleController::class, 'edit'])
            ->middleware('permission:vehicles.edit')
            ->name('edit');
        Route::put('/{vehicle}', [VehicleController::class, 'update'])
            ->middleware('permission:vehicles.edit')
            ->name('update');
        Route::patch('/{vehicle}/status', [VehicleController::class, 'updateStatus'])
            ->middleware('permission:vehicles.change_status')
            ->name('status.update');
        Route::patch('/{vehicle}/condition', [VehicleController::class, 'updateCondition'])
            ->middleware('permission:vehicles.edit|vehicles.log_attendant_activity')
            ->name('condition.update');
        Route::post('/{vehicle}/attendant-logs', [VehicleController::class, 'logAttendantActivity'])
            ->middleware('permission:vehicles.log_attendant_activity')
            ->name('attendant-logs.store');
        Route::post('/{vehicle}/photos', [VehicleController::class, 'uploadPhotos'])
            ->middleware('permission:vehicles.edit')
            ->name('photos.store');
        Route::patch('/{vehicle}/photos/{photo}/primary', [VehicleController::class, 'setPrimaryPhoto'])
            ->middleware('permission:vehicles.edit')
            ->name('photos.primary');
        Route::delete('/{vehicle}/photos/{photo}', [VehicleController::class, 'deletePhoto'])
            ->middleware('permission:vehicles.edit')
            ->name('photos.destroy');
        Route::post('/{vehicle}/archive', [VehicleController::class, 'archive'])
            ->middleware('permission:vehicles.archive')
            ->name('archive');
    });

    // Third-Party Vehicle Owners
    Route::prefix('owners')->name('owners.')->group(function (): void {
        Route::get('/', [VehicleOwnerController::class, 'index'])
            ->middleware('permission:owners.view')
            ->name('index');
        Route::get('/create', [VehicleOwnerController::class, 'create'])
            ->middleware('permission:owners.manage')
            ->name('create');
        Route::post('/', [VehicleOwnerController::class, 'store'])
            ->middleware('permission:owners.manage')
            ->name('store');
        Route::get('/{owner}', [VehicleOwnerController::class, 'show'])
            ->middleware('permission:owners.view')
            ->name('show');
        Route::get('/{owner}/edit', [VehicleOwnerController::class, 'edit'])
            ->middleware('permission:owners.manage')
            ->name('edit');
        Route::put('/{owner}', [VehicleOwnerController::class, 'update'])
            ->middleware('permission:owners.manage')
            ->name('update');
    });

    // Consignment Management
    Route::prefix('consignments')->name('consignments.')->group(function (): void {
        Route::get('/', [ConsignmentController::class, 'index'])
            ->middleware('permission:consignments.view')
            ->name('index');
        Route::get('/create', [ConsignmentController::class, 'create'])
            ->middleware('permission:consignments.create')
            ->name('create');
        Route::post('/', [ConsignmentController::class, 'store'])
            ->middleware('permission:consignments.create')
            ->name('store');
        Route::get('/{consignment}', [ConsignmentController::class, 'show'])
            ->middleware('permission:consignments.view')
            ->name('show');
        Route::post('/{consignment}/amend-terms', [ConsignmentController::class, 'amendTerms'])
            ->middleware('permission:consignments.amend_terms')
            ->name('amend-terms');
        Route::patch('/{consignment}/status', [ConsignmentController::class, 'updateStatus'])
            ->middleware('permission:consignments.edit|consignments.amend_terms')
            ->name('status.update');
        Route::get('/{consignment}/agreement-pdf', [ConsignmentController::class, 'downloadAgreementPdf'])
            ->middleware('permission:consignments.view')
            ->name('agreement-pdf');
    });
});
