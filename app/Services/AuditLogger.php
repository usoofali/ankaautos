<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class AuditLogger
{
    /**
     * Record a general operational activity event.
     *
     * @param  array<string, mixed>|null  $metadata
     */
    public function activity(
        string $action,
        string $description,
        ?Model $subject = null,
        ?array $metadata = null,
        ?User $actor = null,
    ): ActivityLog {
        $user = $actor ?? Auth::user();
        $request = request();

        return ActivityLog::query()->create([
            'user_id' => $user instanceof User ? $user->id : null,
            'action' => $action,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'description' => $description,
            'metadata' => $metadata,
            'ip_address' => $request->ip(),
            'created_at' => now(),
        ]);
    }

    /**
     * Record an immutable audit entry with before-and-after diffs for sensitive operations (SSD 19.12).
     *
     * @param  array<string, mixed>|null  $oldValues
     * @param  array<string, mixed>|null  $newValues
     */
    public function audit(
        string $event,
        Model $auditable,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?string $reason = null,
        ?User $actor = null,
    ): AuditLog {
        $user = $actor ?? Auth::user();
        $request = request();

        return AuditLog::query()->create([
            'user_id' => $user instanceof User ? $user->id : null,
            'event' => $event,
            'auditable_type' => $auditable->getMorphClass(),
            'auditable_id' => (int) $auditable->getKey(),
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'reason' => $reason,
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 500),
            'created_at' => now(),
        ]);
    }
}
