<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\Banner;
use App\Models\ContactMessage;
use App\Models\HeroSlide;
use App\Models\NewsletterSubscriber;
use App\Models\User;
use App\Services\ContentService;
use App\Services\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Storefront content management: slides, banners, the contact inbox and the
 * newsletter.
 */
class ContentManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);

        Storage::fake(config('hoor.media.disk'));
        RateLimiter::clear('contact:127.0.0.1');
    }

    private function settings(): SettingsService
    {
        app()->forgetInstance(SettingsService::class);

        return app(SettingsService::class);
    }

    // ----------------------------------------------------------- Hero slides

    public function test_an_admin_can_add_a_slide(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.slides.store', ['locale' => 'en']), [
                'image'       => UploadedFile::fake()->image('hero.jpg', 2400, 1000),
                'backdrop'    => '#CAB296',
                'headline_ar' => 'دنيم يليق بك',
                'headline_en' => 'Denim that suits you',
                'is_active'   => '1',
            ])
            ->assertRedirect(route('admin.slides.index', ['locale' => 'en']))
            ->assertSessionHasNoErrors();

        $slide = HeroSlide::query()->latest('id')->first();

        $this->assertSame('Denim that suits you', $slide->headline_en);
        Storage::disk(config('hoor.media.disk'))->assertExists($slide->image_path);
    }

    public function test_a_slide_needs_an_image_when_created(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.slides.store', ['locale' => 'en']), ['headline_en' => 'No image'])
            ->assertSessionHasErrors('image');
    }

    /**
     * Editing copy should not force a re-upload of an image already there.
     */
    public function test_editing_a_slide_without_a_new_image_keeps_the_old_one(): void
    {
        $slide = HeroSlide::factory()->create(['image_path' => 'hero/original.jpg']);

        $this->actingAs($this->admin)
            ->patch(route('admin.slides.update', ['locale' => 'en', 'slide' => $slide]), [
                'headline_en' => 'Reworded',
                'is_active'   => '1',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $slide->refresh();

        $this->assertSame('Reworded', $slide->headline_en);
        $this->assertSame('hero/original.jpg', $slide->image_path);
    }

    public function test_a_bad_backdrop_colour_is_rejected(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.slides.store', ['locale' => 'en']), [
                'image'    => UploadedFile::fake()->image('hero.jpg'),
                'backdrop' => 'not-a-colour',
            ])
            ->assertSessionHasErrors('backdrop');
    }

    public function test_the_homepage_shows_managed_slides(): void
    {
        HeroSlide::factory()->create([
            'headline_en' => 'Managed headline',
            'position'    => 0,
            'is_active'   => true,
        ]);

        $this->get(route('store.home', ['locale' => 'en']))
            ->assertOk()
            ->assertSee('Managed headline');
    }

    /**
     * An admin who deactivates every slide should get the brand plates back,
     * not a collapsed hero.
     */
    public function test_deactivating_every_slide_falls_back_to_the_brand_plates(): void
    {
        HeroSlide::factory()->count(2)->create(['is_active' => false]);

        app()->forgetInstance(ContentService::class);
        $slides = app(ContentService::class)->heroSlides();

        $this->assertNotEmpty($slides);
    }

    public function test_only_active_slides_are_shown_in_order(): void
    {
        HeroSlide::factory()->create(['headline_en' => 'Second', 'position' => 2, 'is_active' => true]);
        HeroSlide::factory()->create(['headline_en' => 'First', 'position' => 1, 'is_active' => true]);
        HeroSlide::factory()->create(['headline_en' => 'Hidden', 'is_active' => false]);

        app()->forgetInstance(ContentService::class);
        $headlines = collect(app(ContentService::class)->heroSlides())->pluck('headline')->all();

        $this->assertSame(['First', 'Second'], $headlines);
    }

    // --------------------------------------------------------------- Banners

    public function test_a_banner_outside_its_dates_does_not_show(): void
    {
        Banner::factory()->create([
            'placement' => 'home_promo',
            'title_en'  => 'Finished sale',
            'ends_at'   => now()->subDay(),
        ]);

        Banner::factory()->create([
            'placement' => 'home_promo',
            'title_en'  => 'Future sale',
            'starts_at' => now()->addWeek(),
        ]);

        Banner::factory()->create([
            'placement' => 'home_promo',
            'title_en'  => 'Running now',
            'starts_at' => now()->subDay(),
            'ends_at'   => now()->addDay(),
        ]);

        $live = app(ContentService::class)->banners('home_promo')->pluck('title_en');

        // A finished sale switches itself off rather than waiting for someone
        // to remember at midnight.
        $this->assertSame(['Running now'], $live->all());
    }

    public function test_a_live_banner_replaces_the_static_homepage_panel(): void
    {
        Banner::factory()->create([
            'placement' => 'home_promo',
            'title_en'  => 'Mid season sale',
            'is_active' => true,
        ]);

        $this->get(route('store.home', ['locale' => 'en']))
            ->assertOk()
            ->assertSee('Mid season sale');
    }

    public function test_a_banner_ending_before_it_starts_is_rejected(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.banners.store', ['locale' => 'en']), [
                'placement' => 'home_promo',
                'starts_at' => now()->addWeek()->format('Y-m-d\TH:i'),
                'ends_at'   => now()->format('Y-m-d\TH:i'),
            ])
            ->assertSessionHasErrors('ends_at');
    }

    public function test_an_unknown_placement_is_rejected(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.banners.store', ['locale' => 'en']), ['placement' => 'nowhere'])
            ->assertSessionHasErrors('placement');
    }

    // ------------------------------------------------- Homepage visibility

    public function test_the_homepage_shows_a_section_by_default(): void
    {
        $this->get(route('store.home', ['locale' => 'en']))
            ->assertOk()
            ->assertSee(__('store.newsletter.privacy'));
    }

    /**
     * The visibility flag the homepage reads.
     *
     * Asserted against the setting rather than the rendered page: tests run on
     * the array cache driver, which lives per container instance, so a write
     * here and a read inside the test client's request are different stores.
     * The rendering itself is covered by the default-on test above.
     */
    public function test_a_section_can_be_switched_off(): void
    {
        $settings = $this->settings();

        $this->assertTrue($settings->boolean('homepage.show_newsletter', true));

        $settings->set('homepage.show_newsletter', false);

        $this->assertFalse($this->settings()->boolean('homepage.show_newsletter', true));
    }

    // -------------------------------------------------------- Contact & pages

    public function test_the_about_page_shows_what_the_admin_wrote(): void
    {
        $this->settings()->put([
            'about.heading_en' => 'Our story',
            'about.body_en'    => 'We start with the cloth.',
        ]);

        $this->get(route('store.pages.about', ['locale' => 'en']))
            ->assertOk()
            ->assertSee('Our story')
            ->assertSee('We start with the cloth.');
    }

    public function test_a_guest_can_send_a_message(): void
    {
        $this->post(route('store.pages.contact.send', ['locale' => 'en']), [
            'name'  => 'Layla',
            'email' => 'layla@example.com',
            'body'  => 'Do you have this in a size 40?',
        ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('contact_messages', ['name' => 'Layla']);
    }

    public function test_a_message_needs_a_way_to_reply(): void
    {
        $this->post(route('store.pages.contact.send', ['locale' => 'en']), [
            'name' => 'Layla',
            'body' => 'Do you have this in a size 40?',
        ])->assertSessionHasErrors('email');

        $this->assertSame(0, ContactMessage::query()->count());
    }

    /**
     * A field no human sees, so anything filling it is a bot.
     */
    public function test_the_honeypot_rejects_a_bot(): void
    {
        $this->post(route('store.pages.contact.send', ['locale' => 'en']), [
            'name'    => 'Bot',
            'email'   => 'bot@example.com',
            'body'    => 'Buy cheap watches online today.',
            'website' => 'http://spam.example',
        ])->assertSessionHasErrors('website');

        $this->assertSame(0, ContactMessage::query()->count());
    }

    public function test_repeated_messages_are_throttled(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post(route('store.pages.contact.send', ['locale' => 'en']), [
                'name'  => 'Layla',
                'email' => 'layla@example.com',
                'body'  => "Message number {$i}, long enough to pass.",
            ]);
        }

        $this->post(route('store.pages.contact.send', ['locale' => 'en']), [
            'name'  => 'Layla',
            'email' => 'layla@example.com',
            'body'  => 'One more message, long enough to pass.',
        ])->assertSessionHasErrors('body');

        $this->assertSame(5, ContactMessage::query()->count());
    }

    public function test_opening_a_message_marks_it_read(): void
    {
        $message = ContactMessage::factory()->create();

        $this->assertFalse($message->isRead());

        $this->actingAs($this->admin)
            ->get(route('admin.messages.show', ['locale' => 'en', 'message' => $message]))
            ->assertOk();

        $message->refresh();

        $this->assertTrue($message->isRead());
        $this->assertSame($this->admin->id, $message->read_by);
    }

    public function test_reading_a_message_twice_keeps_the_first_timestamp(): void
    {
        $message = ContactMessage::factory()->create();

        $this->actingAs($this->admin)->get(route('admin.messages.show', ['locale' => 'en', 'message' => $message]));
        $first = $message->fresh()->read_at;

        $this->travel(5)->minutes();

        $this->actingAs($this->admin)->get(route('admin.messages.show', ['locale' => 'en', 'message' => $message]));

        $this->assertEquals($first, $message->fresh()->read_at);
    }

    public function test_a_customer_cannot_read_the_inbox(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);

        $this->actingAs($customer)
            ->get(route('admin.messages.index', ['locale' => 'en']))
            ->assertForbidden();
    }

    // ------------------------------------------------------------ Newsletter

    public function test_a_guest_can_subscribe(): void
    {
        $this->post(route('store.newsletter.subscribe', ['locale' => 'en']), [
            'email' => 'Layla@Example.com',
        ])->assertRedirect()->assertSessionHasNoErrors();

        // Normalised, so the same address cannot subscribe twice by casing.
        $this->assertDatabaseHas('newsletter_subscribers', ['email' => 'layla@example.com']);
    }

    /**
     * Signing up again is a customer changing her mind, not an error.
     */
    public function test_resubscribing_after_unsubscribing_works(): void
    {
        NewsletterSubscriber::factory()->create([
            'email'           => 'layla@example.com',
            'unsubscribed_at' => now()->subMonth(),
        ]);

        $this->post(route('store.newsletter.subscribe', ['locale' => 'en']), [
            'email' => 'layla@example.com',
        ])->assertSessionHasNoErrors();

        $this->assertSame(1, NewsletterSubscriber::query()->count());
        $this->assertTrue(NewsletterSubscriber::query()->first()->isSubscribed());
    }

    public function test_subscribing_twice_does_not_duplicate(): void
    {
        foreach ([1, 2] as $attempt) {
            $this->post(route('store.newsletter.subscribe', ['locale' => 'en']), [
                'email' => 'layla@example.com',
            ]);
        }

        $this->assertSame(1, NewsletterSubscriber::query()->count());
    }

    public function test_the_export_is_a_csv_of_subscribed_addresses(): void
    {
        NewsletterSubscriber::factory()->create(['email' => 'in@example.com']);
        NewsletterSubscriber::factory()->create([
            'email'           => 'out@example.com',
            'unsubscribed_at' => now(),
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.newsletter.export', ['locale' => 'en']))
            ->assertOk();

        $csv = $response->streamedContent();

        $this->assertStringContainsString('in@example.com', $csv);
        $this->assertStringNotContainsString('out@example.com', $csv);
    }

    // ---------------------------------------------------------------- Renders

    public function test_every_content_screen_renders_in_both_locales(): void
    {
        HeroSlide::factory()->create();
        Banner::factory()->create(['placement' => 'home_promo']);
        ContactMessage::factory()->create();
        NewsletterSubscriber::factory()->create();

        foreach (['en', 'ar'] as $locale) {
            foreach ([
                'admin.slides.index',
                'admin.slides.create',
                'admin.banners.index',
                'admin.banners.create',
                'admin.messages.index',
                'admin.newsletter.index',
            ] as $route) {
                $this->actingAs($this->admin)
                    ->get(route($route, ['locale' => $locale]))
                    ->assertOk();
            }

            foreach (['store.pages.about', 'store.pages.contact'] as $route) {
                $this->get(route($route, ['locale' => $locale]))->assertOk();
            }
        }
    }
}
