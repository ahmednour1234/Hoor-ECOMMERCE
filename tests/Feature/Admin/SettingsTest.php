<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\Setting;
use App\Models\User;
use App\Services\SettingsService;
use App\Settings\SettingsRegistry;
use App\Support\StoreContact;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Site settings.
 *
 * The architecture claim under test: the registry is the contract. A key it
 * does not declare cannot be written, every declared key is validated, and a
 * value comes back as the type it was declared as.
 */
class SettingsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
    }

    private function settings(): SettingsService
    {
        // Resolved fresh each time: the service memoises within a request, and
        // a test writing then reading would otherwise see its own stale copy.
        app()->forgetInstance(SettingsService::class);

        return app(SettingsService::class);
    }

    // ------------------------------------------------------------ The service

    public function test_an_unset_setting_returns_its_declared_default(): void
    {
        $this->assertTrue($this->settings()->get('homepage.show_hero'));
        $this->assertFalse($this->settings()->get('seo.noindex'));
    }

    /**
     * Everything is stored as text, so without casting a boolean would come
     * back as the string "0" — which is truthy, and would switch every
     * section back on.
     */
    public function test_a_boolean_survives_the_round_trip_as_a_boolean(): void
    {
        $this->settings()->set('homepage.show_lookbook', false);

        $value = $this->settings()->get('homepage.show_lookbook');

        $this->assertIsBool($value);
        $this->assertFalse($value);
    }

    public function test_an_integer_survives_as_an_integer(): void
    {
        $this->settings()->set('homepage.featured_category_id', '7');

        $this->assertSame(7, $this->settings()->get('homepage.featured_category_id'));
    }

    /**
     * The registry is a whitelist, not a suggestion.
     */
    public function test_an_undeclared_key_is_not_written(): void
    {
        $this->settings()->put(['evil.injected' => 'pwned']);

        $this->assertDatabaseMissing('settings', ['key' => 'evil.injected']);
    }

    public function test_reading_an_undeclared_key_returns_the_fallback(): void
    {
        $this->assertSame('safe', $this->settings()->get('nope.not.a.key', 'safe'));
    }

    public function test_a_translated_setting_follows_the_locale(): void
    {
        $this->settings()->put([
            'about.heading_ar' => 'عن حور',
            'about.heading_en' => 'About HOOR',
        ]);

        app()->setLocale('ar');
        $this->assertSame('عن حور', $this->settings()->translated('about.heading'));

        app()->setLocale('en');
        $this->assertSame('About HOOR', $this->settings()->translated('about.heading'));
    }

    /**
     * A half-translated page should still read rather than showing a blank.
     */
    public function test_a_missing_translation_falls_back_to_the_other_locale(): void
    {
        $this->settings()->put(['about.heading_en' => 'About HOOR', 'about.heading_ar' => null]);

        app()->setLocale('ar');

        $this->assertSame('About HOOR', $this->settings()->translated('about.heading'));
    }

    public function test_the_whole_table_is_read_in_one_query(): void
    {
        $this->settings()->put(['contact.phone' => '01000000000']);

        $settings = $this->settings();

        $count = 0;
        \DB::listen(function () use (&$count): void {
            $count++;
        });

        // Many reads, one query — the footer alone asks for four.
        $settings->get('contact.phone');
        $settings->get('contact.email');
        $settings->get('social.instagram');
        $settings->get('social.facebook');
        $settings->get('homepage.show_hero');

        $this->assertLessThanOrEqual(1, $count);
    }

    public function test_saving_drops_the_cache_so_the_change_is_visible(): void
    {
        $this->settings()->set('contact.phone', '01011111111');
        $this->assertSame('01011111111', $this->settings()->get('contact.phone'));

        $this->settings()->set('contact.phone', '01022222222');
        $this->assertSame('01022222222', $this->settings()->get('contact.phone'));
    }

    // ---------------------------------------------------------------- The form

    public function test_a_customer_cannot_reach_the_settings(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);

        $this->actingAs($customer)
            ->get(route('admin.settings.edit', ['locale' => 'en', 'group' => 'contact']))
            ->assertForbidden();
    }

    public function test_every_panel_renders_in_both_locales(): void
    {
        foreach (['en', 'ar'] as $locale) {
            foreach (app(SettingsRegistry::class)->groups() as $group) {
                $this->actingAs($this->admin)
                    ->get(route('admin.settings.edit', ['locale' => $locale, 'group' => $group]))
                    ->assertOk();
            }
        }
    }

    public function test_an_unknown_group_is_a_404(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.settings.edit', ['locale' => 'en', 'group' => 'nonsense']))
            ->assertNotFound();
    }

    /**
     * Dots are Laravel's nesting syntax, so the keys have to be escaped in the
     * rules — a mistake here would validate the wrong field.
     */
    public function test_settings_save_through_the_form(): void
    {
        $this->actingAs($this->admin)
            ->patch(route('admin.settings.update', ['locale' => 'en', 'group' => 'contact']), [
                'settings' => [
                    'contact.phone' => '01112223334',
                    'contact.email' => 'shop@hoor.eg',
                ],
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertSame('01112223334', $this->settings()->get('contact.phone'));
        $this->assertSame('shop@hoor.eg', $this->settings()->get('contact.email'));
    }

    public function test_an_invalid_url_is_rejected_and_not_stored(): void
    {
        $this->settings()->set('social.instagram', 'https://instagram.com/hoor');

        $this->actingAs($this->admin)
            ->patch(route('admin.settings.update', ['locale' => 'en', 'group' => 'social']), [
                'settings' => ['social.instagram' => 'definitely not a url'],
            ])
            ->assertSessionHasErrors('settings.social.instagram');

        $this->assertSame('https://instagram.com/hoor', $this->settings()->get('social.instagram'));
    }

    /**
     * An unticked checkbox is absent from the post entirely, so without the
     * hidden zero a section could never be switched off.
     */
    public function test_a_section_can_actually_be_switched_off(): void
    {
        $this->actingAs($this->admin)
            ->patch(route('admin.settings.update', ['locale' => 'en', 'group' => 'homepage']), [
                'settings' => ['homepage.show_lookbook' => '0'],
            ])
            ->assertRedirect();

        $this->assertFalse($this->settings()->get('homepage.show_lookbook'));
    }

    // ------------------------------------------------------- Contact helpers

    public function test_a_whatsapp_link_is_built_from_a_plain_number(): void
    {
        // An admin types a phone number, not a wa.me URL.
        foreach ([
            '01012345678'      => 'https://wa.me/201012345678',
            '+20 101 234 5678' => 'https://wa.me/201012345678',
            '٠١٠١٢٣٤٥٦٧٨'      => 'https://wa.me/201012345678',
            '201012345678'     => 'https://wa.me/201012345678',
        ] as $typed => $expected) {
            $this->settings()->set('contact.whatsapp', $typed);

            app()->forgetInstance(StoreContact::class);
            app()->forgetInstance(SettingsService::class);

            $this->assertSame($expected, app(StoreContact::class)->whatsappLink(), "for input: {$typed}");
        }
    }

    public function test_an_empty_social_url_is_dropped_rather_than_rendered(): void
    {
        $this->settings()->put([
            'social.instagram' => 'https://instagram.com/hoor',
            'social.facebook'  => '',
            'social.tiktok'    => '',
        ]);

        app()->forgetInstance(StoreContact::class);
        app()->forgetInstance(SettingsService::class);

        // A footer icon pointing nowhere is worse than an absent icon.
        $this->assertSame(['instagram'], array_keys(app(StoreContact::class)->socials()));
    }

    public function test_the_storefront_shows_the_saved_phone_not_the_config_default(): void
    {
        $this->settings()->set('contact.phone', '01099887766');

        $this->get(route('store.home', ['locale' => 'en']))
            ->assertOk()
            ->assertSee('01099887766');
    }
}
