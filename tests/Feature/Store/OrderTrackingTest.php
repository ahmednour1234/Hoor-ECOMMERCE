<?php

declare(strict_types=1);

namespace Tests\Feature\Store;

use App\Models\Order;
use App\Models\OrderAddress;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

/**
 * Public order tracking.
 *
 * The requirement is twofold and the second half is the one worth testing
 * hardest: tracking must work without an account, and it must not expose one
 * customer's order to anyone who can count.
 */
class OrderTrackingTest extends TestCase
{
    use RefreshDatabase;

    private const PHONE = '01012345678';

    protected function setUp(): void
    {
        parent::setUp();

        RateLimiter::clear('track:127.0.0.1|');
    }

    private function order(string $phone = self::PHONE, ?User $owner = null): Order
    {
        $order = Order::factory()->create(['user_id' => $owner?->id]);

        OrderAddress::factory()->for($order)->create(['phone' => $phone]);
        OrderItem::factory()->for($order)->create();

        return $order;
    }

    private function lookup(string $number, string $phone): \Illuminate\Testing\TestResponse
    {
        return $this->post(route('store.tracking.lookup', ['locale' => 'en']), [
            'number' => $number,
            'phone'  => $phone,
        ]);
    }

    // ------------------------------------------------------- Guests may track

    public function test_the_tracking_form_is_public(): void
    {
        $this->get(route('store.tracking.index', ['locale' => 'en']))
            ->assertOk()
            ->assertSee(__('tracking.title'));
    }

    public function test_a_guest_can_find_an_order_with_the_number_and_phone(): void
    {
        $order = $this->order();

        $this->lookup($order->number, self::PHONE)
            ->assertRedirect(route('store.tracking.show', ['locale' => 'en', 'order' => $order]));

        $this->get(route('store.tracking.show', ['locale' => 'en', 'order' => $order]))
            ->assertOk()
            ->assertSee($order->number);
    }

    public function test_the_alternate_phone_also_works(): void
    {
        $order = Order::factory()->create();
        OrderAddress::factory()->for($order)->create([
            'phone'     => self::PHONE,
            'phone_alt' => '01198765432',
        ]);
        OrderItem::factory()->for($order)->create();

        // The courier may have reached her on the second number, so that is the
        // one she remembers giving.
        $this->lookup($order->number, '01198765432')->assertRedirect();
    }

    public function test_a_phone_typed_with_arabic_digits_or_a_country_code_still_matches(): void
    {
        $order = $this->order();

        foreach (['٠١٠١٢٣٤٥٦٧٨', '+20 101 234 5678', '0101-234-5678'] as $typed) {
            $this->lookup($order->number, $typed)
                ->assertRedirect(route('store.tracking.show', ['locale' => 'en', 'order' => $order]));
        }
    }

    public function test_the_order_number_is_matched_case_insensitively(): void
    {
        $order = $this->order();

        $this->lookup(strtolower($order->number), self::PHONE)->assertRedirect();
    }

    // -------------------------------------------------------------- Disclosure

    public function test_the_wrong_phone_does_not_find_the_order(): void
    {
        $order = $this->order();

        $this->lookup($order->number, '01099999999')
            ->assertSessionHasErrors('number');

        // And the detail page stays shut.
        $this->get(route('store.tracking.show', ['locale' => 'en', 'order' => $order]))
            ->assertNotFound();
    }

    /**
     * The core of the brief: no order is reachable by id.
     */
    public function test_an_order_cannot_be_opened_by_knowing_its_number_alone(): void
    {
        $order = $this->order();

        $this->get(route('store.tracking.show', ['locale' => 'en', 'order' => $order]))
            ->assertNotFound();
    }

    public function test_orders_are_addressed_by_number_never_by_sequential_id(): void
    {
        $order = $this->order();

        // Prove the pair first, so the only thing being tested is the id path.
        $this->lookup($order->number, self::PHONE);

        $this->get(url('/en/track/'.$order->id))->assertNotFound();
    }

    public function test_proving_one_order_does_not_unlock_another(): void
    {
        $mine = $this->order();
        $someoneElse = $this->order('01155554444');

        $this->lookup($mine->number, self::PHONE);

        $this->get(route('store.tracking.show', ['locale' => 'en', 'order' => $mine]))->assertOk();
        $this->get(route('store.tracking.show', ['locale' => 'en', 'order' => $someoneElse]))->assertNotFound();
    }

    public function test_a_missing_order_and_a_wrong_phone_give_the_same_message(): void
    {
        $order = $this->order();

        $wrongPhone = $this->lookup($order->number, '01099999999');
        $noSuchOrder = $this->lookup('HOOR-2026-999999', self::PHONE);

        // Distinguishing them would confirm which order numbers exist.
        $this->assertSame(
            $wrongPhone->getSession()->get('errors')->first('number'),
            $noSuchOrder->getSession()->get('errors')->first('number'),
        );
    }

    public function test_repeated_failures_are_throttled(): void
    {
        $order = $this->order();

        for ($attempt = 0; $attempt < 6; $attempt++) {
            $this->lookup($order->number, '0109999999'.$attempt);
        }

        $this->lookup($order->number, self::PHONE)
            ->assertSessionHasErrors('number');

        // Even the correct pair is refused while the limiter is hot.
        $this->assertStringContainsString(
            'Too many attempts',
            session('errors')->first('number'),
        );
    }

    public function test_a_successful_lookup_clears_the_throttle(): void
    {
        $order = $this->order();

        $this->lookup($order->number, '01099999999');
        $this->lookup($order->number, self::PHONE)->assertRedirect();

        // A customer who mistyped once is not penalised afterwards.
        $this->lookup($order->number, self::PHONE)->assertRedirect();
    }

    // ----------------------------------------------------------- Account holders

    public function test_a_signed_in_customer_sees_her_own_order_without_the_pair(): void
    {
        $customer = User::factory()->create();
        $order = $this->order(owner: $customer);

        $this->actingAs($customer)
            ->get(route('store.tracking.show', ['locale' => 'en', 'order' => $order]))
            ->assertOk();
    }

    public function test_a_signed_in_customer_cannot_see_someone_elses_order(): void
    {
        $customer = User::factory()->create();
        $other = $this->order(owner: User::factory()->create());

        $this->actingAs($customer)
            ->get(route('store.tracking.show', ['locale' => 'en', 'order' => $other]))
            ->assertNotFound();
    }

    public function test_staff_can_open_any_order(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $order = $this->order();

        $this->actingAs($admin)
            ->get(route('store.tracking.show', ['locale' => 'en', 'order' => $order]))
            ->assertOk();
    }

    public function test_the_page_renders_in_arabic(): void
    {
        $order = $this->order();
        $this->lookup($order->number, self::PHONE);

        $this->get(route('store.tracking.show', ['locale' => 'ar', 'order' => $order]))
            ->assertOk()
            ->assertSee('dir="rtl"', escape: false);
    }
}
