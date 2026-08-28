<?php

declare(strict_types=1);

namespace Tests\Feature\Checkout;

use App\Mail\OrderPlaced;
use App\Models\Governorate;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Services\CartService;
use App\Services\CheckoutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Ordering without an account, and the confirmation that follows.
 *
 * Two rules together: registration stays optional, and every order carries an
 * email so the customer has a written record. Cash on delivery gives her no
 * card statement and no receipt — this email is the only place her order
 * number is written down, which is half of what the tracking page asks for.
 */
class GuestOrderEmailTest extends TestCase
{
    use RefreshDatabase;

    private ProductVariant $variant;

    private Governorate $governorate;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();

        $product = Product::factory()->create(['base_price' => 10000, 'sale_price' => null]);

        $this->variant = ProductVariant::factory()->create([
            'product_id'     => $product->id,
            'stock_quantity' => 20,
            'price'          => null,
            'sale_price'     => null,
            'is_active'      => true,
        ]);

        $this->governorate = Governorate::factory()->create([
            'is_active'    => true,
            'shipping_fee' => 5000,
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'full_name'      => 'Layla Hassan',
            'phone'          => '01012345678',
            'email'          => 'layla@example.com',
            'governorate_id' => (string) $this->governorate->id,
            'address'        => '12 Nile Street',
        ], $overrides);
    }

    private function fillCart(int $quantity = 2): void
    {
        $cart = app(CartService::class);
        $cart->clear();
        $cart->add($this->variant, $quantity);
    }

    // ------------------------------------------------- Ordering without login

    public function test_a_guest_can_place_an_order_without_an_account(): void
    {
        $this->fillCart();

        $this->post(route('store.checkout.store', ['locale' => 'en']), $this->payload())
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $order = \App\Models\Order::query()->latest('id')->firstOrFail();

        // The whole point: no account behind it.
        $this->assertNull($order->user_id);
        $this->assertSame('layla@example.com', $order->address->email);
    }

    public function test_the_checkout_page_is_open_to_guests(): void
    {
        $this->fillCart();

        $this->get(route('store.checkout.index', ['locale' => 'en']))
            ->assertOk()
            ->assertSee(__('checkout.fields.email'));
    }

    // ------------------------------------------------------------ The email

    public function test_an_email_is_required(): void
    {
        $this->fillCart();

        $this->post(
            route('store.checkout.store', ['locale' => 'en']),
            \Illuminate\Support\Arr::except($this->payload(), 'email'),
        )->assertSessionHasErrors('email');

        $this->assertSame(0, \App\Models\Order::query()->count());
    }

    public function test_a_malformed_email_is_rejected(): void
    {
        $this->fillCart();

        $this->post(
            route('store.checkout.store', ['locale' => 'en']),
            $this->payload(['email' => 'not-an-email']),
        )->assertSessionHasErrors('email');
    }

    public function test_the_email_is_normalised(): void
    {
        $this->fillCart();

        $this->post(
            route('store.checkout.store', ['locale' => 'en']),
            $this->payload(['email' => '  LAYLA@Example.COM  ']),
        )->assertSessionHasNoErrors();

        $this->assertSame(
            'layla@example.com',
            \App\Models\Order::query()->latest('id')->firstOrFail()->address->email,
        );
    }

    public function test_the_confirmation_is_sent_to_the_address_given(): void
    {
        $this->fillCart();

        $order = app(CheckoutService::class)->place($this->payload());

        Mail::assertQueued(
            OrderPlaced::class,
            fn (OrderPlaced $mail): bool => $mail->order->is($order)
                && $mail->hasTo('layla@example.com'),
        );
    }

    /**
     * The order is committed before the mail is dispatched, so a mail server
     * that is down must not undo a sale.
     */
    public function test_a_failing_mailer_does_not_break_the_order(): void
    {
        $this->fillCart();

        Mail::shouldReceive('to')->andThrow(new \RuntimeException('smtp is down'));

        $order = app(CheckoutService::class)->place($this->payload());

        $this->assertNotNull($order->id);
        $this->assertDatabaseHas('orders', ['number' => $order->number]);
    }

    public function test_the_email_carries_what_the_customer_needs(): void
    {
        $this->fillCart();

        $order = app(CheckoutService::class)->place($this->payload());

        $html = (new OrderPlaced($order->fresh()))->locale('en')->render();

        // Her order number, because the tracking page asks for it.
        $this->assertStringContainsString($order->number, $html);

        // What she will pay the courier.
        $this->assertStringContainsString(\App\Casts\Money::format($order->total), $html);

        // And how to find the order again.
        $this->assertStringContainsString('/track', $html);
    }

    /**
     * A customer who shopped in Arabic must not receive an English email.
     *
     * Asserted through the rendered body rather than envelope(): the framework
     * applies a mailable's locale when it sends, so calling envelope() outside
     * that path reports the app's current locale instead of the mail's.
     */
    public function test_the_email_follows_the_locale_the_order_was_placed_in(): void
    {
        $this->fillCart();

        $order = app(CheckoutService::class)->place($this->payload());

        $arabic = app()->setLocale('ar');
        $arabicBody = (new OrderPlaced($order->fresh()))->render();

        app()->setLocale('en');
        $englishBody = (new OrderPlaced($order->fresh()))->render();

        $this->assertStringContainsString(__('orders.mail.cod', [], 'ar'), $arabicBody);
        $this->assertStringContainsString('Cash on delivery', $englishBody);
    }

    /**
     * The locale is carried onto the queue, so the worker renders the mail in
     * the language the customer shopped in rather than the app default.
     */
    public function test_the_queued_mail_remembers_the_locale(): void
    {
        app()->setLocale('ar');
        $this->fillCart();

        app(CheckoutService::class)->place($this->payload());

        Mail::assertQueued(
            OrderPlaced::class,
            fn (OrderPlaced $mail): bool => $mail->locale === 'ar',
        );
    }

    // ---------------------------------------------------------- Signed in too

    public function test_a_signed_in_customer_also_supplies_an_email(): void
    {
        $customer = User::factory()->create(['email' => 'account@example.com']);

        $this->fillCart();

        $this->actingAs($customer)
            ->post(route('store.checkout.store', ['locale' => 'en']), $this->payload([
                'email' => 'different@example.com',
            ]))
            ->assertSessionHasNoErrors();

        $order = \App\Models\Order::query()->latest('id')->firstOrFail();

        $this->assertSame($customer->id, $order->user_id);

        // The order's own email wins over the account's: she may want this
        // particular confirmation somewhere else.
        $this->assertSame('different@example.com', $order->address->email);
    }
}
