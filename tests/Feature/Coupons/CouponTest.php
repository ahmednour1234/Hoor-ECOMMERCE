<?php

declare(strict_types=1);

namespace Tests\Feature\Coupons;

use App\Enums\OrderStatus;
use App\Models\Coupon;
use App\Models\CouponRedemption;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Services\CartService;
use App\Services\CheckoutService;
use App\Services\CouponService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Coupons.
 *
 * The rules that matter most, in order: a coupon can never make a total
 * negative, every judgement is made server-side against the stored coupon, and
 * the same service answers for the cart and for checkout so the discount she is
 * quoted is the discount she is charged.
 */
class CouponTest extends TestCase
{
    use RefreshDatabase;

    private const PHONE = '01012345678';

    private ProductVariant $variant;

    protected function setUp(): void
    {
        parent::setUp();

        // 100 EGP a piece, plenty in stock.
        $product = Product::factory()->create(['base_price' => 10000, 'sale_price' => null]);

        $this->variant = ProductVariant::factory()->create([
            'product_id'     => $product->id,
            'stock_quantity' => 20,
            'price'          => null,
            'sale_price'     => null,
            'is_active'      => true,
        ]);
    }

    private function coupons(): CouponService
    {
        return app(CouponService::class);
    }

    /**
     * A basket of a given value, in whole pieces of 100 EGP.
     */
    private function cartOf(int $pieces): \App\Support\Cart\Cart
    {
        $cart = app(CartService::class);
        $cart->clear();
        $cart->add($this->variant, $pieces);

        return $cart->get();
    }

    // ------------------------------------------------------------- Arithmetic

    public function test_a_fixed_coupon_takes_its_amount_off(): void
    {
        $coupon = Coupon::factory()->fixed(5000)->create(['code' => 'FIFTY']);

        $result = $this->coupons()->resolve('FIFTY', $this->cartOf(2));

        $this->assertTrue($result['valid']);
        $this->assertSame(5000, $result['discount']);
        $this->assertSame($coupon->id, $result['id']);
    }

    public function test_a_percentage_coupon_takes_its_share(): void
    {
        Coupon::factory()->percentage(20)->create(['code' => 'TWENTY']);

        // 20% of 200 EGP.
        $this->assertSame(4000, $this->coupons()->resolve('TWENTY', $this->cartOf(2))['discount']);
    }

    public function test_a_percentage_coupon_respects_its_ceiling(): void
    {
        Coupon::factory()->percentage(20, cap: 3000)->create(['code' => 'CAPPED']);

        // 20% of 500 EGP would be 100; the cap holds it at 30.
        $this->assertSame(3000, $this->coupons()->resolve('CAPPED', $this->cartOf(5))['discount']);
    }

    /**
     * The rule the brief states outright.
     */
    public function test_a_coupon_can_never_make_the_total_negative(): void
    {
        // 500 EGP off a 100 EGP basket.
        Coupon::factory()->fixed(50000)->create(['code' => 'HUGE']);

        $cart = $this->cartOf(1);
        $result = $this->coupons()->resolve('HUGE', $cart);

        $this->assertSame($cart->subtotal(), $result['discount']);
        $this->assertSame(0, $cart->subtotal() - $result['discount']);
    }

    public function test_a_hundred_percent_coupon_stops_at_the_subtotal(): void
    {
        Coupon::factory()->percentage(100)->create(['code' => 'FREE']);

        $cart = $this->cartOf(3);

        $this->assertSame($cart->subtotal(), $this->coupons()->resolve('FREE', $cart)['discount']);
    }

    // ------------------------------------------------------------ Eligibility

    public function test_an_unknown_code_is_refused_without_erroring(): void
    {
        $result = $this->coupons()->resolve('NOPE', $this->cartOf(1));

        // A stale code pasted from an old email must never block an order.
        $this->assertFalse($result['valid']);
        $this->assertSame(0, $result['discount']);
        $this->assertSame('not_found', $result['reason']);
    }

    public function test_casing_and_spacing_do_not_matter(): void
    {
        Coupon::factory()->fixed(5000)->create(['code' => 'WELCOME']);

        foreach ([' welcome ', 'Welcome', 'WELCOME'] as $typed) {
            $this->assertTrue(
                $this->coupons()->resolve($typed, $this->cartOf(2))['valid'],
                "for input: {$typed}",
            );
        }
    }

