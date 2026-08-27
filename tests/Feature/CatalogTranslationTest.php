<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\ProductStatus;
use App\Models\Category;
use App\Models\Color;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Size;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Tests\TestCase;

class CatalogTranslationTest extends TestCase
{
    use RefreshDatabase;

    public function test_translated_attributes_follow_the_active_locale(): void
    {
        Product::factory()->create([
            'name_en' => 'Wide Leg Jeans',
            'name_ar' => 'جينز واسع الساق',
        ]);

        // Re-fetched from the database rather than reused from the factory: a
        // freshly created model still holds its attributes in memory, which
        // would mask a broken attribute hook.
        App::setLocale('en');
        $this->assertSame('Wide Leg Jeans', Product::query()->firstOrFail()->name);

        App::setLocale('ar');
        $this->assertSame('جينز واسع الساق', Product::query()->firstOrFail()->name);
    }

    public function test_translated_attributes_resolve_through_relationships(): void
    {
        $parent = Category::factory()->create(['name_en' => 'Jeans', 'name_ar' => 'الجينز']);
        Category::factory()->childOf($parent)->create(['name_en' => 'Wide Leg', 'name_ar' => 'واسع الساق']);

        App::setLocale('ar');
        $loaded = Category::query()->with('children')->whereKey($parent->id)->firstOrFail();

        $this->assertSame('الجينز', $loaded->name);
        $this->assertSame('واسع الساق', $loaded->children->first()->name);
    }

    public function test_missing_translation_falls_back_to_english(): void
    {
        $product = Product::factory()->create([
            'name_en'        => 'Denim Jacket',
            'name_ar'        => 'جاكيت دنيم',
            'description_en' => 'A classic layer.',
            'description_ar' => null,
        ]);

        App::setLocale('ar');

        $this->assertSame('جاكيت دنيم', $product->name);
        $this->assertSame('A classic layer.', $product->description);
    }

    public function test_search_matches_either_language(): void
    {
        Product::factory()->create(['name_en' => 'Indigo Jeans', 'name_ar' => 'جينز نيلي']);
        Product::factory()->create(['name_en' => 'Black Skirt',  'name_ar' => 'جيبة سوداء']);

        $this->assertSame(1, Product::query()->searchTranslation('name', 'Indigo')->count());
        $this->assertSame(1, Product::query()->searchTranslation('name', 'نيلي')->count());
        $this->assertSame(0, Product::query()->searchTranslation('name', 'Corduroy')->count());
    }

    public function test_published_scope_hides_drafts_and_archives(): void
    {
        Product::factory()->count(2)->create();
        Product::factory()->draft()->create();
        Product::factory()->archived()->create();

        $this->assertSame(2, Product::query()->published()->count());
    }

    public function test_publishing_stamps_published_at_once(): void
    {
        $product = Product::factory()->draft()->create();
        $this->assertNull($product->published_at);

        $product->update(['status' => ProductStatus::Published]);
        $firstStamp = $product->fresh()->published_at;
        $this->assertNotNull($firstStamp);

        // Re-saving must not move the "new in" ordering key.
        $product->update(['is_featured' => true]);
        $this->assertEquals($firstStamp, $product->fresh()->published_at);
    }

    public function test_in_stock_scope_only_returns_products_with_sellable_variants(): void
    {
        $sellable = Product::factory()->create();
        ProductVariant::factory()->for($sellable)->inStock(5)->create();

        $empty = Product::factory()->create();
        ProductVariant::factory()->for($empty)->outOfStock()->create();

        $results = Product::query()->inStock()->pluck('id');

        $this->assertTrue($results->contains($sellable->id));
        $this->assertFalse($results->contains($empty->id));
    }

    public function test_slugs_are_generated_from_the_english_name(): void
    {
        $category = Category::factory()->create(['name_en' => 'Denim Skirts', 'slug' => null]);

        $this->assertSame('denim-skirts', $category->slug);
    }

    public function test_colour_hex_is_normalised_and_luminance_is_computed(): void
    {
        $light = Color::factory()->create(['hex' => 'efe7da']);
        $dark  = Color::factory()->create(['hex' => '#2b4166']);

        $this->assertSame('#EFE7DA', $light->hex);
        $this->assertTrue($light->isLight());

        $this->assertSame('#2B4166', $dark->hex);
        $this->assertFalse($dark->isLight());
    }

    public function test_variant_sku_is_generated_and_uppercased(): void
    {
        $product = Product::factory()->create();
        $color = Color::factory()->create(['slug' => 'indigo']);
        $size = Size::factory()->create(['code' => 'M']);

        $variant = ProductVariant::factory()
            ->for($product)->for($color)->for($size)
            ->create(['sku' => null]);

        $this->assertStringStartsWith('HOOR-', $variant->sku);
        $this->assertStringContainsString('IND', $variant->sku);
        $this->assertStringEndsWith('-M', $variant->sku);
    }

    public function test_variant_label_reads_naturally_in_both_languages(): void
    {
        $color = Color::factory()->create(['name_en' => 'Indigo', 'name_ar' => 'نيلي']);
        $size = Size::factory()->create(['name_en' => 'M', 'name_ar' => 'M']);
        $variant = ProductVariant::factory()->for($color)->for($size)->create();

        App::setLocale('en');
        $this->assertSame('Indigo / M', $variant->fresh()->load(['color', 'size'])->label());

        App::setLocale('ar');
        $this->assertSame('نيلي / M', $variant->fresh()->load(['color', 'size'])->label());
    }
}
