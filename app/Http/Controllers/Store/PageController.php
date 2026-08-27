<?php

declare(strict_types=1);

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Http\Requests\Store\ContactMessageRequest;
use App\Http\Requests\Store\NewsletterRequest;
use App\Models\ContactMessage;
use App\Models\Faq;
use App\Models\NewsletterSubscriber;
use App\Services\SettingsService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

/**
 * The static pages the admin writes: About Us and Contact.
 *
 * Content comes from settings rather than being written into the templates, so
 * the shop can reword its own story without a deployment.
 */
class PageController extends Controller
{
    /**
     * Contact submissions per hour, per IP.
     *
     * A public form with no captcha is a spam target; the limit is generous
     * enough that a customer who writes twice is unaffected.
     */
    private const MAX_MESSAGES = 5;

    public function __construct(private readonly SettingsService $settings)
    {
    }

    public function about(): View
    {
        return view('store.pages.about', [
            'heading' => $this->settings->translated('about.heading', __('store.pages.about')),
            'intro'   => $this->settings->translated('about.intro'),
            'body'    => $this->settings->translated('about.body'),
            'values'  => $this->settings->translated('about.values'),
            'image'   => $this->settings->get('about.image_path'),
        ]);
    }

    public function contact(): View
    {
        return view('store.pages.contact', [
            'heading'  => $this->settings->translated('contact_page.heading', __('store.pages.contact')),
            'intro'    => $this->settings->translated('contact_page.intro'),
            'showForm' => $this->settings->boolean('contact_page.show_form', true),

            // Admin-managed, so a question can be added the day customers
            // start asking it.
            'faqs' => Faq::query()->placement('contact')->active()->ordered()->get(),
        ]);
    }

    /**
     * Receive a message.
     *
     * Stored rather than emailed: an inbox nobody has configured SMTP for is a
     * contact form that silently fails, and a message that only ever existed in
     * an email cannot be searched or counted later.
     */
    public function storeMessage(ContactMessageRequest $request): RedirectResponse
    {
        $key = 'contact:'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, self::MAX_MESSAGES)) {
            return back()
                ->withInput()
                ->withErrors(['body' => __('store.contact.throttled', [
                    'minutes' => (int) ceil(RateLimiter::availableIn($key) / 60),
                ])]);
        }

        RateLimiter::hit($key, 3600);

        ContactMessage::create($request->messageData() + [
            'user_id' => $request->user()?->id,
        ]);

        return back()->with('status', __('store.contact.sent'));
    }

    /**
     * Join the newsletter.
     *
     * Signing up again after unsubscribing resubscribes rather than erroring:
     * the customer's intent is plain, and a unique-email failure would read as
     * a bug to her.
     */
    public function subscribe(NewsletterRequest $request): RedirectResponse
    {
        if (! $this->settings->boolean('newsletter.enabled', true)) {
            return back();
        }

        $subscriber = NewsletterSubscriber::query()->firstOrNew([
            'email' => $request->email(),
        ]);

        $subscriber->fill([
            'locale'          => app()->getLocale(),
            'unsubscribed_at' => null,
        ])->save();

        return back()->with('status', __('store.newsletter_page.subscribed'));
    }
}
