<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductVariant;
use App\Support\DatePeriodFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * The dashboard's reads.
 *
 * Every figure here is one grouped query. Aggregating in PHP would mean
 * loading every order in the window into memory to count it, which is fine on
 * a demo database and ruinous on a real one.
 *
 * Two conventions run through the whole class:
 *
 *   Revenue counts delivered orders only. Cash on delivery means a placed
 *   order is not money — counting it would overstate the business by however
 *   many orders are cancelled or refused at the door.
 *
 *   Revenue is dated by `delivered_at`, everything else by `created_at`.
 *   Filtering revenue by when the order was *placed* would attribute today's
 *   cash to last week.
 */
class DashboardRepository
{
    /**
     * Statuses that represent a sale that actually happened.
     *
     * @var list<OrderStatus>
     */
    private const EARNED = [OrderStatus::Delivered];

    /**
     * Statuses whose units are spoken for and awaiting dispatch.
     *
     * @var list<OrderStatus>
     */
    private const AWAITING_SHIPPING = [
        OrderStatus::Confirmed,
        OrderStatus::Preparing,
        OrderStatus::ReadyForShipping,
    ];

    // ------------------------------------------------------------------ Cards

    /**
     * The headline figures.
     *
     * @return array<string, int>
     */
    public function cards(DatePeriodFilter $period): array
    {
        return [
            'orders'            => $this->orderCount($period),
            'revenue'           => $this->revenue($period),
            'pending'           => $this->countByStatus(OrderStatus::Pending),
            'awaiting_shipping' => $this->countByStatuses(self::AWAITING_SHIPPING),
            'delivered'         => $this->countByStatus(OrderStatus::Delivered, $period),
            'cancelled'         => $this->countByStatus(OrderStatus::Cancelled, $period),
            'low_stock'         => $this->lowStockCount(),
            'out_of_stock'      => $this->outOfStockCount(),
        ];
    }

    /**
     * Only the figures that mean something compared against a past window.
     *
     * The trend row needs these for the previous period; re-running the stock
     * and work-queue counts as well would be four wasted queries per page,
     * since neither has a "previous window" to speak of.
     *
     * @return array<string, int>
     */
    public function comparableCards(DatePeriodFilter $period): array
    {
        return [
            'orders'    => $this->orderCount($period),
            'revenue'   => $this->revenue($period),
            'delivered' => $this->countByStatus(OrderStatus::Delivered, $period),
            'cancelled' => $this->countByStatus(OrderStatus::Cancelled, $period),
        ];
    }

    public function orderCount(DatePeriodFilter $period): int
    {
        return Order::query()
            ->whereBetween('created_at', $period->bounds())
            ->count();
    }

    /**
     * Money actually collected in the window, in piastres.
     */
    public function revenue(DatePeriodFilter $period): int
    {
        return (int) Order::query()
            ->whereIn('status', self::EARNED)
            ->whereBetween('delivered_at', $period->bounds())
            ->sum('total');
    }

    /**
     * A status count, over the window when one is given.
     *
     * Pending and awaiting-shipping are deliberately *not* windowed: they are
     * work queues. An order placed five weeks ago that nobody has confirmed is
     * exactly the one the dashboard must not hide.
     */
    private function countByStatus(OrderStatus $status, ?DatePeriodFilter $period = null): int
    {
        return $this->countByStatuses([$status], $period);
    }

    /**
     * @param  list<OrderStatus>  $statuses
     */
    private function countByStatuses(array $statuses, ?DatePeriodFilter $period = null): int
    {
        return Order::query()
            ->whereIn('status', $statuses)
            ->when($period, fn (Builder $q) => $q->whereBetween('created_at', $period->bounds()))
            ->count();
    }

    public function lowStockCount(): int
    {
        return ProductVariant::query()->active()->inStock()->lowStock()->count();
    }

