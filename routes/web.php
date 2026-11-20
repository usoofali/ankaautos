<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PublicListingController;
use Illuminate\Support\Facades\Route;

// Public Anka Auto Sales Storefront & OpenGraph Vehicle Listings
Route::get('/', [PublicListingController::class, 'index'])->name('home');
Route::get('/listings/{vehicle:slug}', [PublicListingController::class, 'show'])->name('public.listings.show');

Route::middleware(['auth', 'verified', 'active'])->group(function (): void {
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::patch('notifications/{notificationId}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::post('notifications/read-all', [NotificationController::class, 'markAllAsRead'])->name('notifications.read-all');
});

require __DIR__.'/inventory.php';
require __DIR__.'/expenses.php';
require __DIR__.'/sales.php';
require __DIR__.'/finance.php';
require __DIR__.'/marketing_reports.php';
require __DIR__.'/admin.php';
require __DIR__.'/settings.php';
