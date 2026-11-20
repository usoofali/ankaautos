<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;
use LogicException;

/**
 * Immutable audit record for sensitive modifications and financial operations (SSD 18.4 & 19.12).
 *
 * @property int $id
 * @property int|null $user_id
 * @property string $event
 * @property string $auditable_type
 * @property int $auditable_id
 * @property array<string, mixed>|null $old_values
 * @property array<string, mixed>|null $new_values
 * @property string|null $reason
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property Carbon|null $created_at
 */
#[Fillable(['user_id', 'event', 'auditable_type', 'auditable_id', 'old_values', 'new_values', 'reason', 'ip_address', 'user_agent', 'created_at'])]
class AuditLog extends Model
{
    public const UPDATED_AT = null;

    protected static function booted(): void
    {
        static::updating(static function (): never {
            throw new LogicException('Audit logs are immutable and cannot be modified.');
        });

        static::deleting(static function (): never {
            throw new LogicException('Audit logs are immutable and cannot be deleted.');
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
            'created_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }
}
