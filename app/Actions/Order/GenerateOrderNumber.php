<?php

declare(strict_types=1);

namespace App\Actions\Order;

use App\Models\Order;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Produces the human-facing order reference, e.g. HOOR-2026-000042.
 *
 * Customers quote this on the phone and couriers write it on parcels, so it is
 * readable and year-scoped rather than being the primary key.
 *
 * The sequence is derived from the highest existing number for the year inside
 * the caller's transaction, and the unique index on `number` is the real
 * guarantee: if two requests somehow computed the same value, the second insert
 * fails rather than producing a duplicate reference.
 */
class GenerateOrderNumber
{
    private const PREFIX = 'HOOR';

    private const PAD = 6;

    private const MAX_ATTEMPTS = 5;

    public function generate(): string
    {
        $year = now()->year;

        for ($attempt = 0; $attempt < self::MAX_ATTEMPTS; $attempt++) {
            $number = $this->compose($year, $this->nextSequence($year) + $attempt);

            if (! Order::query()->where('number', $number)->exists()) {
                return $number;
            }
        }

        throw new RuntimeException('Could not allocate an order number.');
    }

    /**
     * The next sequence for a year, read from the highest number so far.
     */
    private function nextSequence(int $year): int
    {
        $prefix = self::PREFIX.'-'.$year.'-';

        $latest = Order::query()
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
