<style>
    .invoice-paper {
        background: #fff;
        border: 1px solid #e2e8ed;
        border-radius: 4px;
        box-shadow: 0 4px 18px rgba(34, 54, 72, .055);
        color: #172c3c;
        margin: 0 auto;
        max-width: 800px;
        padding: 49px 50px 38px;
    }

    .invoice-doc-header,
    .party-grid,
    .doc-total,
    .doc-payment {
        display: table;
        width: 100%;
    }

    .invoice-doc-header {
        margin-bottom: 27px;
    }

    .invoice-doc-header > div {
        display: table-cell;
        vertical-align: bottom;
    }

    .invoice-doc-header > div:first-child {
        width: 42%;
    }

    .doc-kicker {
        color: #84919b;
        font-size: 8px;
        letter-spacing: 1.1px;
        margin-bottom: 6px;
    }

    .invoice-doc-header h2 {
        color: #17324d;
        font-size: 26px;
        letter-spacing: .7px;
        margin: 0;
    }

    .doc-meta {
        text-align: right;
    }

    .doc-meta > div {
        display: table;
        margin-bottom: 7px;
        width: 100%;
    }

    .doc-meta span,
    .doc-meta strong {
        display: table-cell;
        font-size: 9px;
    }

    .doc-meta span {
        color: #667786;
        font-weight: 600;
        text-align: left;
    }

    .doc-meta strong {
        color: #2a4053;
        font-weight: 500;
        text-align: right;
    }

    .party-grid {
        border: 1px solid #d7e0e7;
        margin-bottom: 20px;
        table-layout: fixed;
    }

    .party-card {
        background: #f4f7f9;
        display: table-cell;
        padding: 14px 16px;
        vertical-align: top;
        width: 51%;
    }

    .party-card + .party-card {
        border-left: 1px solid #d7e0e7;
        width: 49%;
    }

    .party-card h3 {
        color: #17324d;
        font-size: 9px;
        letter-spacing: .55px;
        margin: 0 0 12px;
    }

    .party-content strong,
    .party-content span {
        display: block;
        font-size: 9px;
        line-height: 1.75;
    }

    .party-content strong {
        color: #263d50;
        font-weight: 700;
    }

    .party-content span {
        color: #405365;
    }

    .doc-items {
        border: 1px solid #cbd5dd;
        margin-bottom: 12px;
    }

    .doc-items table {
        border-collapse: collapse;
        width: 100%;
    }

    .doc-items thead {
        background: #17324d;
        color: #fff;
    }

    .doc-items th {
        font-size: 8px;
        font-weight: 600;
        letter-spacing: .3px;
        padding: 12px 10px;
        text-align: left;
    }

    .doc-items th:nth-child(2),
    .doc-items td:nth-child(2) {
        border-left: 1px solid #d7e0e7;
        border-right: 1px solid #d7e0e7;
        width: 21%;
    }

    .doc-items th:last-child,
    .doc-items td:last-child {
        text-align: right;
        width: 26%;
    }

    .doc-items td {
        border-top: 1px solid #e2e8ed;
        color: #2a4052;
        font-size: 9px;
        padding: 13px 10px;
        vertical-align: top;
    }

    .doc-items td strong {
        font-weight: 600;
    }

    .doc-item-details {
        color: #788791;
        display: block;
        font-size: 8px;
        line-height: 1.6;
        margin-top: 3px;
    }

    .doc-total {
        background: #eaf1f6;
        border: 1px solid #d2dce4;
        margin-bottom: 20px;
        padding: 13px 15px;
    }

    .doc-total > span,
    .doc-total > strong {
        display: table-cell;
        vertical-align: middle;
    }

    .doc-total > span {
        color: #17324d;
        font-size: 10px;
        font-weight: 700;
    }

    .doc-total > strong {
        color: #17324d;
        font-size: 19px;
        font-weight: 700;
        text-align: right;
    }

    .doc-total small {
        font-size: 10px;
        font-weight: 500;
    }

    .doc-payment {
        border: 1px solid #d7e0e7;
        margin-bottom: 15px;
    }

    .doc-payment > div {
        border-right: 1px solid #d7e0e7;
        display: table-cell;
        padding: 11px 12px;
        vertical-align: top;
        width: 33.333%;
    }

    .doc-payment > div:last-child {
        border-right: 0;
    }

    .doc-payment span,
    .doc-payment strong {
        display: block;
        line-height: 1.6;
    }

    .doc-payment span {
        color: #17324d;
        font-size: 8px;
        font-weight: 700;
    }

    .doc-payment strong {
        color: #526371;
        font-size: 8px;
        font-weight: 500;
        margin-top: 2px;
    }

    .doc-footer {
        border-top: 1px solid #e3e8ec;
        color: #788791;
        font-size: 8px;
        line-height: 1.6;
        padding-top: 10px;
    }

    @media (max-width: 900px) {
        .invoice-paper {
            padding: 36px 34px 30px;
        }
    }

    @media (max-width: 700px) {
        .invoice-paper {
            min-width: 0;
            padding: 20px 14px 18px;
        }

        .invoice-doc-header {
            display: block;
        }

        .invoice-doc-header > div {
            display: block;
        }

        .invoice-doc-header > div:first-child {
            margin-bottom: 16px;
            width: auto;
        }

        .doc-meta {
            text-align: left;
        }

        .doc-meta > div {
            table-layout: fixed;
        }

        .doc-meta span {
            width: 35%;
        }

        .doc-meta strong {
            overflow-wrap: anywhere;
        }

        .party-grid {
            display: block;
            table-layout: auto;
        }

        .party-card,
        .party-card + .party-card {
            border-left: 0;
            display: block;
            width: auto;
        }

        .party-card + .party-card {
            border-top: 1px solid #d7e0e7;
        }

        .doc-payment {
            display: block;
        }

        .doc-payment > div {
            border-bottom: 1px solid #d7e0e7;
            border-right: 0;
            display: block;
            width: auto;
        }

        .doc-payment > div:last-child {
            border-bottom: 0;
        }
    }

    @media print {
        .invoice-paper {
            border: 0;
            border-radius: 0;
            box-shadow: none;
            margin: 0;
            max-width: none;
            padding: 0;
        }
    }
</style>
