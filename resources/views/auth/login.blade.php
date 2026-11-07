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
    <p class="auth-switch">Pas encore de compte ? <a href="{{ route('register') }}">Créer mon espace</a></p>
@endsection
