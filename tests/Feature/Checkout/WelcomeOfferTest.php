<?php

declare(strict_types=1);

namespace Tests\Feature\Checkout;

use App\Models\Coupon;
use App\Models\CouponRedemption;
use App\Models\Governorate;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Services\CartService;
use App\Services\CheckoutService;
use App\Services\WelcomeOfferService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * The "sign in with Google and save 5%" offer at checkout.
 *
 * The point of these tests is that the offer is **real**. A banner promising a
 * discount that does not materialise is worse than no banner: she signs in
 * expecting a saving and the total does not move.
 *
 * So the discount goes through the ordinary coupon machinery — validated
 * server-side, recorded as a redemption, released if the order is cancelled —
 * rather than being a special case in the checkout code.
 */
class WelcomeOfferTest extends TestCase
{
    use RefreshDatabase;

    private ProductVariant $variant;

    private Governorate $governorate;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();

        config([
            'services.google.client_id'     => 'test-id',
            'services.google.client_secret' => 'test-secret',
            'hoor.welcome_offer.code'       => 'WELCOME5',
        ]);

        $this->seed(\Database\Seeders\WelcomeCouponSeeder::class);

        $product = Product::factory()->create(['base_price' => 100000, 'sale_price' => null]);

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

    private function offer(): WelcomeOfferService
    {
        return app(WelcomeOfferService::class);
    }

    private function fillCart(int $quantity = 2): void
    {
        $cart = app(CartService::class);
        $cart->clear();
        $cart->add($this->variant, $quantity);
    }

    // ------------------------------------------------------------ The campaign

    public function test_the_welcome_coupon_is_a_real_coupon(): void
    {
        $coupon = Coupon::query()->code('WELCOME5')->firstOrFail();

        $this->assertTrue($coupon->isLive());
        $this->assertSame(5, $coupon->value);

        // Once per customer is what makes it a welcome offer rather than a
        // standing discount on every order.
        $this->assertSame(1, $coupon->per_customer_limit);
    }

    /**
     * A percentage with no ceiling gives away more than the campaign intends
     * on a large basket.
     */
    public function test_the_discount_is_capped(): void
    {
        $coupon = Coupon::query()->code('WELCOME5')->firstOrFail();

        $this->assertNotNull($coupon->max_discount);

        // 5% of 100,000 EGP would be 5,000; the cap holds it.
        $this->assertSame($coupon->max_discount, $coupon->discountFor(10_000_000));
    }

    // ------------------------------------------------------------ The banner

    public function test_a_guest_sees_the_offer_at_checkout(): void
    {
        $this->fillCart();

        $this->get(route('store.checkout.index', ['locale' => 'en']))
            ->assertOk()
            ->assertSee(__('checkout.welcome_offer.cta'));
    }

    /**
     * For a signed-in customer it is either applied or already spent; either
     * way the banner would be asking her to do something she has done.
     */
    public function test_a_signed_in_customer_does_not_see_it(): void
    {
        $this->fillCart();

        $this->actingAs(User::factory()->create())
            ->get(route('store.checkout.index', ['locale' => 'en']))
            ->assertOk()
            ->assertDontSee(__('checkout.welcome_offer.cta'));
    }

    public function test_the_banner_disappears_when_the_campaign_is_switched_off(): void
    {
        Coupon::query()->code('WELCOME5')->update(['is_active' => false]);

        $this->fillCart();

        $this->get(route('store.checkout.index', ['locale' => 'en']))
            ->assertOk()
            ->assertDontSee(__('checkout.welcome_offer.cta'));
    }

    public function test_the_banner_disappears_when_the_campaign_expires(): void
    {
        Coupon::query()->code('WELCOME5')->update(['expires_at' => now()->subDay()]);

        $this->fillCart();

        $this->get(route('store.checkout.index', ['locale' => 'en']))
            ->assertOk()
            ->assertDontSee(__('checkout.welcome_offer.cta'));
    }

