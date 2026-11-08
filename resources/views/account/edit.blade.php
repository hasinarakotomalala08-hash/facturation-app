@extends('layouts.app')

@section('title', 'Mon compte')
@section('breadcrumb', 'Mon compte')

@section('content')
    <div class="page-heading">
        <div class="eyebrow">VOTRE ESPACE PERSONNEL</div>
        <h1>Mon compte<span class="heading-period">.</span></h1>
        <p>Modifiez les informations associées à votre compte.</p>
    </div>

    @if ($errors->any())
        <div class="alert alert-error" role="alert">{{ $errors->first() }}</div>
    @endif

    <div class="account-settings">
        <form class="form-section" method="POST" action="{{ route('account.update') }}">
            @csrf
            @method('PUT')
            <div class="form-section-heading">
                <span class="section-number">01</span>
                <div>
                    <h2>Informations personnelles</h2>
                    <p>Votre nom et votre adresse e-mail de connexion.</p>
                </div>
            </div>
            <div class="form-grid">
                <div class="form-field">
                    <label for="name">Nom complet</label>
                    <input id="name" name="name" type="text" value="{{ old('name', auth()->user()->name) }}" autocomplete="name" maxlength="120" required>
                </div>
                <div class="form-field">
                    <label for="email">Adresse e-mail</label>
                    <input id="email" name="email" type="email" value="{{ old('email', auth()->user()->email) }}" autocomplete="email" maxlength="150" required>
                </div>
            </div>
            <div class="form-actions">
                <button class="button button-primary" type="submit">Enregistrer les modifications</button>
            </div>
        </form>

        <section class="account-danger-zone" aria-labelledby="account-deletion-title">
            <div>
                <h2 id="account-deletion-title">Supprimer mon compte</h2>
                <p>Cette action est définitive. Votre compte et toutes les factures associées seront supprimés.</p>
            </div>
            <form method="POST" action="{{ route('account.destroy') }}" onsubmit="return confirm('Supprimer définitivement votre compte et toutes vos factures ? Cette action est irréversible.');">
                @csrf
                @method('DELETE')
                <button class="button button-danger" type="submit">Supprimer mon compte</button>
            </form>
        </section>
    </div>
@endsection