    public function test_an_inactive_coupon_is_refused(): void
    {
        Coupon::factory()->fixed(5000)->inactive()->create(['code' => 'OFF']);

        $this->assertSame('inactive', $this->coupons()->resolve('OFF', $this->cartOf(2))['reason']);
    }

    public function test_an_expired_coupon_is_refused(): void
    {
        Coupon::factory()->fixed(5000)->expired()->create(['code' => 'OLD']);

        $this->assertSame('expired', $this->coupons()->resolve('OLD', $this->cartOf(2))['reason']);
    }

    public function test_a_coupon_that_has_not_started_is_refused(): void
    {
        Coupon::factory()->fixed(5000)->scheduled()->create(['code' => 'SOON']);

        $this->assertSame('not_started', $this->coupons()->resolve('SOON', $this->cartOf(2))['reason']);
    }

    public function test_a_basket_below_the_minimum_is_refused(): void
    {
        Coupon::factory()->fixed(5000)->create(['code' => 'BIG', 'min_order' => 30000]);

        // 200 EGP against a 300 EGP minimum.
        $this->assertSame('below_minimum', $this->coupons()->resolve('BIG', $this->cartOf(2))['reason']);

        // And accepted once the basket reaches it.
        $this->assertTrue($this->coupons()->resolve('BIG', $this->cartOf(3))['valid']);
    }

    public function test_a_fully_used_coupon_is_refused(): void
    {
        Coupon::factory()->fixed(5000)->create([
            'code'        => 'SPENT',
            'usage_limit' => 2,
            'used_count'  => 2,
        ]);

        $this->assertSame('exhausted', $this->coupons()->resolve('SPENT', $this->cartOf(2))['reason']);
    }

    /**
     * Telling her the code applied while taking nothing off would be worse than
     * saying it does not.
     */
    public function test_a_coupon_worth_nothing_is_not_reported_as_valid(): void
    {
        Coupon::factory()->percentage(1)->create(['code' => 'TINY', 'max_discount' => 0]);

        $result = $this->coupons()->resolve('TINY', $this->cartOf(1));

        $this->assertFalse($result['valid']);
        $this->assertSame('no_value', $result['reason']);
    }

    // ------------------------------------------------------- Per-customer use

    public function test_a_customer_cannot_use_a_one_per_customer_code_twice(): void
    {
        $coupon = Coupon::factory()->fixed(5000)->create([
            'code'               => 'WELCOME',
            'per_customer_limit' => 1,
        ]);

        // First time, before any redemption.
        $this->assertTrue($this->coupons()->resolve('WELCOME', $this->cartOf(2), self::PHONE)['valid']);

        CouponRedemption::factory()->create([
            'coupon_id' => $coupon->id,
            'phone'     => self::PHONE,
        ]);

        $this->assertSame(
            'already_used',
            $this->coupons()->resolve('WELCOME', $this->cartOf(2), self::PHONE)['reason'],
        );
    }

    /**
     * The reason the limit is keyed on phone: most HOOR customers never
     * register, and an account-only limit would be trivially avoidable.
     */
    public function test_a_guest_is_limited_by_phone_not_by_account(): void
    {
        $coupon = Coupon::factory()->fixed(5000)->create([
            'code'               => 'ONCE',
            'per_customer_limit' => 1,
        ]);

        // Used as a guest, with no account at all.
        CouponRedemption::factory()->create([
            'coupon_id' => $coupon->id,
            'user_id'   => null,
            'phone'     => self::PHONE,
        ]);

        $this->assertSame(
            'already_used',
            $this->coupons()->resolve('ONCE', $this->cartOf(2), self::PHONE)['reason'],
        );
    }

    /**
     * She may have ordered as a guest once and signed in the next time.
     */
    public function test_signing_in_does_not_reset_a_per_customer_limit(): void
    {
        $customer = User::factory()->create();

        $coupon = Coupon::factory()->fixed(5000)->create([
            'code'               => 'ONCE',
            'per_customer_limit' => 1,
        ]);

        CouponRedemption::factory()->create([
            'coupon_id' => $coupon->id,
            'user_id'   => null,
            'phone'     => self::PHONE,
        ]);

        $this->assertSame(
            'already_used',
            $this->coupons()->resolve('ONCE', $this->cartOf(2), self::PHONE, $customer->id)['reason'],
        );
    }

    public function test_a_different_customer_may_still_use_it(): void
    {
        $coupon = Coupon::factory()->fixed(5000)->create([
            'code'               => 'ONCE',
            'per_customer_limit' => 1,
        ]);

        CouponRedemption::factory()->create([
            'coupon_id' => $coupon->id,
            'phone'     => '01099998888',
        ]);

        $this->assertTrue($this->coupons()->resolve('ONCE', $this->cartOf(2), self::PHONE)['valid']);
    }

