<?php

declare(strict_types=1);

namespace Tests\Feature\Store;

use App\Models\Category;
use App\Models\Color;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\Size;
use App\Repositories\ProductRepository;
use App\Support\ProductFilter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ShopPageTest extends TestCase
{
    use RefreshDatabase;

    private ProductRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = app(ProductRepository::class);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function product(array $attributes = []): Product
    {
        $product = Product::factory()->create($attributes);
        ProductImage::factory()->for($product)->primary()->create();

        return $product;
    }

    private function shop(array $query = [], string $locale = 'en'): \Illuminate\Testing\TestResponse
    {
        return $this->get(route('store.shop', array_merge(['locale' => $locale], $query)));
    }

    // ----------------------------------------------------------- Rendering

    public function test_the_shop_page_renders(): void
    {
        $product = $this->product(['name_en' => 'Wide Leg Indigo Jeans']);
        ProductVariant::factory()->for($product)->inStock(5)->create();

        $this->shop()
            ->assertOk()
            ->assertSee(__('store.shop.title'))
            ->assertSee('Wide Leg Indigo Jeans');
    }

    public function test_the_shop_page_renders_rtl_in_arabic(): void
    {
        $product = $this->product();
        ProductVariant::factory()->for($product)->inStock(5)->create();

        $this->shop([], 'ar')
            ->assertOk()
            ->assertSee('dir="rtl"', escape: false)
            ->assertSee(__('store.shop.title', [], 'ar'));
    }

    public function test_only_published_products_are_listed(): void
    {
        $live = $this->product(['name_en' => 'Live Jeans']);
        ProductVariant::factory()->for($live)->inStock(5)->create();

        Product::factory()->draft()->create(['name_en' => 'Draft Jeans']);
        Product::factory()->archived()->create(['name_en' => 'Archived Jeans']);

        $this->shop()
            ->assertOk()
            ->assertSee('Live Jeans')
            ->assertDontSee('Draft Jeans')
            ->assertDontSee('Archived Jeans');
    }

    // ------------------------------------------------------------ Filtering

    public function test_filtering_by_category_includes_sub_categories(): void
    {
        // Products are filed against the most specific category, so choosing a
        // parent must return everything beneath it.
        $jeans = Category::factory()->create(['slug' => 'jeans', 'name_en' => 'Jeans']);
        $wideLeg = Category::factory()->childOf($jeans)->create(['slug' => 'wide-leg']);

        $child = $this->product(['category_id' => $wideLeg->id, 'name_en' => 'Wide Leg Pair']);
        ProductVariant::factory()->for($child)->inStock(5)->create();

        $other = $this->product(['name_en' => 'Unrelated Skirt']);
        ProductVariant::factory()->for($other)->inStock(5)->create();

        $this->shop(['category' => 'jeans'])
            ->assertOk()
            ->assertSee('Wide Leg Pair')
            ->assertDontSee('Unrelated Skirt');
    }

    public function test_filtering_by_size_matches_variants(): void
    {
        $medium = Size::factory()->create(['code' => 'M']);
        $large = Size::factory()->create(['code' => 'L']);

        $inMedium = $this->product(['name_en' => 'Medium Only']);
        ProductVariant::factory()->for($inMedium)->for($medium)->inStock(4)->create();

        $inLarge = $this->product(['name_en' => 'Large Only']);
        ProductVariant::factory()->for($inLarge)->for($large)->inStock(4)->create();

        $this->shop(['size' => 'm'])
            ->assertOk()
            ->assertSee('Medium Only')
            ->assertDontSee('Large Only');
    }

    public function test_size_filtering_is_case_insensitive(): void
    {
        $medium = Size::factory()->create(['code' => 'M']);
        $product = $this->product(['name_en' => 'Medium Piece']);
        ProductVariant::factory()->for($product)->for($medium)->inStock(4)->create();

        $this->shop(['size' => 'M'])->assertOk()->assertSee('Medium Piece');
        $this->shop(['size' => 'm'])->assertOk()->assertSee('Medium Piece');
    }

    public function test_filtering_by_colour_matches_variants(): void
    {
        $indigo = Color::factory()->create(['slug' => 'indigo']);
        $black = Color::factory()->create(['slug' => 'black-denim']);

        $inIndigo = $this->product(['name_en' => 'Indigo Piece']);
        ProductVariant::factory()->for($inIndigo)->for($indigo)->inStock(4)->create();

        $inBlack = $this->product(['name_en' => 'Black Piece']);
        ProductVariant::factory()->for($inBlack)->for($black)->inStock(4)->create();

        $this->shop(['color' => 'indigo'])
            ->assertOk()
            ->assertSee('Indigo Piece')
            ->assertDontSee('Black Piece');
    }

    public function test_size_and_colour_must_match_the_same_variant(): void
    {
        // A product offering M-in-black and L-in-indigo must NOT match a search
        // for M-in-indigo: the constraints apply to one variant, not the set.
        $medium = Size::factory()->create(['code' => 'M']);
        $large = Size::factory()->create(['code' => 'L']);
        $indigo = Color::factory()->create(['slug' => 'indigo']);
        $black = Color::factory()->create(['slug' => 'black-denim']);

        $split = $this->product(['name_en' => 'Split Combination']);
        ProductVariant::factory()->for($split)->for($medium)->for($black)->inStock(3)->create();
        ProductVariant::factory()->for($split)->for($large)->for($indigo)->inStock(3)->create();

        $matching = $this->product(['name_en' => 'True Match']);
        ProductVariant::factory()->for($matching)->for($medium)->for($indigo)->inStock(3)->create();

        $this->shop(['size' => 'm', 'color' => 'indigo'])
            ->assertOk()
            ->assertSee('True Match')
            ->assertDontSee('Split Combination');
    }

    public function test_filtering_by_price_uses_the_effective_price(): void
    {
        // A product discounted into range must appear; its base price is above
        // the ceiling, so filtering on base_price would wrongly exclude it.
        $discounted = $this->product([
            'name_en'    => 'Discounted Into Range',
            'base_price' => 200000,
            'sale_price' => 90000,
        ]);
        ProductVariant::factory()->for($discounted)->inStock(4)->create();

        $expensive = $this->product(['name_en' => 'Genuinely Expensive', 'base_price' => 200000]);
        ProductVariant::factory()->for($expensive)->inStock(4)->create();

        $this->shop(['max_price' => 1000])
            ->assertOk()
            ->assertSee('Discounted Into Range')
            ->assertDontSee('Genuinely Expensive');
    }

    public function test_sale_new_and_stock_filters(): void
    {
        $onSale = $this->product(['name_en' => 'Sale Piece', 'base_price' => 100000, 'sale_price' => 70000]);
        ProductVariant::factory()->for($onSale)->inStock(4)->create();

        $newArrival = $this->product(['name_en' => 'New Piece', 'is_new' => true]);
        ProductVariant::factory()->for($newArrival)->inStock(4)->create();

        $soldOut = $this->product(['name_en' => 'Sold Out Piece']);
        ProductVariant::factory()->for($soldOut)->outOfStock()->create();

        $this->shop(['sale' => 1])->assertOk()->assertSee('Sale Piece')->assertDontSee('New Piece');
        $this->shop(['new' => 1])->assertOk()->assertSee('New Piece')->assertDontSee('Sale Piece');
        $this->shop(['in_stock' => 1])->assertOk()->assertSee('Sale Piece')->assertDontSee('Sold Out Piece');
    }

    public function test_filters_combine_rather_than_replace_each_other(): void
    {
        $medium = Size::factory()->create(['code' => 'M']);

        $both = $this->product(['name_en' => 'New And Medium', 'is_new' => true]);
        ProductVariant::factory()->for($both)->for($medium)->inStock(4)->create();

        $onlyNew = $this->product(['name_en' => 'Only New', 'is_new' => true]);
        ProductVariant::factory()->for($onlyNew)->inStock(4)->create();

        $this->shop(['new' => 1, 'size' => 'm'])
            ->assertOk()
            ->assertSee('New And Medium')
            ->assertDontSee('Only New');
    }

    // -------------------------------------------------------------- Sorting

    public function test_products_can_be_sorted_by_price(): void
    {
        foreach ([['Cheapest', 50000], ['Middle', 100000], ['Dearest', 150000]] as [$name, $price]) {
            $product = $this->product(['name_en' => $name, 'base_price' => $price]);
            ProductVariant::factory()->for($product)->inStock(4)->create();
        }

        $ascending = $this->repository
            ->paginateForShop(new ProductFilter(sort: 'price_asc'))
            ->pluck('name_en')->all();

        $this->assertSame(['Cheapest', 'Middle', 'Dearest'], $ascending);

        $descending = $this->repository
            ->paginateForShop(new ProductFilter(sort: 'price_desc'))
            ->pluck('name_en')->all();

        $this->assertSame(['Dearest', 'Middle', 'Cheapest'], $descending);
    }

    public function test_price_sorting_respects_discounts(): void
    {
        // Sorting must rank on what a shopper pays, not the list price.
        $cheapAfterDiscount = $this->product([
            'name_en' => 'Discounted', 'base_price' => 300000, 'sale_price' => 40000,
        ]);
        ProductVariant::factory()->for($cheapAfterDiscount)->inStock(4)->create();

        $fullPrice = $this->product(['name_en' => 'Full Price', 'base_price' => 90000]);
        ProductVariant::factory()->for($fullPrice)->inStock(4)->create();

        $order = $this->repository
            ->paginateForShop(new ProductFilter(sort: 'price_asc'))
            ->pluck('name_en')->all();

        $this->assertSame(['Discounted', 'Full Price'], $order);
    }

    public function test_best_selling_is_offered_now_that_orders_exist(): void
    {
        // The sort is gated on the orders table, which the checkout module
        // created; it is therefore a real control rather than a dead one.
        $this->assertTrue(ProductFilter::isSortAvailable('best_selling'));
        $this->assertContains('best_selling', ProductFilter::availableSorts());

        $product = $this->product();
        ProductVariant::factory()->for($product)->inStock(4)->create();

        $this->assertCount(
            1,
            $this->repository->paginateForShop(new ProductFilter(sort: 'best_selling')),
        );
    }

    public function test_best_selling_ranks_by_units_actually_sold(): void
    {
        $quiet = $this->product(['name_en' => 'Quiet Piece']);
        $quietVariant = ProductVariant::factory()->for($quiet)->inStock(20)->create();

        $popular = $this->product(['name_en' => 'Popular Piece']);
        $popularVariant = ProductVariant::factory()->for($popular)->inStock(20)->create();

        $order = \App\Models\Order::factory()->create();

        \App\Models\OrderItem::factory()->for($order)->create([
            'product_id'         => $quiet->id,
            'product_variant_id' => $quietVariant->id,
            'quantity'           => 1,
        ]);

        \App\Models\OrderItem::factory()->for($order)->create([
            'product_id'         => $popular->id,
            'product_variant_id' => $popularVariant->id,
            'quantity'           => 9,
        ]);

        $ranked = $this->repository
            ->paginateForShop(new ProductFilter(sort: 'best_selling'))
            ->pluck('name_en')
            ->all();

        $this->assertSame('Popular Piece', $ranked[0], 'The best seller did not lead the results.');
    }

    // ------------------------------------------------------- Query strings

    public function test_an_unknown_sort_falls_back_to_the_default(): void
    {
        $filter = ProductFilter::fromRequest(
            \Illuminate\Http\Request::create('/shop', 'GET', ['sort' => 'drop-tables']),
        );

        $this->assertSame(ProductFilter::DEFAULT_SORT, $filter->sort);
    }

    public function test_facet_values_are_restricted_to_slugs(): void
    {
        $filter = ProductFilter::fromRequest(\Illuminate\Http\Request::create('/shop', 'GET', [
            'color' => "indigo,'; DROP TABLE products;--,sand",
        ]));

        // The injection attempt is discarded; the legitimate slugs survive.
        $this->assertSame(['indigo', 'sand'], $filter->colors);
    }

    public function test_the_filter_round_trips_through_its_own_query_string(): void
    {
        $original = new ProductFilter(
            categories: ['jeans'],
            sizes: ['m'],
            colors: ['indigo'],
            minPrice: 50000,
            maxPrice: 150000,
            newArrivals: true,
            onSale: true,
            inStockOnly: true,
            search: 'denim',
            sort: 'price_asc',
        );

        $rebuilt = ProductFilter::fromRequest(
            \Illuminate\Http\Request::create('/shop', 'GET', $original->toQuery()),
        );

        $this->assertEquals($original, $rebuilt);
    }

    public function test_the_shop_url_from_the_brief_works(): void
    {
        $jeans = Category::factory()->create(['slug' => 'jeans']);
        $medium = Size::factory()->create(['code' => 'M']);
        $color = Color::factory()->create(['slug' => 'dark-blue']);

        $product = $this->product(['category_id' => $jeans->id, 'name_en' => 'Target Jeans']);
        ProductVariant::factory()->for($product)->for($medium)->for($color)->inStock(4)->create();

        $this->get('/en/shop?category=jeans&size=m&color=dark-blue&sort=price_asc')
            ->assertOk()
            ->assertSee('Target Jeans');
    }

    public function test_pagination_preserves_the_filters(): void
    {
        $medium = Size::factory()->create(['code' => 'M']);

        collect(range(1, 15))->each(function (int $i) use ($medium): void {
            $product = $this->product(['name_en' => "Piece {$i}", 'is_new' => true]);
            ProductVariant::factory()->for($product)->for($medium)->inStock(4)->create();
        });

        $response = $this->shop(['size' => 'm', 'new' => 1, 'sort' => 'price_asc']);

        $response->assertOk();
        // Page links must carry the filter set, or page two silently drops it.
        $response->assertSee('size=m', escape: false);
        $response->assertSee('sort=price_asc', escape: false);
    }

    // ---------------------------------------------------------- Efficiency

    public function test_the_listing_stays_on_a_constant_query_budget(): void
    {
        $colors = Color::factory()->count(4)->create();
        $size = Size::factory()->create();

        collect(range(1, 12))->each(function () use ($colors, $size): void {
            $product = $this->product();

            foreach ($colors as $color) {
                ProductVariant::factory()->for($product)->for($color)->for($size)->inStock(4)->create();
            }
        });

        DB::enableQueryLog();

        $this->shop()->assertOk();

        $queries = count(DB::getQueryLog());
        DB::disableQueryLog();

        // Cards read price, stock and colour swatches through their variants;
        // without eager loading this would climb with every product shown.
        $this->assertLessThan(
            20,
            $queries,
            "The shop ran {$queries} queries; eager loading has regressed.",
        );
    }

    public function test_the_query_count_does_not_grow_with_the_catalog(): void
    {
        $size = Size::factory()->create();
        $color = Color::factory()->create();

        $build = function (int $count) use ($size, $color): void {
            collect(range(1, $count))->each(function () use ($size, $color): void {
                $product = $this->product();
                ProductVariant::factory()->for($product)->for($size)->for($color)->inStock(4)->create();
            });
        };

        $measure = function (): int {
            DB::flushQueryLog();
            DB::enableQueryLog();

            $this->repository
                ->paginateForShop(new ProductFilter())
                ->each(fn (Product $p) => $p->effectivePrice());

            $count = count(DB::getQueryLog());
            DB::disableQueryLog();

            return $count;
        };

        $build(3);
        $small = $measure();

        $build(9);
        $large = $measure();

        $this->assertSame(
            $small,
            $large,
            "Query count grew from {$small} to {$large} as the catalog grew — an N+1 has appeared.",
        );
    }

    public function test_facets_are_counted_in_sql_not_in_php(): void
    {
        $jeans = Category::factory()->create(['slug' => 'jeans']);
        $wideLeg = Category::factory()->childOf($jeans)->create();

        collect(range(1, 10))->each(function () use ($wideLeg): void {
            $product = $this->product(['category_id' => $wideLeg->id]);
            ProductVariant::factory()->for($product)->inStock(4)->create();
        });

        DB::enableQueryLog();
        $facets = $this->repository->shopFacets();
        $queries = count(DB::getQueryLog());
        DB::disableQueryLog();

        // A parent counts its children's products, so "Jeans" reports 10.
        $this->assertSame(10, $facets['categories']->firstWhere('slug', 'jeans')->products_count);

        $this->assertLessThan(
            10,
            $queries,
            "Facet counting ran {$queries} queries; it should be a handful of grouped reads.",
        );
    }
}
