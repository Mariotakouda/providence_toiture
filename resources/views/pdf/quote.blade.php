<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>{{ $quote->quote_number }}</title>
    <style>
        @page { margin: 28px 32px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #222; }
        h1 { font-size: 20px; margin: 0; color: #222222; }
        table { width: 100%; border-collapse: collapse; }
        .header td { vertical-align: top; }
        .muted { color: #6B7280; }
        .right { text-align: right; }
        .box { border: 1px solid #D5DACE; border-left: 3px solid #96C11F; padding: 8px; }
        .lines { margin-top: 16px; }
        .lines th { background: #E8EDE1; color: #222222; border: 1px solid #D5DACE; padding: 6px; text-align: left; font-size: 10px; }
        .lines td { border: 1px solid #E3E8DC; padding: 6px; }
        .totals { width: 45%; margin-left: 55%; margin-top: 12px; }
        .totals td { padding: 3px 6px; }
        .totals .grand td { border-top: 2px solid #96C11F; font-weight: bold; font-size: 13px; }
        .terms { margin-top: 18px; font-size: 10px; white-space: pre-line; }
        .draft { color: #b00020; font-weight: bold; font-size: 14px; }
        .sign { margin-top: 28px; }
    </style>
</head>
<body>
    <table class="header">
        <tr>
            <td style="width: 55%;">
                @if ($logo)
                    <img src="{{ $logo }}" style="height: 54px; margin-bottom: 6px;"><br>
                @endif
                <strong>{{ $company['company_name'] }}</strong><br>
                @if ($company['company_address']) {{ $company['company_address'] }}<br> @endif
                @if ($company['company_city']) {{ $company['company_city'] }}<br> @endif
                @if ($company['company_phone']) Tél. : {{ $company['company_phone'] }}<br> @endif
                @if ($company['company_email']) {{ $company['company_email'] }}<br> @endif
                @if ($company['company_tax_id']) {{ $company['company_tax_id'] }} @endif
            </td>
            <td class="right">
                <h1>DEVIS</h1>
                <div style="font-size: 14px;">{{ $quote->quote_number }}</div>
                @if ($quote->status === \App\Enums\QuoteStatus::Draft)
                    <div class="draft">BROUILLON</div>
                @endif
                <div class="muted" style="margin-top: 6px;">
                    Émis le {{ $quote->issue_date->format('d/m/Y') }}<br>
                    Valable jusqu’au {{ $quote->valid_until->format('d/m/Y') }}
                </div>
            </td>
        </tr>
    </table>

    <div class="box" style="margin-top: 16px; width: 50%;">
        <span class="muted">Client</span><br>
        <strong>{{ $quote->customer->name }}</strong><br>
        @if ($quote->customer->address) {{ $quote->customer->address }}<br> @endif
        @if ($quote->customer->city) {{ $quote->customer->city }}<br> @endif
        Tél. : {{ $quote->customer->phone }}
    </div>

    <table class="lines">
        <thead>
            <tr>
                <th>Désignation</th>
                <th class="right">Qté</th>
                <th class="right">Prix unitaire</th>
                <th class="right">Remise</th>
                <th class="right">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($quote->items as $item)
                <tr>
                    <td>
                        {{ $item->product_name_snapshot }}<br>
                        <span class="muted">{{ $item->sku_snapshot }}</span>
                    </td>
                    <td class="right">{{ \App\Support\Format::quantity($item->quantity) }} {{ $item->unit_snapshot }}</td>
                    <td class="right">{{ \App\Support\Format::money($item->unit_price) }}</td>
                    <td class="right">{{ (float) $item->discount_percent > 0 ? \App\Support\Format::quantity($item->discount_percent).' %' : '-' }}</td>
                    <td class="right">{{ \App\Support\Format::money($item->line_total) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals">
        <tr><td>Sous-total</td><td class="right">{{ \App\Support\Format::money($quote->subtotal) }}</td></tr>
        @if ((float) $quote->discount_total > 0)
            <tr><td>Remises</td><td class="right">- {{ \App\Support\Format::money($quote->discount_total) }}</td></tr>
        @endif
        @if ((float) $quote->tax_total > 0)
            <tr><td>Taxe</td><td class="right">{{ \App\Support\Format::money($quote->tax_total) }}</td></tr>
        @endif
        @if ((float) $quote->delivery_fee > 0)
            <tr><td>Frais annexes</td><td class="right">{{ \App\Support\Format::money($quote->delivery_fee) }}</td></tr>
        @endif
        <tr class="grand"><td>Total</td><td class="right">{{ \App\Support\Format::money($quote->total) }}</td></tr>
    </table>

    @if ($quote->terms)
        <div class="terms"><strong>Conditions commerciales</strong><br>{{ $quote->terms }}</div>
    @endif

    <div class="sign">
        <table>
            <tr>
                <td style="width: 50%;" class="muted">Établi par : {{ $quote->creator->name }}</td>
                <td class="muted">Bon pour accord (date, nom et signature du client) :</td>
            </tr>
        </table>
    </div>
</body>
</html>
