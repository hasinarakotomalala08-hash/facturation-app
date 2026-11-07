<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') · Factura</title>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body class="auth-page">
    <a class="brand auth-brand" href="{{ route('login') }}">
        <span class="brand-mark">F</span>
        <span>factura<span class="brand-dot">.</span><small>GESTION DE FACTURES</small></span>
    </a>
    <main class="auth-card">
        @yield('content')
    </main>
    <p class="auth-footnote">Une facturation claire, à votre image.</p>
</body>
</html>
