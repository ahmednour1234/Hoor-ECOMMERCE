<?php

declare(strict_types=1);

namespace App\Settings;

use Illuminate\Support\Collection;

/**
 * Every setting the site has, declared in one place.
 *
 * This list is the contract. A key absent from here cannot be written, which
 * means a crafted form post cannot invent settings, and a typo in a Blade
 * template fails loudly at development time rather than silently reading null
 * in production.
 *
 * Defaults come from config/hoor.php where one already exists, so the shop
 * behaves identically before an admin has saved anything.
 */
class SettingsRegistry
{
    /** @var array<string, SettingDefinition>|null */
    private ?array $definitions = null;

    /**
     * @return array<string, SettingDefinition>
     */
    public function all(): array
    {
        return $this->definitions ??= $this->build();
    }

    public function get(string $key): ?SettingDefinition
    {
        return $this->all()[$key] ?? null;
    }

    public function has(string $key): bool
    {
        return isset($this->all()[$key]);
    }

    /**
     * Definitions for one admin panel.
     *
     * @return Collection<string, SettingDefinition>
     */
    public function group(string $group): Collection
    {
        return collect($this->all())->filter(
            fn (SettingDefinition $definition): bool => $definition->group === $group,
        );
    }

    /**
     * The panels, in the order the admin form shows them.
     *
     * @return list<string>
     */
    public function groups(): array
    {
        return ['contact', 'social', 'homepage', 'about', 'contact_page', 'newsletter', 'seo'];
    }

    /**
     * Defaults for every setting, for a first read before anything is saved.
     *
     * @return array<string, mixed>
     */
    public function defaults(): array
    {
        return collect($this->all())
            ->map(fn (SettingDefinition $definition): mixed => $definition->default)
            ->all();
    }

    /**
     * @return array<string, SettingDefinition>
     */
    private function build(): array
    {
        $definitions = [];

        foreach ([
            ...$this->contact(),
            ...$this->social(),
            ...$this->homepage(),
            ...$this->pages(),
            ...$this->newsletter(),
            ...$this->seo(),
        ] as $definition) {
            $definitions[$definition->key] = $definition;
        }

        return $definitions;
    }

    /**
     * How customers reach the shop.
     *
     * @return list<SettingDefinition>
     */
    private function contact(): array
    {
        return [
            new SettingDefinition('contact.phone', 'phone', 'contact', config('hoor.brand.phone')),

            /*
             * Stored as a bare number, not a wa.me URL.
             *
             * The link is composed at render time, so an admin pasting a phone
             * number — which is what they will do — works, and the URL format
             * can change without every stored value being wrong.
             */
            new SettingDefinition('contact.whatsapp', 'phone', 'contact', config('hoor.brand.whatsapp')),

            new SettingDefinition('contact.email', 'email', 'contact', config('hoor.brand.email')),
            new SettingDefinition('contact.address_ar', 'string', 'contact', null, translatable: true),
            new SettingDefinition('contact.address_en', 'string', 'contact', null, translatable: true),
            new SettingDefinition('contact.hours_ar', 'string', 'contact', null, translatable: true),
            new SettingDefinition('contact.hours_en', 'string', 'contact', null, translatable: true),

            // A second line of opening hours, for shops whose weekend differs.
            new SettingDefinition('contact.hours_alt_ar', 'string', 'contact', null, translatable: true),
            new SettingDefinition('contact.hours_alt_en', 'string', 'contact', null, translatable: true),

            /*
             * Where the map pin points.
             *
             * A plain Google Maps URL rather than coordinates and an API key:
             * the storefront opens it in a new tab instead of loading a
             * third-party script on every visit, and an admin can paste the
             * link straight from the browser.
             */
            new SettingDefinition('contact.map_url', 'url', 'contact'),

            // How long the shop takes to reply, shown under the form.
            new SettingDefinition('contact.response_ar', 'string', 'contact', null, translatable: true),
            new SettingDefinition('contact.response_en', 'string', 'contact', null, translatable: true),
        ];
    }