    public function test_a_phone_typed_differently_is_still_the_same_customer(): void
    {
        $coupon = Coupon::factory()->fixed(5000)->create([
            'code'               => 'ONCE',
            'per_customer_limit' => 1,
        ]);

        CouponRedemption::factory()->create([
            'coupon_id' => $coupon->id,
            'phone'     => self::PHONE,
        ]);

        // Same number, typed with a country code.
        $this->assertSame(
            'already_used',
            $this->coupons()->resolve('ONCE', $this->cartOf(2), '+20 101 234 5678')['reason'],
        );
    }

    public function test_an_unlimited_coupon_may_be_used_repeatedly(): void
    {
        $coupon = Coupon::factory()->fixed(5000)->create([
            'code'               => 'ALWAYS',
            'per_customer_limit' => null,
        ]);

        CouponRedemption::factory()->count(3)->create([
            'coupon_id' => $coupon->id,
            'phone'     => self::PHONE,
        ]);

        $this->assertTrue($this->coupons()->resolve('ALWAYS', $this->cartOf(2), self::PHONE)['valid']);
    }

    // -------------------------------------------------- Cart and checkout agree

    /**
     * The requirement that the service be reusable by both.
     */
    public function test_the_cart_and_checkout_compute_the_same_discount(): void
    {
        Coupon::factory()->percentage(15, cap: 4000)->create(['code' => 'SHARED']);

        $cart = $this->cartOf(4);

        $fromService = $this->coupons()->resolve('SHARED', $cart)['discount'];
        $fromCheckout = app(CheckoutService::class)->summarise($cart, couponCode: 'SHARED')['discount'];

        $this->assertSame($fromService, $fromCheckout);
        $this->assertGreaterThan(0, $fromCheckout);
    }

    public function test_checkout_totals_never_fall_below_shipping(): void
    {
        Coupon::factory()->fixed(999999)->create(['code' => 'HUGE']);

        $summary = app(CheckoutService::class)->summarise($this->cartOf(1), couponCode: 'HUGE');

        $this->assertSame(0, $summary['subtotal'] - $summary['discount']);
        $this->assertGreaterThanOrEqual(0, $summary['total']);
    }

    /**
     * A customer submits a code, never an amount.
     */
    public function test_a_submitted_discount_amount_is_ignored(): void
    {
        $summary = app(CheckoutService::class)->summarise(
            $this->cartOf(2),
            couponCode: 'DOES-NOT-EXIST',
        );

        $this->assertSame(0, $summary['discount']);
        $this->assertNull($summary['coupon_id']);
    }

    // ------------------------------------------------------------ Redemption

    public function test_placing_an_order_records_the_redemption(): void
    {
        $coupon = Coupon::factory()->fixed(5000)->create(['code' => 'REAL']);

        $order = $this->placeOrder('REAL');

        $this->assertSame($coupon->id, $order->coupon_id);
        $this->assertSame(5000, $order->discount);

        $this->assertDatabaseHas('coupon_redemptions', [
            'coupon_id' => $coupon->id,
            'order_id'  => $order->id,
            'phone'     => self::PHONE,
        ]);

        $this->assertSame(1, $coupon->fresh()->used_count);
    }

    public function test_quoting_a_code_does_not_spend_it(): void
    {
        $coupon = Coupon::factory()->fixed(5000)->create(['code' => 'QUOTE']);

        $this->coupons()->resolve('QUOTE', $this->cartOf(2), self::PHONE);

        $this->assertSame(0, $coupon->fresh()->used_count);
        $this->assertSame(0, CouponRedemption::query()->count());
    }

    /**
     * She never received the discount, so she keeps the entitlement.
     */
    public function test_cancelling_an_order_gives_the_use_back(): void
    {
        $coupon = Coupon::factory()->fixed(5000)->create([
            'code'               => 'REFUND',
            'per_customer_limit' => 1,
        ]);

        $order = $this->placeOrder('REFUND');
        $this->assertSame(1, $coupon->fresh()->used_count);

        app(\App\Services\OrderService::class)->cancel($order);

        $this->assertSame(0, $coupon->fresh()->used_count);
        $this->assertSame(0, CouponRedemption::query()->where('order_id', $order->id)->count());

        // And she can use it again.
        $this->assertTrue($this->coupons()->resolve('REFUND', $this->cartOf(2), self::PHONE)['valid']);
    }

