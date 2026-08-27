<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/en/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register(): void
    {
        $response = $this->post('/en/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'terms' => '1',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('store.home', ['locale' => 'en'], absolute: false));
    }

    /**
     * The terms box is marked required in the markup, but that only binds a
     * browser — the rule has to hold for a form posted any other way.
     */
    public function test_registration_requires_agreeing_to_the_terms(): void
    {
        $this->post('/en/register', [
            'name' => 'Test User',
            'email' => 'untermed@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertSessionHasErrors('terms');

        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'untermed@example.com']);
    }
}