    /**
     * @return list<SettingDefinition>
     */
    private function social(): array
    {
        return [
            new SettingDefinition('social.instagram', 'url', 'social', config('hoor.brand.social.instagram')),
            new SettingDefinition('social.facebook', 'url', 'social', config('hoor.brand.social.facebook')),
            new SettingDefinition('social.tiktok', 'url', 'social', config('hoor.brand.social.tiktok')),
        ];
    }

    /**
     * Which homepage sections show, and what the featured rail contains.
     *
     * @return list<SettingDefinition>
     */
    private function homepage(): array
    {
        $sections = [
            'hero', 'categories', 'new_arrivals', 'promo_banner',
            'featured', 'lookbook', 'benefits', 'why_hoor', 'newsletter',
        ];

        $definitions = [];

        foreach ($sections as $section) {
            $definitions[] = new SettingDefinition(
                'homepage.show_'.$section,
                'boolean',
                'homepage',
                default: true,
            );
        }

        // Which category the featured rail draws from; empty means the
        // repository's own choice.
        $definitions[] = new SettingDefinition(
            'homepage.featured_category_id',
            'integer',
            'homepage',
            rules: ['exists:categories,id'],
        );

        $definitions[] = new SettingDefinition('homepage.featured_title_ar', 'string', 'homepage', translatable: true);
        $definitions[] = new SettingDefinition('homepage.featured_title_en', 'string', 'homepage', translatable: true);

        return $definitions;
    }

    /**
     * About Us and the Contact page.
     *
     * Structured fields rather than a free HTML body: nothing here accepts
     * markup, so there is no admin-submitted HTML to sanitise and no way for a
     * bad paste to break the layout.
     *
     * @return list<SettingDefinition>
     */
    private function pages(): array
    {
        $definitions = [];

        foreach (['ar', 'en'] as $locale) {
            $definitions[] = new SettingDefinition("about.heading_{$locale}", 'string', 'about', translatable: true);
            $definitions[] = new SettingDefinition("about.intro_{$locale}", 'text', 'about', translatable: true);
            $definitions[] = new SettingDefinition("about.body_{$locale}", 'text', 'about', translatable: true);
            $definitions[] = new SettingDefinition("about.values_{$locale}", 'text', 'about', translatable: true);

            $definitions[] = new SettingDefinition("contact_page.heading_{$locale}", 'string', 'contact_page', translatable: true);
            $definitions[] = new SettingDefinition("contact_page.intro_{$locale}", 'text', 'contact_page', translatable: true);
        }

        $definitions[] = new SettingDefinition('about.image_path', 'string', 'about');
        $definitions[] = new SettingDefinition('contact_page.show_form', 'boolean', 'contact_page', default: true);

        return $definitions;
    }

    /**
     * @return list<SettingDefinition>
     */
    private function newsletter(): array
    {
        return [
            new SettingDefinition('newsletter.enabled', 'boolean', 'newsletter', default: true),
            new SettingDefinition('newsletter.heading_ar', 'string', 'newsletter', translatable: true),
            new SettingDefinition('newsletter.heading_en', 'string', 'newsletter', translatable: true),
            new SettingDefinition('newsletter.body_ar', 'text', 'newsletter', translatable: true),
            new SettingDefinition('newsletter.body_en', 'text', 'newsletter', translatable: true),
        ];
    }

    /**
     * Site-wide SEO defaults.
     *
     * Per-product SEO already lives on the product; these are the fallbacks for
     * everything without its own.
     *
     * @return list<SettingDefinition>
     */
    private function seo(): array
    {
        $definitions = [];

        foreach (['ar', 'en'] as $locale) {
            $definitions[] = new SettingDefinition("seo.title_{$locale}", 'string', 'seo', translatable: true);
            $definitions[] = new SettingDefinition("seo.description_{$locale}", 'text', 'seo', rules: ['max:320'], translatable: true);
        }

        $definitions[] = new SettingDefinition('seo.og_image_path', 'string', 'seo');
        $definitions[] = new SettingDefinition('seo.keywords', 'string', 'seo');

        // Kept explicit so a staging site can be de-indexed without an env
        // change and a redeploy.
        $definitions[] = new SettingDefinition('seo.noindex', 'boolean', 'seo', default: false);

        return $definitions;
    }
}
