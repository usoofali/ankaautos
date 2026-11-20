<?php

use App\Models\User;
use App\Notifications\BusinessEventNotification;

test('users can view and mark their notifications as read but cannot access others notifications', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    $user->notify(new BusinessEventNotification(
        title: 'Consignment Registered',
        message: 'Vehicle ANK-VEH-2026-0001 has been registered.',
        category: 'consignment',
        severity: 'success',
    ));

    $user->notify(new BusinessEventNotification(
        title: 'Payment Recorded',
        message: 'Deposit payment received.',
        category: 'finance',
        severity: 'info',
    ));

    $otherUser->notify(new BusinessEventNotification(
        title: 'Private Alert',
        message: 'For another user.',
    ));

    $firstNotification = $user->notifications()->firstOrFail();
    $otherNotification = $otherUser->notifications()->firstOrFail();

    $this->actingAs($user)
        ->get(route('notifications.index'))
        ->assertOk();

    $this->actingAs($user)
        ->patch(route('notifications.read', $firstNotification->id))
        ->assertRedirect();

    expect($firstNotification->fresh()->read_at)->not->toBeNull()
        ->and($user->unreadNotifications()->count())->toBe(1);

    $this->actingAs($user)
        ->patch(route('notifications.read', $otherNotification->id))
        ->assertNotFound();

    $this->actingAs($user)
        ->post(route('notifications.read-all'))
        ->assertRedirect();

    expect($user->unreadNotifications()->count())->toBe(0);
});
