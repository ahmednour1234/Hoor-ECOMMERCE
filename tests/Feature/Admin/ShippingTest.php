<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Casts\Money;
use App\Models\Area;
use App\Models\Governorate;
use App\Models\User;
use App\Services\ShippingService;
use App\Support\Cart\Cart;
use Database\Seeders\ShippingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShippingTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $staff;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
        $this->staff = User::factory()->staff()->create();
    }

    private function shipping(): ShippingService
    {
        return app(ShippingService::class);
    }

    // ------------------------------------------------------------- Seeding

    public function test_every_egyptian_governorate_is_seeded(): void
    {
        $this->seed(ShippingSeeder::class);

        $this->assertSame(27, Governorate::query()->count());

        // A spread across the country, in both languages.
        foreach ([
            'C'   => ['Cairo', 'القاهرة'],
            'ALX' => ['Alexandria', 'الإسكندرية'],
            'ASN' => ['Aswan', 'أسوان'],
            'WAD' => ['New Valley', 'الوادي الجديد'],
            'JS'  => ['South Sinai', 'جنوب سيناء'],
        ] as $code => [$english, $arabic]) {
            $governorate = Governorate::query()->where('code', $code)->first();

            $this->assertNotNull($governorate, "Governorate {$code} is missing.");
            $this->assertSame($english, $governorate->name_en);
            $this->assertSame($arabic, $governorate->name_ar);
        }
    }

    public function test_every_governorate_has_a_fee_and_a_delivery_window(): void
    {
        $this->seed(ShippingSeeder::class);

        Governorate::query()->each(function (Governorate $governorate): void {
            $this->assertGreaterThan(0, $governorate->shipping_fee, "{$governorate->code} has no fee.");
            $this->assertGreaterThanOrEqual(
                $governorate->delivery_days_min,
                $governorate->delivery_days_max,
                "{$governorate->code} has an inverted delivery window.",
            );
        });
    }

    public function test_areas_are_seeded_only_where_the_names_are_verified(): void
    {
        $this->seed(ShippingSeeder::class);

        // Inventing districts for 24 more governorates would put unverified
        // data in front of customers, so only these three ship with areas.
        $withAreas = Governorate::query()
            ->whereHas('areas')
            ->pluck('code')
            ->sort()
            ->values()
            ->all();

        $this->assertSame(['ALX', 'C', 'GZ'], $withAreas);
    }

    public function test_seeded_areas_inherit_their_governorate_fee(): void
    {
        $this->seed(ShippingSeeder::class);

        Area::query()->each(function (Area $area): void {
            $this->assertNull(
                $area->shipping_fee,
                "Seeded area {$area->name_en} sets its own fee; areas should inherit by default.",
            );
        });
    }

    public function test_the_seeder_is_idempotent(): void
    {
        $this->seed(ShippingSeeder::class);
        $before = [Governorate::query()->count(), Area::query()->count()];

        $this->seed(ShippingSeeder::class);

        $this->assertSame($before, [Governorate::query()->count(), Area::query()->count()]);
    }

    // ------------------------------------------------------ Fee resolution

    public function test_a_governorate_fee_applies_when_no_area_is_chosen(): void
    {
        $governorate = Governorate::factory()->fee(50)->create();

        $this->assertSame(5000, $this->shipping()->feeFor($governorate));
    }

    public function test_an_area_without_its_own_fee_inherits_the_governorate(): void
    {
        $governorate = Governorate::factory()->fee(50)->create();
        $area = Area::factory()->for($governorate)->create(['shipping_fee' => null]);

        $this->assertSame(5000, $this->shipping()->feeFor($governorate, $area));
    }

    public function test_an_area_fee_overrides_the_governorate(): void
    {
        $governorate = Governorate::factory()->fee(50)->create();
        $area = Area::factory()->for($governorate)->fee(75)->create();

        $this->assertSame(7500, $this->shipping()->feeFor($governorate, $area));
    }

    public function test_an_area_from_another_governorate_is_not_priced_against_this_one(): void
    {
        // A mismatched pair must not quietly price against the wrong place.
        $cairo = Governorate::factory()->fee(50)->create();
        $aswan = Governorate::factory()->fee(90)->create();
        $cairoArea = Area::factory()->for($cairo)->fee(30)->create();

        // Direct call falls back to the governorate rather than using the
        // foreign area's cheaper fee.
        $this->assertSame(9000, $this->shipping()->feeFor($aswan, $cairoArea));

        // Resolving by id refuses outright, so the caller must handle it.
        $this->assertNull($this->shipping()->feeForIds($aswan->id, $cairoArea->id));
    }

    public function test_an_inactive_destination_cannot_be_delivered_to(): void
    {
        $governorate = Governorate::factory()->inactive()->fee(50)->create();

        $this->assertNull($this->shipping()->feeForIds($governorate->id));
        $this->assertFalse($this->shipping()->canDeliverTo($governorate->id));
    }

    public function test_an_inactive_area_cannot_be_chosen(): void
    {
        $governorate = Governorate::factory()->fee(50)->create();
        $area = Area::factory()->for($governorate)->inactive()->create();

        $this->assertNull($this->shipping()->feeForIds($governorate->id, $area->id));
    }

    public function test_a_quote_adds_shipping_to_the_cart_subtotal(): void
    {
        $governorate = Governorate::factory()->fee(60)->create();

        $quote = $this->shipping()->quote(Cart::empty(), $governorate);

        $this->assertSame(6000, $quote['fee']);
        $this->assertSame(0, $quote['subtotal']);
        $this->assertSame(6000, $quote['total']);
        $this->assertSame($governorate->deliveryWindow(), $quote['delivery_days']);
    }

    public function test_only_active_destinations_are_offered(): void
    {
        Governorate::factory()->count(2)->create();
        Governorate::factory()->inactive()->create();

        $this->assertCount(2, $this->shipping()->deliverableGovernorates());
    }

    public function test_the_fee_range_reports_the_cheapest_and_dearest(): void
    {
        Governorate::factory()->fee(40)->create();
        Governorate::factory()->fee(95)->create();
        Governorate::factory()->inactive()->fee(500)->create();

        $range = $this->shipping()->feeRange();

        $this->assertSame(4000, $range['min']);
        $this->assertSame(9500, $range['max']);
    }

    // ------------------------------------------------------------ Admin UI

    public function test_an_admin_can_manage_governorates(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.governorates.index', ['locale' => 'en']))
            ->assertOk();

        $this->actingAs($this->admin)
            ->post(route('admin.governorates.store', ['locale' => 'en']), [
                'name_en'           => 'Test Governorate',
                'name_ar'           => 'محافظة اختبار',
                'code'              => 'TST',
                'shipping_fee'      => 65,
                'delivery_days_min' => 2,
                'delivery_days_max' => 4,
                'is_active'         => 1,
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $governorate = Governorate::query()->where('code', 'TST')->firstOrFail();

        // Entered in EGP, stored in piastres.
        $this->assertSame(6500, $governorate->shipping_fee);
    }

    public function test_a_governorate_can_be_deactivated_rather_than_deleted(): void
    {
        $governorate = Governorate::factory()->create(['is_active' => true]);

        $this->actingAs($this->admin)
            ->patch(route('admin.governorates.toggle', ['locale' => 'en', 'governorate' => $governorate]))
            ->assertRedirect();

        $this->assertFalse($governorate->fresh()->is_active);
    }

    public function test_a_governorate_holding_areas_cannot_be_deleted(): void
    {
        $governorate = Governorate::factory()->create();
        Area::factory()->for($governorate)->create();

        $this->actingAs($this->admin)
            ->from(route('admin.governorates.index', ['locale' => 'en']))
            ->delete(route('admin.governorates.destroy', ['locale' => 'en', 'governorate' => $governorate]))
            ->assertSessionHasErrors('governorate');

        $this->assertModelExists($governorate);
    }

    public function test_an_admin_can_add_an_area_with_an_inherited_fee(): void
    {
        $governorate = Governorate::factory()->fee(50)->create();

        $this->actingAs($this->admin)
            ->post(route('admin.governorates.areas.store', ['locale' => 'en', 'governorate' => $governorate]), [
                'name_en'      => 'Test District',
                'name_ar'      => 'حي اختبار',
                'shipping_fee' => '',
                'is_active'    => 1,
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $area = Area::query()->where('name_en', 'Test District')->firstOrFail();

        // Blank means inherit, not free.
        $this->assertNull($area->shipping_fee);
        $this->assertSame(5000, $this->shipping()->feeFor($governorate, $area));
    }

    public function test_an_area_reached_through_the_wrong_governorate_is_refused(): void
    {
        $cairo = Governorate::factory()->create();
        $aswan = Governorate::factory()->create();
        $area = Area::factory()->for($cairo)->create();

        $this->actingAs($this->admin)
            ->get(route('admin.governorates.areas.edit', [
                'locale' => 'en', 'governorate' => $aswan, 'area' => $area,
            ]))
            ->assertNotFound();
    }

    public function test_area_names_must_be_unique_within_a_governorate_only(): void
    {
        $cairo = Governorate::factory()->create();
        $aswan = Governorate::factory()->create();

        Area::factory()->for($cairo)->create(['name_en' => 'Downtown']);

        // The same district name in another governorate is legitimate.
        $this->actingAs($this->admin)
            ->post(route('admin.governorates.areas.store', ['locale' => 'en', 'governorate' => $aswan]), [
                'name_en' => 'Downtown', 'name_ar' => 'وسط البلد', 'is_active' => 1,
            ])
            ->assertSessionHasNoErrors();

        // Repeating it within the same governorate is not.
        $this->actingAs($this->admin)
            ->post(route('admin.governorates.areas.store', ['locale' => 'en', 'governorate' => $cairo]), [
                'name_en' => 'Downtown', 'name_ar' => 'وسط البلد', 'is_active' => 1,
            ])
            ->assertSessionHasErrors('name_en');
    }

    public function test_only_administrators_may_delete_destinations(): void
    {
        $governorate = Governorate::factory()->create();

        $this->actingAs($this->staff)
            ->delete(route('admin.governorates.destroy', ['locale' => 'en', 'governorate' => $governorate]))
            ->assertForbidden();
    }

    public function test_customers_cannot_reach_shipping_settings(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('admin.governorates.index', ['locale' => 'en']))
            ->assertForbidden();
    }

    public function test_the_admin_screens_render_in_arabic(): void
    {
        $governorate = Governorate::factory()->create();
        Area::factory()->for($governorate)->create();

        $this->actingAs($this->admin)
            ->get(route('admin.governorates.index', ['locale' => 'ar']))
            ->assertOk()
            ->assertSee('dir="rtl"', escape: false);

        $this->actingAs($this->admin)
            ->get(route('admin.governorates.areas.index', ['locale' => 'ar', 'governorate' => $governorate]))
            ->assertOk()
            ->assertSee('dir="rtl"', escape: false);
    }

    public function test_no_shipping_fee_is_hardcoded_in_the_service(): void
    {
        // Changing the stored fee must change what the service reports; if a
        // number were baked in, this would not move.
        $governorate = Governorate::factory()->fee(50)->create();
        $this->assertSame(5000, $this->shipping()->feeFor($governorate));

        $governorate->update(['shipping_fee' => Money::fromMajor(123)]);

        $this->assertSame(12300, $this->shipping()->feeFor($governorate->fresh()));
    }
}
