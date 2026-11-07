<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_visitor_can_create_an_account_and_sign_out(): void
    {
        $this->freezeTime();

        $response = $this->post(route('register.store'), [
            'name' => 'Tanjona Razakaria',
            'email' => 'tanjona@example.test',
            'password' => 'securite123',
            'password_confirmation' => 'securite123',
        ]);

        $response->assertRedirect(route('invoices.index'));

        $sessionCookie = $response->getCookie(config('session.cookie'), false);
        $this->assertNotNull($sessionCookie);
        $this->assertSame(now()->addHours(2)->getTimestamp(), $sessionCookie->getExpiresTime());
        $this->assertTrue($sessionCookie->isHttpOnly());
        $this->assertSame('lax', $sessionCookie->getSameSite());

        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', ['email' => 'tanjona@example.test']);

        $this->post(route('logout'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_registration_shows_a_french_error_when_password_confirmation_does_not_match(): void
    {
        $response = $this->from(route('register'))->post(route('register.store'), [
            'name' => 'Tanjona Razakaria',
            'email' => 'tanjona@example.test',
            'password' => 'securite123',
            'password_confirmation' => 'different123',
        ]);

        $response->assertRedirect(route('register'))
            ->assertSessionHasErrors([
                'password' => 'La confirmation du mot de passe ne correspond pas.',
            ]);
        $this->assertDatabaseMissing('users', ['email' => 'tanjona@example.test']);
        $this->assertGuest();
    }

    public function test_registration_shows_a_french_error_when_email_is_already_used(): void
    {
        User::factory()->create(['email' => 'tanjona@example.test']);

        $response = $this->from(route('register'))->post(route('register.store'), [
            'name' => 'Tanjona Razakaria',
            'email' => 'tanjona@example.test',
            'password' => 'securite123',
            'password_confirmation' => 'securite123',
        ]);

        $response->assertRedirect(route('register'))
            ->assertSessionHasErrors([
                'email' => 'Cette adresse e-mail est déjà utilisée.',
            ]);
        $this->assertDatabaseCount('users', 1);
        $this->assertGuest();
    }

    public function test_a_user_can_sign_in_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'tanjona@example.test',
            'password' => 'securite123',
        ]);

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'securite123',
        ])->assertRedirect(route('invoices.index'));

        $this->assertAuthenticatedAs($user);
    }
}
