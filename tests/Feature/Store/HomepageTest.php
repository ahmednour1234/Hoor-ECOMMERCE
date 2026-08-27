<?php

declare(strict_types=1);

namespace Tests\Feature\Store;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Repositories\ProductRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class HomepageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // The header caches its menu; a stale menu would leak between tests.
        Cache::flush();
    }

    private function publishedProduct(array $attributes = []): Product
    {
        $product = Product::factory()->create($attributes);

        ProductVariant::factory()->for($product)->inStock(10)->create();
        ProductImage::factory()->for($product)->primary()->create();

        return $product;
    }

    // ---------------------------------------------------------- Rendering

    public function test_the_homepage_renders_every_section(): void
    {
        $this->publishedProduct(['is_new' => true]);
        $this->publishedProduct(['is_featured' => true]);

        $this->get(route('store.home', ['locale' => 'en']))
            ->assertOk()
            ->assertSee(__('store.hero.headline'))                // hero slider
            ->assertSee(__('store.promise.cod.title'))            // benefits
            ->assertSee(__('store.categories.title'))             // shop by category
            ->assertSee(__('store.new_arrivals.title'))           // new arrivals
            ->assertSee(__('store.collection.title'))             // collection banner
            ->assertSee(__('store.featured.title'))               // featured
            ->assertSee(__('store.why.title'))                    // why HOOR
            ->assertSee(__('store.lookbook.title'))               // lookbook
            ->assertSee(__('store.newsletter.title'))             // newsletter
            ->assertSee(__('store.footer.rights'));               // footer
    }

    public function test_the_homepage_renders_rtl_with_arabic_copy(): void
    {
        $this->publishedProduct(['is_new' => true]);

        $this->get(route('store.home', ['locale' => 'ar']))
            ->assertOk()
            ->assertSee('dir="rtl"', escape: false)
            ->assertSee(__('store.hero.headline', [], 'ar'))
            ->assertSee(__('store.why.title', [], 'ar'));
    }

    // -------------------------------------------------- Database-driven rails

    public function test_new_arrivals_come_from_the_database(): void
    {
        $newArrival = $this->publishedProduct(['name_en' => 'Fresh Indigo Jeans', 'is_new' => true]);

        $this->get(route('store.home', ['locale' => 'en']))
            ->assertOk()
            ->assertSee($newArrival->name_en);
    }

    public function test_featured_products_come_from_the_database(): void
    {
        $featured = $this->publishedProduct(['name_en' => 'Hero Denim Skirt', 'is_featured' => true]);

        $this->get(route('store.home', ['locale' => 'en']))
            ->assertOk()
            ->assertSee($featured->name_en);
    }

    public function test_draft_and_archived_products_never_reach_the_storefront(): void
    {
        $draft = Product::factory()->draft()->create(['name_en' => 'Secret Draft Jeans']);
        ProductVariant::factory()->for($draft)->inStock(5)->create();

        $archived = Product::factory()->archived()->create(['name_en' => 'Retired Old Jeans']);
        ProductVariant::factory()->for($archived)->inStock(5)->create();

        $this->publishedProduct(['name_en' => 'Live Jeans', 'is_new' => true]);

        $this->get(route('store.home', ['locale' => 'en']))
            ->assertOk()
            ->assertSee('Live Jeans')
            ->assertDontSee('Secret Draft Jeans')
            ->assertDontSee('Retired Old Jeans');
    }

    public function test_prices_render_in_egyptian_pounds(): void
    {
        $this->publishedProduct(['base_price' => 129000, 'is_new' => true]);

        $this->get(route('store.home', ['locale' => 'en']))
            ->assertOk()
            ->assertSee('EGP 1,290.00');

        $this->get(route('store.home', ['locale' => 'ar']))
            ->assertOk()
            ->assertSee('ج.م');
    }

    public function test_a_discounted_product_shows_its_sale_price(): void
    {
        $this->publishedProduct([
            'name_en'    => 'Discounted Jeans',
            'base_price' => 100000,
            'sale_price' => 80000,
            'is_new'     => true,
        ]);

        $this->get(route('store.home', ['locale' => 'en']))
            ->assertOk()
            ->assertSee('EGP 800.00')   // effective
            ->assertSee('EGP 1,000.00') // struck through
            ->assertSee('-20%');
    }

    // ------------------------------------------------------------ Categories

    public function test_only_categories_holding_products_are_shown(): void
    {
        $stocked = Category::factory()->create(['name_en' => 'Stocked Category']);
        $this->publishedProduct(['category_id' => $stocked->id]);

        Category::factory()->create(['name_en' => 'Empty Category']);

        $this->get(route('store.home', ['locale' => 'en']))
            ->assertOk()
            ->assertSee('Stocked Category')
            ->assertDontSee('Empty Category');
    }

    public function test_a_parent_category_counts_products_filed_under_its_children(): void
    {
        // Products sit in the most specific category, so a parent with no direct
        // products must still appear if its children have some.
        $parent = Category::factory()->create(['name_en' => 'Jeans Parent']);
        $child = Category::factory()->childOf($parent)->create();

        $this->publishedProduct(['category_id' => $child->id]);

        $this->get(route('store.home', ['locale' => 'en']))
            ->assertOk()
            ->assertSee('Jeans Parent');
    }

    // ---------------------------------------------------------- Repository

    public function test_rails_are_topped_up_to_a_full_row(): void
    {
        // Only one flagged product, but six published overall.
        $this->publishedProduct(['is_new' => true]);
        collect(range(1, 5))->each(fn () => $this->publishedProduct());

        $sections = app(ProductRepository::class)->forHomepage();

        $this->assertCount(4, $sections['new_arrivals']);
        $this->assertCount(4, $sections['featured']);
    }

    public function test_top_up_never_repeats_a_product_within_a_rail(): void
    {
        $this->publishedProduct(['is_new' => true]);
        $this->publishedProduct();

        $rail = app(ProductRepository::class)->forHomepage()['new_arrivals'];

        $this->assertSame(
            $rail->modelKeys(),
            array_unique($rail->modelKeys()),
            'A product appeared twice in the same rail.',
        );
    }

    public function test_a_flagged_product_leads_its_rail(): void
    {
        // Padding must not push the curated product out of first position.
        collect(range(1, 3))->each(fn () => $this->publishedProduct());
        $featured = $this->publishedProduct(['is_featured' => true]);

        $rail = app(ProductRepository::class)->forHomepage()['featured'];

        $this->assertTrue($rail->first()->is($featured));
    }

    public function test_the_homepage_stays_on_a_constant_query_budget(): void
    {
        collect(range(1, 8))->each(fn () => $this->publishedProduct(['is_new' => true]));

        Cache::flush();
        DB::enableQueryLog();

        $this->get(route('store.home', ['locale' => 'en']))->assertOk();

        $queries = count(DB::getQueryLog());
        DB::disableQueryLog();

        // Cards read price and stock through their variants; without eager
        // loading this would climb with every product on the page.
        $this->assertLessThan(
            25,
            $queries,
            "The homepage ran {$queries} queries; eager loading has regressed.",
        );
    }

    public function test_an_empty_catalog_still_renders_the_page(): void
    {
        // No products at all: rails hide themselves rather than erroring.
        // Asserted on the rail's lead copy rather than its heading, because the
        // hero's secondary button carries the same "New in" label.
        $this->get(route('store.home', ['locale' => 'en']))
            ->assertOk()
            ->assertSee(__('store.hero.headline'))
            ->assertSee(__('store.why.title'))
            ->assertDontSee(__('store.new_arrivals.lead'))
            ->assertDontSee(__('store.featured.lead'))
            ->assertDontSee(__('store.categories.title'));
    }
}
