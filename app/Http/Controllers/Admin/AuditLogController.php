<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AuditLogController extends Controller
{
    public function index(Request $request): Response
    {
        $tab = $request->string('tab', 'audit')->toString() === 'activity' ? 'activity' : 'audit';
        $search = trim((string) $request->string('search'));
        $perPage = in_array($request->integer('per_page', 15), [10, 15, 25, 50], true)
            ? $request->integer('per_page', 15)
            : 15;

        if ($tab === 'activity') {
            $logs = ActivityLog::query()
                ->with('user:id,name,email')
                ->when($search !== '', function ($query) use ($search): void {
                    $query->where(function ($sub) use ($search): void {
                        $sub->where('action', 'like', "%{$search}%")
                            ->orWhere('description', 'like', "%{$search}%")
                            ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$search}%"));
                    });
                })
                ->latest('created_at')
                ->paginate($perPage)
                ->withQueryString()
                ->through(static fn (ActivityLog $log): array => [
                    'id' => $log->id,
                    'user_name' => $log->user?->name ?? 'System',
                    'user_email' => $log->user?->email,
                    'action' => $log->action,
                    'description' => $log->description,
                    'ip_address' => $log->ip_address,
                    'created_at' => $log->created_at?->toISOString(),
                ]);
        } else {
            $logs = AuditLog::query()
                ->with('user:id,name,email')
                ->when($search !== '', function ($query) use ($search): void {
                    $query->where(function ($sub) use ($search): void {
                        $sub->where('event', 'like', "%{$search}%")
                            ->orWhere('auditable_type', 'like', "%{$search}%")
                            ->orWhere('reason', 'like', "%{$search}%")
                            ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$search}%"));
                    });
                })
                ->latest('created_at')
                ->paginate($perPage)
                ->withQueryString()
                ->through(static fn (AuditLog $log): array => [
                    'id' => $log->id,
                    'user_name' => $log->user?->name ?? 'System',
                    'user_email' => $log->user?->email,
                    'event' => $log->event,
                    'auditable_type' => class_basename($log->auditable_type),
                    'auditable_id' => $log->auditable_id,
                    'old_values' => $log->old_values,
                    'new_values' => $log->new_values,
                    'reason' => $log->reason,
                    'ip_address' => $log->ip_address,
                    'created_at' => $log->created_at?->toISOString(),
                ]);
        }

        return Inertia::render('admin/audit/Index', [
            'activeTab' => $tab,
            'logs' => $logs,
            'filters' => [
                'search' => $search,
                'per_page' => $perPage,
            ],
        ]);
    }
}
