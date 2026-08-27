<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\OrderStatus;
use App\Repositories\DashboardRepository;
use App\Support\DatePeriodFilter;
use Illuminate\Support\Collection;

/**
 * Assembles the admin dashboard.
 *
 * The repository answers questions; this class decides which questions the
 * page asks and shapes the answers for Blade — including picking the right
 * language off the analytics rows, which come back as raw aggregates rather
 * than models and so miss the HasTranslations trait.
 */
class DashboardService
{
    public function __construct(private readonly DashboardRepository $repository)
    {
    }

    /**
     * Everything the dashboard renders, for one period.
     *
     * @return array<string, mixed>
     */
    public function overview(DatePeriodFilter $period): array
    {
        return [
            'period'     => $period,
            'cards'      => $this->cards($period),
            'charts'     => $this->charts($period),
            'recent'     => $this->repository->recentOrders(),
            'lowStock'   => $this->repository->lowStockVariants(),
            'analytics'  => $this->analytics($period),
        ];
    }

    /**
     * The eight headline figures, each with the trend against the previous
     * equivalent window.
     *
     * A number without a comparison is hard to act on: 12 orders is good or
     * bad depending on whether last week was 6 or 30.
     *
     * @return array<string, array{value: int, change: ?float, money: bool}>
     */
    private function cards(DatePeriodFilter $period): array
    {
        $current = $this->repository->cards($period);

        // Only period-scoped figures get a trend. Pending, awaiting-shipping
        // and the stock counts are "right now" queues, so comparing them to a
        // past window is meaningless — and asking for them twice would be four
        // wasted queries.
        $previous = $this->repository->comparableCards($period->previous());
        $money = ['revenue'];

        $cards = [];

        foreach ($current as $key => $value) {
            $cards[$key] = [
                'value'  => $value,
                'money'  => in_array($key, $money, true),
                'change' => array_key_exists($key, $previous)
                    ? $this->percentageChange($previous[$key], $value)
                    : null,
            ];
        }

        return $cards;
    }

    /**
     * Growth from one window to the next.
     *
     * Returns null rather than 100% when the previous window was zero: every
     * first sale is not an infinite improvement, and showing it as one trains
     * people to ignore the number.
     */
    private function percentageChange(int $previous, int $current): ?float
    {
        if ($previous === 0) {
            return null;
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }

    /**
     * @return array<string, mixed>
     */
    private function charts(DatePeriodFilter $period): array
    {
        $series = $this->repository->timeSeries($period);
        $byStatus = $this->repository->ordersByStatus($period);

        return [
            'series'   => $series,
            'grouping' => $period->grouping(),
            'status'   => collect($byStatus)
                ->map(fn (int $count, string $status): array => [
                    'label'   => OrderStatus::from($status)->label(),
                    'count'   => $count,
                    'variant' => OrderStatus::from($status)->badge(),
                ])
                ->values()
                ->all(),
        ];
    }

    /**
     * @return array<string, Collection<int, object>>
     */
    private function analytics(DatePeriodFilter $period): array
    {
        return [
            'products'     => $this->localise($this->repository->bestSellingProducts($period)),
            'categories'   => $this->localise($this->repository->topCategories($period)),
            'sizes'        => $this->localise($this->repository->topSizes($period)),
            'colors'       => $this->localise($this->repository->topColors($period)),
            'governorates' => $this->localise($this->repository->salesByGovernorate($period)),
        ];
    }

    /**
     * Resolve `name` on raw aggregate rows the way a model would.
     *
     * Falls back to the other language rather than rendering blank: a product
     * with only an English name still needs to appear in the report.
     *
     * @param  Collection<int, object>  $rows
     * @return Collection<int, object>
     */
    private function localise(Collection $rows): Collection
    {
        $locale = app()->getLocale();

        return $rows->each(function (object $row) use ($locale): void {
            $preferred = $row->{'name_'.$locale} ?? null;
            $fallback = $row->name_en ?? $row->name_ar ?? null;

            $row->name = filled($preferred) ? $preferred : $fallback;
        });
    }
}
