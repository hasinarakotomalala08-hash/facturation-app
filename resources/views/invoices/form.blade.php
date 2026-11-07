@extends('layouts.app')

@php($isEditing = $invoice->exists)

@section('title', $isEditing ? 'Modifier la facture' : 'Nouvelle facture')
@section('breadcrumb', $isEditing ? 'Modifier une facture' : 'Nouvelle facture')

@section('content')
    <div class="page-heading">
        <a class="back-link" href="{{ $isEditing ? route('invoices.show', $invoice) : route('invoices.index') }}">← <span>Retour</span></a>
        <div class="eyebrow">{{ $isEditing ? 'MISE À JOUR' : 'NOUVEAU DOCUMENT' }}</div>
        <h1>{{ $isEditing ? 'Modifier la facture' : 'Créer une facture' }}<span class="heading-period">.</span></h1>
        <p>Renseignez les informations ci-dessous. Les champs marqués d'un * sont obligatoires.</p>
    </div>

    @if ($errors->any())
        <div class="alert alert-error"><strong>Vérifiez les informations saisies.</strong><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif

    <form method="POST" action="{{ $isEditing ? route('invoices.update', $invoice) : route('invoices.store') }}" id="invoice-form" class="invoice-form">
        @csrf
        @if ($isEditing) @method('PUT') @endif

        <section class="form-section">
            <div class="form-section-heading"><span class="section-number">01</span><div><h2>Informations générales</h2><p>Numéro, date et période de facturation.</p></div></div>
            <div class="form-grid form-grid-three">
                <div class="form-field"><label for="invoice_number">N° de facture <b>*</b></label><input id="invoice_number" name="invoice_number" value="{{ old('invoice_number', $invoice->invoice_number) }}" maxlength="50" required>@error('invoice_number')<small class="field-error">{{ $message }}</small>@enderror</div>
                <div class="form-field"><label for="issue_date">Date d'émission <b>*</b></label><input id="issue_date" name="issue_date" type="date" value="{{ old('issue_date', $invoice->issue_date?->format('Y-m-d')) }}" required></div>
                <div class="form-field"><label for="period">Période facturée <b>*</b></label><input id="period" name="period" value="{{ old('period', $invoice->period) }}" placeholder="Ex. Prestation septembre 2026" maxlength="120" required></div>
            </div>
        </section>

        <section class="form-section">
            <div class="form-section-heading"><span class="section-number">02</span><div><h2>Prestataire &amp; client</h2><p>Les coordonnées qui apparaîtront sur la facture.</p></div></div>
            <div class="two-column-form">
                <div class="form-subsection">
                    <h3><span class="mini-dot"></span> Prestataire</h3>
                    <div class="form-field"><label for="provider_name">Nom / raison sociale <b>*</b></label><input id="provider_name" name="provider_name" value="{{ old('provider_name', $invoice->provider_name) }}" maxlength="150" required></div>
                    <div class="form-field"><label for="provider_title">Fonction / activité <b>*</b></label><input id="provider_title" name="provider_title" value="{{ old('provider_title', $invoice->provider_title) }}" placeholder="Ex. Consultant, assistant virtuel" maxlength="120" required></div>
                    <div class="form-field"><label for="provider_address">Adresse <b>*</b></label><input id="provider_address" name="provider_address" value="{{ old('provider_address', $invoice->provider_address) }}" maxlength="255" required></div>
                    <div class="form-grid form-grid-two">
                        <div class="form-field"><label for="provider_phone">Téléphone</label><input id="provider_phone" name="provider_phone" value="{{ old('provider_phone', $invoice->provider_phone) }}" maxlength="60"></div>
                        <div class="form-field"><label for="provider_email">E-mail</label><input id="provider_email" name="provider_email" type="email" value="{{ old('provider_email', $invoice->provider_email) }}" maxlength="150"></div>
                    </div>
                    <div class="form-field"><label for="provider_tax_id">NIF / STAT</label><input id="provider_tax_id" name="provider_tax_id" value="{{ old('provider_tax_id', $invoice->provider_tax_id) }}" maxlength="100"></div>
                </div>
                <div class="form-subsection">
                    <h3><span class="mini-dot client-dot"></span> Client</h3>
                    <div class="form-field"><label for="client_name">Nom / raison sociale <b>*</b></label><input id="client_name" name="client_name" value="{{ old('client_name', $invoice->client_name) }}" maxlength="150" required></div>
                    <div class="form-field"><label for="client_contact">Contact / responsable</label><input id="client_contact" name="client_contact" value="{{ old('client_contact', $invoice->client_contact) }}" maxlength="150"></div>
                    <div class="form-field"><label for="client_address">Adresse <b>*</b></label><input id="client_address" name="client_address" value="{{ old('client_address', $invoice->client_address) }}" maxlength="255" required></div>
                    <div class="form-field"><label for="payment_method">Mode de paiement <b>*</b></label>
                        <select id="payment_method" name="payment_method" required>
                            @foreach (['Virement bancaire', 'Espèces', 'Mobile Money', 'Chèque', 'Autre'] as $method)
                                <option value="{{ $method }}" @selected(old('payment_method', $invoice->payment_method) === $method)>{{ $method }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        </section>

        <section class="form-section">
            <div class="form-section-heading"><span class="section-number">03</span><div><h2>Prestations</h2><p>Ajoutez une ou plusieurs lignes à votre facture.</p></div></div>
            <div class="line-items" id="line-items">
                <div class="line-item-head"><span>DÉSIGNATION</span><span>QUANTITÉ</span><span>UNITÉ</span><span>MONTANT TTC (Ar)</span><span></span></div>
                @foreach (old('items', $rows) as $index => $item)
                    <div class="line-item-row" data-line-item>
                        <div class="form-field"><label class="mobile-label" for="item-description-{{ $index }}">Désignation *</label><input id="item-description-{{ $index }}" data-name="description" name="items[{{ $index }}][description]" value="{{ $item['description'] ?? '' }}" placeholder="Ex. Prestation d'assistant virtuel" maxlength="180" required><input class="item-details" data-name="details" name="items[{{ $index }}][details]" value="{{ $item['details'] ?? '' }}" placeholder="Détail (facultatif)" maxlength="500" aria-label="Détail de la prestation"></div>
                        <div class="form-field"><label class="mobile-label" for="item-quantity-{{ $index }}">Quantité *</label><input id="item-quantity-{{ $index }}" data-name="quantity" name="items[{{ $index }}][quantity]" type="number" step="0.01" min="0.01" value="{{ $item['quantity'] ?? '1' }}" required></div>
                        <div class="form-field"><label class="mobile-label" for="item-unit-{{ $index }}">Unité *</label><input id="item-unit-{{ $index }}" data-name="unit" name="items[{{ $index }}][unit]" value="{{ $item['unit'] ?? 'jour' }}" maxlength="40" required></div>
                        <div class="form-field"><label class="mobile-label" for="item-amount-{{ $index }}">Montant TTC *</label><input id="item-amount-{{ $index }}" data-name="amount" name="items[{{ $index }}][amount]" type="number" step="1" min="0" value="{{ $item['amount'] ?? '' }}" placeholder="0" required></div>
                        <button class="remove-line" type="button" aria-label="Supprimer cette prestation" title="Supprimer">×</button>
                    </div>
                @endforeach
            </div>
            <button type="button" class="add-line-button" id="add-line"><span>＋</span> Ajouter une prestation</button>
            <div class="total-preview"><span>Total TTC</span><strong id="total-preview">0 <small>Ar</small></strong></div>
        </section>

        <div class="form-actions">
            <a class="button button-secondary" href="{{ $isEditing ? route('invoices.show', $invoice) : route('invoices.index') }}">Annuler</a>
            <button class="button button-primary" type="submit">{{ $isEditing ? 'Enregistrer les modifications' : 'Créer la facture' }} <span>→</span></button>
        </div>
    </form>

    <template id="line-item-template">
        <div class="line-item-row" data-line-item>
            <div class="form-field"><label class="mobile-label">Désignation *</label><input data-name="description" placeholder="Ex. Prestation d'assistant virtuel" maxlength="180" required><input class="item-details" data-name="details" placeholder="Détail (facultatif)" maxlength="500" aria-label="Détail de la prestation"></div>
            <div class="form-field"><label class="mobile-label">Quantité *</label><input data-name="quantity" type="number" step="0.01" min="0.01" value="1" required></div>
            <div class="form-field"><label class="mobile-label">Unité *</label><input data-name="unit" value="jour" maxlength="40" required></div>
            <div class="form-field"><label class="mobile-label">Montant TTC *</label><input data-name="amount" type="number" step="1" min="0" placeholder="0" required></div>
            <button class="remove-line" type="button" aria-label="Supprimer cette prestation" title="Supprimer">×</button>
        </div>
    </template>
    <script src="{{ asset('js/invoices.js') }}" defer></script>
@endsection
