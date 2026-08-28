<?php

declare(strict_types=1);

namespace Tests\Feature\Checkout;

use App\Models\Governorate;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Services\CartService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * The pin a customer drops at checkout.
 *
 * A written address in Egypt is often a description rather than a location, so
 * a coordinate saves the courier a phone call. It supplements the address and
 * never replaces it — the written fields stay required.
 */
class DeliveryLocationTest extends TestCase
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

    private function fillCart(): void
    {
        $cart = app(CartService::class);
        $cart->clear();
        $cart->add($this->variant, 1);
    }

    private function place(array $overrides = []): \Illuminate\Testing\TestResponse
    {
        $this->fillCart();

        return $this->post(route('store.checkout.store', ['locale' => 'en']), $this->payload($overrides));
    }

    // ------------------------------------------------------------- The button

    public function test_the_location_button_is_on_the_checkout_form(): void
    {
        $this->fillCart();

        $this->get(route('store.checkout.index', ['locale' => 'en']))
            ->assertOk()
            ->assertSee(__('checkout.location.use'));
    }

    public function test_it_renders_in_both_locales(): void
    {
        $this->fillCart();

        foreach (['en', 'ar'] as $locale) {
            // Resolve the label in the page's own locale: asserting the
            // English string against the Arabic page would fail for the wrong
            // reason.
            $this->get(route('store.checkout.index', ['locale' => $locale]))
                ->assertOk()
                ->assertSee(__('checkout.location.use', [], $locale));
        }
    }

    // -------------------------------------------------------------- Storing it

    public function test_a_dropped_pin_is_saved_with_the_order(): void
    {
        // Cairo.
        $this->place(['latitude' => '30.0444196', 'longitude' => '31.2357116'])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $address = Order::query()->latest('id')->firstOrFail()->address;

        $this->assertSame('30.0444196', (string) $address->latitude);
        $this->assertSame('31.2357116', (string) $address->longitude);
    }

    /**
     * The pin is a convenience, not a requirement: a customer who refuses the
     * permission must still be able to order.
     */
    public function test_an_order_without_a_pin_is_accepted(): void
    {
        $this->place()->assertRedirect()->assertSessionHasNoErrors();

        $address = Order::query()->latest('id')->firstOrFail()->address;

        $this->assertNull($address->latitude);
        $this->assertNull($address->longitude);
    }

    // ------------------------------------------------------------- The bounds

    /**
     * The shop delivers only in Egypt, so a coordinate outside it is either a
     * spoofed payload or a browser reporting nonsense. Storing it would send a
     * courier somewhere absurd.
     */
    public function test_a_coordinate_outside_egypt_is_rejected(): void
    {
        // London.
        $this->place(['latitude' => '51.5074', 'longitude' => '-0.1278'])
            ->assertSessionHasErrors(['latitude', 'longitude']);

        $this->assertSame(0, Order::query()->count());
    }

    public function test_a_latitude_without_a_longitude_is_rejected(): void
    {
        $this->place(['latitude' => '30.0444'])
            ->assertSessionHasErrors('longitude');
    }

    public function test_a_longitude_without_a_latitude_is_rejected(): void
    {
        $this->place(['longitude' => '31.2357'])
            ->assertSessionHasErrors('latitude');
    }

    public function test_a_non_numeric_coordinate_is_rejected(): void
    {
        $this->place(['latitude' => 'here', 'longitude' => 'there'])
            ->assertSessionHasErrors(['latitude', 'longitude']);
    }

    /**
     * Egypt's corners, so the bounds are wide enough for real customers.
     */
    public function test_the_bounds_cover_the_whole_country(): void
    {
        foreach ([
            'Alexandria' => ['31.2001', '29.9187'],
            'Aswan'      => ['24.0889', '32.8998'],
            'Marsa Matruh' => ['31.3543', '27.2373'],
            'Hurghada'   => ['27.2579', '33.8116'],
        ] as $city => [$lat, $lng]) {
            $this->place(['latitude' => $lat, 'longitude' => $lng])
                ->assertSessionHasNoErrors("{$city} should be inside the accepted bounds.");

            Order::query()->delete();
        }
    }

    // ---------------------------------------------------------------- Admin

    public function test_staff_can_open_the_pin_on_a_map(): void
    {
        $this->place(['latitude' => '30.0444196', 'longitude' => '31.2357116']);

        $order = Order::query()->latest('id')->firstOrFail();
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);

        $this->actingAs($admin)
            ->get(route('admin.orders.show', ['locale' => 'en', 'order' => $order]))
            ->assertOk()
            ->assertSee('maps.google.com/?q=30.0444196,31.2357116', escape: false);
    }

    public function test_no_map_link_when_no_pin_was_dropped(): void
    {
        $this->place();

        $order = Order::query()->latest('id')->firstOrFail();
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);

        $this->actingAs($admin)
            ->get(route('admin.orders.show', ['locale' => 'en', 'order' => $order]))
            ->assertOk()
            ->assertDontSee(__('orders.admin.open_map'));
    }
}
