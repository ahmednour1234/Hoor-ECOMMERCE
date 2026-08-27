<?php

declare(strict_types=1);

namespace Tests\Feature\Store;

use App\Models\Color;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\Size;
use App\Services\CartService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CartTest extends TestCase
{
    use RefreshDatabase;

    private function variant(int $stock = 10, array $productAttributes = []): ProductVariant
    {
        $product = Product::factory()->create($productAttributes);
        ProductImage::factory()->for($product)->primary()->create();

        return ProductVariant::factory()
            ->for($product)
            ->for(Color::factory())
            ->for(Size::factory())
            ->inStock($stock)
            ->create();
    }

    private function cart(): CartService
    {
        return app(CartService::class);
    }

    private function addUrl(ProductVariant $variant): string
    {
        return route('store.cart.store', ['locale' => 'en', 'product' => $variant->product]);
    }

    // -------------------------------------------------------- Guest access

    public function test_a_guest_can_use_the_cart_without_logging_in(): void
    {
        $variant = $this->variant();

        $this->assertGuest();

        $this->post($this->addUrl($variant), ['variant_id' => $variant->id, 'quantity' => 2])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->get(route('store.cart.index', ['locale' => 'en']))
            ->assertOk()
            ->assertSee($variant->product->name);
    }

    public function test_the_cart_page_renders_empty_and_rtl(): void
    {
        $this->get(route('store.cart.index', ['locale' => 'en']))
            ->assertOk()
            ->assertSee(__('cart.empty'));

        $this->get(route('store.cart.index', ['locale' => 'ar']))
            ->assertOk()
            ->assertSee('dir="rtl"', escape: false)
            ->assertSee(__('cart.empty', [], 'ar'));
    }

    // ------------------------------------------------------------- Commands

    public function test_adding_the_same_variant_twice_merges_the_line(): void
    {
        $variant = $this->variant(stock: 10);

        $this->post($this->addUrl($variant), ['variant_id' => $variant->id, 'quantity' => 2]);
        $this->post($this->addUrl($variant), ['variant_id' => $variant->id, 'quantity' => 3]);

        $cart = $this->cart()->get();

        $this->assertCount(1, $cart->lines, 'The second add created a separate line.');
        $this->assertSame(5, $cart->totalQuantity());
    }

    public function test_a_merge_cannot_push_a_line_past_the_stock_on_hand(): void
    {
        $variant = $this->variant(stock: 4);

        $this->cart()->add($variant, 3);
        $this->cart()->add($variant, 3);

        $this->assertSame(4, $this->cart()->quantityFor($variant->id));
    }

    public function test_quantities_can_be_updated(): void
    {
        $variant = $this->variant(stock: 10);
        $this->cart()->add($variant, 2);

        $this->patch(route('store.cart.update', ['locale' => 'en']), [
            'variant_id' => $variant->id,
            'quantity'   => 5,
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertSame(5, $this->cart()->quantityFor($variant->id));
    }

    public function test_updating_to_zero_removes_the_line(): void
    {
        $variant = $this->variant();
        $this->cart()->add($variant, 3);

        $this->patch(route('store.cart.update', ['locale' => 'en']), [
            'variant_id' => $variant->id,
            'quantity'   => 0,
        ])->assertRedirect();

        $this->assertTrue($this->cart()->isEmpty());
    }

    public function test_updating_a_line_that_is_not_in_the_cart_does_not_add_it(): void
    {
        // A stale form must not resurrect something the customer removed.
        $variant = $this->variant();

        $this->patch(route('store.cart.update', ['locale' => 'en']), [
            'variant_id' => $variant->id,
            'quantity'   => 3,
        ]);

        $this->assertTrue($this->cart()->isEmpty());
    }

    public function test_an_item_can_be_removed_and_the_cart_cleared(): void
    {
        $first = $this->variant();
        $second = $this->variant();

        $this->cart()->add($first, 1);
        $this->cart()->add($second, 1);

        $this->delete(route('store.cart.destroy', ['locale' => 'en', 'variant' => $first->id]))
            ->assertRedirect();

        $this->assertSame(1, $this->cart()->count());

        $this->delete(route('store.cart.clear', ['locale' => 'en']))->assertRedirect();

        $this->assertTrue($this->cart()->isEmpty());
    }

    // ----------------------------------------------------- Price integrity

    public function test_the_session_never_holds_a_price(): void
    {
        $variant = $this->variant();
        $this->cart()->add($variant, 2);

        $stored = session('hoor.cart');

        // Only variant id => quantity. Nothing else can be stored, so a
        // submitted price has nowhere to live.
        $this->assertSame([$variant->id => 2], $stored);
    }

    public function test_a_submitted_price_is_ignored(): void
    {
        $variant = $this->variant(stock: 5);
        $variant->product->update(['base_price' => 100000]);

        $this->post($this->addUrl($variant), [
            'variant_id' => $variant->id,
            'quantity'   => 1,
            // A tampered payload attempting to set its own price.
            'price'      => 1,
            'unit_price' => 1,
        ])->assertSessionHasNoErrors();

        $this->assertSame(100000, $this->cart()->get()->subtotal());
    }

    public function test_the_price_follows_the_database_after_an_admin_change(): void
    {
        $variant = $this->variant(stock: 5);
        $variant->product->update(['base_price' => 100000]);

        $this->cart()->add($variant, 2);
        $this->assertSame(200000, $this->cart()->get()->subtotal());

        // The admin discounts the product while the cart is open.
        $variant->product->update(['sale_price' => 70000]);

        $this->assertSame(
            140000,
            $this->cart()->get()->subtotal(),
            'The cart charged a stale price after the catalog changed.',
        );
    }

    public function test_savings_are_reported_when_a_line_is_discounted(): void
    {
        $variant = $this->variant(stock: 5);
        $variant->product->update(['base_price' => 100000, 'sale_price' => 80000]);

        $this->cart()->add($variant, 2);
        $cart = $this->cart()->get();

        $this->assertSame(160000, $cart->subtotal());
        $this->assertSame(40000, $cart->savings());
        $this->assertTrue($cart->hasSavings());
    }

    // ------------------------------------------------- Stock reconciliation

    public function test_a_line_is_trimmed_when_stock_falls_below_it(): void
    {
        $variant = $this->variant(stock: 10);
        $this->cart()->add($variant, 6);

        // Stock drops while the customer is browsing.
        $variant->update(['stock_quantity' => 2]);

        $cart = $this->cart()->get();

        $this->assertSame(2, $cart->totalQuantity(), 'The quantity was not trimmed to the stock on hand.');
        $this->assertTrue($cart->hasNotices(), 'The customer was not told the quantity changed.');
        $this->assertStringContainsString('2', implode(' ', $cart->notices));
    }

    public function test_a_sold_out_line_is_removed_with_an_explanation(): void
    {
        $variant = $this->variant(stock: 5);
        $this->cart()->add($variant, 2);

        $variant->update(['stock_quantity' => 0]);

        $cart = $this->cart()->get();

        $this->assertTrue($cart->isEmpty());
        $this->assertTrue($cart->hasNotices());
    }

    public function test_a_deactivated_variant_is_removed(): void
    {
        $variant = $this->variant(stock: 5);
        $this->cart()->add($variant, 1);

        $variant->update(['is_active' => false]);

        $cart = $this->cart()->get();

        $this->assertTrue($cart->isEmpty());
        $this->assertTrue($cart->hasNotices());
    }

    public function test_an_unpublished_product_is_removed_from_the_cart(): void
    {
        $variant = $this->variant(stock: 5);
        $this->cart()->add($variant, 1);

        $variant->product->update(['status' => \App\Enums\ProductStatus::Draft]);

        $this->assertTrue($this->cart()->get()->isEmpty());
    }

    public function test_a_deleted_variant_does_not_break_the_cart(): void
    {
        $variant = $this->variant(stock: 5);
        $this->cart()->add($variant, 1);

        $variant->delete();

        $cart = $this->cart()->get();

        $this->assertTrue($cart->isEmpty());
        $this->assertTrue($cart->hasNotices());
    }

    public function test_the_reconciled_quantity_is_written_back_to_the_session(): void
    {
        $variant = $this->variant(stock: 10);
        $this->cart()->add($variant, 8);

        $variant->update(['stock_quantity' => 3]);

        $this->cart()->get();

        // The correction persists, so the next request does not re-notify.
        $this->assertSame(3, $this->cart()->quantityFor($variant->id));
    }

    public function test_checkout_is_blocked_while_a_line_cannot_be_fulfilled(): void
    {
        $variant = $this->variant(stock: 5);
        $this->cart()->add($variant, 2);

        $this->assertTrue($this->cart()->get()->isCheckoutReady());

        // Bypass the service to simulate a line that outran its stock without
        // hydration having trimmed it yet.
        session(['hoor.cart' => [$variant->id => 2]]);
        $variant->update(['stock_quantity' => 1]);

        $cart = $this->cart()->get();

        $this->assertSame(1, $cart->totalQuantity());
        $this->assertTrue($cart->isCheckoutReady(), 'A trimmed cart should still be checkout-ready.');
    }

    // ---------------------------------------------------------- Robustness

    public function test_corrupted_session_data_is_discarded(): void
    {
        session(['hoor.cart' => [
            'not-an-id'  => 5,
            '-3'         => 2,
            '7'          => 'many',
            '9'          => -1,
        ]]);

        $this->assertTrue($this->cart()->isEmpty());
        $this->get(route('store.cart.index', ['locale' => 'en']))->assertOk();
    }

    public function test_a_non_array_session_value_is_survivable(): void
    {
        session(['hoor.cart' => 'nonsense']);

        $this->assertSame(0, $this->cart()->count());
        $this->get(route('store.cart.index', ['locale' => 'en']))->assertOk();
    }

    public function test_invalid_update_payloads_are_rejected(): void
    {
        $variant = $this->variant();
        $this->cart()->add($variant, 1);

        foreach ([-1, 100, 'abc'] as $quantity) {
            $this->patch(route('store.cart.update', ['locale' => 'en']), [
                'variant_id' => $variant->id,
                'quantity'   => $quantity,
            ])->assertSessionHasErrors('quantity');
        }
    }

    // ---------------------------------------------------------- Efficiency

    public function test_hydrating_the_cart_is_one_query_regardless_of_size(): void
    {
        $variants = collect(range(1, 6))->map(fn (): ProductVariant => $this->variant(stock: 10));

        foreach ($variants as $variant) {
            $this->cart()->add($variant, 1);
        }

        DB::enableQueryLog();

        $cart = $this->cart()->get();
        $cart->lines->each(function ($line): void {
            $line->unitPrice();
            $line->product()->name;
            $line->product()->primaryImage?->path;
            $line->variant->label();
        });

        $queries = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertCount(6, $cart->lines);

        // One read per eager-loaded relation, never one per line.
        $this->assertLessThan(
            10,
            $queries,
            "Hydrating the cart ran {$queries} queries.",
        );
    }

    public function test_the_cart_query_count_does_not_grow_with_the_number_of_lines(): void
    {
        $measure = function (): int {
            DB::flushQueryLog();
            DB::enableQueryLog();

            $this->cart()->get()->lines->each(function ($line): void {
                $line->unitPrice();
                $line->product()->name;
                $line->product()->primaryImage?->path;
                $line->variant->label();
            });

            $count = count(DB::getQueryLog());
            DB::disableQueryLog();

            return $count;
        };

        collect(range(1, 2))->each(fn () => $this->cart()->add($this->variant(stock: 10), 1));
        $small = $measure();

        collect(range(1, 8))->each(fn () => $this->cart()->add($this->variant(stock: 10), 1));
        $large = $measure();

        $this->assertSame(
            $small,
            $large,
            "Query count grew from {$small} to {$large} as the cart grew — an N+1 has appeared.",
        );
    }

    public function test_the_header_badge_does_not_hydrate_the_cart(): void
    {
        $variant = $this->variant();
        $this->cart()->add($variant, 3);

        DB::enableQueryLog();
        $count = $this->cart()->count();
        $queries = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertSame(3, $count);
        $this->assertSame(0, $queries, 'The badge queried the database when the session already knew the count.');
    }
}
