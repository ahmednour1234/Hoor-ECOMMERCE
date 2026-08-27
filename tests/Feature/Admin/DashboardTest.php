<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderAddress;
use App\Models\OrderItem;
use App\Models\ProductVariant;
use App\Models\User;
use App\Repositories\DashboardRepository;
use App\Support\DatePeriodFilter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * The admin dashboard.
 *
 * The figures matter more than the markup here: a dashboard that renders
 * beautifully and reports the wrong revenue is worse than no dashboard.
 */
class DashboardTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
    }

    private function repository(): DashboardRepository
    {
        return app(DashboardRepository::class);
    }

    /**
     * An order with one line, placed (and optionally delivered) on given dates.
     */
    private function order(
        OrderStatus $status = OrderStatus::Pending,
        string $placed = 'now',
        ?string $delivered = null,
        int $total = 10000,
        int $quantity = 1,
    ): Order {
        $variant = ProductVariant::factory()->create(['stock_quantity' => 50]);

        $order = Order::factory()->create([
            'status'       => $status,
            'total'        => $total,
            'created_at'   => new \DateTimeImmutable($placed),
            'delivered_at' => $delivered ? new \DateTimeImmutable($delivered) : null,
        ]);

        OrderAddress::factory()->for($order)->create();

        OrderItem::factory()->for($order)->create([
            'product_id'         => $variant->product_id,
            'product_variant_id' => $variant->id,
            'quantity'           => $quantity,
        ]);

        return $order;
    }

    // --------------------------------------------------------- Authorization

    public function test_a_customer_cannot_see_the_dashboard(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'customer']))
            ->get(route('admin.dashboard', ['locale' => 'en']))
            ->assertForbidden();
    }

    // ----------------------------------------------------------- The window

    public function test_the_default_window_is_thirty_days(): void
    {
        $period = DatePeriodFilter::fromRequest([]);

        $this->assertSame(DatePeriodFilter::MONTH, $period->key);
        $this->assertSame(30, $period->days());
    }

    public function test_an_unknown_period_falls_back_rather_than_erroring(): void
    {
        $this->assertSame(DatePeriodFilter::MONTH, DatePeriodFilter::fromRequest(['period' => 'wat'])->key);
    }

    public function test_a_backwards_custom_range_is_swapped_rather_than_returning_nothing(): void
    {
        $period = DatePeriodFilter::custom('2026-08-26', '2026-08-01');

        $this->assertSame('2026-08-01', $period->start->toDateString());
        $this->assertSame('2026-08-26', $period->end->toDateString());
    }

    public function test_an_unparseable_custom_range_falls_back_to_defaults(): void
    {
        $period = DatePeriodFilter::custom('not a date', null);

        $this->assertSame(30, $period->days());
    }

    public function test_a_long_range_groups_by_month_so_the_chart_stays_readable(): void
    {
        $this->assertSame('day', DatePeriodFilter::custom('2026-01-01', '2026-02-01')->grouping());
        $this->assertSame('month', DatePeriodFilter::custom('2026-01-01', '2026-08-01')->grouping());
    }

    public function test_the_previous_window_is_the_same_length_and_immediately_before(): void
    {
        $period = DatePeriodFilter::preset(DatePeriodFilter::WEEK);
        $previous = $period->previous();

        $this->assertSame($period->days(), $previous->days());
        $this->assertTrue($previous->end->lessThan($period->start));
    }

    // -------------------------------------------------------------- Revenue

    /**
     * Cash on delivery means a placed order is not money.
     */
    public function test_revenue_counts_delivered_orders_only(): void
    {
        $this->order(OrderStatus::Delivered, 'now', 'now', total: 50000);
        $this->order(OrderStatus::Pending, 'now', total: 90000);
        $this->order(OrderStatus::Cancelled, 'now', total: 70000);
        $this->order(OrderStatus::Shipped, 'now', total: 30000);

        $revenue = $this->repository()->revenue(DatePeriodFilter::preset(DatePeriodFilter::TODAY));

        $this->assertSame(50000, $revenue);
    }

    /**
     * An order placed last month and delivered today is this period's cash.
     */
    public function test_revenue_is_dated_by_delivery_not_by_placement(): void
    {
        $this->order(OrderStatus::Delivered, placed: '-20 days', delivered: 'now', total: 40000);

        $today = DatePeriodFilter::preset(DatePeriodFilter::TODAY);

        $this->assertSame(40000, $this->repository()->revenue($today));

        // ...and it is not counted again as an order placed today.
        $this->assertSame(0, $this->repository()->orderCount($today));
    }

    // ----------------------------------------------------------------- Cards

    public function test_work_queues_are_not_limited_to_the_window(): void
    {
        // An order nobody has confirmed in five weeks is exactly the one the
        // dashboard must not hide behind a 30-day filter.
        $this->order(OrderStatus::Pending, placed: '-40 days');

        $cards = $this->repository()->cards(DatePeriodFilter::preset(DatePeriodFilter::TODAY));

        $this->assertSame(1, $cards['pending']);
        $this->assertSame(0, $cards['orders']);
    }

    public function test_awaiting_shipping_covers_the_statuses_before_dispatch(): void
    {
        $this->order(OrderStatus::Confirmed);
        $this->order(OrderStatus::Preparing);
        $this->order(OrderStatus::ReadyForShipping);
        $this->order(OrderStatus::Shipped);      // already gone
        $this->order(OrderStatus::Pending);      // not yet confirmed

        $cards = $this->repository()->cards(DatePeriodFilter::preset(DatePeriodFilter::TODAY));

        $this->assertSame(3, $cards['awaiting_shipping']);
    }

    public function test_stock_cards_count_active_variants(): void
    {
        ProductVariant::factory()->create(['stock_quantity' => 0, 'is_active' => true]);
        ProductVariant::factory()->create(['stock_quantity' => 2, 'low_stock_threshold' => 5, 'is_active' => true]);
        ProductVariant::factory()->create(['stock_quantity' => 99, 'low_stock_threshold' => 5, 'is_active' => true]);

        $cards = $this->repository()->cards(DatePeriodFilter::preset(DatePeriodFilter::TODAY));

        $this->assertSame(1, $cards['low_stock']);
        $this->assertSame(1, $cards['out_of_stock']);
    }

    // ---------------------------------------------------------------- Charts

    public function test_the_time_series_fills_quiet_days_with_zero(): void
    {
        $this->order(OrderStatus::Pending, placed: 'now');

        $series = $this->repository()->timeSeries(DatePeriodFilter::preset(DatePeriodFilter::WEEK));

        // Seven buckets whatever happened, so a quiet Tuesday reads as zero
        // rather than vanishing and compressing the line.
        $this->assertCount(7, $series);
        $this->assertSame(1, array_sum(array_column($series, 'orders')));
    }

    public function test_orders_by_status_lists_every_status(): void
    {
        $this->order(OrderStatus::Pending);
        $this->order(OrderStatus::Pending);

        $byStatus = $this->repository()->ordersByStatus(DatePeriodFilter::preset(DatePeriodFilter::TODAY));

        $this->assertCount(count(OrderStatus::cases()), $byStatus);
        $this->assertSame(2, $byStatus['pending']);
        $this->assertSame(0, $byStatus['shipped']);
    }

    // ------------------------------------------------------------- Analytics

    public function test_best_sellers_ignore_cancelled_and_returned_orders(): void
    {
        $kept = $this->order(OrderStatus::Delivered, quantity: 3);
        $lost = $this->order(OrderStatus::Cancelled, quantity: 99);

        $rows = $this->repository()->bestSellingProducts(DatePeriodFilter::preset(DatePeriodFilter::TODAY));

        $this->assertCount(1, $rows);
        $this->assertSame($kept->items->first()->product_id, (int) $rows->first()->product_id);
        $this->assertSame(3, (int) $rows->first()->units);

        $this->assertNotContains(
            $lost->items->first()->product_id,
            $rows->pluck('product_id')->map(fn ($id): int => (int) $id)->all(),
        );
    }

    public function test_sales_by_governorate_sums_delivered_revenue(): void
    {
        $order = $this->order(OrderStatus::Delivered, 'now', 'now', total: 25000);
        $order->address->update(['governorate_name_en' => 'Cairo', 'governorate_name_ar' => 'القاهرة']);

        $rows = $this->repository()->salesByGovernorate(DatePeriodFilter::preset(DatePeriodFilter::TODAY));

        $this->assertSame('Cairo', $rows->first()->name_en);
        $this->assertSame(25000, (int) $rows->first()->revenue);
        $this->assertSame(1, (int) $rows->first()->orders);
    }

    public function test_top_sizes_and_colors_group_by_the_order_snapshot(): void
    {
        $variant = ProductVariant::factory()->create();

        foreach ([2, 3] as $quantity) {
            $order = Order::factory()->status(OrderStatus::Delivered)->create();
            OrderItem::factory()->for($order)->create([
                'product_id'         => $variant->product_id,
                'product_variant_id' => $variant->id,
                'size_name_en'       => 'M',
                'size_name_ar'       => 'م',
                'color_name_en'      => 'Indigo',
                'color_name_ar'      => 'نيلي',
                'quantity'           => $quantity,
            ]);
        }

        $period = DatePeriodFilter::preset(DatePeriodFilter::TODAY);

        $this->assertSame(5, (int) $this->repository()->topSizes($period)->first()->units);
        $this->assertSame(5, (int) $this->repository()->topColors($period)->first()->units);
    }

    // ------------------------------------------------------------ Efficiency

    /**
     * The whole page must cost a fixed number of queries.
     *
     * Every figure is a grouped aggregate, so ten thousand orders cost exactly
     * what ten do. If this starts failing, something is being counted in PHP.
     */
    public function test_the_query_count_does_not_grow_with_the_data(): void
    {
        for ($i = 0; $i < 3; $i++) {
            $this->order(OrderStatus::Delivered, 'now', 'now');
        }

        $baseline = $this->countQueriesForDashboard();

        for ($i = 0; $i < 25; $i++) {
            $this->order(OrderStatus::Delivered, 'now', 'now');
        }

        $this->assertSame($baseline, $this->countQueriesForDashboard());
    }

    /**
     * Nor with the length of the window.
     */
    public function test_the_query_count_does_not_grow_with_the_date_range(): void
    {
        $this->order(OrderStatus::Delivered, 'now', 'now');

        $today = $this->countQueriesForDashboard(['period' => 'today']);
        $long = $this->countQueriesForDashboard(['period' => 'custom', 'from' => '2020-01-01', 'to' => '2026-01-01']);

        $this->assertSame($today, $long);
    }

    /**
     * Every aggregate must be computed in SQL rather than by counting rows in
     * PHP.
     *
     * A statement count cannot see this distinction: `->get()->count()` is
     * still one query, just one that drags the whole table into memory. What
     * separates the two is whether the SQL says so — an aggregating query
     * carries count/sum/group by, and a query that hydrates models does not.
     */
    public function test_every_reporting_query_aggregates_in_sql(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->order(OrderStatus::Delivered, 'now', 'now');
        }

        $hydrating = [];

        DB::listen(function ($event) use (&$hydrating): void {
            $sql = strtolower($event->sql);

            if (! str_starts_with(ltrim($sql), 'select')) {
                return;
            }

            $aggregates = str_contains($sql, 'count(')
                || str_contains($sql, 'sum(')
                || str_contains($sql, 'max(')
                || str_contains($sql, 'group by');

            // The two tables are allowed to fetch rows: they carry a LIMIT,
            // as do the eager-loads that hang off them, whose "in (...)" list
            // is bounded by that same limit.
            $bounded = str_contains($sql, 'limit') || str_contains($sql, ' in (');

            if (! $aggregates && ! $bounded) {
                $hydrating[] = $event->sql;
            }
        });

        $this->actingAs($this->admin)
            ->get(route('admin.dashboard', ['locale' => 'en']))
            ->assertOk();

        $this->assertSame(
            [],
            $hydrating,
            "These queries fetch unbounded rows instead of aggregating:\n".implode("\n", $hydrating),
        );
    }

    /**
     * @param  array<string, string>  $query
     */
    private function countQueriesForDashboard(array $query = []): int
    {
        $count = 0;
        DB::listen(function () use (&$count): void {
            $count++;
        });

        $this->actingAs($this->admin)
            ->get(route('admin.dashboard', $query + ['locale' => 'en']))
            ->assertOk();

        return $count;
    }

    // --------------------------------------------------------------- Renders

    public function test_the_dashboard_renders_with_every_section(): void
    {
        $this->order(OrderStatus::Delivered, 'now', 'now');

        $this->actingAs($this->admin)
            ->get(route('admin.dashboard', ['locale' => 'en']))
            ->assertOk()
            ->assertSee(__('admin.dashboard.sales_over_time'))
            ->assertSee(__('admin.dashboard.orders_over_time'))
            ->assertSee(__('admin.dashboard.orders_by_status'))
            ->assertSee(__('admin.dashboard.recent_orders'))
            ->assertSee(__('admin.dashboard.low_stock'))
            ->assertSee(__('admin.dashboard.best_selling'))
            ->assertSee(__('admin.dashboard.top_categories'))
            ->assertSee(__('admin.dashboard.top_sizes'))
            ->assertSee(__('admin.dashboard.top_colors'))
            ->assertSee(__('admin.dashboard.by_governorate'));
    }

    public function test_the_dashboard_renders_on_an_empty_database(): void
    {
        // The day the shop opens, every query returns nothing. It still has to
        // draw rather than dividing by zero.
        $this->actingAs($this->admin)
            ->get(route('admin.dashboard', ['locale' => 'en']))
            ->assertOk()
            ->assertSee(__('admin.dashboard.no_data'));
    }

    public function test_the_dashboard_renders_in_arabic(): void
    {
        $this->order(OrderStatus::Delivered, 'now', 'now');

        $this->actingAs($this->admin)
            ->get(route('admin.dashboard', ['locale' => 'ar']))
            ->assertOk()
            ->assertSee('dir="rtl"', escape: false)
            ->assertSee(__('admin.dashboard.sales_over_time', locale: 'ar'));
    }

    public function test_each_period_renders(): void
    {
        $this->order(OrderStatus::Delivered, 'now', 'now');

        foreach (DatePeriodFilter::presetKeys() as $key) {
            $this->actingAs($this->admin)
                ->get(route('admin.dashboard', ['locale' => 'en', 'period' => $key]))
                ->assertOk();
        }

        $this->actingAs($this->admin)
            ->get(route('admin.dashboard', [
                'locale' => 'en', 'period' => 'custom', 'from' => '2026-01-01', 'to' => '2026-06-30',
            ]))
            ->assertOk();
    }
}
