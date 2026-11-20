<?php

use App\Http\Controllers\NotificationController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth', 'verified', 'active'])->group(function (): void {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');

    Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::patch('notifications/{notificationId}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::post('notifications/read-all', [NotificationController::class, 'markAllAsRead'])->name('notifications.read-all');
});

require __DIR__.'/inventory.php';
require __DIR__.'/expenses.php';
require __DIR__.'/sales.php';
require __DIR__.'/finance.php';
require __DIR__.'/admin.php';
require __DIR__.'/settings.php';
