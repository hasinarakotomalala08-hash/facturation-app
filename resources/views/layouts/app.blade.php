<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Factures') · Factura</title>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    @yield('head')
</head>
<body>
    <div class="app-shell">
        <aside class="sidebar">
            <a class="brand" href="{{ route('invoices.index') }}">
                <span class="brand-mark">F</span>
                <span>factura<span class="brand-dot">.</span><small>GESTION DE FACTURES</small></span>
            </a>
            <div class="nav-caption">ESPACE DE TRAVAIL</div>
            <nav class="side-nav" aria-label="Navigation principale">
                <a class="{{ request()->routeIs('invoices.index') ? 'active' : '' }}" href="{{ route('invoices.index') }}">
                    <span class="nav-icon">▤</span> Mes factures
                </a>
                <a class="{{ request()->routeIs('invoices.create') ? 'active' : '' }}" href="{{ route('invoices.create') }}">
                    <span class="nav-icon nav-plus">＋</span> Nouvelle facture
                </a>
                <a class="{{ request()->routeIs('account.*') ? 'active' : '' }}" href="{{ route('account.edit') }}">
                    <svg class="nav-icon account-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <circle cx="12" cy="8" r="3.5"/>
                        <path d="M5 20c.7-3.5 3.2-5.5 7-5.5s6.3 2 7 5.5"/>
                    </svg>
                    Mon compte
                </a>
            </nav>
            <div class="sidebar-bottom">
                <div class="secure-note"><span class="secure-dot"></span> Vos documents restent privés</div>
                <div class="profile">
                    <span class="avatar">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
                    <span class="profile-copy"><strong>{{ auth()->user()->name }}</strong><small>{{ auth()->user()->email }}</small></span>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="logout-button" type="submit" aria-label="Se déconnecter" title="Se déconnecter">↗</button>
                    </form>
                </div>
            </div>
        </aside>

        <main class="main-area">
            <header class="topbar">
                <div class="breadcrumb">Espace de facturation <span>/</span> <strong>@yield('breadcrumb', 'Mes factures')</strong></div>
                <div class="topbar-right"><span class="status-dot"></span> Espace sécurisé</div>
            </header>
            <div class="page-content">
                @if (session('status'))
                    <div class="alert alert-success" role="status"><span>✓</span> {{ session('status') }}</div>
                @endif
                @yield('content')
            </div>
            <footer class="app-footer">Factura <span>·</span> Vos factures, simplement.</footer>
        </main>
    </div>
</body>
</html>
