<?php

declare(strict_types=1);

namespace Tests\Feature\Store;

use App\Models\Faq;
use App\Models\User;
use App\Services\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The contact page.
 *
 * Everything on it is admin-owned: the numbers, the address, the hours, the map
 * link and the questions. The tests check that it reads from those rather than
 * from anything baked into the template.
 */
class ContactPageTest extends TestCase
{
    use RefreshDatabase;

    private function settings(): SettingsService
    {
        app()->forgetInstance(SettingsService::class);

        return app(SettingsService::class);
    }

    private function url(string $locale = 'en'): string
    {
        return route('store.pages.contact', ['locale' => $locale]);
    }

    public function test_the_page_is_public(): void
    {
        $this->get($this->url())->assertOk();
    }

    public function test_it_renders_in_both_locales(): void
    {
        $this->get($this->url('en'))->assertOk()->assertSee('dir="ltr"', escape: false);
        $this->get($this->url('ar'))->assertOk()->assertSee('dir="rtl"', escape: false);
    }

    // ----------------------------------------------------------------- Details

    public function test_the_contact_details_come_from_settings(): void
    {
        $this->settings()->put([
            'contact.phone'      => '01099887766',
            'contact.email'      => 'hello@example.test',
            'contact.address_en' => '99 Test Road, Cairo',
        ]);

        $this->get($this->url())
            ->assertOk()
            ->assertSee('01099887766')
            ->assertSee('hello@example.test')
            ->assertSee('99 Test Road, Cairo');
    }

    /**
     * The admin types a phone number; the wa.me link is composed from it.
     */
    public function test_the_whatsapp_card_links_to_a_composed_url(): void
    {
        $this->settings()->set('contact.whatsapp', '01012345678');

        $this->get($this->url())
            ->assertOk()
            ->assertSee('https://wa.me/201012345678', escape: false);
    }

    /**
     * A card pointing nowhere is worse than one absent.
     */
    public function test_a_card_is_hidden_when_its_detail_is_empty(): void
    {
        $this->settings()->put([
            'contact.email'    => '',
            'social.instagram' => '',
        ]);

        $response = $this->get($this->url())->assertOk();

        $response->assertDontSee(__('store.contact_page.cards.email.title'));
        $response->assertDontSee(__('store.contact_page.cards.follow.title'));

        // The delivery card has no setting behind it, so it always shows.
        $response->assertSee(__('store.contact_page.cards.delivery.title'));
    }

    // --------------------------------------------------------------------- FAQ

    public function test_active_questions_are_listed_in_order(): void
    {
        Faq::factory()->create(['question_en' => 'Second question', 'position' => 2]);
        Faq::factory()->create(['question_en' => 'First question', 'position' => 1]);

        $html = $this->get($this->url())->assertOk()->getContent();

        $this->assertLessThan(
            strpos($html, 'Second question'),
            strpos($html, 'First question'),
        );
    }

    public function test_an_inactive_question_is_not_shown(): void
    {
        Faq::factory()->create(['question_en' => 'Hidden question', 'is_active' => false]);

        $this->get($this->url())->assertOk()->assertDontSee('Hidden question');
    }

    public function test_a_question_from_another_placement_is_not_shown(): void
    {
        Faq::factory()->create(['question_en' => 'Elsewhere', 'placement' => 'help_centre']);

        $this->get($this->url())->assertOk()->assertDontSee('Elsewhere');
    }

    public function test_questions_follow_the_locale(): void
    {
        Faq::factory()->create([
            'question_en' => 'Where is my order?',
            'question_ar' => 'أين طلبي؟',
        ]);

        $this->get($this->url('en'))->assertOk()->assertSee('Where is my order?');
        $this->get($this->url('ar'))->assertOk()->assertSee('أين طلبي؟');
    }

    /**
     * Native <details>, so the accordion opens without JavaScript.
     */
    public function test_the_accordion_works_without_javascript(): void
    {
        Faq::factory()->count(3)->create();

        $html = $this->get($this->url())->assertOk()->getContent();

        $this->assertSame(3, substr_count($html, '<details'));
    }

    // -------------------------------------------------------------------- Map

    /**
     * The map is a link, not an embed: nothing third-party loads on the
     * storefront.
     */
    public function test_the_map_loads_no_external_script_or_frame(): void
    {
        $this->settings()->set('contact.map_url', 'https://maps.google.com/?q=Cairo');

        $html = $this->get($this->url())->assertOk()->getContent();

        $this->assertStringContainsString('https://maps.google.com/?q=Cairo', $html);
        $this->assertStringNotContainsString('<iframe', $html);
        $this->assertStringNotContainsString('maps.googleapis', $html);
    }

    public function test_the_page_renders_without_a_map_link(): void
    {
        $this->settings()->set('contact.map_url', '');

        $this->get($this->url())
            ->assertOk()
            ->assertDontSee(__('store.contact_page.location.directions'));
    }

    // ------------------------------------------------------------------- Form

    public function test_the_form_can_be_switched_off_from_the_admin(): void
    {
        $this->settings()->set('contact_page.show_form', false);

        $this->get($this->url())
            ->assertOk()
            ->assertDontSee(__('store.contact_page.form.title'));
    }

    public function test_a_message_sent_from_the_page_is_stored(): void
    {
        $this->post(route('store.pages.contact.send', ['locale' => 'en']), [
            'name'  => 'Layla',
            'email' => 'layla@example.com',
            'body'  => 'Do you have the wide leg in a 40?',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertDatabaseHas('contact_messages', ['name' => 'Layla']);
    }

    // ------------------------------------------------------------------ Admin

    public function test_staff_can_manage_the_questions(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);

        $this->actingAs($admin)
            ->post(route('admin.faqs.store', ['locale' => 'en']), [
                'question_en' => 'Do you ship abroad?',
                'question_ar' => 'هل تشحنون للخارج؟',
                'answer_en'   => 'Not yet — we deliver within Egypt only.',
                'answer_ar'   => 'ليس بعد — نوصل داخل مصر فقط.',
                'is_active'   => '1',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('faqs', ['question_en' => 'Do you ship abroad?']);

        // And it appears on the page immediately.
        $this->get($this->url())->assertOk()->assertSee('Do you ship abroad?');
    }

    /**
     * A question in only one language would leave the other accordion blank.
     */
    public function test_both_languages_are_required(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);

        $this->actingAs($admin)
            ->post(route('admin.faqs.store', ['locale' => 'en']), [
                'question_en' => 'English only',
                'answer_en'   => 'No Arabic given.',
            ])
            ->assertSessionHasErrors(['question_ar', 'answer_ar']);
    }

    public function test_a_customer_cannot_manage_the_questions(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);

        $this->actingAs($customer)
            ->get(route('admin.faqs.index', ['locale' => 'en']))
            ->assertForbidden();
    }

    public function test_the_admin_screens_render_in_both_locales(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $faq = Faq::factory()->create();

        foreach (['en', 'ar'] as $locale) {
            foreach ([
                route('admin.faqs.index', ['locale' => $locale]),
                route('admin.faqs.create', ['locale' => $locale]),
                route('admin.faqs.edit', ['locale' => $locale, 'faq' => $faq]),
            ] as $url) {
                $this->actingAs($admin)->get($url)->assertOk();
            }
        }
    }
}
