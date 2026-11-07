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
    <p class="auth-switch">Vous avez déjà un compte ? <a href="{{ route('login') }}">Se connecter</a></p>
@endsection