    public function outOfStockCount(): int
    {
        return ProductVariant::query()->active()->where('stock_quantity', '<=', 0)->count();
    }

    // ----------------------------------------------------------------- Charts

    /**
     * Revenue and order count per day (or month), gap-filled.
     *
     * Two grouped queries, then a merge in PHP over at most ~92 buckets — the
     * gap fill is what makes a quiet Tuesday read as zero rather than vanishing
     * and distorting the line.
     *
     * @return list<array{label: string, orders: int, revenue: int}>
     */
    public function timeSeries(DatePeriodFilter $period): array
    {
        $unit = $period->grouping();

        $orders = $this->bucketed(
            Order::query()->whereBetween('created_at', $period->bounds()),
            'created_at',
            $unit,
            'count(*)',
        );

        $revenue = $this->bucketed(
            Order::query()
                ->whereIn('status', self::EARNED)
                ->whereBetween('delivered_at', $period->bounds()),
            'delivered_at',
            $unit,
            'sum(total)',
        );

        $series = [];
        $cursor = $period->start;

        while ($cursor->lessThanOrEqualTo($period->end)) {
            $key = $unit === 'month' ? $cursor->format('Y-m') : $cursor->toDateString();

            $series[] = [
                'label'   => $key,
                'orders'  => (int) ($orders[$key] ?? 0),
                'revenue' => (int) ($revenue[$key] ?? 0),
            ];

            $cursor = $unit === 'month' ? $cursor->addMonth()->startOfMonth() : $cursor->addDay();
        }

        return $series;
    }

    /**
     * Group a query into date buckets in SQL.
     *
     * The date expression is driven off the connection: SQLite has no DATE_FORMAT
     * and MySQL has no strftime, and the dashboard has to run on both.
     *
     * @param  Builder<Order>  $query
     * @return Collection<string, mixed>
     */
    private function bucketed(Builder $query, string $column, string $unit, string $aggregate): Collection
    {
        $expression = $this->dateExpression($column, $unit);

        return $query
            ->selectRaw("{$expression} as bucket, {$aggregate} as aggregate")
            ->groupBy('bucket')
            ->pluck('aggregate', 'bucket');
    }

    private function dateExpression(string $column, string $unit): string
    {
        $sqlite = DB::connection()->getDriverName() === 'sqlite';
        $format = $unit === 'month' ? '%Y-%m' : '%Y-%m-%d';

        return $sqlite
            ? "strftime('{$format}', {$column})"
            : "DATE_FORMAT({$column}, '{$format}')";
    }

    /**
     * Orders per status across the window, every status present.
     *
     * @return array<string, int>
     */
    public function ordersByStatus(DatePeriodFilter $period): array
    {
        $counts = Order::query()
            ->whereBetween('created_at', $period->bounds())
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        return array_reduce(
            OrderStatus::cases(),
            static fn (array $carry, OrderStatus $status): array => $carry + [
                $status->value => (int) ($counts[$status->value] ?? 0),
            ],
            [],
        );
    }

    // ----------------------------------------------------------------- Tables

    /**
     * @return Collection<int, Order>
     */
    public function recentOrders(int $limit = 8): Collection
    {
        return Order::query()
            ->with('address')
            ->withCount('items')
            ->latest('created_at')
            ->limit($limit)
            ->get();
    }

    /**
     * @return Collection<int, ProductVariant>
     */
    public function lowStockVariants(int $limit = 8): Collection
    {
        return ProductVariant::query()
            ->with(['product', 'color', 'size'])
            ->active()
            ->lowStock()
            ->orderBy('stock_quantity')
            ->limit($limit)
            ->get();
    }

    // -------------------------------------------------------------- Analytics

