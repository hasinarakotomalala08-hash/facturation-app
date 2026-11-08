@extends('layouts.auth')

@section('title', 'Créer un compte')

@section('content')
    <div class="eyebrow">VOTRE ESPACE, EN QUELQUES SECONDES</div>
    <h1 class="auth-title">Créez votre compte<span>.</span></h1>
    <p class="auth-subtitle">Centralisez vos factures dans un espace privé.</p>

    @if ($errors->any())
        <div class="alert alert-error">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('register.store') }}" class="stack-form">
        @csrf
        <label class="field-label" for="name">Nom complet</label>
        <input class="text-input" id="name" name="name" type="text" value="{{ old('name') }}" autocomplete="name" required autofocus>
        <label class="field-label" for="email">Adresse e-mail</label>
        <input class="text-input" id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" required>
        <label class="field-label" for="password">Mot de passe</label>
        <input class="text-input" id="password" name="password" type="password" autocomplete="new-password" required>
        <label class="field-label" for="password_confirmation">Confirmer le mot de passe</label>
        <input class="text-input" id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required>
        <button class="button button-primary button-full" type="submit">Créer mon espace <span>→</span></button>
    </form>
    <p class="auth-switch">Ou inscrivez-vous avec</p>
    <a class="button button-secondary button-full google-button" href="{{ route('auth.google.redirect') }}">
        <svg class="google-button-icon" viewBox="0 0 48 48" aria-hidden="true">
            <path fill="#4285F4" d="M43.6 24.5c0-1.4-.1-2.8-.4-4.1H24v7.8h11c-.5 2.5-1.9 4.6-4 6v5.1h6.5c3.8-3.5 6.1-8.6 6.1-14.8Z"/>
            <path fill="#34A853" d="M24 44c5.5 0 10.1-1.8 13.5-4.8L31 34.1c-1.8 1.2-4.1 2-7 2-5.3 0-9.7-3.6-11.3-8.5H6v5.3C9.4 39.5 16.2 44 24 44Z"/>
            <path fill="#FBBC05" d="M12.7 27.6a12 12 0 0 1 0-7.2v-5.3H6a20 20 0 0 0 0 17.8l6.7-5.3Z"/>
            <path fill="#EA4335" d="M24 11.9c3 0 5.7 1 7.8 3.1l5.8-5.8C34.1 5.9 29.5 4 24 4 16.2 4 9.4 8.5 6 15.1l6.7 5.3c1.6-4.9 6-8.5 11.3-8.5Z"/>
        </svg>
        Continuer avec Google
    </a>
    <p class="auth-switch">Vous avez déjà un compte ? <a href="{{ route('login') }}">Se connecter</a></p>
@endsection
