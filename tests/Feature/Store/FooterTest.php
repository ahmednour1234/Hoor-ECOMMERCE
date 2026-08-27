<?php

declare(strict_types=1);

namespace Tests\Feature\Store;

use App\Services\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The storefront footer.
 *
 * The recurring fault across this build has been links that resolve but lead
 * nowhere useful — three entries in the Shop column all pointed at the same
 * unfiltered page. These tests check destinations differ, not just that they
 * exist.
 */
class FooterTest extends TestCase
{
    use RefreshDatabase;

    private function homepage(string $locale = 'en'): string
    {
        return $this->get(route('store.home', ['locale' => $locale]))
            ->assertOk()
            ->getContent();
    }

    /**
     * Shop, New in and Jeans must be three destinations, not one.
     */
    public function test_the_shop_column_links_somewhere_different_each_time(): void
    {
        $html = $this->homepage();

        $this->assertStringContainsString('shop?new=1', $html);
        $this->assertStringContainsString('shop?category=jeans', $html);
    }

    public function test_no_translation_key_leaks_into_the_markup(): void
    {
        $html = $this->homepage();

        // A missing key renders as its own name, which is how nav.returns
        // would have shipped.
        $this->assertStringNotContainsString('nav.', $html);
        $this->assertStringNotContainsString('store.footer.', $html);
    }

    public function test_the_footer_links_render_in_both_locales(): void
    {
        $this->assertStringContainsString('Returns', $this->homepage('en'));
        $this->assertStringContainsString('المرتجعات', $this->homepage('ar'));
    }

    /**
     * Contact details are admin-owned, never hardcoded.
     */
    public function test_the_contact_strip_reads_from_settings(): void
    {
        app(SettingsService::class)->put([
            'contact.email' => 'shop@example.test',
            'contact.phone' => '01055554444',
        ]);

        $html = $this->homepage();

        $this->assertStringContainsString('shop@example.test', $html);
        $this->assertStringContainsString('01055554444', $html);
    }

    /**
     * A network with no URL saved should not render a dead pill.
     */
    public function test_an_empty_social_network_is_not_shown(): void
    {
        app(SettingsService::class)->put([
            'social.instagram' => '',
            'social.facebook'  => '',
            'social.tiktok'    => '',
        ]);

        $html = $this->homepage();

        $this->assertStringNotContainsString('Instagram', $html);
    }
}
