@extends('layouts.auth')

@section('title', 'Connexion')

@section('content')
    <div class="eyebrow">BON RETOUR</div>
    <h1 class="auth-title">Connectez-vous<span>.</span></h1>
    <p class="auth-subtitle">Retrouvez vos factures et vos documents.</p>

    @if ($errors->any())
        <div class="alert alert-error">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('login.store') }}" class="stack-form">
        @csrf
        <label class="field-label" for="email">Adresse e-mail</label>
        <input class="text-input" id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" required autofocus>
        <label class="field-label" for="password">Mot de passe</label>
        <input class="text-input" id="password" name="password" type="password" autocomplete="current-password" required>
        <label class="check-row"><input type="checkbox" name="remember" value="1"> <span>Rester connecté</span></label>
        <button class="button button-primary button-full" type="submit">Se connecter <span>→</span></button>
    </form>
    <p class="auth-switch">Ou continuez avec</p>
    <a class="button button-secondary button-full google-button" href="{{ route('auth.google.redirect') }}">
        <svg class="google-button-icon" viewBox="0 0 48 48" aria-hidden="true">
            <path fill="#4285F4" d="M43.6 24.5c0-1.4-.1-2.8-.4-4.1H24v7.8h11c-.5 2.5-1.9 4.6-4 6v5.1h6.5c3.8-3.5 6.1-8.6 6.1-14.8Z"/>
            <path fill="#34A853" d="M24 44c5.5 0 10.1-1.8 13.5-4.8L31 34.1c-1.8 1.2-4.1 2-7 2-5.3 0-9.7-3.6-11.3-8.5H6v5.3C9.4 39.5 16.2 44 24 44Z"/>
            <path fill="#FBBC05" d="M12.7 27.6a12 12 0 0 1 0-7.2v-5.3H6a20 20 0 0 0 0 17.8l6.7-5.3Z"/>
            <path fill="#EA4335" d="M24 11.9c3 0 5.7 1 7.8 3.1l5.8-5.8C34.1 5.9 29.5 4 24 4 16.2 4 9.4 8.5 6 15.1l6.7 5.3c1.6-4.9 6-8.5 11.3-8.5Z"/>
        </svg>
        Continuer avec Google
    </a>
    <p class="auth-switch">Pas encore de compte ? <a href="{{ route('register') }}">Créer mon espace</a></p>
@endsection
