<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Casts\Money;
use App\Enums\ProductStatus;
use App\Enums\StockStatus;
use App\Models\Category;
use App\Models\Color;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\Size;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogSchemaTest extends TestCase
{
    use RefreshDatabase;

    // ------------------------------------------------------------- Relations

    public function test_category_relationships_resolve(): void
    {
        $parent = Category::factory()->create();
        $child = Category::factory()->childOf($parent)->create();
        $product = Product::factory()->for($child)->create();

        $this->assertTrue($parent->children->contains($child));
        $this->assertTrue($child->parent->is($parent));
        $this->assertTrue($child->products->contains($product));
    }

    public function test_product_variant_and_image_relationships_resolve(): void
    {
        $product = Product::factory()->create();
        $color = Color::factory()->create();
        $size = Size::factory()->create();

        $variant = ProductVariant::factory()
            ->for($product)->for($color)->for($size)->create();

        $image = ProductImage::factory()->for($product)->primary()->create();

        $this->assertTrue($variant->product->is($product));
        $this->assertTrue($variant->color->is($color));
        $this->assertTrue($variant->size->is($size));
        $this->assertTrue($product->variants->contains($variant));
        $this->assertTrue($product->primaryImage->is($image));
    }

    public function test_deleting_a_product_removes_its_variants(): void
    {
        $product = Product::factory()->create();
        ProductVariant::factory()->for($product)->count(3)->create();

        $product->forceDelete();

        $this->assertSame(0, ProductVariant::query()->count());
    }

    public function test_a_category_in_use_cannot_be_deleted(): void
    {
        $category = Category::factory()->create();
        Product::factory()->for($category)->create();

        $this->expectException(QueryException::class);

        $category->forceDelete();
    }

    // -------------------------------------------------------------- Integrity

    public function test_duplicate_variant_combinations_are_rejected(): void
    {
        $product = Product::factory()->create();
        $color = Color::factory()->create();
        $size = Size::factory()->create();

        ProductVariant::factory()->for($product)->for($color)->for($size)->create();

        $this->expectException(QueryException::class);

        ProductVariant::factory()->for($product)->for($color)->for($size)->create();
    }

    public function test_skus_are_globally_unique(): void
    {
        ProductVariant::factory()->create(['sku' => 'HOOR-0001-IND-M']);

        $this->expectException(QueryException::class);

        ProductVariant::factory()->create(['sku' => 'HOOR-0001-IND-M']);
    }

    public function test_only_one_image_stays_primary_per_product(): void
    {
        $product = Product::factory()->create();

        $first = ProductImage::factory()->for($product)->primary()->create();
        $second = ProductImage::factory()->for($product)->primary()->create();

        $this->assertFalse($first->fresh()->is_primary);
        $this->assertTrue($second->fresh()->is_primary);
        $this->assertSame(1, $product->images()->where('is_primary', true)->count());
    }

    public function test_combination_guard_catches_the_nullable_case(): void
    {
        // SQL treats NULLs as distinct, so the unique index cannot cover this;
        // the application guard must.
        $product = Product::factory()->create();
        $color = Color::factory()->create();

        ProductVariant::factory()->for($product)->for($color)->create(['size_id' => null]);

        $this->assertTrue(
            ProductVariant::hasCombination($product->id, $color->id, null)
        );
        $this->assertFalse(
            ProductVariant::hasCombination($product->id, null, null)
        );
    }
}
