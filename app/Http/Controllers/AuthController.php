<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;
use Laravel\Socialite\Facades\Socialite;

class AuthController extends Controller
{
    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()
                ->withErrors(['email' => 'Ces identifiants ne correspondent pas à un compte.'])
                ->onlyInput('email');
        }

        $request->session()->regenerate();

        return redirect()->intended(route('invoices.index'));
    }

    public function redirectToGoogle(): RedirectResponse
    {
        return Socialite::driver('google')->redirect();
    }

    public function redirectToGoogleConsent(Request $request): RedirectResponse
    {
        $pendingGoogleAccount = $request->session()->get('google_reauthorization');

        if (
            ! is_array($pendingGoogleAccount)
            || ! is_string($pendingGoogleAccount['email'] ?? null)
            || ! is_string($pendingGoogleAccount['google_id'] ?? null)
        ) {
            return redirect()->route('login');
        }

        $emailHash = hash('sha256', Str::lower(trim($pendingGoogleAccount['email'])));

        if (! DB::table('deleted_google_accounts')->where('email_hash', $emailHash)->exists()) {
            $request->session()->forget('google_reauthorization');

            return redirect()->route('login');
        }

        return Socialite::driver('google')
            ->with([
                'prompt' => 'consent',
                'login_hint' => $pendingGoogleAccount['email'],
            ])
            ->redirect();
    }

    public function handleGoogleCallback(Request $request): RedirectResponse
    {
        $oauthError = $request->query('error');

        if (is_string($oauthError) && $oauthError !== '') {
            $request->session()->forget('google_reauthorization');

            $message = $oauthError === 'access_denied'
                ? 'Vous avez annulé le partage des informations avec Factura. Vous pouvez réessayer.'
                : 'Google n’a pas pu terminer la connexion. Veuillez réessayer.';

            return redirect()->route('login')->withErrors(['email' => $message]);
        }

        $googleUser = Socialite::driver('google')->user();
        $googleId = $googleUser->getId();
        $email = $googleUser->getEmail();

        if (
            ! is_string($googleId)
            || $googleId === ''
            || ! is_string($email)
            || filter_var($email, FILTER_VALIDATE_EMAIL) === false
            || ($googleUser->getRaw()['email_verified'] ?? false) !== true
        ) {
            return redirect()->route('login')
                ->withErrors(['email' => 'Google doit fournir une adresse e-mail vérifiée pour vous connecter.']);
        }

        $emailHash = hash('sha256', Str::lower(trim($email)));
        $pendingGoogleAccount = $request->session()->get('google_reauthorization');

        if ($pendingGoogleAccount !== null) {
            if (
                ! is_array($pendingGoogleAccount)
                || ! is_string($pendingGoogleAccount['email'] ?? null)
                || ! is_string($pendingGoogleAccount['google_id'] ?? null)
                || ! hash_equals($pendingGoogleAccount['email'], $email)
                || ! hash_equals($pendingGoogleAccount['google_id'], $googleId)
            ) {
                $request->session()->forget('google_reauthorization');

                return redirect()->route('login')
                    ->withErrors(['email' => 'Veuillez réessayer avec le même compte Google que celui associé à cette adresse.']);
            }

            DB::table('deleted_google_accounts')->where('email_hash', $emailHash)->delete();
            $request->session()->forget('google_reauthorization');
        } elseif (DB::table('deleted_google_accounts')->where('email_hash', $emailHash)->exists()) {
            $request->session()->put('google_reauthorization', [
                'email' => $email,
                'google_id' => $googleId,
            ]);

            return redirect()->route('auth.google.consent');
        }

        $user = User::query()->where('google_id', $googleId)->first();

        if (! $user) {
            $user = User::query()->where('email', $email)->first();

            if ($user && $user->google_id !== null && $user->google_id !== $googleId) {
                return redirect()->route('login')
                    ->withErrors(['email' => 'Ce compte est déjà associé à un autre compte Google.']);
            }

            if ($user) {
                $user->google_id = $googleId;
                $user->email_verified_at ??= now();
                $user->save();
            } else {
                $name = $googleUser->getName();

                $user = User::create([
                    'name' => is_string($name) && $name !== '' ? $name : Str::before($email, '@'),
                    'email' => $email,
                    'password' => Str::random(64),
                    'google_id' => $googleId,
                    'email_verified_at' => now(),
                ]);
            }
        }

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->intended(route('invoices.index'));
    }

    public function showRegister(): View
    {
        return view('auth.register');
    }

    public function register(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:150', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ], [
            'password.confirmed' => 'La confirmation du mot de passe ne correspond pas.',
            'email.unique' => 'Cette adresse e-mail est déjà utilisée.',
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('invoices.index');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    public function editAccount(): View
    {
        return view('account.edit');
    }

    public function updateAccount(Request $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')->ignore($user->id)],
        ], [
            'email.unique' => 'Cette adresse e-mail est déjà utilisée.',
        ]);

        $user->fill($data);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        return redirect()->route('account.edit')
            ->with('status', 'Les informations de votre compte ont été mises à jour.');
    }

    public function destroyAccount(Request $request): RedirectResponse
    {
        $user = $request->user();
        $passwordResetTable = config('auth.passwords.'.config('auth.defaults.passwords').'.table');
        $userId = $user->getAuthIdentifier();

        Auth::logout();

        DB::transaction(function () use ($user, $userId, $passwordResetTable): void {
            if ($user->google_id !== null) {
                DB::table('deleted_google_accounts')->updateOrInsert(
                    ['email_hash' => hash('sha256', Str::lower(trim($user->email)))],
                    ['created_at' => now(), 'updated_at' => now()],
                );
            }

            DB::table($passwordResetTable)->where('email', $user->email)->delete();

            if (config('session.driver') === 'database') {
                DB::connection(config('session.connection'))
                    ->table(config('session.table'))
                    ->where('user_id', $userId)
                    ->delete();
            }

            $user->delete();
        });

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')
            ->with('status', 'Votre compte et toutes vos factures ont été supprimés.');
    }
}
