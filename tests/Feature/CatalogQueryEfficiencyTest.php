<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Color;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\Size;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Guards the catalog against N+1 regressions.
 *
 * Reading a price goes through the parent product, so a variant collection
 * loaded without its inverse relation would re-query the product once per row —
 * invisible in tests that only assert values, ruinous on a real listing page.
 */
class CatalogQueryEfficiencyTest extends TestCase
{
    use RefreshDatabase;

    private function buildCatalog(int $products = 5, int $variantsEach = 6): void
    {
        $colors = Color::factory()->count($variantsEach)->create();
        $size = Size::factory()->create();

        Product::factory()->count($products)->create()->each(
            function (Product $product) use ($colors, $size): void {
                ProductImage::factory()->for($product)->primary()->create();

                foreach ($colors as $color) {
                    ProductVariant::factory()
                        ->for($product)->for($color)->for($size)
                        ->inStock(10)
                        ->create();
                }
            }
        );
    }

    public function test_listing_a_catalog_page_stays_on_a_constant_query_budget(): void
    {
        $this->buildCatalog();

        DB::enableQueryLog();

        $products = Product::query()
            ->published()
            ->with(['category', 'primaryImage', 'variants.color', 'variants.size'])
            ->get();

        foreach ($products as $product) {
            $product->totalStock();
            $product->stockStatus();
            $product->priceRange();
            $product->category->name;
            $product->primaryImage?->path;
        }

        $queries = count(DB::getQueryLog());
        DB::disableQueryLog();

        // One query per eager-loaded relation, and nothing per row.
        $this->assertLessThanOrEqual(
            8,
            $queries,
            "Catalog listing ran {$queries} queries; eager loading has regressed.",
        );
    }

    public function test_query_count_does_not_grow_with_the_catalog(): void
    {
        $this->buildCatalog(products: 3);

        $measure = function (): int {
            DB::flushQueryLog();
            DB::enableQueryLog();

            Product::query()->published()
                ->with(['variants.color', 'variants.size'])
                ->get()
                ->each(fn (Product $p) => $p->priceRange());

            $count = count(DB::getQueryLog());
            DB::disableQueryLog();

            return $count;
        };

        $small = $measure();

        $this->buildCatalog(products: 6);
        $large = $measure();

        $this->assertSame(
            $small,
            $large,
            "Query count grew from {$small} to {$large} as the catalog grew — an N+1 has been introduced.",
        );
    }
}
