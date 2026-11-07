<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 20mm 16mm 18mm; }
        * { box-sizing: border-box; }
        body { margin: 0; color: #172c3c; font-family: DejaVu Sans, sans-serif; font-size: 9pt; }
    </style>
    @include('invoices._document-styles')
    <style>
        .invoice-paper {
            border: 0;
            border-radius: 0;
            box-shadow: none;
            margin: 0;
            max-width: none;
            padding: 0;
            width: 100%;
        }
    </style>
</head>
<body>
    @include('invoices._document', ['invoice' => $invoice])
</body>
</html>
