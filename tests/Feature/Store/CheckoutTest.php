<?php

declare(strict_types=1);

namespace Tests\Feature\Store;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Exceptions\CartChangedException;
use App\Exceptions\InsufficientStockException;
use App\Models\Area;
use App\Models\Color;
use App\Models\Governorate;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\Size;
use App\Models\User;
use App\Services\CartService;
use App\Services\CheckoutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    private Governorate $governorate;

    protected function setUp(): void
    {
        parent::setUp();

        $this->governorate = Governorate::factory()->fee(45)->create([
            'name_en' => 'Cairo', 'name_ar' => 'القاهرة',
            'delivery_days_min' => 1, 'delivery_days_max' => 3,
        ]);
    }

    private function variant(int $stock = 10, int $price = 100000): ProductVariant
    {
        $product = Product::factory()->create(['base_price' => $price]);
        ProductImage::factory()->for($product)->primary()->create();

        return ProductVariant::factory()
            ->for($product)
            ->for(Color::factory()->create(['name_en' => 'Indigo', 'name_ar' => 'نيلي']))
            ->for(Size::factory()->create(['code' => 'M', 'name_en' => 'M', 'name_ar' => 'M']))
            ->inStock($stock)
            ->create();
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function details(array $overrides = []): array
    {
        return array_merge([
            'full_name'      => 'Layla Hassan',
            'phone'          => '01012345678',
            // Required since the confirmation email shipped: cash on delivery
            // leaves the customer no other written record of her order.
            'email'          => 'layla@example.com',
            'phone_alt'      => null,
            'governorate_id' => $this->governorate->id,
            'area_id'        => null,
            'address'        => '12 Street Name, Building 3, Floor 2',
            'landmark'       => 'Near the pharmacy',
            'notes'          => 'Please call before arriving',
        ], $overrides);
    }

    private function cart(): CartService
    {
        return app(CartService::class);
    }

    private function checkout(): CheckoutService
    {
        return app(CheckoutService::class);
    }

    // -------------------------------------------------------- Placing orders

    public function test_a_guest_can_place_a_cash_on_delivery_order(): void
    {
        $variant = $this->variant(stock: 5, price: 100000);
        $this->cart()->add($variant, 2);

        $this->assertGuest();

        $response = $this->post(route('store.checkout.store', ['locale' => 'en']), $this->details());

        $response->assertRedirect()->assertSessionHasNoErrors();

        $order = Order::query()->firstOrFail();

        $this->assertNull($order->user_id, 'A guest order should not be attached to an account.');
        $this->assertSame(OrderStatus::Pending, $order->status);
        $this->assertSame(PaymentMethod::CashOnDelivery, $order->payment_method);
    }

    public function test_ids_arriving_as_form_strings_are_accepted(): void
    {
        // A browser posts every field as a string. Passing integers in tests
        // hid a type error that broke the real form.
        $area = Area::factory()->for($this->governorate)->create();
        $variant = $this->variant(stock: 5);
        $this->cart()->add($variant, 1);

        $order = $this->checkout()->place($this->details([
            'governorate_id' => (string) $this->governorate->id,
            'area_id'        => (string) $area->id,
        ]));

        $this->assertSame($this->governorate->id, $order->address->governorate_id);
        $this->assertSame($area->id, $order->address->area_id);
    }

    public function test_totals_are_computed_server_side(): void
    {
        $variant = $this->variant(stock: 5, price: 100000);
        $this->cart()->add($variant, 2);

        $order = $this->checkout()->place($this->details());

        $this->assertSame(200000, $order->subtotal);   // 2 × 1,000.00
        $this->assertSame(4500, $order->shipping);     // Cairo
        $this->assertSame(0, $order->discount);
        $this->assertSame(204500, $order->total);
        $this->assertTrue($order->load('items')->totalsReconcile());
    }

    public function test_submitted_money_is_ignored(): void
    {
        // A tampered payload must not influence a single figure.
        $variant = $this->variant(stock: 5, price: 100000);
        $this->cart()->add($variant, 1);

        $this->post(route('store.checkout.store', ['locale' => 'en']), $this->details([
            'subtotal' => 1,
            'shipping' => 0,
            'discount' => 999999,
            'total'    => 1,
            'price'    => 1,
        ]))->assertSessionHasNoErrors();

        $order = Order::query()->firstOrFail();

        $this->assertSame(100000, $order->subtotal);
        $this->assertSame(4500, $order->shipping);
        $this->assertSame(0, $order->discount);
        $this->assertSame(104500, $order->total);
    }

    public function test_shipping_comes_from_the_chosen_destination(): void
    {
        $expensive = Governorate::factory()->fee(95)->create();
        $variant = $this->variant(stock: 5, price: 50000);
        $this->cart()->add($variant, 1);

        $order = $this->checkout()->place($this->details(['governorate_id' => $expensive->id]));

        $this->assertSame(9500, $order->shipping);
        $this->assertSame(59500, $order->total);
    }

    public function test_an_area_fee_overrides_the_governorate(): void
    {
        $area = Area::factory()->for($this->governorate)->fee(70)->create();
        $variant = $this->variant(stock: 5, price: 50000);
        $this->cart()->add($variant, 1);

        $order = $this->checkout()->place($this->details(['area_id' => $area->id]));

        $this->assertSame(7000, $order->shipping);
    }

    // ------------------------------------------------------------- Snapshots

    public function test_order_items_snapshot_everything_the_line_needs(): void
    {
        $variant = $this->variant(stock: 5, price: 120000);
        $variant->product->update(['name_en' => 'Wide Leg Jeans', 'name_ar' => 'جينز واسع']);
        $this->cart()->add($variant, 2);

        $order = $this->checkout()->place($this->details());
        $item = $order->items()->firstOrFail();

        $this->assertSame('Wide Leg Jeans', $item->product_name_en);
        $this->assertSame('جينز واسع', $item->product_name_ar);
        $this->assertSame($variant->sku, $item->sku);
        $this->assertSame('Indigo', $item->color_name_en);
        $this->assertSame('M', $item->size_name_en);
        $this->assertSame(120000, $item->unit_price);
        $this->assertSame(2, $item->quantity);
        $this->assertSame(240000, $item->line_total);
        $this->assertNotNull($item->image_path);
    }

    public function test_the_snapshot_survives_the_product_changing_afterwards(): void
    {
        // This is the whole point of snapshotting: the order is the record, not
        // a view onto a catalog that keeps moving.
        $variant = $this->variant(stock: 5, price: 100000);
        $variant->product->update(['name_en' => 'Original Name']);
        $this->cart()->add($variant, 1);

        $order = $this->checkout()->place($this->details());

        $variant->product->update(['name_en' => 'Renamed Later', 'base_price' => 500000]);
        $variant->update(['sku' => 'CHANGED-SKU']);

        $item = $order->items()->firstOrFail();

        $this->assertSame('Original Name', $item->product_name_en);
        $this->assertNotSame('CHANGED-SKU', $item->sku);
        $this->assertSame(100000, $item->unit_price);
        $this->assertSame(100000, $order->fresh()->subtotal);
    }

    public function test_the_snapshot_survives_the_product_being_deleted(): void
    {
        $variant = $this->variant(stock: 5);
        $this->cart()->add($variant, 1);

        $order = $this->checkout()->place($this->details());
        $item = $order->items()->firstOrFail();
        $name = $item->product_name_en;

        $variant->product->forceDelete();

        $item->refresh();

        $this->assertSame($name, $item->product_name_en, 'The order line lost its snapshot.');
        $this->assertNull($item->product_id, 'The link should be nulled, not cascade the row away.');
    }

    public function test_the_address_snapshots_the_destination_names(): void
    {
        $area = Area::factory()->for($this->governorate)->create([
            'name_en' => 'Nasr City', 'name_ar' => 'مدينة نصر',
        ]);

        $variant = $this->variant(stock: 5);
        $this->cart()->add($variant, 1);

        $order = $this->checkout()->place($this->details(['area_id' => $area->id]));
        $address = $order->address;

        $this->assertSame('Cairo', $address->governorate_name_en);
        $this->assertSame('القاهرة', $address->governorate_name_ar);
        $this->assertSame('Nasr City', $address->area_name_en);
        $this->assertSame('Layla Hassan', $address->full_name);
        $this->assertSame('01012345678', $address->phone);

        // Renaming the governorate must not rewrite a past delivery address.
        $this->governorate->update(['name_en' => 'Renamed']);

        $this->assertSame('Cairo', $address->fresh()->governorate_name_en);
    }

    // ------------------------------------------------------ Stock and locking

    public function test_stock_is_decremented_on_a_successful_order(): void
    {
        $variant = $this->variant(stock: 10);
        $this->cart()->add($variant, 3);

        $this->checkout()->place($this->details());

        $this->assertSame(7, $variant->fresh()->stock_quantity);
    }

    public function test_an_order_exceeding_stock_is_refused_and_nothing_is_written(): void
    {
        $variant = $this->variant(stock: 5);
        $this->cart()->add($variant, 5);

        // Stock drops after the basket was built.
        $variant->update(['stock_quantity' => 2]);

        // The basket is reconciled on read, so the customer is about to be
        // charged for a different order than the one they confirmed. Checkout
        // must stop rather than silently ordering less.
        try {
            $this->checkout()->place($this->details());
            $this->fail('The order was placed despite the basket having changed.');
        } catch (CartChangedException | InsufficientStockException $e) {
            $this->assertNotEmpty($e->messages());
        }

        $this->assertSame(0, Order::query()->count(), 'A rejected checkout left an order behind.');
        $this->assertSame(2, $variant->fresh()->stock_quantity, 'Stock moved despite the failure.');
    }

    public function test_two_orders_cannot_both_take_the_last_unit(): void
    {
        // The overselling case the row lock exists for. Running sequentially
        // still proves the invariant: the second attempt must see the first
        // one's decrement and refuse.
        $variant = $this->variant(stock: 1);

        $this->cart()->add($variant, 1);
        $first = $this->checkout()->place($this->details());

        $this->assertSame(0, $variant->fresh()->stock_quantity);

        // A second shopper, same last unit.
        $this->cart()->clear();
        session(['hoor.cart' => [$variant->id => 1]]);

        try {
            $this->checkout()->place($this->details(['full_name' => 'Second Customer']));
            $this->fail('Two orders were allowed to take the same unit.');
        } catch (InsufficientStockException) {
            // Expected.
        }

        $this->assertSame(1, Order::query()->count());
        $this->assertSame(0, $variant->fresh()->stock_quantity, 'Stock went negative.');
    }

    public function test_stock_can_never_be_driven_negative(): void
    {
        $variant = $this->variant(stock: 3);
        session(['hoor.cart' => [$variant->id => 3]]);

        $this->checkout()->place($this->details());

        $this->assertSame(0, $variant->fresh()->stock_quantity);
        $this->assertGreaterThanOrEqual(0, $variant->fresh()->stock_quantity);
    }

    // -------------------------------------------------------------- The cart

    public function test_the_cart_is_cleared_only_after_the_order_is_created(): void
    {
        $variant = $this->variant(stock: 5);
        $this->cart()->add($variant, 2);

        $this->checkout()->place($this->details());

        $this->assertTrue($this->cart()->isEmpty());
        $this->assertSame(1, Order::query()->count());
    }

    public function test_a_failed_checkout_leaves_the_cart_intact(): void
    {
        // The customer must be able to fix the problem and retry, not lose
        // their basket to a failure they did not cause.
        $variant = $this->variant(stock: 5);
        $this->cart()->add($variant, 5);

        $variant->update(['stock_quantity' => 1]);

        try {
            $this->checkout()->place($this->details());
        } catch (CartChangedException | InsufficientStockException) {
            // Expected.
        }

        $this->assertFalse($this->cart()->isEmpty(), 'The basket was lost on a failed checkout.');
    }

    public function test_an_empty_cart_cannot_be_checked_out(): void
    {
        $this->expectException(InsufficientStockException::class);

        $this->checkout()->place($this->details());
    }

    public function test_the_checkout_page_redirects_when_the_cart_is_empty(): void
    {
        $this->get(route('store.checkout.index', ['locale' => 'en']))
            ->assertRedirect(route('store.cart.index', ['locale' => 'en']));
    }

    // ------------------------------------------------------------ Validation

    public function test_required_details_are_validated(): void
    {
        $variant = $this->variant();
        $this->cart()->add($variant, 1);

        $this->post(route('store.checkout.store', ['locale' => 'en']), [])
            ->assertSessionHasErrors(['full_name', 'phone', 'email', 'governorate_id', 'address']);
    }

    public function test_egyptian_phone_numbers_are_enforced(): void
    {
        $variant = $this->variant();
        $this->cart()->add($variant, 1);

        foreach (['12345', '00000000000', '0201234567', '+441234567890'] as $invalid) {
            $this->post(route('store.checkout.store', ['locale' => 'en']), $this->details(['phone' => $invalid]))
                ->assertSessionHasErrors('phone');
        }

        foreach (['01012345678', '01112345678', '01212345678', '01512345678'] as $valid) {
            $this->cart()->add($variant, 1);

            $this->post(route('store.checkout.store', ['locale' => 'en']), $this->details(['phone' => $valid]))
                ->assertSessionHasNoErrors();
        }
    }

    public function test_arabic_digits_and_country_codes_are_normalised(): void
    {
        // Egyptian keyboards produce Arabic-Indic digits; a valid number typed
        // that way must not be rejected.
        $variant = $this->variant();
        $this->cart()->add($variant, 1);

        $this->post(route('store.checkout.store', ['locale' => 'en']), $this->details([
            'phone' => '٠١٠١٢٣٤٥٦٧٨',
        ]))->assertSessionHasNoErrors();

        $this->assertSame('01012345678', Order::query()->firstOrFail()->address->phone);
    }

    public function test_an_area_from_another_governorate_is_rejected(): void
    {
        $other = Governorate::factory()->create();
        $area = Area::factory()->for($other)->create();

        $variant = $this->variant();
        $this->cart()->add($variant, 1);

        $this->post(route('store.checkout.store', ['locale' => 'en']), $this->details([
            'area_id' => $area->id,
        ]))->assertSessionHasErrors('area_id');

        $this->assertSame(0, Order::query()->count());
    }

    public function test_an_inactive_governorate_is_rejected(): void
    {
        $inactive = Governorate::factory()->inactive()->create();

        $variant = $this->variant();
        $this->cart()->add($variant, 1);

        $this->post(route('store.checkout.store', ['locale' => 'en']), $this->details([
            'governorate_id' => $inactive->id,
        ]))->assertSessionHasErrors('governorate_id');
    }

    // ------------------------------------------------------------- Order data

    public function test_each_order_gets_a_unique_readable_number(): void
    {
        $variant = $this->variant(stock: 10);

        $numbers = [];

        for ($i = 0; $i < 3; $i++) {
            $this->cart()->add($variant, 1);
            $numbers[] = $this->checkout()->place($this->details())->number;
        }

        $this->assertSame($numbers, array_unique($numbers), 'Order numbers collided.');
        $this->assertMatchesRegularExpression('/^HOOR-\d{4}-\d{6}$/', $numbers[0]);
    }

    public function test_an_order_opens_its_status_history(): void
    {
        $variant = $this->variant();
        $this->cart()->add($variant, 1);

        $order = $this->checkout()->place($this->details());
        $history = $order->statusHistory;

        $this->assertCount(1, $history);
        $this->assertNull($history->first()->from_status);
        $this->assertSame(OrderStatus::Pending, $history->first()->to_status);
    }

    public function test_a_signed_in_customer_gets_their_order_attached(): void
    {
        $user = User::factory()->create();
        $variant = $this->variant();
        $this->cart()->add($variant, 1);

        $this->actingAs($user)
            ->post(route('store.checkout.store', ['locale' => 'en']), $this->details())
            ->assertSessionHasNoErrors();

        $this->assertSame($user->id, Order::query()->firstOrFail()->user_id);
    }

    // ------------------------------------------------------------ Confirmation

    public function test_the_success_page_shows_what_the_customer_needs(): void
    {
        $variant = $this->variant(stock: 5, price: 100000);
        $this->cart()->add($variant, 2);

        $response = $this->post(route('store.checkout.store', ['locale' => 'en']), $this->details());
        $order = Order::query()->firstOrFail();

        $this->followRedirects($response)
            ->assertOk()
            ->assertSee($order->number)                       // order number
            ->assertSee('Layla Hassan')                       // customer name
            ->assertSee(\App\Casts\Money::format($order->total)) // total
            ->assertSee($order->status->label())              // status
            ->assertSee(__('checkout.success.next_title'));   // next step
    }

    public function test_the_success_page_is_not_exposed_to_strangers(): void
    {
        $variant = $this->variant();
        $this->cart()->add($variant, 1);

        $order = $this->checkout()->place($this->details());

        // A fresh visitor with no session flash and no account cannot read
        // someone else's order just by knowing its number.
        $this->flushSession();

        $this->get(route('store.checkout.success', ['locale' => 'en', 'order' => $order]))
            ->assertNotFound();
    }

    public function test_the_checkout_page_renders_in_both_languages(): void
    {
        $variant = $this->variant();
        $this->cart()->add($variant, 1);

        $this->get(route('store.checkout.index', ['locale' => 'en']))
            ->assertOk()
            ->assertSee(__('checkout.payment.cod'))
            ->assertSee('Cairo');

        $this->get(route('store.checkout.index', ['locale' => 'ar']))
            ->assertOk()
            ->assertSee('dir="rtl"', escape: false)
            ->assertSee(__('checkout.payment.cod', [], 'ar'));
    }

    // ----------------------------------------------------------- Coupon hook

    public function test_the_coupon_path_runs_and_currently_yields_no_discount(): void
    {
        // The coupon module has not shipped; a code must be accepted, validated
        // server-side, and simply produce no discount rather than an error.
        $variant = $this->variant(stock: 5, price: 100000);
        $this->cart()->add($variant, 1);

        $order = $this->checkout()->place($this->details(['coupon_code' => 'ANYTHING']));

        $this->assertSame(0, $order->discount);
        $this->assertNull($order->coupon_id);
        $this->assertSame(104500, $order->total);
    }
}
