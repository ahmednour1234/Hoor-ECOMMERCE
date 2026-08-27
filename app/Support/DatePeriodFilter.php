<?php

declare(strict_types=1);

namespace App\Support;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * The window a dashboard is reporting on.
 *
 * Resolved from the query string once, then handed to every query, so a single
 * page cannot end up mixing windows — a card reading "today" beside a chart
 * reading "this month" is worse than either alone.
 *
 * Bounds are inclusive and snapped to whole days: Egypt sells in local time,
 * and a report that ends at "now" makes today's figure incomparable with
 * yesterday's.
 */
final class DatePeriodFilter
{
    public const TODAY = 'today';
    public const WEEK = '7d';
    public const MONTH = '30d';
    public const CUSTOM = 'custom';

    private function __construct(
        public readonly string $key,
        public readonly CarbonImmutable $start,
        public readonly CarbonImmutable $end,
    ) {
    }

    /**
     * Build from request input, falling back to 30 days.
     *
     * Anything unrecognised — a hand-edited URL, a stale bookmark — resolves to
     * the default rather than erroring: a dashboard should still draw.
     *
     * @param  array<string, mixed>  $input
     */
    public static function fromRequest(array $input): self
    {
        $key = (string) ($input['period'] ?? self::MONTH);

        if ($key === self::CUSTOM) {
            return self::custom($input['from'] ?? null, $input['to'] ?? null);
        }

        return self::preset($key);
    }

    public static function preset(string $key): self
    {
        $today = CarbonImmutable::today();

        return match ($key) {
            self::TODAY => new self(self::TODAY, $today, $today->endOfDay()),
            self::WEEK  => new self(self::WEEK, $today->subDays(6), $today->endOfDay()),
            default     => new self(self::MONTH, $today->subDays(29), $today->endOfDay()),
        };
    }

    /**
     * A custom range, defended against the ways a date input can arrive wrong.
     */
    public static function custom(mixed $from, mixed $to): self
    {
        $start = self::parse($from) ?? CarbonImmutable::today()->subDays(29);
        $end = self::parse($to) ?? CarbonImmutable::today();

        // A range entered backwards is a slip, not a request for no data.
        if ($start->greaterThan($end)) {
            [$start, $end] = [$end, $start];
        }

        return new self(self::CUSTOM, $start->startOfDay(), $end->endOfDay());
    }

    private static function parse(mixed $value): ?CarbonImmutable
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            return CarbonImmutable::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * The inclusive bounds, for a whereBetween.
     *
     * @return array{0: CarbonInterface, 1: CarbonInterface}
     */
    public function bounds(): array
    {
        return [$this->start, $this->end];
    }

    /**
     * Whole days covered, counting both ends.
     */
    public function days(): int
    {
        return (int) $this->start->startOfDay()->diffInDays($this->end->startOfDay()) + 1;
    }

    /**
     * Group daily or monthly, so a long range does not draw 365 bars.
     */
    public function grouping(): string
    {
        return $this->days() > 92 ? 'month' : 'day';
    }

    /**
     * The equivalent window immediately before this one, for "vs previous".
     */
    public function previous(): self
    {
        $length = $this->days();

        return new self(
            $this->key,
            $this->start->subDays($length),
            $this->start->subDay()->endOfDay(),
        );
    }

    public function isCustom(): bool
    {
        return $this->key === self::CUSTOM;
    }

    /**
     * The query string that reproduces this period.
     *
     * @return array<string, string>
     */
    public function toQuery(): array
    {
        return $this->isCustom()
            ? ['period' => self::CUSTOM, 'from' => $this->start->toDateString(), 'to' => $this->end->toDateString()]
            : ['period' => $this->key];
    }

    /**
     * @return list<string>
     */
    public static function presetKeys(): array
    {
        return [self::TODAY, self::WEEK, self::MONTH];
    }
}
