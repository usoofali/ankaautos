<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $consignment_id
 * @property int $current_version
 * @property Carbon $commencement_date
 * @property int|null $duration_days
 * @property string|null $signed_document_path
 * @property Carbon|null $signed_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['consignment_id', 'current_version', 'commencement_date', 'duration_days', 'signed_document_path', 'signed_at'])]
class ConsignmentAgreement extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'current_version' => 'integer',
            'commencement_date' => 'date',
            'duration_days' => 'integer',
            'signed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Consignment, $this>
     */
    public function consignment(): BelongsTo
    {
        return $this->belongsTo(Consignment::class);
    }

    /**
     * @return HasMany<ConsignmentAgreementVersion, $this>
     */
    public function versions(): HasMany
    {
        return $this->hasMany(ConsignmentAgreementVersion::class)->orderByDesc('version_number');
    }
}
