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
        $this->post(route('register.store'), [
            'name' => 'Tanjona Razakaria',
            'email' => 'tanjona@example.test',
            'password' => 'securite123',
            'password_confirmation' => 'securite123',
        ])->assertRedirect(route('invoices.index'));

        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', ['email' => 'tanjona@example.test']);

        $this->post(route('logout'))
            ->assertRedirect(route('login'));

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
