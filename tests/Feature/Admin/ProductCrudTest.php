<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Casts\Money;
use App\Enums\ProductStatus;
use App\Models\Category;
use App\Models\Color;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\Size;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductCrudTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Category $category;
    private Color $color;
    private Size $size;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->admin = User::factory()->admin()->create();
        $this->category = Category::factory()->create();
        $this->color = Color::factory()->create(['slug' => 'indigo']);
        $this->size = Size::factory()->create(['code' => 'M']);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name_en'     => 'Wide Leg Jeans',
            'name_ar'     => 'جينز واسع الساق',
            'category_id' => $this->category->id,
            'base_price'  => 1290,
            'status'      => ProductStatus::Published->value,
            'variants'    => [
                [
                    'color_id'            => $this->color->id,
                    'size_id'             => $this->size->id,
                    'sku'                 => 'HOOR-TEST-M',
                    'stock_quantity'      => 12,
                    'low_stock_threshold' => 3,
                    'is_active'           => 1,
                ],
            ],
        ], $overrides);
    }

    // -------------------------------------------------------------- Listing

    public function test_admin_can_list_products(): void
    {
        $product = Product::factory()->for($this->category)->create(['name_en' => 'Indigo Jeans']);
        ProductVariant::factory()->for($product)->inStock(5)->create();

        $this->actingAs($this->admin)
            ->get(route('admin.products.index', ['locale' => 'en']))
            ->assertOk()
            ->assertSee('Indigo Jeans');
    }

    public function test_listing_can_be_filtered_by_stock_state(): void
    {
        $inStock = Product::factory()->for($this->category)->create(['name_en' => 'Has Stock']);
        ProductVariant::factory()->for($inStock)->inStock(20)->create();

        $soldOut = Product::factory()->for($this->category)->create(['name_en' => 'Sold Out']);
        ProductVariant::factory()->for($soldOut)->outOfStock()->create();

        $this->actingAs($this->admin)
            ->get(route('admin.products.index', ['locale' => 'en', 'stock' => 'out']))
            ->assertOk()
            ->assertSee('Sold Out')
            ->assertDontSee('Has Stock');
    }

    // ------------------------------------------------------------- Creating

    public function test_admin_can_create_a_product_with_a_variant(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.products.store', ['locale' => 'en']), $this->payload())
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $product = Product::query()->firstOrFail();

        $this->assertSame('Wide Leg Jeans', $product->name_en);
        $this->assertSame('جينز واسع الساق', $product->name_ar);
        // Entered in EGP, stored in piastres.
        $this->assertSame(129000, $product->base_price);
        $this->assertSame(ProductStatus::Published, $product->status);

        $variant = $product->variants()->firstOrFail();
        $this->assertSame('HOOR-TEST-M', $variant->sku);
        $this->assertSame(12, $variant->stock_quantity);
    }

    public function test_slug_is_generated_when_left_blank(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.products.store', ['locale' => 'en']), $this->payload())
            ->assertSessionHasNoErrors();

        $this->assertSame('wide-leg-jeans', Product::query()->firstOrFail()->slug);
    }

    public function test_sale_price_must_undercut_the_base_price(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.products.store', ['locale' => 'en']), $this->payload([
                'base_price' => 1000,
                'sale_price' => 1200,
            ]))
            ->assertSessionHasErrors('sale_price');

        $this->assertSame(0, Product::query()->count());
    }

    // ------------------------------------------------------------- Updating

    public function test_admin_can_update_a_product_and_its_variants(): void
    {
        $product = Product::factory()->for($this->category)->create(['base_price' => 100000]);
        $variant = ProductVariant::factory()->for($product)->for($this->color)->for($this->size)
            ->create(['sku' => 'HOOR-OLD', 'stock_quantity' => 5]);

        $this->actingAs($this->admin)
            ->put(route('admin.products.update', ['locale' => 'en', 'product' => $product]), $this->payload([
                'name_en'  => 'Renamed Jeans',
                'slug'     => $product->slug,
                'variants' => [[
                    'id'                  => $variant->id,
                    'color_id'            => $this->color->id,
                    'size_id'             => $this->size->id,
                    'sku'                 => 'HOOR-NEW',
                    'stock_quantity'      => 40,
                    'low_stock_threshold' => 5,
                    'is_active'           => 1,
                ]],
            ]))
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $product->refresh();
        $variant->refresh();

        $this->assertSame('Renamed Jeans', $product->name_en);
        $this->assertSame('HOOR-NEW', $variant->sku);
        $this->assertSame(40, $variant->stock_quantity);
        // Updated in place rather than replaced.
        $this->assertSame(1, $product->variants()->count());
    }

    public function test_variants_omitted_from_the_submission_are_removed(): void
    {
        $product = Product::factory()->for($this->category)->create();
        $keep = ProductVariant::factory()->for($product)->for($this->color)->for($this->size)->create();
        $drop = ProductVariant::factory()->for($product)->create();

        $this->actingAs($this->admin)
            ->put(route('admin.products.update', ['locale' => 'en', 'product' => $product]), $this->payload([
                'slug'     => $product->slug,
                'variants' => [[
                    'id'                  => $keep->id,
                    'color_id'            => $this->color->id,
                    'size_id'             => $this->size->id,
                    'sku'                 => $keep->sku,
                    'stock_quantity'      => 3,
                    'low_stock_threshold' => 3,
                    'is_active'           => 1,
                ]],
            ]))
            ->assertSessionHasNoErrors();

        $this->assertModelExists($keep);
        $this->assertModelMissing($drop);
    }

    // ------------------------------------------------------------ SKU rules

    public function test_sku_must_be_unique_across_the_catalog(): void
    {
        ProductVariant::factory()->create(['sku' => 'HOOR-TAKEN']);

        $this->actingAs($this->admin)
            ->post(route('admin.products.store', ['locale' => 'en']), $this->payload([
                'variants' => [[
                    'color_id'            => $this->color->id,
                    'size_id'             => $this->size->id,
                    'sku'                 => 'HOOR-TAKEN',
                    'stock_quantity'      => 1,
                    'low_stock_threshold' => 3,
                    'is_active'           => 1,
                ]],
            ]))
            ->assertSessionHasErrors('variants.0.sku');

        // Only the product the variant factory created for itself exists; the
        // submitted one was rejected.
        $this->assertSame(0, Product::query()->where('name_en', 'Wide Leg Jeans')->count());
    }

    public function test_sku_uniqueness_is_case_insensitive(): void
    {
        ProductVariant::factory()->create(['sku' => 'HOOR-TAKEN']);

        $this->actingAs($this->admin)
            ->post(route('admin.products.store', ['locale' => 'en']), $this->payload([
                'variants' => [[
                    'color_id'            => $this->color->id,
                    'size_id'             => $this->size->id,
                    'sku'                 => 'hoor-taken',
                    'stock_quantity'      => 1,
                    'low_stock_threshold' => 3,
                    'is_active'           => 1,
                ]],
            ]))
            ->assertSessionHasErrors('variants.0.sku');
    }

    public function test_duplicate_skus_within_one_submission_are_rejected(): void
    {
        $otherSize = Size::factory()->create(['code' => 'L']);

        $this->actingAs($this->admin)
            ->post(route('admin.products.store', ['locale' => 'en']), $this->payload([
                'variants' => [
                    [
                        'color_id' => $this->color->id, 'size_id' => $this->size->id,
                        'sku' => 'HOOR-DUP', 'stock_quantity' => 1,
                        'low_stock_threshold' => 3, 'is_active' => 1,
                    ],
                    [
                        'color_id' => $this->color->id, 'size_id' => $otherSize->id,
                        'sku' => 'HOOR-DUP', 'stock_quantity' => 1,
                        'low_stock_threshold' => 3, 'is_active' => 1,
                    ],
                ],
            ]))
            ->assertSessionHasErrors('variants.1.sku');

        $this->assertSame(0, Product::query()->count());
    }

    public function test_editing_a_variant_may_keep_its_own_sku(): void
    {
        $product = Product::factory()->for($this->category)->create();
        $variant = ProductVariant::factory()->for($product)->for($this->color)->for($this->size)
            ->create(['sku' => 'HOOR-KEEP']);

        $this->actingAs($this->admin)
            ->put(route('admin.products.update', ['locale' => 'en', 'product' => $product]), $this->payload([
                'slug'     => $product->slug,
                'variants' => [[
                    'id'                  => $variant->id,
                    'color_id'            => $this->color->id,
                    'size_id'             => $this->size->id,
                    'sku'                 => 'HOOR-KEEP',
                    'stock_quantity'      => 9,
                    'low_stock_threshold' => 3,
                    'is_active'           => 1,
                ]],
            ]))
            ->assertSessionHasNoErrors();
    }

    public function test_duplicate_colour_and_size_pairs_are_rejected(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.products.store', ['locale' => 'en']), $this->payload([
                'variants' => [
                    [
                        'color_id' => $this->color->id, 'size_id' => $this->size->id,
                        'sku' => 'HOOR-A', 'stock_quantity' => 1,
                        'low_stock_threshold' => 3, 'is_active' => 1,
                    ],
                    [
                        'color_id' => $this->color->id, 'size_id' => $this->size->id,
                        'sku' => 'HOOR-B', 'stock_quantity' => 1,
                        'low_stock_threshold' => 3, 'is_active' => 1,
                    ],
                ],
            ]))
            ->assertSessionHasErrors('variants.1.size_id');
    }

    // -------------------------------------------------------------- Deleting

    public function test_deleting_a_product_soft_deletes_it(): void
    {
        $product = Product::factory()->for($this->category)->create();

        $this->actingAs($this->admin)
            ->delete(route('admin.products.destroy', ['locale' => 'en', 'product' => $product]))
            ->assertRedirect(route('admin.products.index', ['locale' => 'en']));

        $this->assertSoftDeleted($product);
    }

    // --------------------------------------------------------- Authorization

    public function test_customers_cannot_reach_product_management(): void
    {
        $customer = User::factory()->create();

        $this->actingAs($customer)
            ->get(route('admin.products.index', ['locale' => 'en']))
            ->assertForbidden();
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('admin.products.index', ['locale' => 'ar']))
            ->assertRedirect(route('login', ['locale' => 'ar']));
    }
}
