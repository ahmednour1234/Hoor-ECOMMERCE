<?php

declare(strict_types=1);

namespace Tests\Feature\Store;

use App\Models\Category;
use App\Models\Color;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\Size;
use App\Services\VariantResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ProductDetailTest extends TestCase
{
    use RefreshDatabase;

    private Color $indigo;
    private Color $black;
    private Size $small;
    private Size $medium;

    protected function setUp(): void
    {
        parent::setUp();

        $this->indigo = Color::factory()->create(['slug' => 'indigo', 'name_en' => 'Indigo']);
        $this->black = Color::factory()->create(['slug' => 'black-denim', 'name_en' => 'Black']);
        $this->small = Size::factory()->create(['code' => 'S', 'sort_order' => 1]);
        $this->medium = Size::factory()->create(['code' => 'M', 'sort_order' => 2]);
    }

    private function product(array $attributes = []): Product
    {
        $product = Product::factory()->create($attributes);
        ProductImage::factory()->for($product)->primary()->create();

        return $product;
    }

    private function url(Product $product, string $locale = 'en'): string
    {
        return route('store.products.show', ['locale' => $locale, 'product' => $product]);
    }

    // ----------------------------------------------------------- Rendering

    public function test_the_product_page_renders_everything_a_shopper_needs(): void
    {
        $product = $this->product([
            'name_en'   => 'Wide Leg Indigo Jeans',
            'fabric_en' => '100% cotton denim',
            'care_en'   => 'Machine wash cold',
        ]);

        ProductVariant::factory()->for($product)->for($this->indigo)->for($this->small)
            ->inStock(8)->create(['sku' => 'HOOR-TEST-S']);

        $this->get($this->url($product))
            ->assertOk()
            ->assertSee('Wide Leg Indigo Jeans')
            ->assertSee('100% cotton denim')
            ->assertSee('Machine wash cold')
            ->assertSee(__('store.product.add_to_cart'))
            ->assertSee(__('store.product.sections.shipping'))
            ->assertSee(__('store.product.sections.size_guide'))
            ->assertSee('Indigo')
            ->assertSee('HOOR-TEST-S');
    }

    public function test_the_product_page_renders_rtl_in_arabic(): void
    {
        $product = $this->product();
        ProductVariant::factory()->for($product)->for($this->indigo)->for($this->small)->inStock(5)->create();

        $this->get($this->url($product, 'ar'))
            ->assertOk()
            ->assertSee('dir="rtl"', escape: false)
            ->assertSee(__('store.product.add_to_cart', [], 'ar'));
    }

    public function test_unpublished_products_are_not_reachable(): void
    {
        $draft = Product::factory()->draft()->create();
        $archived = Product::factory()->archived()->create();

        $this->get($this->url($draft))->assertNotFound();
        $this->get($this->url($archived))->assertNotFound();
    }

    public function test_related_products_are_shown(): void
    {
        $category = Category::factory()->create();

        $product = $this->product(['category_id' => $category->id]);
        ProductVariant::factory()->for($product)->inStock(5)->create();

        $sibling = $this->product(['category_id' => $category->id, 'name_en' => 'Sibling Piece']);
        ProductVariant::factory()->for($sibling)->inStock(5)->create();

        $this->get($this->url($product))
            ->assertOk()
            ->assertSee(__('store.product.related'))
            ->assertSee('Sibling Piece');
    }

    // ----------------------------------------------------- Variant matrix

    public function test_the_matrix_contains_only_real_active_variants(): void
    {
        $product = $this->product();

        $live = ProductVariant::factory()->for($product)->for($this->indigo)->for($this->small)
            ->inStock(5)->create();

        $inactive = ProductVariant::factory()->for($product)->for($this->black)->for($this->medium)
            ->inactive()->create();

        $product->load('variants.color', 'variants.size');
        $matrix = app(VariantResolver::class)->matrix($product);

        $ids = array_column($matrix, 'id');

        $this->assertContains($live->id, $ids);
        $this->assertNotContains($inactive->id, $ids, 'An inactive variant leaked into the matrix.');
    }

    public function test_a_combination_with_no_variant_is_absent_from_the_matrix(): void
    {
        // Indigo/S and Black/M exist; Indigo/M and Black/S deliberately do not.
        $product = $this->product();
        ProductVariant::factory()->for($product)->for($this->indigo)->for($this->small)->inStock(5)->create();
        ProductVariant::factory()->for($product)->for($this->black)->for($this->medium)->inStock(5)->create();

        $product->load('variants.color', 'variants.size');
        $matrix = app(VariantResolver::class)->matrix($product);

        $pairs = array_map(
            fn (array $row): string => $row['color_id'].':'.$row['size_id'],
            $matrix,
        );

        $this->assertContains($this->indigo->id.':'.$this->small->id, $pairs);
        $this->assertNotContains(
            $this->indigo->id.':'.$this->medium->id,
            $pairs,
            'A combination with no variant row appeared in the selectable matrix.',
        );
    }

    public function test_the_default_selection_prefers_something_in_stock(): void
    {
        $product = $this->product();

        ProductVariant::factory()->for($product)->for($this->indigo)->for($this->small)
            ->outOfStock()->create();

        $available = ProductVariant::factory()->for($product)->for($this->indigo)->for($this->medium)
            ->inStock(6)->create();

        $product->load('variants');

        $this->assertTrue(
            app(VariantResolver::class)->defaultVariant($product)->is($available),
            'The page opened on a sold-out combination while stock existed elsewhere.',
        );
    }

    public function test_sold_out_variants_are_still_offered_so_the_shopper_can_see_them(): void
    {
        $product = $this->product();
        ProductVariant::factory()->for($product)->for($this->indigo)->for($this->small)
            ->outOfStock()->create();

        $product->load('variants.color', 'variants.size');
        $matrix = app(VariantResolver::class)->matrix($product);

        $this->assertCount(1, $matrix);
        $this->assertFalse($matrix[0]['in_stock']);
        $this->assertSame('out_of_stock', $matrix[0]['status']);
    }

    // ------------------------------------------- Server-side add to cart

    public function test_a_valid_combination_can_be_added(): void
    {
        $product = $this->product();
        $variant = ProductVariant::factory()->for($product)->for($this->indigo)->for($this->small)
            ->inStock(10)->create();

        $this->from($this->url($product))
            ->post(route('store.cart.store', ['locale' => 'en', 'product' => $product]), [
                'variant_id' => $variant->id,
                'quantity'   => 2,
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors()
            ->assertSessionHas('cart_status');
    }

    public function test_a_variant_belonging_to_another_product_is_rejected(): void
    {
        // The critical case: a tampered variant_id must not let a customer buy
        // one product's stock through another product's page.
        $product = $this->product(['base_price' => 200000]);
        ProductVariant::factory()->for($product)->for($this->indigo)->for($this->small)->inStock(5)->create();

        $cheaper = $this->product(['base_price' => 10000]);
        $foreignVariant = ProductVariant::factory()->for($cheaper)->for($this->black)->for($this->medium)
            ->inStock(5)->create();

        $this->post(route('store.cart.store', ['locale' => 'en', 'product' => $product]), [
            'variant_id' => $foreignVariant->id,
            'quantity'   => 1,
        ])->assertSessionHasErrors('variant_id');
    }

    public function test_an_inactive_variant_is_rejected(): void
    {
        $product = $this->product();
        $inactive = ProductVariant::factory()->for($product)->for($this->indigo)->for($this->small)
            ->inactive()->create(['stock_quantity' => 20]);

        $this->post(route('store.cart.store', ['locale' => 'en', 'product' => $product]), [
            'variant_id' => $inactive->id,
            'quantity'   => 1,
        ])->assertSessionHasErrors('variant_id');
    }

    public function test_an_out_of_stock_variant_is_rejected(): void
    {
        $product = $this->product();
        $soldOut = ProductVariant::factory()->for($product)->for($this->indigo)->for($this->small)
            ->outOfStock()->create();

        $this->post(route('store.cart.store', ['locale' => 'en', 'product' => $product]), [
            'variant_id' => $soldOut->id,
            'quantity'   => 1,
        ])->assertSessionHasErrors('variant_id');
    }

    public function test_ordering_more_than_the_stock_on_hand_is_rejected(): void
    {
        $product = $this->product();
        $variant = ProductVariant::factory()->for($product)->for($this->indigo)->for($this->small)
            ->inStock(3)->create();

        $this->post(route('store.cart.store', ['locale' => 'en', 'product' => $product]), [
            'variant_id' => $variant->id,
            'quantity'   => 4,
        ])->assertSessionHasErrors('quantity');

        // The boundary itself is allowed.
        $this->post(route('store.cart.store', ['locale' => 'en', 'product' => $product]), [
            'variant_id' => $variant->id,
            'quantity'   => 3,
        ])->assertSessionHasNoErrors();
    }

    public function test_a_variant_of_an_unpublished_product_is_rejected(): void
    {
        $draft = Product::factory()->draft()->create();
        $variant = ProductVariant::factory()->for($draft)->for($this->indigo)->for($this->small)
            ->inStock(5)->create();

        $this->post(route('store.cart.store', ['locale' => 'en', 'product' => $draft]), [
            'variant_id' => $variant->id,
            'quantity'   => 1,
        ])->assertSessionHasErrors('variant_id');
    }

    public function test_a_nonexistent_variant_id_is_rejected(): void
    {
        $product = $this->product();
        ProductVariant::factory()->for($product)->for($this->indigo)->for($this->small)->inStock(5)->create();

        $this->post(route('store.cart.store', ['locale' => 'en', 'product' => $product]), [
            'variant_id' => 999999,
            'quantity'   => 1,
        ])->assertSessionHasErrors('variant_id');
    }

    public function test_invalid_quantities_are_rejected(): void
    {
        $product = $this->product();
        $variant = ProductVariant::factory()->for($product)->for($this->indigo)->for($this->small)
            ->inStock(10)->create();

        foreach ([0, -5, 'abc'] as $quantity) {
            $this->post(route('store.cart.store', ['locale' => 'en', 'product' => $product]), [
                'variant_id' => $variant->id,
                'quantity'   => $quantity,
            ])->assertSessionHasErrors('quantity');
        }
    }

    // ---------------------------------------------------------- Efficiency

    public function test_the_page_stays_on_a_constant_query_budget(): void
    {
        $product = $this->product();

        foreach (Color::factory()->count(3)->create() as $color) {
            foreach (Size::factory()->count(4)->create() as $size) {
                ProductVariant::factory()->for($product)->for($color)->for($size)->inStock(5)->create();
            }
        }

        ProductImage::factory()->for($product)->count(4)->create();

        DB::enableQueryLog();
        $this->get($this->url($product))->assertOk();
        $queries = count(DB::getQueryLog());
        DB::disableQueryLog();

        // 12 variants, each with a colour and size, plus a gallery and the
        // related rail — all eager loaded rather than queried per row.
        $this->assertLessThan(
            20,
            $queries,
            "The product page ran {$queries} queries; eager loading has regressed.",
        );
    }
}
