@extends('layouts.app')

@section('title', 'Mes factures')
@section('breadcrumb', 'Mes factures')

@section('content')
    <div class="page-heading heading-with-action">
        <div>
            <div class="eyebrow">VOTRE ACTIVITÉ</div>
            <h1>Mes factures<span class="heading-period">.</span></h1>
            <p>Créez, retrouvez et exportez vos factures en un seul endroit.</p>
        </div>
        <a class="button button-primary" href="{{ route('invoices.create') }}"><span class="button-plus">＋</span> Nouvelle facture</a>
    </div>

    <section class="stats-grid" aria-label="Résumé de votre activité">
        <article class="stat-card">
            <div class="stat-icon stat-icon-navy">▤</div>
            <div><span class="stat-label">FACTURES CRÉÉES</span><strong class="stat-value">{{ $invoiceCount }}</strong><span class="stat-note">dans votre espace</span></div>
        </article>
        <article class="stat-card">
            <div class="stat-icon stat-icon-blue">◷</div>
            <div><span class="stat-label">CE MOIS-CI</span><strong class="stat-value">{{ $monthCount }}</strong><span class="stat-note">facture(s) émise(s)</span></div>
        </article>
        <article class="stat-card stat-card-total">
            <div class="stat-icon stat-icon-pale">Ar</div>
            <div><span class="stat-label">MONTANT TOTAL ÉMIS</span><strong class="stat-value stat-value-total">{{ number_format($totalAmount, 0, ',', ' ') }} <small>Ar</small></strong><span class="stat-note">toutes vos factures</span></div>
        </article>
    </section>

    <section class="panel invoice-list-panel">
        <div class="panel-heading">
            <div><h2>Historique des factures</h2><p>Consultez et gérez vos documents.</p></div>
            <form class="search-form" method="GET" action="{{ route('invoices.index') }}">
                <label class="sr-only" for="search">Rechercher une facture</label>
                <span class="search-icon">⌕</span>
                <input id="search" type="search" name="search" value="{{ request('search') }}" placeholder="N° facture, client…">
                @if (request('search'))
                    <a class="clear-search" href="{{ route('invoices.index') }}" aria-label="Effacer la recherche">×</a>
                @endif
            </form>
        </div>

        @if ($invoices->isEmpty())
            <div class="empty-state">
                <div class="empty-icon">▤</div>
                @if (request('search'))
                    <h3>Aucun résultat</h3>
                    <p>Aucune facture ne correspond à « {{ request('search') }} ».</p>
                    <a class="button button-secondary" href="{{ route('invoices.index') }}">Effacer la recherche</a>
                @else
                    <h3>Votre espace est prêt</h3>
                    <p>Créez votre première facture. Vous pourrez ensuite la télécharger en PDF à tout moment.</p>
                    <a class="button button-primary" href="{{ route('invoices.create') }}">Créer ma première facture <span>→</span></a>
                @endif
            </div>
        @else
            <div class="table-wrap">
                <table class="invoice-table">
                    <thead><tr><th>NUMÉRO</th><th>CLIENT</th><th>PÉRIODE</th><th>DATE D'ÉMISSION</th><th class="align-right">MONTANT TTC</th><th></th></tr></thead>
                    <tbody>
                    @foreach ($invoices as $invoice)
                        <tr>
                            <td><a class="invoice-number" href="{{ route('invoices.show', $invoice) }}">{{ $invoice->invoice_number }}</a></td>
                            <td><span class="client-name">{{ $invoice->client_name }}</span>@if ($invoice->client_contact)<small class="table-subtext">{{ $invoice->client_contact }}</small>@endif</td>
                            <td class="muted-cell">{{ $invoice->period }}</td>
                            <td class="muted-cell">{{ $invoice->issue_date->format('d/m/Y') }}</td>
                            <td class="align-right amount-cell">{{ number_format($invoice->total, 0, ',', ' ') }} <small>Ar</small></td>
                            <td><a class="row-arrow" href="{{ route('invoices.show', $invoice) }}" aria-label="Ouvrir {{ $invoice->invoice_number }}">↗</a></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            @if ($invoices->hasPages())
                <div class="pagination-wrap">{{ $invoices->links() }}</div>
            @endif
        @endif
    </section>

    <p class="privacy-note"><span>◇</span> Chaque espace est privé : vos factures ne sont visibles que par vous.</p>
@endsection
