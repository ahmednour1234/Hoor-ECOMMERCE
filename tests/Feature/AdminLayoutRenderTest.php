<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Guards the admin shell: the sidebar, navbar and stat tiles must render for a
 * privileged user in both writing directions.
 */
class AdminLayoutRenderTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_renders_the_full_admin_shell(): void
    {
        $admin = User::factory()->admin()->create(['name' => 'Layla']);

        $this->actingAs($admin)->get('/en/admin')
            ->assertOk()
            ->assertSee('dir="ltr"', escape: false)
            ->assertSee(__('admin.nav.products'))
            ->assertSee(__('admin.nav.orders'))
            ->assertSee(__('admin.stats.orders_period'))
            ->assertSee(__('admin.nav.view_store'))
            ->assertSee('Layla')
            ->assertSee('noindex, nofollow', escape: false);
    }

    public function test_dashboard_renders_rtl_in_arabic(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get('/ar/admin')
            ->assertOk()
            ->assertSee('dir="rtl"', escape: false)
            ->assertSee(__('admin.nav.products', [], 'ar'))
            ->assertSee(__('admin.stats.low_stock', [], 'ar'));
    }

    public function test_storefront_renders_header_footer_and_switcher(): void
    {
        $this->get('/en')
            ->assertOk()
            ->assertSee(__('store.hero.headline'))
            ->assertSee(__('store.promise.cod.title'))
            ->assertSee(__('store.footer.rights'))
            ->assertSee('العربية', escape: false)
            ->assertSee('hoor-horizontal-white.svg', escape: false);
    }

    public function test_currency_is_egp_on_the_dashboard(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get('/en/admin')->assertSee('EGP');
        $this->actingAs($admin)->get('/ar/admin')->assertSee('ج.م');
    }
}
