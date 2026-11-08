<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
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

    public function test_the_account_page_displays_account_information_and_deletion_action(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('account.edit'))
            ->assertOk()
            ->assertSee('Informations personnelles')
            ->assertSee($user->email)
            ->assertSee(route('account.edit'), false)
            ->assertSee('Supprimer mon compte')
            ->assertSee('Votre compte et toutes les factures associées seront supprimés.');
    }

    public function test_a_user_can_update_their_account_information(): void
    {
        $user = User::factory()->create([
            'name' => 'Ancien nom',
            'email' => 'ancien@example.test',
        ]);

        $this->actingAs($user)
            ->put(route('account.update'), [
                'name' => 'Nouveau nom',
                'email' => 'nouveau@example.test',
            ])
            ->assertRedirect(route('account.edit'))
            ->assertSessionHas('status', 'Les informations de votre compte ont été mises à jour.');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Nouveau nom',
            'email' => 'nouveau@example.test',
            'email_verified_at' => null,
        ]);
        $this->assertAuthenticatedAs($user);
    }

    public function test_an_account_cannot_be_updated_with_an_email_used_by_another_user(): void
    {
        $user = User::factory()->create(['email' => 'user@example.test']);
        User::factory()->create(['email' => 'taken@example.test']);

        $this->actingAs($user)
            ->from(route('account.edit'))
            ->put(route('account.update'), [
                'name' => 'Nouveau nom',
                'email' => 'taken@example.test',
            ])
            ->assertRedirect(route('account.edit'))
            ->assertSessionHasErrors([
                'email' => 'Cette adresse e-mail est déjà utilisée.',
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => $user->name,
            'email' => 'user@example.test',
        ]);
    }

    public function test_account_settings_require_authentication(): void
    {
        $this->get(route('account.edit'))->assertRedirect(route('login'));
        $this->put(route('account.update'), [])->assertRedirect(route('login'));
    }

    public function test_a_user_can_delete_their_account_and_all_associated_invoices(): void
    {
        $user = User::factory()->create([
            'google_id' => 'google-user-to-delete',
        ]);
        DB::table(config('auth.passwords.'.config('auth.defaults.passwords').'.table'))->insert([
            'email' => $user->email,
            'token' => 'password-reset-token',
            'created_at' => now(),
        ]);
        $invoice = $user->invoices()->create([
            'invoice_number' => 'FAC-202611-ABCDE',
            'issue_date' => '2026-11-03',
            'period' => 'Prestation octobre 2026',
            'provider_name' => 'Prestataire Test',
            'provider_title' => 'Consultant',
            'provider_address' => 'Antananarivo',
            'client_name' => 'Client Test',
            'client_address' => 'Antananarivo',
            'payment_method' => 'Virement bancaire',
        ]);
        $invoice->items()->create([
            'description' => 'Prestation de test',
            'quantity' => 1,
            'unit' => 'forfait',
            'amount' => 100_000,
        ]);

        config(['session.driver' => 'database']);
        DB::table(config('session.table'))->insert([
            [
                'id' => 'user-session-one',
                'user_id' => $user->id,
                'ip_address' => '127.0.0.1',
                'user_agent' => 'Test browser',
                'payload' => '',
                'last_activity' => now()->timestamp,
            ],
            [
                'id' => 'user-session-two',
                'user_id' => $user->id,
                'ip_address' => '127.0.0.1',
                'user_agent' => 'Another browser',
                'payload' => '',
                'last_activity' => now()->timestamp,
            ],
        ]);

        $this->actingAs($user)
            ->delete(route('account.destroy'))
            ->assertRedirect(route('login'))
            ->assertSessionHas('status', 'Votre compte et toutes vos factures ont été supprimés.');

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
        $this->assertDatabaseMissing('invoices', ['id' => $invoice->id]);
        $this->assertDatabaseMissing('invoice_items', ['invoice_id' => $invoice->id]);
        $this->assertDatabaseMissing(config('auth.passwords.'.config('auth.defaults.passwords').'.table'), [
            'email' => $user->email,
        ]);
        $this->assertDatabaseMissing(config('session.table'), ['user_id' => $user->id]);
        $this->assertDatabaseCount('deleted_google_accounts', 1);
        $this->assertGuest();
    }

    public function test_deleting_an_account_without_a_google_link_does_not_require_google_reconsent(): void
    {
        $user = User::factory()->create(['google_id' => null]);

        $this->actingAs($user)
            ->delete(route('account.destroy'))
            ->assertRedirect(route('login'));

        $this->assertDatabaseCount('deleted_google_accounts', 0);
    }

    public function test_account_deletion_requires_authentication(): void
    {
        $this->delete(route('account.destroy'))
            ->assertRedirect(route('login'));
    }

    public function test_a_visitor_can_sign_in_with_a_verified_google_account(): void
    {
        Socialite::fake('google', SocialiteUser::fake([
            'id' => 'google-user-123',
            'name' => 'Tanjona Razakaria',
            'email' => 'tanjona@example.test',
            'email_verified' => true,
        ]));

        $response = $this->get(route('auth.google.callback'));

        $response->assertRedirect(route('invoices.index'));
        $this->assertDatabaseHas('users', [
            'name' => 'Tanjona Razakaria',
            'email' => 'tanjona@example.test',
            'google_id' => 'google-user-123',
        ]);
        $this->assertAuthenticated();
    }

    public function test_a_deleted_google_account_must_reauthorize_before_it_can_be_created_again(): void
    {
        DB::table('deleted_google_accounts')->insert([
            'email_hash' => hash('sha256', 'tanjona@example.test'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        Socialite::fake('google', SocialiteUser::fake([
            'id' => 'google-user-123',
            'name' => 'Tanjona Razakaria',
            'email' => 'tanjona@example.test',
            'email_verified' => true,
        ]));

        $response = $this->get(route('auth.google.callback'));

        $response->assertRedirect(route('auth.google.consent'));
        $this->assertDatabaseMissing('users', ['email' => 'tanjona@example.test']);
        $this->assertDatabaseCount('deleted_google_accounts', 1);
        $this->assertGuest();

        $this->get(route('auth.google.callback'))
            ->assertRedirect(route('invoices.index'));

        $this->assertDatabaseHas('users', [
            'email' => 'tanjona@example.test',
            'google_id' => 'google-user-123',
        ]);
        $this->assertDatabaseCount('deleted_google_accounts', 0);
        $this->assertAuthenticated();
    }

    public function test_a_different_google_identity_cannot_complete_deleted_account_reauthorization(): void
    {
        DB::table('deleted_google_accounts')->insert([
            'email_hash' => hash('sha256', 'tanjona@example.test'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        Socialite::fake('google', SocialiteUser::fake([
            'id' => 'google-user-123',
            'name' => 'Tanjona Razakaria',
            'email' => 'tanjona@example.test',
            'email_verified' => true,
        ]));

        $this->get(route('auth.google.callback'))->assertRedirect(route('auth.google.consent'));

        Socialite::fake('google', SocialiteUser::fake([
            'id' => 'another-google-user',
            'name' => 'Other User',
            'email' => 'other@example.test',
            'email_verified' => true,
        ]));

        $this->get(route('auth.google.callback'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors([
                'email' => 'Veuillez réessayer avec le même compte Google que celui associé à cette adresse.',
            ]);

        $this->assertDatabaseMissing('users', ['email' => 'other@example.test']);
        $this->assertDatabaseCount('deleted_google_accounts', 1);
        $this->assertGuest();
    }

    public function test_cancelling_google_consent_shows_a_friendly_message_and_keeps_reauthorization_required(): void
    {
        DB::table('deleted_google_accounts')->insert([
            'email_hash' => hash('sha256', 'tanjona@example.test'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->withSession([
            'google_reauthorization' => [
                'email' => 'tanjona@example.test',
                'google_id' => 'google-user-123',
            ],
        ])->followingRedirects()->get(route('auth.google.callback', ['error' => 'access_denied']));

        $response
            ->assertOk()
            ->assertSee('Vous avez annulé le partage des informations avec Factura. Vous pouvez réessayer.')
            ->assertSessionMissing('google_reauthorization');

        $this->assertDatabaseCount('deleted_google_accounts', 1);
        $this->assertDatabaseMissing('users', ['email' => 'tanjona@example.test']);
        $this->assertGuest();
    }

    public function test_deleted_google_account_consent_redirect_is_limited_to_its_pending_identity(): void
    {
        config([
            'services.google.client_id' => 'google-client-id',
            'services.google.client_secret' => 'google-client-secret',
            'services.google.redirect' => 'http://localhost/auth/google/callback',
        ]);
        DB::table('deleted_google_accounts')->insert([
            'email_hash' => hash('sha256', 'tanjona@example.test'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->withSession([
            'google_reauthorization' => [
                'email' => 'tanjona@example.test',
                'google_id' => 'google-user-123',
            ],
        ])->get(route('auth.google.consent'));

        $response->assertRedirect();

        parse_str((string) parse_url($response->headers->get('Location'), PHP_URL_QUERY), $query);
        $this->assertSame('consent', $query['prompt'] ?? null);
        $this->assertSame('tanjona@example.test', $query['login_hint'] ?? null);
    }

    public function test_a_verified_google_email_is_linked_to_an_existing_account(): void
    {
        $user = User::factory()->unverified()->create([
            'email' => 'tanjona@example.test',
            'google_id' => null,
        ]);

        Socialite::fake('google', SocialiteUser::fake([
            'id' => 'google-user-123',
            'name' => 'Tanjona Razakaria',
            'email' => 'tanjona@example.test',
            'email_verified' => true,
        ]));

        $response = $this->get(route('auth.google.callback'));

        $response->assertRedirect(route('invoices.index'));
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'google_id' => 'google-user-123',
        ]);
        $this->assertNotNull($user->fresh()->email_verified_at);
        $this->assertAuthenticatedAs($user);
    }

    public function test_a_google_account_with_an_unverified_email_cannot_sign_in(): void
    {
        Socialite::fake('google', SocialiteUser::fake([
            'id' => 'google-user-123',
            'name' => 'Tanjona Razakaria',
            'email' => 'tanjona@example.test',
            'email_verified' => false,
        ]));

        $response = $this->get(route('auth.google.callback'));

        $response->assertRedirect(route('login'))
            ->assertSessionHasErrors([
                'email' => 'Google doit fournir une adresse e-mail vérifiée pour vous connecter.',
            ]);
        $this->assertDatabaseMissing('users', ['email' => 'tanjona@example.test']);
        $this->assertGuest();
    }

    public function test_a_google_account_cannot_be_linked_to_an_account_already_linked_elsewhere(): void
    {
        User::factory()->create([
            'email' => 'tanjona@example.test',
            'google_id' => 'another-google-user',
        ]);

        Socialite::fake('google', SocialiteUser::fake([
            'id' => 'google-user-123',
            'name' => 'Tanjona Razakaria',
            'email' => 'tanjona@example.test',
            'email_verified' => true,
        ]));

        $response = $this->get(route('auth.google.callback'));

        $response->assertRedirect(route('login'))
            ->assertSessionHasErrors([
                'email' => 'Ce compte est déjà associé à un autre compte Google.',
            ]);
        $this->assertDatabaseHas('users', [
            'email' => 'tanjona@example.test',
            'google_id' => 'another-google-user',
        ]);
        $this->assertGuest();
    }

    public function test_a_visitor_is_redirected_to_google_for_authentication(): void
    {
        config([
            'services.google.client_id' => 'google-client-id',
            'services.google.client_secret' => 'google-client-secret',
            'services.google.redirect' => 'http://localhost/auth/google/callback',
        ]);

        $response = $this->get(route('auth.google.redirect'));

        $response->assertRedirect();

        parse_str((string) parse_url($response->headers->get('Location'), PHP_URL_QUERY), $query);
        $this->assertArrayNotHasKey('prompt', $query);
    }
}
