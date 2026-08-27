<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocaleAndAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_root_redirects_to_the_default_locale(): void
    {
        $this->get('/')->assertRedirect('/en');
    }

    public function test_english_home_renders_ltr(): void
    {
        $this->get('/en')
            ->assertOk()
            ->assertSee('dir="ltr"', escape: false)
            ->assertSee('lang="en"', escape: false);
    }

    public function test_arabic_home_renders_rtl_and_translated_copy(): void
    {
        $this->get('/ar')
            ->assertOk()
            ->assertSee('dir="rtl"', escape: false)
            ->assertSee('lang="ar"', escape: false)
            ->assertSee(__('store.hero.headline', [], 'ar'));
    }

    public function test_unsupported_locale_is_not_routable(): void
    {
        $this->get('/fr')->assertNotFound();
    }

    public function test_guests_are_sent_to_login_in_their_own_language(): void
    {
        $this->get('/ar/admin')->assertRedirect('/ar/login');
        $this->get('/en/admin')->assertRedirect('/en/login');
    }

    public function test_customers_cannot_reach_the_dashboard(): void
    {
        $customer = User::factory()->create(['role' => UserRole::Customer]);

        $this->actingAs($customer)->get('/en/admin')->assertForbidden();
    }

    public function test_deactivated_admins_cannot_reach_the_dashboard(): void
    {
        $admin = User::factory()->admin()->inactive()->create();

        $this->actingAs($admin)->get('/en/admin')->assertForbidden();
    }

    public function test_admins_reach_the_dashboard(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get('/en/admin')->assertOk();
    }

    public function test_staff_reach_the_dashboard(): void
    {
        $staff = User::factory()->staff()->create();

        $this->actingAs($staff)->get('/ar/admin')->assertOk();
    }

    public function test_language_switch_preserves_the_current_page(): void
    {
        $this->get('/language/ar', ['Referer' => url('/en/login')])
            ->assertRedirect(url('/ar/login'));
    }

    public function test_language_switch_ignores_external_referers(): void
    {
        $this->get('/language/ar', ['Referer' => 'https://evil.example.com/en/login'])
            ->assertRedirect(url('/ar'));
    }
}
