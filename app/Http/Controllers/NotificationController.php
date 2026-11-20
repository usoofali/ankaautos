<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Inertia\Inertia;
use Inertia\Response;

class NotificationController extends Controller
{
    public function index(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();
        $filter = $request->string('filter', 'all')->toString();
        $perPage = in_array($request->integer('per_page', 15), [10, 15, 25, 50], true)
            ? $request->integer('per_page', 15)
            : 15;

        $query = $user->notifications()->latest();

        if ($filter === 'unread') {
            $query->whereNull('read_at');
        } elseif ($filter === 'read') {
            $query->whereNotNull('read_at');
        }

        $notifications = $query
            ->paginate($perPage)
            ->withQueryString()
            ->through(static function (DatabaseNotification $notification): array {
                /** @var array<string, mixed> $data */
                $data = $notification->data;

                return [
                    'id' => $notification->id,
                    'title' => (string) ($data['title'] ?? 'Notification'),
                    'message' => (string) ($data['message'] ?? ''),
                    'category' => (string) ($data['category'] ?? 'general'),
                    'action_url' => isset($data['action_url']) ? (string) $data['action_url'] : null,
                    'severity' => (string) ($data['severity'] ?? 'info'),
                    'read_at' => $notification->read_at?->toISOString(),
                    'created_at' => $notification->created_at?->toISOString(),
                ];
            });

        return Inertia::render('notifications/Index', [
            'notifications' => $notifications,
            'filters' => [
                'filter' => $filter,
                'per_page' => $perPage,
            ],
        ]);
    }

    public function markAsRead(Request $request, string $notificationId): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        /** @var DatabaseNotification $notification */
        $notification = $user->notifications()->where('id', $notificationId)->firstOrFail();

        if ($notification->read_at === null) {
            $notification->markAsRead();
        }

        return back();
    }

    public function markAllAsRead(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $user->unreadNotifications->markAsRead();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'All notifications marked as read.',
        ]);

        return back();
    }
}
