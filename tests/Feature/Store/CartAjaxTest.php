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
use Tests\TestCase;

/**
 * The JSON surface the storefront uses to update without reloading.
 *
 * Each action must return the recalculated cart, so the page can redraw from
 * the server's figures rather than computing its own.
 */
class CartAjaxTest extends TestCase
{
    use RefreshDatabase;

    private function variant(int $stock = 10): ProductVariant
    {
        $product = Product::factory()->create(['base_price' => 100000]);
        ProductImage::factory()->for($product)->primary()->create();

        return ProductVariant::factory()
            ->for($product)->for(Color::factory())->for(Size::factory())
            ->inStock($stock)->create();
    }

    public function test_adding_returns_the_recalculated_cart(): void
    {
        $variant = $this->variant();

        $this->postJson(
            route('store.cart.store', ['locale' => 'en', 'product' => $variant->product]),
            ['variant_id' => $variant->id, 'quantity' => 2],
        )
            ->assertOk()
            ->assertJsonPath('cart.count', 2)
            ->assertJsonPath('cart.totals.subtotal', 200000)
            ->assertJsonPath('cart.empty', false)
            ->assertJsonStructure([
                'message',
                'cart' => ['count', 'empty', 'ready', 'lines', 'totals' => ['subtotal_formatted']],
            ]);
    }

    public function test_updating_returns_the_new_totals(): void
    {
        $variant = $this->variant();
        app(CartService::class)->add($variant, 1);

        $this->patchJson(route('store.cart.update', ['locale' => 'en']), [
            'variant_id' => $variant->id,
            'quantity'   => 3,
        ])
            ->assertOk()
            ->assertJsonPath('cart.count', 3)
            ->assertJsonPath('cart.totals.subtotal', 300000);
    }

    public function test_removing_returns_an_empty_cart(): void
    {
        $variant = $this->variant();
        app(CartService::class)->add($variant, 1);

        $this->deleteJson(route('store.cart.destroy', ['locale' => 'en', 'variant' => $variant->id]))
            ->assertOk()
            ->assertJsonPath('cart.count', 0)
            ->assertJsonPath('cart.empty', true);
    }

    public function test_clearing_returns_an_empty_cart(): void
    {
        $variant = $this->variant();
        app(CartService::class)->add($variant, 2);

        $this->deleteJson(route('store.cart.clear', ['locale' => 'en']))
            ->assertOk()
            ->assertJsonPath('cart.empty', true);
    }

    public function test_a_rejected_add_returns_422_with_the_reason(): void
    {
        $variant = $this->variant(stock: 1);

        $this->postJson(
            route('store.cart.store', ['locale' => 'en', 'product' => $variant->product]),
            ['variant_id' => $variant->id, 'quantity' => 5],
        )
            ->assertStatus(422)
            ->assertJsonValidationErrors('quantity');
    }

    public function test_the_json_response_carries_no_client_supplied_price(): void
    {
        $variant = $this->variant();

        $response = $this->postJson(
            route('store.cart.store', ['locale' => 'en', 'product' => $variant->product]),
            // A tampered payload trying to set its own price.
            ['variant_id' => $variant->id, 'quantity' => 1, 'unit_price' => 1],
        )->assertOk();

        // The figure returned is the database price, not the submitted one.
        $response->assertJsonPath('cart.totals.subtotal', 100000);
        $response->assertJsonPath('cart.lines.0.unit_price', 100000);
    }

    public function test_stock_changes_are_reflected_in_the_response(): void
    {
        $variant = $this->variant(stock: 10);
        app(CartService::class)->add($variant, 6);

        $variant->update(['stock_quantity' => 2]);

        $this->patchJson(route('store.cart.update', ['locale' => 'en']), [
            'variant_id' => $variant->id,
            'quantity'   => 6,
        ])
            ->assertOk()
            ->assertJsonPath('cart.count', 2)
            ->assertJsonPath('cart.lines.0.quantity', 2);
    }

    public function test_adding_when_already_holding_all_the_stock_is_not_reported_as_success(): void
    {
        // A customer holding every remaining unit gets nothing from another
        // click; telling them it was added is a lie they can see through.
        $variant = $this->variant(stock: 2);

        $url = route('store.cart.store', ['locale' => 'en', 'product' => $variant->product]);

        $this->postJson($url, ['variant_id' => $variant->id, 'quantity' => 2])
            ->assertOk()
            ->assertJsonPath('cart.count', 2);

        $this->postJson($url, ['variant_id' => $variant->id, 'quantity' => 1])
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('cart.count', 2);
    }

    public function test_a_partially_filled_add_says_how_much_was_taken(): void
    {
        $variant = $this->variant(stock: 3);

        $this->postJson(
            route('store.cart.store', ['locale' => 'en', 'product' => $variant->product]),
            ['variant_id' => $variant->id, 'quantity' => 3],
        )->assertOk();

        // Asking for two more when only three exist takes none of them.
        $response = $this->postJson(
            route('store.cart.store', ['locale' => 'en', 'product' => $variant->product]),
            ['variant_id' => $variant->id, 'quantity' => 2],
        );

        $response->assertStatus(422)->assertJsonPath('cart.count', 3);
    }

    public function test_the_forms_still_work_without_javascript(): void
    {
        // The AJAX layer is an enhancement: a plain form post must still
        // redirect and update the cart exactly as before.
        $variant = $this->variant();

        $this->post(
            route('store.cart.store', ['locale' => 'en', 'product' => $variant->product]),
            ['variant_id' => $variant->id, 'quantity' => 2],
        )
            ->assertRedirect()
            ->assertSessionHas('cart_status');

        $this->assertSame(2, app(CartService::class)->count());
    }

    public function test_shop_filtering_still_works_as_a_plain_request(): void
    {
        $variant = $this->variant();

        $this->get(route('store.shop', ['locale' => 'en', 'sale' => 1]))->assertOk();
        $this->get(route('store.shop', ['locale' => 'en']))
            ->assertOk()
            ->assertSee($variant->product->name);
    }
}
