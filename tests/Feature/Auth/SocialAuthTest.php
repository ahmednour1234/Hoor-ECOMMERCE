<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Exceptions\SocialAuthException;
use App\Models\SocialAccount;
use App\Models\User;
use App\Services\SocialAuthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Two\User as SocialiteUser;
use Tests\TestCase;

/**
 * Signing in with Google.
 *
 * The rule doing the most work here: an existing account is only linked when
 * the provider says it has verified the email itself. Without that, anyone who
 * can create a Google account naming a customer's address inherits her HOOR
 * account — orders, addresses and all.
 */
class SocialAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // The button and the routes are both gated on configuration.
        config([
            'services.google.client_id'     => 'test-client-id',
            'services.google.client_secret' => 'test-secret',
        ]);
    }

    private function social(): SocialAuthService
    {
        return app(SocialAuthService::class);
    }

    /**
     * A profile as Socialite hands it over.
     */
    private function profile(
        string $id = 'google-123',
        string $email = 'layla@example.com',
        string $name = 'Layla Hassan',
        bool $verified = true,
    ): SocialiteUser {
        $user = new SocialiteUser();

        $user->map([
            'id'     => $id,
            'name'   => $name,
            'email'  => $email,
            'avatar' => 'https://example.test/avatar.jpg',
        ]);

        // Google reports verification in the raw payload; the service reads it
        // from there rather than trusting the email alone.
        $user->setRaw(['email_verified' => $verified]);

        return $user;
    }

    // ------------------------------------------------------------ New customer

    public function test_a_new_customer_gets_an_account(): void
    {
        $user = $this->social()->resolve('google', $this->profile());

        $this->assertSame('layla@example.com', $user->email);
        $this->assertSame('Layla Hassan', $user->name);
        $this->assertTrue($user->is_active);

        $this->assertDatabaseHas('social_accounts', [
            'user_id'     => $user->id,
            'provider'    => 'google',
            'provider_id' => 'google-123',
        ]);
    }

    /**
     * A random password would look like a credential she could reset into, and
     * every "does this account have a password" check would be wrong about her.
     */
    public function test_a_google_only_account_has_no_password(): void
    {
        $user = $this->social()->resolve('google', $this->profile());

        $this->assertNull($user->password);
        $this->assertFalse($user->hasPassword());
    }

    /**
     * Google has already proved she owns the address, which is the same
     * assurance our own verification email buys.
     */
    public function test_a_verified_email_is_not_re_verified(): void
    {
        $user = $this->social()->resolve('google', $this->profile(verified: true));

        $this->assertNotNull($user->email_verified_at);
    }

    public function test_an_unverified_new_email_still_needs_verifying(): void
    {
        $user = $this->social()->resolve('google', $this->profile(verified: false));

        $this->assertNull($user->email_verified_at);
    }

    public function test_a_profile_without_an_email_is_refused(): void
    {
        $this->expectException(SocialAuthException::class);

        $this->social()->resolve('google', $this->profile(email: ''));
    }

    public function test_a_missing_name_falls_back_to_the_address(): void
    {
        $user = $this->social()->resolve('google', $this->profile(name: '', email: 'nadia@example.com'));

        $this->assertSame('nadia', $user->name);
    }

    // --------------------------------------------------------------- Returning

    public function test_a_returning_customer_signs_into_the_same_account(): void
    {
        $first = $this->social()->resolve('google', $this->profile());
        $second = $this->social()->resolve('google', $this->profile());

        $this->assertTrue($first->is($second));
        $this->assertSame(1, User::query()->count());
        $this->assertSame(1, SocialAccount::query()->count());
    }

    /**
     * People change their email address; the provider's id is what identifies
     * the account at the far end.
     */
    public function test_the_link_survives_the_customer_changing_her_google_email(): void
    {
        $user = $this->social()->resolve('google', $this->profile(email: 'old@example.com'));

        $again = $this->social()->resolve('google', $this->profile(email: 'new@example.com'));

        $this->assertTrue($user->is($again));
        $this->assertSame(1, User::query()->count());
    }

    // ----------------------------------------------------------------- Linking

    public function test_an_existing_account_is_linked_when_google_verified_the_email(): void
    {
        $existing = User::factory()->create([
            'email'    => 'layla@example.com',
            'password' => Hash::make('OldPassword!1'),
        ]);

        $user = $this->social()->resolve('google', $this->profile(verified: true));

        $this->assertTrue($existing->is($user));
        $this->assertSame(1, User::query()->count());

        // Her password still works: linking adds a way in, it does not replace
        // the one she had.
        $this->assertTrue(Hash::check('OldPassword!1', $user->fresh()->password));
    }

    /**
     * The rule that stops an account takeover.
     */
    public function test_an_existing_account_is_not_linked_on_an_unverified_email(): void
    {
        User::factory()->create(['email' => 'layla@example.com']);

        try {
            $this->social()->resolve('google', $this->profile(verified: false));
            $this->fail('An unverified email should not link to an existing account.');
        } catch (SocialAuthException $e) {
            $this->assertSame('email_not_verified', $e->reason);
        }

        // And no second account was quietly created either.
        $this->assertSame(1, User::query()->count());
        $this->assertSame(0, SocialAccount::query()->count());
    }

    // ------------------------------------------------------------------- HTTP

    public function test_the_button_appears_once_google_is_configured(): void
    {
        $this->get(route('login', ['locale' => 'en']))
            ->assertOk()
            ->assertSee(__('auth.social.google'));
    }

    public function test_the_button_is_hidden_when_google_is_not_configured(): void
    {
        config(['services.google.client_id' => null, 'services.google.client_secret' => null]);

        $this->get(route('login', ['locale' => 'en']))
            ->assertOk()
            ->assertDontSee(__('auth.social.google'));
    }

    public function test_an_unconfigured_provider_is_not_routable(): void
    {
        config(['services.google.client_id' => null, 'services.google.client_secret' => null]);

        $this->get(route('social.redirect', ['locale' => 'en', 'provider' => 'google']))
            ->assertNotFound();
    }

    public function test_an_unknown_provider_is_not_routable(): void
    {
        $this->get(url('/en/auth/facebook'))->assertNotFound();
    }

    public function test_the_redirect_sends_the_customer_to_google(): void
    {
        $response = $this->get(route('social.redirect', ['locale' => 'en', 'provider' => 'google']));

        $response->assertRedirectContains('accounts.google.com');
    }

    /**
     * A redirect parameter pointing off-site would be an open redirect: a link
     * that starts here and finishes on someone else's domain.
     */
    public function test_an_off_site_return_url_is_ignored(): void
    {
        $this->get(route('social.redirect', ['locale' => 'en', 'provider' => 'google']).'?redirect=https://evil.test/steal');

        $this->assertNull(session('social.intended'));
    }

    public function test_the_screens_render_in_both_locales(): void
    {
        foreach (['en', 'ar'] as $locale) {
            $this->get(route('login', ['locale' => $locale]))->assertOk();
            $this->get(route('register', ['locale' => $locale]))->assertOk();
        }
    }
}
