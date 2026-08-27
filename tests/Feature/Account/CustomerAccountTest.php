<?php

declare(strict_types=1);

namespace Tests\Feature\Account;

use App\Models\Area;
use App\Models\CustomerAddress;
use App\Models\Governorate;
use App\Models\Order;
use App\Models\OrderAddress;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Services\CustomerAddressService;
use App\Services\WishlistService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The customer account: profile, addresses, orders and wishlist.
 *
 * Running underneath all of it: registration stays optional. Nothing in the
 * buying journey may require it.
 */
class CustomerAccountTest extends TestCase
{
    use RefreshDatabase;

    private User $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customer = User::factory()->create(['role' => 'customer']);
    }

    private function orderFor(?User $user): Order
    {
        $order = Order::factory()->create(['user_id' => $user?->id]);

        OrderAddress::factory()->for($order)->create();
        OrderItem::factory()->for($order)->create();

        return $order;
    }

    // ------------------------------------------------- Registration is optional

    public function test_guests_can_still_reach_the_shop_cart_and_checkout(): void
    {
        // The whole buying journey stays open, which is what "optional
        // registration" actually means in practice.
        foreach (['store.home', 'store.shop', 'store.cart.index', 'store.tracking.index'] as $route) {
            $this->get(route($route, ['locale' => 'en']))->assertOk();
        }
    }

    public function test_the_account_area_requires_signing_in(): void
    {
        foreach ([
            'store.account.index',
            'store.account.orders.index',
            'store.account.addresses.index',
            'store.account.wishlist.index',
            'store.account.returns.index',
        ] as $route) {
            $this->get(route($route, ['locale' => 'en']))->assertRedirect();
        }
    }

    // --------------------------------------------------------------- Overview

    public function test_the_overview_summarises_the_account(): void
    {
        $this->orderFor($this->customer);
        CustomerAddress::factory()->for($this->customer)->create();

        $response = $this->actingAs($this->customer)
            ->get(route('store.account.index', ['locale' => 'en']))
            ->assertOk();

        $this->assertSame(1, $response->viewData('orderCount'));
        $this->assertSame(1, $response->viewData('addressCount'));
        $this->assertSame(0, $response->viewData('wishlistCount'));
    }

    // ----------------------------------------------------------------- Orders

    public function test_a_customer_sees_only_her_own_orders(): void
    {
        $mine = $this->orderFor($this->customer);
        $hers = $this->orderFor(User::factory()->create());

        $this->actingAs($this->customer)
            ->get(route('store.account.orders.index', ['locale' => 'en']))
            ->assertOk()
            ->assertSee($mine->number)
            ->assertDontSee($hers->number);
    }

    public function test_opening_someone_elses_order_is_a_404_not_a_403(): void
    {
        // A 403 would confirm the order exists.
        $hers = $this->orderFor(User::factory()->create());

        $this->actingAs($this->customer)
            ->get(route('store.account.orders.show', ['locale' => 'en', 'order' => $hers]))
            ->assertNotFound();
    }

    public function test_a_guest_order_is_not_claimed_by_a_matching_phone(): void
    {
        // Guest checkout leaves user_id null. Inferring ownership from the
        // phone number would hand one customer's order to anyone who knows it.
        $guestOrder = $this->orderFor(null);

        $this->actingAs($this->customer)
            ->get(route('store.account.orders.show', ['locale' => 'en', 'order' => $guestOrder]))
            ->assertNotFound();
    }

    // -------------------------------------------------------------- Addresses

    public function test_a_customer_can_save_an_address(): void
    {
        $governorate = Governorate::factory()->create(['is_active' => true]);

        $this->actingAs($this->customer)
            ->post(route('store.account.addresses.store', ['locale' => 'en']), [
                'label'          => 'Home',
                'full_name'      => 'Layla Hassan',
                'phone'          => '01012345678',
                'governorate_id' => $governorate->id,
                'address'        => '12 Nile Street',
            ])
            ->assertRedirect(route('store.account.addresses.index', ['locale' => 'en']));

        $this->assertDatabaseHas('customer_addresses', [
            'user_id'   => $this->customer->id,
            'label'     => 'Home',
            'full_name' => 'Layla Hassan',
        ]);
    }

    public function test_the_first_address_saved_becomes_the_default(): void
    {
        $address = app(CustomerAddressService::class)->create(
            $this->customer,
            CustomerAddress::factory()->raw(['is_default' => false]),
        );

        // An address book of one has an obvious answer.
        $this->assertTrue($address->fresh()->is_default);
    }

    public function test_only_one_address_is_ever_the_default(): void
    {
        $service = app(CustomerAddressService::class);

        $first = $service->create($this->customer, CustomerAddress::factory()->raw());
        $second = $service->create($this->customer, CustomerAddress::factory()->raw(['is_default' => true]));

        $this->assertFalse($first->fresh()->is_default);
        $this->assertTrue($second->fresh()->is_default);
        $this->assertSame(1, $this->customer->addresses()->where('is_default', true)->count());
    }

    public function test_deleting_the_default_promotes_another(): void
    {
        $service = app(CustomerAddressService::class);

        $first = $service->create($this->customer, CustomerAddress::factory()->raw());
        $second = $service->create($this->customer, CustomerAddress::factory()->raw());

        $service->delete($first);

        // The book must not be left without a default to prefill checkout with.
        $this->assertTrue($second->fresh()->is_default);
    }

    public function test_a_customer_cannot_edit_someone_elses_address(): void
    {
        $hers = CustomerAddress::factory()->for(User::factory())->create();

        $this->actingAs($this->customer)
            ->get(route('store.account.addresses.edit', ['locale' => 'en', 'address' => $hers]))
            ->assertForbidden();

        $this->actingAs($this->customer)
            ->delete(route('store.account.addresses.destroy', ['locale' => 'en', 'address' => $hers]))
            ->assertForbidden();
    }

    public function test_an_area_from_another_governorate_is_rejected(): void
    {
        $governorate = Governorate::factory()->create(['is_active' => true]);
        $elsewhere = Area::factory()->create(['is_active' => true]);

        $this->actingAs($this->customer)
            ->post(route('store.account.addresses.store', ['locale' => 'en']), [
                'full_name'      => 'Layla Hassan',
                'phone'          => '01012345678',
                'governorate_id' => $governorate->id,
                'area_id'        => $elsewhere->id,
                'address'        => '12 Nile Street',
            ])
            ->assertSessionHasErrors('area_id');
    }

    /**
     * HTML posts strings; an int-typed service reached by '1' is exactly what
     * broke checkout in an earlier phase.
     */
    public function test_string_ids_from_the_form_are_accepted(): void
    {
        $governorate = Governorate::factory()->create(['is_active' => true]);

        $this->actingAs($this->customer)
            ->post(route('store.account.addresses.store', ['locale' => 'en']), [
                'full_name'      => 'Layla Hassan',
                'phone'          => '01012345678',
                'governorate_id' => (string) $governorate->id,
                'address'        => '12 Nile Street',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();
    }

    // --------------------------------------------------------------- Wishlist

    public function test_a_customer_can_save_and_unsave_a_product(): void
    {
        $product = Product::factory()->create();

        $this->actingAs($this->customer)
            ->post(route('store.account.wishlist.toggle', ['locale' => 'en', 'product' => $product]))
            ->assertRedirect();

        $this->assertDatabaseHas('wishlists', [
            'user_id'    => $this->customer->id,
            'product_id' => $product->id,
        ]);

        $this->actingAs($this->customer)
            ->post(route('store.account.wishlist.toggle', ['locale' => 'en', 'product' => $product]))
            ->assertRedirect();

        $this->assertDatabaseMissing('wishlists', [
            'user_id'    => $this->customer->id,
            'product_id' => $product->id,
        ]);
    }

    public function test_saving_the_same_product_twice_does_not_duplicate_it(): void
    {
        $product = Product::factory()->create();
        $wishlist = app(WishlistService::class);

        $wishlist->add($this->customer, $product);
        $wishlist->add($this->customer, $product);

        $this->assertSame(1, $wishlist->count($this->customer));
    }

    public function test_the_toggle_answers_json_when_asked(): void
    {
        $product = Product::factory()->create();

        $this->actingAs($this->customer)
            ->postJson(route('store.account.wishlist.toggle', ['locale' => 'en', 'product' => $product]))
            ->assertOk()
            ->assertJson(['saved' => true, 'count' => 1]);
    }

    public function test_a_guest_cannot_save_to_a_wishlist(): void
    {
        $product = Product::factory()->create();

        $this->post(route('store.account.wishlist.toggle', ['locale' => 'en', 'product' => $product]))
            ->assertRedirect(route('login'));
    }

    /**
     * The wishlist is a grid of product cards, so it needs the same
     * eager-loads the shop grid does.
     *
     * Measured against the service rather than the page: the storefront header
     * renders a category nav whose own queries depend on the catalog, and that
     * would drown out the thing being tested.
     */
    public function test_loading_the_wishlist_costs_the_same_however_much_is_saved(): void
    {
        $wishlist = app(WishlistService::class);
        $products = Product::factory()->count(8)->create();

        $wishlist->add($this->customer, $products->first());
        $baseline = $this->countQueriesLoadingWishlist();

        foreach ($products->skip(1) as $product) {
            $wishlist->add($this->customer, $product);
        }

        $this->assertSame($baseline, $this->countQueriesLoadingWishlist());
    }

    /**
     * Load the wishlist and touch everything a card renders, so a missing
     * eager-load shows up as a lazy query rather than passing unnoticed.
     */
    private function countQueriesLoadingWishlist(): int
    {
        $count = 0;
        \DB::listen(function () use (&$count): void {
            $count++;
        });

        foreach (app(WishlistService::class)->paginate($this->customer) as $product) {
            $product->category?->name;
            $product->primaryImage?->path;
            $product->variants->count();
        }

        return $count;
    }

    // ---------------------------------------------------------------- Renders

    public function test_every_account_page_renders_in_both_locales(): void
    {
        $this->orderFor($this->customer);
        CustomerAddress::factory()->for($this->customer)->create();

        foreach (['en', 'ar'] as $locale) {
            foreach ([
                'store.account.index',
                'store.account.orders.index',
                'store.account.addresses.index',
                'store.account.addresses.create',
                'store.account.wishlist.index',
                'store.account.returns.index',
                'store.account.profile.edit',
            ] as $route) {
                $this->actingAs($this->customer)
                    ->get(route($route, ['locale' => $locale]))
                    ->assertOk();
            }
        }
    }
}
