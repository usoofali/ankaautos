<?php

namespace App\Services;

use App\Models\ReferenceSequence;
use Illuminate\Support\Facades\DB;

/**
 * Atomic, concurrency-safe generator for human-readable business reference numbers (SSD 18.6).
 * Format: {ORG_PREFIX}-{TYPE}-{YEAR}-{NNNN}, e.g. ANK-VEH-2026-0001.
 */
class ReferenceNumberGenerator
{
    public function __construct(
        private BusinessSettingService $settings,
    ) {}

    public function generate(string $entityCode, ?int $year = null): string
    {
        $year ??= (int) now()->format('Y');
        $orgPrefix = strtoupper(trim($this->settings->get('reference_prefix', 'ANK')));
        $sequencePrefix = strtoupper(trim($entityCode));

        $nextNumber = DB::transaction(function () use ($sequencePrefix, $year): int {
            /** @var ReferenceSequence|null $sequence */
            $sequence = ReferenceSequence::query()
                ->where('prefix', $sequencePrefix)
                ->where('year', $year)
                ->lockForUpdate()
                ->first();

            if ($sequence === null) {
                $sequence = ReferenceSequence::query()->create([
                    'prefix' => $sequencePrefix,
                    'year' => $year,
                    'last_number' => 1,
                ]);

                return 1;
            }

            $sequence->last_number++;
            $sequence->save();

            return $sequence->last_number;
        });

        return sprintf('%s-%s-%d-%04d', $orgPrefix, $sequencePrefix, $year, $nextNumber);
    }
}
