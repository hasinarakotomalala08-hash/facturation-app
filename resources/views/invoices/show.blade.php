@extends('layouts.app')

@section('title', $invoice->invoice_number)
@section('breadcrumb', $invoice->invoice_number)

@section('head')
    @include('invoices._document-styles')
@endsection

@section('content')
    <div class="page-heading heading-with-action detail-heading">
        <div>
            <a class="back-link" href="{{ route('invoices.index') }}">← <span>Retour aux factures</span></a>
            <div class="eyebrow">FACTURE ÉMISE</div>
            <h1>{{ $invoice->invoice_number }}<span class="heading-period">.</span></h1>
            <p>Émise le {{ $invoice->issue_date->format('d/m/Y') }} · {{ $invoice->period }}</p>
        </div>
        <div class="heading-actions">
            <a class="button button-secondary" href="{{ route('invoices.edit', $invoice) }}">✎ <span>Modifier</span></a>
            <a class="button button-primary" href="{{ route('invoices.pdf', $invoice) }}"><span>↓</span> Télécharger le PDF</a>
        </div>
    </div>

    <div class="document-wrap">
        @include('invoices._document', ['invoice' => $invoice])
    </div>
    <div class="detail-footer-actions">
        <span><span class="privacy-lock">◇</span> Seul vous pouvez consulter cette facture.</span>
        <form method="POST" action="{{ route('invoices.destroy', $invoice) }}" onsubmit="return confirm('Supprimer définitivement cette facture ?');">
            @csrf
            @method('DELETE')
            <button type="submit" class="delete-link">Supprimer cette facture</button>
        </form>
    </div>
@endsection