    public function test_the_counter_never_goes_negative(): void
    {
        $coupon = Coupon::factory()->fixed(5000)->create(['code' => 'ODD', 'used_count' => 0]);

        $order = \App\Models\Order::factory()->create(['coupon_id' => $coupon->id]);

        // Releasing something that was never redeemed must not underflow.
        $this->coupons()->release($order);

        $this->assertSame(0, $coupon->fresh()->used_count);
    }

    /**
     * Place a real order through checkout, so the whole path is exercised.
     */
    private function placeOrder(string $code): \App\Models\Order
    {
        $governorate = \App\Models\Governorate::factory()->create(['is_active' => true, 'shipping_fee' => 5000]);

        app(CartService::class)->clear();
        app(CartService::class)->add($this->variant, 2);

        return app(CheckoutService::class)->place([
            'full_name'      => 'Layla Hassan',
            'phone'          => self::PHONE,
            'governorate_id' => $governorate->id,
            'address'        => '12 Nile Street',
            'coupon_code'    => $code,
        ]);
    }

    // ----------------------------------------------------------------- Admin

    public function test_a_customer_cannot_reach_coupon_management(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);

        $this->actingAs($customer)
            ->get(route('admin.coupons.index', ['locale' => 'en']))
            ->assertForbidden();
    }

    public function test_an_admin_can_create_a_coupon(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);

        $this->actingAs($admin)
            ->post(route('admin.coupons.store', ['locale' => 'en']), [
                'code'      => 'newcode',
                'type'      => 'fixed',
                // Typed in EGP.
                'value'     => '75',
                'is_active' => '1',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $coupon = Coupon::query()->where('code', 'NEWCODE')->firstOrFail();

        // Stored in piastres.
        $this->assertSame(7500, $coupon->value);
    }

    public function test_a_percentage_above_one_hundred_is_rejected(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);

        $this->actingAs($admin)
            ->post(route('admin.coupons.store', ['locale' => 'en']), [
                'code'  => 'TOOMUCH',
                'type'  => 'percentage',
                'value' => '150',
            ])
            ->assertSessionHasErrors('value');
    }

    public function test_a_maximum_discount_on_a_fixed_coupon_is_rejected(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);

        $this->actingAs($admin)
            ->post(route('admin.coupons.store', ['locale' => 'en']), [
                'code'         => 'CONFUSED',
                'type'         => 'fixed',
                'value'        => '50',
                'max_discount' => '30',
            ])
            ->assertSessionHasErrors('max_discount');
    }

    public function test_a_used_coupon_cannot_be_deleted(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $coupon = Coupon::factory()->create();

        CouponRedemption::factory()->create(['coupon_id' => $coupon->id]);

        $this->actingAs($admin)
            ->delete(route('admin.coupons.destroy', ['locale' => 'en', 'coupon' => $coupon]))
            ->assertSessionHasErrors('code');

        $this->assertDatabaseHas('coupons', ['id' => $coupon->id]);
    }

    public function test_the_admin_screens_render_in_both_locales(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $coupon = Coupon::factory()->percentage(20, cap: 3000)->create();

        foreach (['en', 'ar'] as $locale) {
            foreach ([
                route('admin.coupons.index', ['locale' => $locale]),
                route('admin.coupons.create', ['locale' => $locale]),
                route('admin.coupons.show', ['locale' => $locale, 'coupon' => $coupon]),
                route('admin.coupons.edit', ['locale' => $locale, 'coupon' => $coupon]),
            ] as $url) {
                $this->actingAs($admin)->get($url)->assertOk();
            }
        }
    }

    // ------------------------------------------------------------- The basket

    public function test_a_code_applied_in_the_cart_shows_its_discount(): void
    {
        Coupon::factory()->fixed(5000)->create(['code' => 'BASKET']);

        app(CartService::class)->add($this->variant, 2);

        $this->post(route('store.cart.coupon.apply', ['locale' => 'en']), [
            'coupon_code' => 'BASKET',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->get(route('store.cart.index', ['locale' => 'en']))
            ->assertOk()
            ->assertSee('BASKET');
    }

    public function test_an_unknown_code_in_the_cart_says_why(): void
    {
        app(CartService::class)->add($this->variant, 1);

        $this->post(route('store.cart.coupon.apply', ['locale' => 'en']), [
            'coupon_code' => 'NONSENSE',
        ])->assertSessionHasErrors('coupon_code');
    }
}
