<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\StockStatus;
use App\Models\Category;
use App\Models\Color;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Size;
use Database\Seeders\CategorySeeder;
use Database\Seeders\ColorSeeder;
use Database\Seeders\ProductSeeder;
use Database\Seeders\SizeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogSeederTest extends TestCase
{
    use RefreshDatabase;

    private function seedCatalog(): void
    {
        $this->seed([SizeSeeder::class, ColorSeeder::class, CategorySeeder::class, ProductSeeder::class]);
    }

    public function test_seeding_builds_the_full_demo_catalog(): void
    {
        $this->seedCatalog();

        $this->assertSame(6, Size::query()->count());
        $this->assertSame(8, Color::query()->count());
        $this->assertGreaterThanOrEqual(7, Product::query()->count());
        $this->assertGreaterThan(50, ProductVariant::query()->count());

        // Every product must ship with a gallery and exactly one primary image.
        Product::query()->with('images')->each(function (Product $product): void {
            $this->assertGreaterThan(0, $product->images->count());
            $this->assertSame(1, $product->images->where('is_primary', true)->count());
        });
    }

    public function test_sizes_seed_in_wearable_order(): void
    {
        $this->seedCatalog();

        $this->assertSame(
            ['XS', 'S', 'M', 'L', 'XL', 'XXL'],
            Size::query()->ordered()->pluck('code')->all(),
        );
    }

    public function test_categories_seed_with_a_sub_category_tree(): void
    {
        $this->seedCatalog();

        $jeans = Category::query()->where('slug', 'jeans')->firstOrFail();

        $this->assertNull($jeans->parent_id);
        $this->assertSame(4, $jeans->children()->count());
    }

    public function test_seeded_variants_cover_every_stock_state(): void
    {
        $this->seedCatalog();

        $states = ProductVariant::query()->with('product')->get()
            ->map(fn (ProductVariant $variant): string => $variant->stockStatus()->value)
            ->unique();

        // Demo data must exercise the sold-out and low-stock UI paths.
        $this->assertTrue($states->contains(StockStatus::OutOfStock->value));
        $this->assertTrue($states->contains(StockStatus::LowStock->value));
        $this->assertTrue($states->contains(StockStatus::InStock->value));
    }

    public function test_seeded_products_are_bilingual_and_priced(): void
    {
        $this->seedCatalog();

        Product::query()->each(function (Product $product): void {
            $this->assertNotEmpty($product->name_en);
            $this->assertNotEmpty($product->name_ar);
            $this->assertGreaterThan(0, $product->base_price);

            if ($product->sale_price !== null) {
                $this->assertLessThan($product->base_price, $product->sale_price);
            }
        });
    }

    public function test_seeder_is_idempotent(): void
    {
        $this->seedCatalog();

        $counts = [
            'products' => Product::query()->count(),
            'variants' => ProductVariant::query()->count(),
            'images'   => Product::query()->withCount('images')->get()->sum('images_count'),
        ];

        // Re-running must update in place rather than duplicate.
        $this->seedCatalog();

        $this->assertSame($counts['products'], Product::query()->count());
        $this->assertSame($counts['variants'], ProductVariant::query()->count());
        $this->assertSame(
            $counts['images'],
            Product::query()->withCount('images')->get()->sum('images_count'),
        );
    }
}