    /**
     * Best sellers, ranked by units that were actually sold.
     *
     * Joined against orders so cancelled and returned lines do not count: those
     * units came back, and a "best seller" that was mostly refused at the door
     * is a misleading thing to restock against.
     *
     * @return Collection<int, object>
     */
    public function bestSellingProducts(DatePeriodFilter $period, int $limit = 8): Collection
    {
        return $this->soldItems($period)
            ->selectRaw('order_items.product_id')
            ->selectRaw('max(order_items.product_name_ar) as name_ar')
            ->selectRaw('max(order_items.product_name_en) as name_en')
            ->selectRaw('sum(order_items.quantity) as units')
            ->selectRaw('sum(order_items.line_total) as revenue')
            ->groupBy('order_items.product_id')
            ->orderByDesc('units')
            ->limit($limit)
            ->get();
    }

    /**
     * @return Collection<int, object>
     */
    public function topCategories(DatePeriodFilter $period, int $limit = 6): Collection
    {
        return $this->soldItems($period)
            ->join('products', 'products.id', '=', 'order_items.product_id')
            ->join('categories', 'categories.id', '=', 'products.category_id')
            ->selectRaw('categories.id')
            ->selectRaw('max(categories.name_ar) as name_ar')
            ->selectRaw('max(categories.name_en) as name_en')
            ->selectRaw('sum(order_items.quantity) as units')
            ->selectRaw('sum(order_items.line_total) as revenue')
            ->groupBy('categories.id')
            ->orderByDesc('units')
            ->limit($limit)
            ->get();
    }

    /**
     * Top sizes and colours, read off the order snapshot.
     *
     * Grouped by the snapshot name rather than a size_id, because that is what
     * the order recorded — and a size renamed since does not silently merge
     * into another bucket.
     *
     * @return Collection<int, object>
     */
    public function topSizes(DatePeriodFilter $period, int $limit = 6): Collection
    {
        return $this->topAttribute($period, 'size', $limit);
    }

    /**
     * @return Collection<int, object>
     */
    public function topColors(DatePeriodFilter $period, int $limit = 6): Collection
    {
        return $this->topAttribute($period, 'color', $limit);
    }

    /**
     * @return Collection<int, object>
     */
    private function topAttribute(DatePeriodFilter $period, string $attribute, int $limit): Collection
    {
        return $this->soldItems($period)
            ->selectRaw("order_items.{$attribute}_name_en as name_en")
            ->selectRaw("max(order_items.{$attribute}_name_ar) as name_ar")
            ->selectRaw('sum(order_items.quantity) as units')
            ->whereNotNull("order_items.{$attribute}_name_en")
            ->groupBy("order_items.{$attribute}_name_en")
            ->orderByDesc('units')
            ->limit($limit)
            ->get();
    }

    /**
     * Where the orders are going.
     *
     * Grouped off the address snapshot, so a governorate deleted from the
     * shipping table still reports the sales it made.
     *
     * @return Collection<int, object>
     */
    public function salesByGovernorate(DatePeriodFilter $period, int $limit = 12): Collection
    {
        return Order::query()
            ->join('order_addresses', 'order_addresses.order_id', '=', 'orders.id')
            ->whereIn('orders.status', self::EARNED)
            ->whereBetween('orders.delivered_at', $period->bounds())
            ->selectRaw('order_addresses.governorate_name_en as name_en')
            ->selectRaw('max(order_addresses.governorate_name_ar) as name_ar')
            ->selectRaw('count(*) as orders')
            ->selectRaw('sum(orders.total) as revenue')
            ->groupBy('order_addresses.governorate_name_en')
            ->orderByDesc('revenue')
            ->limit($limit)
            ->get();
    }

    /**
     * Order lines from sales that stuck, within the window.
     *
     * The shared base for every product analytic, so "what counts as sold" is
     * defined once.
     *
     * @return Builder<OrderItem>
     */
    private function soldItems(DatePeriodFilter $period): Builder
    {
        return OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->whereNotIn('orders.status', [OrderStatus::Cancelled, OrderStatus::Returned])
            ->whereBetween('orders.created_at', $period->bounds());
    }
}
