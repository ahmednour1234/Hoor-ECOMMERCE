<?php

declare(strict_types=1);

namespace Tests\Feature\Store;

use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The heart on a product card.
 *
 * The button is Alpine, so what these can reach is the contract around it: the
 * endpoint it posts to, the state the server sends back, and whether the markup
 * it needs is on the page at all — including on pages the shop builds by
 * fetching and swapping, where a component can be rendered and still be inert.
 */
class WishlistButtonTest extends TestCase
{
    use RefreshDatabase;

    private function product(array $attributes = []): Product
    {
        $product = Product::factory()->create($attributes);

        ProductVariant::factory()->for($product)->inStock(5)->create();
        ProductImage::factory()->for($product)->primary()->create();

        return $product;
    }

    private function shop(array $query = []): \Illuminate\Testing\TestResponse
    {
        return $this->get(route('store.shop', ['locale' => 'en'] + $query));
    }

    // -------------------------------------------------------------- Markup

    public function test_every_card_carries_a_working_toggle(): void
    {
        $this->product();

        $html = $this->shop()->assertOk()->getContent();

        // The component is bound by x-data; without it the heart is decoration.
        $this->assertStringContainsString('wishlistButton(', $html);
        $this->assertStringContainsString('toggle()', $html);
    }

    public function test_the_script_the_button_depends_on_is_on_the_page(): void
    {
        $this->product();

        // The component is included once by the layout rather than pushed from
        // the card, so a page rendering cards without it would silently lose
        // every heart.
        $this->assertStringContainsString(
            'function wishlistButton(',
            $this->shop()->assertOk()->getContent(),
        );
    }

    public function test_the_shop_re_initialises_alpine_after_swapping_results(): void
    {
        $this->product();

        /*
         * The regression this guards.
         *
         * Filtering replaces the results with innerHTML and the infinite scroll
         * appends nodes, and Alpine walks neither on its own. Every heart past
         * the first page, and every heart after a filter change, was rendered
         * but dead.
         */
        $html = $this->shop()->assertOk()->getContent();

        $this->assertStringContainsString('initTree', $html);

        /*
         * Both call sites, not just the helper: a swap that forgets to call it
         * is the bug, and a test that only looks for the method would pass
         * while every heart on the page stayed dead.
         */
        $this->assertStringContainsString('this.startAlpineOn(appended)', $html);
        $this->assertStringContainsString('this.startAlpineOn([...current.children])', $html);
    }

    // ------------------------------------------------------------ Toggling

    public function test_a_signed_in_customer_can_save_and_unsave(): void
    {
        $user = User::factory()->create();
        $product = $this->product();

        $save = $this->actingAs($user)
            ->postJson(route('store.account.wishlist.toggle', [
                'locale' => 'en', 'product' => $product,
            ]))
            ->assertOk();

        $this->assertTrue($save->json('saved'));

        // The same call again takes it back off, which is what the heart does
        // on a second click.
        $unsave = $this->actingAs($user)
            ->postJson(route('store.account.wishlist.toggle', [
                'locale' => 'en', 'product' => $product,
            ]))
            ->assertOk();

        $this->assertFalse($unsave->json('saved'));
    }

    public function test_the_response_carries_the_count_the_header_badge_reads(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson(route('store.account.wishlist.toggle', [
                'locale' => 'en', 'product' => $this->product(),
            ]))
            ->assertOk();

        // Dispatched as wishlist:changed; without it the badge never moves.
        $this->assertSame(1, $response->json('count'));
        $this->assertNotNull($response->json('message'));
    }

    public function test_a_guest_cannot_toggle_and_is_sent_to_sign_in(): void
    {
        // A wishlist has to belong to someone. The button sends a guest to log
        // in rather than posting; this is the server holding the same line.
        $this->post(route('store.account.wishlist.toggle', [
            'locale' => 'en', 'product' => $this->product(),
        ]))->assertRedirect(route('login'));
    }

    // ------------------------------------------------------- Saved state

    public function test_the_shop_fills_in_hearts_the_customer_has_already_saved(): void
    {
        $user = User::factory()->create();
        $product = $this->product();

        $this->actingAs($user)->postJson(route('store.account.wishlist.toggle', [
            'locale' => 'en', 'product' => $product,
        ]));

        $html = $this->actingAs($user)->get(route('store.shop', ['locale' => 'en']))
            ->assertOk()
            ->getContent();

        /*
         * Rendered filled from the server rather than left to the browser.
         * Without it every heart starts empty and a saved product looks unsaved
         * until the customer clicks it — which then removes it.
         */
        $this->assertStringContainsString(
            'wishlistButton('.$product->id.', true)',
            $html,
        );
    }

    public function test_an_unsaved_product_renders_an_empty_heart(): void
    {
        $product = $this->product();

        $this->assertStringContainsString(
            'wishlistButton('.$product->id.', false)',
            $this->actingAs(User::factory()->create())
                ->get(route('store.shop', ['locale' => 'en']))
                ->assertOk()
                ->getContent(),
        );
    }
}
