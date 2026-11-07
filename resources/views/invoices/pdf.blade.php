<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 0; }
        * { box-sizing: border-box; }
        body { margin: 0; color: #172c3c; font-family: DejaVu Sans, sans-serif; font-size: 9pt; }
    </style>
    @include('invoices._document-styles')
    <style>
        body.pdf-document .invoice-paper {
            border: 1px solid #e2e8ed;
            border-radius: 4px;
            box-shadow: none;
            margin: 0 auto;
            max-width: 800px;
            padding: 49px 50px 38px;
        }
    </style>
</head>
<body class="pdf-document">
    @include('invoices._document', ['invoice' => $invoice])
</body>
</html>
