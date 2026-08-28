<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Mail\PasswordResetCode;
use App\Models\User;
use App\Services\PasswordResetCodeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Resetting a password with a code sent by email.
 *
 * The security properties are the point of most of these: the code is a
 * temporary credential, so it must be hashed at rest, guessable only a handful
 * of times, single-use, and unable to tell an attacker which email addresses
 * have accounts.
 */
class PasswordResetCodeTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();

        $this->user = User::factory()->create([
            'email'    => 'layla@example.com',
            'password' => Hash::make('OldPassword!1'),
        ]);
    }

    private function codes(): PasswordResetCodeService
    {
        return app(PasswordResetCodeService::class);
    }

    private function request(string $email = 'layla@example.com'): \Illuminate\Testing\TestResponse
    {
        return $this->post(route('password.email', ['locale' => 'en']), ['email' => $email]);
    }

    // ----------------------------------------------------------- Asking for one

    public function test_the_forgot_password_page_is_public(): void
    {
        $this->get(route('password.request', ['locale' => 'en']))->assertOk();
    }

    public function test_a_code_is_emailed_to_a_registered_address(): void
    {
        $this->request()->assertRedirect(route('password.code', ['locale' => 'en']));

        Mail::assertQueued(
            PasswordResetCode::class,
            fn (PasswordResetCode $mail): bool => $mail->hasTo('layla@example.com'),
        );
    }

    /**
     * A form that says "no account with that address" is a way to test who
     * shops here, so the answer must not depend on whether the account exists.
     */
    public function test_an_unknown_address_gets_the_same_answer(): void
    {
        $known = $this->request('layla@example.com');
        $unknown = $this->request('nobody@example.com');

        $this->assertSame($known->getStatusCode(), $unknown->getStatusCode());
        $this->assertSame(
            $known->getSession()->get('status'),
            $unknown->getSession()->get('status'),
        );

        // But only the real one is actually mailed.
        Mail::assertQueued(PasswordResetCode::class, 1);
    }

    public function test_asking_again_immediately_is_throttled(): void
    {
        $this->request();
        $this->request()->assertSessionHasErrors('email');

        // Still only the first code went out.
        Mail::assertQueued(PasswordResetCode::class, 1);
    }

    // ------------------------------------------------------------- The code

    /**
     * A leaked database backup must not hand over live codes.
     */
    public function test_the_code_is_stored_hashed(): void
    {
        $code = $this->codes()->issue('layla@example.com');

        $stored = DB::table('password_reset_tokens')->where('email', 'layla@example.com')->value('token');

        $this->assertNotSame($code, $stored);
        $this->assertTrue(Hash::check($code, $stored));
    }

    public function test_a_six_digit_code_is_issued(): void
    {
        $this->assertMatchesRegularExpression('/^\d{6}$/', $this->codes()->issue('layla@example.com'));
    }

    public function test_the_right_code_verifies_and_a_wrong_one_does_not(): void
    {
        $code = $this->codes()->issue('layla@example.com');

        $this->assertFalse($this->codes()->verify('layla@example.com', '000000'));
        $this->assertTrue($this->codes()->verify('layla@example.com', $code));
    }

    /**
     * Egyptian keyboards produce ٠١٢, and a customer reading the code off her
     * screen may well type it back that way.
     */
    public function test_a_code_typed_in_arabic_digits_is_accepted(): void
    {
        $code = $this->codes()->issue('layla@example.com');

        $arabic = str_replace(
            str_split('0123456789'),
            ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'],
            $code,
        );

        $this->assertTrue($this->codes()->verify('layla@example.com', $arabic));
    }

    /**
     * Six digits is a million combinations, which a script can walk. Rate
     * limiting by IP is not enough — an attacker can rotate addresses — so the
     * count is kept against the code itself.
     */
    public function test_a_code_is_destroyed_after_too_many_wrong_guesses(): void
    {
        $code = $this->codes()->issue('layla@example.com');

        for ($i = 0; $i < PasswordResetCodeService::MAX_ATTEMPTS; $i++) {
            $this->codes()->verify('layla@example.com', '000000');
        }

        // Even the correct code no longer works.
        $this->assertFalse($this->codes()->verify('layla@example.com', $code));
    }

    public function test_a_code_expires(): void
    {
        $code = $this->codes()->issue('layla@example.com');

        $this->travel($this->codes()->lifetimeMinutes() + 1)->minutes();

        $this->assertFalse($this->codes()->verify('layla@example.com', $code));
    }

    public function test_a_new_code_replaces_the_old_one(): void
    {
        $first = $this->codes()->issue('layla@example.com');
        $second = $this->codes()->issue('layla@example.com');

        $this->assertFalse($this->codes()->verify('layla@example.com', $first));
        $this->assertTrue($this->codes()->verify('layla@example.com', $second));
    }

    public function test_a_code_cannot_be_used_twice(): void
    {
        $code = $this->codes()->issue('layla@example.com');

        $this->assertTrue($this->codes()->consume('layla@example.com', $code));
        $this->assertFalse($this->codes()->consume('layla@example.com', $code));
    }

    // -------------------------------------------------------- The whole flow

    public function test_a_customer_can_reset_her_password_with_a_code(): void
    {
        $this->request();

        $code = $this->issuedCode();

        $this->post(route('password.code.verify', ['locale' => 'en']), ['code' => $code])
            ->assertRedirect(route('password.reset', ['locale' => 'en']));

        $this->post(route('password.store', ['locale' => 'en']), [
            'code'                  => $code,
            'password'              => 'BrandNew!2026',
            'password_confirmation' => 'BrandNew!2026',
        ])->assertRedirect(route('login', ['locale' => 'en']));

        $this->assertTrue(Hash::check('BrandNew!2026', $this->user->fresh()->password));
    }

    public function test_the_code_step_cannot_be_reached_without_asking_first(): void
    {
        $this->get(route('password.code', ['locale' => 'en']))
            ->assertRedirect(route('password.request', ['locale' => 'en']));
    }

    /**
     * The final step must not be reachable by typing its URL.
     */
    public function test_the_password_step_cannot_be_reached_without_a_verified_code(): void
    {
        $this->request();

        $this->get(route('password.reset', ['locale' => 'en']))
            ->assertRedirect(route('password.request', ['locale' => 'en']));
    }

    public function test_a_wrong_code_is_rejected_at_the_form(): void
    {
        $this->request();

        $this->post(route('password.code.verify', ['locale' => 'en']), ['code' => '000000'])
            ->assertSessionHasErrors('code');
    }

    /**
     * The session flag says she proved the code once; it may have expired
     * since, so it is checked again at the moment the password changes.
     */
    public function test_the_code_is_rechecked_when_the_password_is_saved(): void
    {
        $this->request();
        $code = $this->issuedCode();

        $this->post(route('password.code.verify', ['locale' => 'en']), ['code' => $code]);

        // It expires between the two steps.
        $this->travel($this->codes()->lifetimeMinutes() + 1)->minutes();

        $this->post(route('password.store', ['locale' => 'en']), [
            'code'                  => $code,
            'password'              => 'BrandNew!2026',
            'password_confirmation' => 'BrandNew!2026',
        ])->assertSessionHasErrors('code');

        $this->assertTrue(Hash::check('OldPassword!1', $this->user->fresh()->password));
    }

    public function test_the_new_password_must_be_confirmed(): void
    {
        $this->request();
        $code = $this->issuedCode();

        $this->post(route('password.code.verify', ['locale' => 'en']), ['code' => $code]);

        $this->post(route('password.store', ['locale' => 'en']), [
            'code'                  => $code,
            'password'              => 'BrandNew!2026',
            'password_confirmation' => 'Different!2026',
        ])->assertSessionHasErrors('password');
    }

    public function test_the_screens_render_in_both_locales(): void
    {
        foreach (['en', 'ar'] as $locale) {
            $this->get(route('password.request', ['locale' => $locale]))->assertOk();

            $this->post(route('password.email', ['locale' => $locale]), ['email' => 'layla@example.com']);

            $this->get(route('password.code', ['locale' => $locale]))->assertOk();

            $this->codes()->forget('layla@example.com');
        }
    }

    /**
     * Read the plaintext code out of the queued mail, which is the only place
     * it exists — the database holds a hash.
     */
    private function issuedCode(): string
    {
        $code = null;

        Mail::assertQueued(PasswordResetCode::class, function (PasswordResetCode $mail) use (&$code): bool {
            $code = $mail->code;

            return true;
        });

        return (string) $code;
    }
}