    public function test_no_banner_when_no_code_is_configured(): void
    {
        config(['hoor.welcome_offer.code' => '']);

        $this->fillCart();

        $this->get(route('store.checkout.index', ['locale' => 'en']))
            ->assertOk()
            ->assertDontSee(__('checkout.welcome_offer.cta'));
    }

    /**
     * The banner reads its terms from the coupon, so changing the campaign in
     * the admin changes what it promises. Copy that says 5% while the coupon
     * says 10% would be worse than no banner at all.
     */
    public function test_the_banner_announces_the_coupons_own_percentage(): void
    {
        Coupon::query()->code('WELCOME5')->update(['value' => 12]);

        $this->fillCart();

        $this->get(route('store.checkout.index', ['locale' => 'en']))
            ->assertOk()
            ->assertSee(__('checkout.welcome_offer.title', ['percent' => 12]));
    }

    /**
     * The figure shown is the one checkout will actually apply.
     */
    public function test_the_saving_shown_matches_what_would_be_charged(): void
    {
        $this->fillCart(2);

        $cart = app(CartService::class)->get();

        $shown = $this->offer()->discountFor($cart);

        $summary = app(CheckoutService::class)->summarise($cart, couponCode: 'WELCOME5');

        $this->assertGreaterThan(0, $shown);
        $this->assertSame($shown, $summary['discount']);
    }

    // ------------------------------------------------------ Eligibility

    public function test_a_guest_who_already_used_it_is_not_offered_it_again(): void
    {
        $coupon = Coupon::query()->code('WELCOME5')->firstOrFail();

        CouponRedemption::factory()->create([
            'coupon_id' => $coupon->id,
            'phone'     => '01012345678',
        ]);

        $this->assertFalse($this->offer()->isAvailableTo(null, '01012345678'));

        // Somebody else still gets it.
        $this->assertTrue($this->offer()->isAvailableTo(null, '01099998888'));
    }

    // ------------------------------------------------- The discount is real

    /**
     * The whole point: the total actually falls.
     */
    public function test_the_discount_reaches_the_order(): void
    {
        $this->fillCart(2);

        app(CartService::class)->applyCoupon('WELCOME5');

        $customer = User::factory()->create();

        $order = app(CheckoutService::class)->place([
            'full_name'      => 'Layla Hassan',
            'phone'          => '01012345678',
            'email'          => 'layla@example.com',
            'governorate_id' => (string) $this->governorate->id,
            'address'        => '12 Nile Street',
            'coupon_code'    => 'WELCOME5',
        ], $customer->id);

        // 5% of 2,000 EGP.
        $this->assertSame(10000, $order->discount);
        $this->assertSame(200000 - 10000 + 5000, $order->total);

        // And it went through the coupon machinery, not around it.
        $this->assertDatabaseHas('coupon_redemptions', [
            'coupon_id' => Coupon::query()->code('WELCOME5')->value('id'),
            'order_id'  => $order->id,
        ]);
    }

    /**
     * Once per customer, ever.
     */
    public function test_it_cannot_be_used_on_a_second_order(): void
    {
        $customer = User::factory()->create();

        $place = function () use ($customer) {
            $this->fillCart(1);
            app(CartService::class)->applyCoupon('WELCOME5');

            return app(CheckoutService::class)->place([
                'full_name'      => 'Layla Hassan',
                'phone'          => '01012345678',
                'email'          => 'layla@example.com',
                'governorate_id' => (string) $this->governorate->id,
                'address'        => '12 Nile Street',
                'coupon_code'    => 'WELCOME5',
            ], $customer->id);
        };

        $first = $place();
        $this->assertGreaterThan(0, $first->discount);

        $second = $place();
        $this->assertSame(0, $second->discount, 'The welcome discount should apply only once.');
    }

    public function test_the_offer_renders_in_both_locales(): void
    {
        $this->fillCart();

        foreach (['en', 'ar'] as $locale) {
            $this->get(route('store.checkout.index', ['locale' => $locale]))->assertOk();
        }
    }
}
