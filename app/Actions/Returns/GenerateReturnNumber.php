<?php

declare(strict_types=1);

namespace App\Actions\Returns;

use App\Models\ReturnRequest;
use RuntimeException;

/**
 * Produces the customer-facing return reference, e.g. RET-2026-000042.
 *
 * Mirrors GenerateOrderNumber: readable, year-scoped, and distinct from the
 * order number so nobody quotes one when they mean the other. The unique index
 * on `number` remains the real guarantee against collision.
 */
class GenerateReturnNumber
{
    private const PREFIX = 'RET';

    private const PAD = 6;

    private const MAX_ATTEMPTS = 5;

    public function generate(): string
    {
        $year = now()->year;

        for ($attempt = 0; $attempt < self::MAX_ATTEMPTS; $attempt++) {
            $number = $this->compose($year, $this->nextSequence($year) + $attempt);

            if (! ReturnRequest::query()->where('number', $number)->exists()) {
                return $number;
            }
        }

        throw new RuntimeException('Could not allocate a return number.');
    }

    private function nextSequence(int $year): int
    {
        $prefix = self::PREFIX.'-'.$year.'-';

        $latest = ReturnRequest::query()
            ->where('number', 'like', $prefix.'%')
            ->orderByDesc('number')
            ->value('number');

        if ($latest === null) {
            return 1;
        }

        return ((int) substr($latest, strlen($prefix))) + 1;
    }

    private function compose(int $year, int $sequence): string
    {
        return sprintf('%s-%d-%s', self::PREFIX, $year, str_pad((string) $sequence, self::PAD, '0', STR_PAD_LEFT));
    }
}
