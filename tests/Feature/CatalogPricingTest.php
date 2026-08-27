<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Casts\Money;
use App\Enums\StockStatus;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogPricingTest extends TestCase
{
    use RefreshDatabase;

    // ----------------------------------------------------------------- Money

    public function test_money_converts_between_units_without_drift(): void
    {
        $this->assertSame(129000, Money::fromMajor(1290));
        $this->assertSame(92050, Money::fromMajor('920.50'));
        $this->assertSame(1290.0, Money::toMajor(129000));

        // The classic float trap: 0.1 + 0.2 must total exactly 0.30.
        $total = Money::fromMajor(0.1) + Money::fromMajor(0.2);
        $this->assertSame(30, $total);
        $this->assertSame(0.3, Money::toMajor($total));
    }

    public function test_money_formats_per_locale(): void
    {
        $this->assertSame('EGP 1,290.00', Money::format(129000, 'en'));
        $this->assertSame('1,290.00 ج.م', Money::format(129000, 'ar'));
    }

    public function test_negative_money_is_rejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Product::factory()->create(['base_price' => -1]);
    }

    // --------------------------------------------------------------- Pricing

    public function test_product_sale_price_applies_only_when_it_undercuts_base(): void
    {
        $discounted = Product::factory()->create(['base_price' => 100000, 'sale_price' => 80000]);
        $this->assertTrue($discounted->isOnSale());
        $this->assertSame(80000, $discounted->effectivePrice());
        $this->assertSame(20, $discounted->discountPercentage());

        // A mis-keyed "sale" above the base price must never raise the price.
        $mistyped = Product::factory()->create(['base_price' => 100000, 'sale_price' => 120000]);
        $this->assertFalse($mistyped->isOnSale());
        $this->assertSame(100000, $mistyped->effectivePrice());
        $this->assertSame(0, $mistyped->discountPercentage());
    }

    public function test_variant_inherits_product_pricing_when_it_has_none(): void
    {
        $product = Product::factory()->create(['base_price' => 100000, 'sale_price' => 90000]);
        $variant = ProductVariant::factory()->for($product)->create([
            'price' => null, 'sale_price' => null,
        ]);

        $this->assertSame(90000, $variant->effectivePrice());
        $this->assertSame(100000, $variant->basePrice());
        $this->assertTrue($variant->isOnSale());
    }

    public function test_variant_price_override_wins_over_product_pricing(): void
    {
        $product = Product::factory()->create(['base_price' => 100000, 'sale_price' => 90000]);

        // An override replaces the product price outright, so the product's
        // sale must not leak through and undercut the overridden amount.
        $variant = ProductVariant::factory()->for($product)->create([
            'price' => 130000, 'sale_price' => null,
        ]);

        $this->assertSame(130000, $variant->effectivePrice());
        $this->assertFalse($variant->isOnSale());
    }

    public function test_variant_sale_price_override_applies(): void
    {
        $product = Product::factory()->create(['base_price' => 100000]);
        $variant = ProductVariant::factory()->for($product)->create([
            'price' => 130000, 'sale_price' => 110000,
        ]);

        $this->assertSame(110000, $variant->effectivePrice());
        $this->assertSame(130000, $variant->basePrice());
        $this->assertTrue($variant->isOnSale());
    }

    public function test_price_range_spans_variant_overrides(): void
    {
        $product = Product::factory()->create(['base_price' => 100000]);
        ProductVariant::factory()->for($product)->create(['price' => 90000]);
        ProductVariant::factory()->for($product)->create(['price' => 150000]);

        $range = $product->fresh()->load('variants')->priceRange();

        $this->assertSame(90000, $range['min']);
        $this->assertSame(150000, $range['max']);
    }

    // ------------------------------------------------------------- Inventory

    public function test_stock_status_classifies_against_each_variants_threshold(): void
    {
        $this->assertSame(StockStatus::OutOfStock, StockStatus::forQuantity(0, 3));
        $this->assertSame(StockStatus::LowStock, StockStatus::forQuantity(3, 3));
        $this->assertSame(StockStatus::InStock, StockStatus::forQuantity(4, 3));
    }

    public function test_product_stock_is_derived_from_active_variants_only(): void
    {
        $product = Product::factory()->create();
        ProductVariant::factory()->for($product)->inStock(10)->create();
        ProductVariant::factory()->for($product)->inStock(5)->create();
        ProductVariant::factory()->for($product)->inactive()->create(['stock_quantity' => 99]);

        $product = $product->fresh()->load('variants');

        $this->assertSame(15, $product->totalStock());
        $this->assertTrue($product->isInStock());
    }

    public function test_product_is_out_of_stock_when_every_variant_is_empty(): void
    {
        $product = Product::factory()->create();
        ProductVariant::factory()->for($product)->outOfStock()->count(2)->create();

        $product = $product->fresh()->load('variants');

        $this->assertSame(0, $product->totalStock());
        $this->assertSame(StockStatus::OutOfStock, $product->stockStatus());
        $this->assertFalse($product->isInStock());
    }

    public function test_variant_can_only_fulfil_what_it_holds(): void
    {
        $variant = ProductVariant::factory()->inStock(5)->create();

        $this->assertTrue($variant->canFulfil(5));
        $this->assertFalse($variant->canFulfil(6));
        $this->assertFalse($variant->canFulfil(0));

        $inactive = ProductVariant::factory()->inactive()->inStock(50)->create();
        $this->assertFalse($inactive->canFulfil(1));
    }

    public function test_low_stock_scope_compares_each_variant_to_its_own_threshold(): void
    {
        ProductVariant::factory()->create(['stock_quantity' => 2, 'low_stock_threshold' => 3]);
        ProductVariant::factory()->create(['stock_quantity' => 8, 'low_stock_threshold' => 10]);
        ProductVariant::factory()->create(['stock_quantity' => 20, 'low_stock_threshold' => 3]);

        $this->assertSame(2, ProductVariant::query()->lowStock()->count());
    }

    public function test_sellable_scope_excludes_inactive_and_empty_variants(): void
    {
        ProductVariant::factory()->inStock(5)->create();
        ProductVariant::factory()->outOfStock()->create();
        ProductVariant::factory()->inactive()->inStock(5)->create();

        $this->assertSame(1, ProductVariant::query()->sellable()->count());
    }
}
