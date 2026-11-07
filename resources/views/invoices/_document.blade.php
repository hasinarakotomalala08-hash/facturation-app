<article class="invoice-paper">
    <header class="invoice-doc-header">
        <div>
            <div class="doc-kicker">DOCUMENT DE FACTURATION</div>
            <h2>FACTURE</h2>
        </div>
        <div class="doc-meta">
            <div><span>Date</span><strong>{{ $invoice->issue_date->format('d/m/Y') }}</strong></div>
            <div><span>Période</span><strong>{{ $invoice->period }}</strong></div>
            <div><span>N° de facture</span><strong>{{ $invoice->invoice_number }}</strong></div>
        </div>
    </header>

    <section class="party-grid">
        <div class="party-card">
            <h3>PRESTATAIRE</h3>
            <div class="party-content">
                <strong>{{ $invoice->provider_name }}</strong>
                <span>{{ $invoice->provider_title }}</span>
                <span>{{ $invoice->provider_address }}</span>
                @if ($invoice->provider_phone)<span>Tél. : {{ $invoice->provider_phone }}</span>@endif
                @if ($invoice->provider_email)<span>E-mail : {{ $invoice->provider_email }}</span>@endif
                @if ($invoice->provider_tax_id)<span>NIF/STAT : {{ $invoice->provider_tax_id }}</span>@endif
            </div>
        </div>
        <div class="party-card">
            <h3>CLIENT</h3>
            <div class="party-content">
                <strong>{{ $invoice->client_name }}</strong>
                @if ($invoice->client_contact)<span>Responsable : {{ $invoice->client_contact }}</span>@endif
                <span>{{ $invoice->client_address }}</span>
            </div>
        </div>
    </section>

    <section class="doc-items">
        <table>
            <thead>
                <tr><th>DÉSIGNATION DE LA PRESTATION</th><th>QUANTITÉ</th><th>MONTANT TTC</th></tr>
            </thead>
            <tbody>
                @foreach ($invoice->items as $item)
                    <tr>
                        <td><strong>{{ $item->description }}</strong>@if ($item->details)<span class="doc-item-details">{{ $item->details }}</span>@endif</td>
                        <td>{{ rtrim(rtrim(number_format((float) $item->quantity, 2, ',', ' '), '0'), ',') }}&nbsp;{{ $item->unit }}</td>
                        <td>{{ number_format($item->amount, 0, ',', ' ') }} Ar</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </section>

    <div class="doc-total">
        <span>TOTAL TTC</span>
        <strong>{{ number_format($invoice->total, 0, ',', ' ') }} <small>Ariary</small></strong>
    </div>

    <section class="doc-payment">
        <div><span>MODE DE PAIEMENT</span><strong>{{ $invoice->payment_method }}</strong></div>
        <div><span>NOMBRE DE JOURS FACTURÉS</span><strong>{{ rtrim(rtrim(number_format($invoice->billed_days, 2, ',', ' '), '0'), ',') }} jours</strong></div>
        <div><span>NIF/STAT</span><strong>{{ $invoice->provider_tax_id ?: '—' }}</strong></div>
    </section>

    <footer class="doc-footer">Facture établie le {{ $invoice->issue_date->format('d/m/Y') }} pour la période : {{ $invoice->period }}.</footer>
</article>
