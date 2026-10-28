<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>{{ $invoice->invoice_number }}</title>
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
        .stamp { font-weight: bold; font-size: 14px; color: #b00020; }
        .stamp.ok { color: #5E7D0B; }
        .terms { margin-top: 18px; font-size: 10px; white-space: pre-line; }
    </style>
</head>
<body>
    @php
        $snap = $invoice->customer_snapshot ?? [];
        $status = $invoice->status;
    @endphp

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
                <h1>FACTURE</h1>
                <div style="font-size: 14px;">{{ $invoice->invoice_number }}</div>
                @if ($status === \App\Enums\InvoiceStatus::Draft) <div class="stamp">BROUILLON</div> @endif
                @if ($status === \App\Enums\InvoiceStatus::Cancelled) <div class="stamp">ANNULÉE</div> @endif
                @if ($status === \App\Enums\InvoiceStatus::Paid) <div class="stamp ok">PAYÉE</div> @endif
                <div class="muted" style="margin-top: 6px;">
                    Émise le {{ $invoice->issue_date->format('d/m/Y') }}<br>
                    @if ($invoice->due_date) Échéance : {{ $invoice->due_date->format('d/m/Y') }}<br> @endif
                    @if ($invoice->order) Commande {{ $invoice->order->order_number }} @endif
                </div>
            </td>
        </tr>
    </table>

    <div class="box" style="margin-top: 16px; width: 50%;">
        <span class="muted">Facturé à</span><br>
        <strong>{{ $snap['name'] ?? '' }}</strong><br>
        @if (! empty($snap['address'])) {{ $snap['address'] }}<br> @endif
        @if (! empty($snap['city'])) {{ $snap['city'] }}<br> @endif
        @if (! empty($snap['phone'])) Tél. : {{ $snap['phone'] }} @endif
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
            @foreach ($invoice->items as $item)
                <tr>
                    <td>{{ $item->product_name_snapshot }}<br><span class="muted">{{ $item->sku_snapshot }}</span></td>
                    <td class="right">{{ \App\Support\Format::quantity($item->quantity) }} {{ $item->unit_snapshot }}</td>
                    <td class="right">{{ \App\Support\Format::money($item->unit_price) }}</td>
                    <td class="right">{{ (float) $item->discount_percent > 0 ? \App\Support\Format::quantity($item->discount_percent).' %' : '-' }}</td>
                    <td class="right">{{ \App\Support\Format::money($item->line_total) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals">
        <tr><td>Sous-total</td><td class="right">{{ \App\Support\Format::money($invoice->subtotal) }}</td></tr>
        @if ((float) $invoice->discount_total > 0)
            <tr><td>Remises</td><td class="right">- {{ \App\Support\Format::money($invoice->discount_total) }}</td></tr>
        @endif
        @if ((float) $invoice->tax_total > 0)
            <tr><td>Taxe</td><td class="right">{{ \App\Support\Format::money($invoice->tax_total) }}</td></tr>
        @endif
        @if ((float) $invoice->delivery_fee > 0)
            <tr><td>Frais annexes</td><td class="right">{{ \App\Support\Format::money($invoice->delivery_fee) }}</td></tr>
        @endif
        <tr class="grand"><td>Total</td><td class="right">{{ \App\Support\Format::money($invoice->total) }}</td></tr>
        @if ((float) $invoice->amount_paid > 0)
            <tr><td>Déjà payé</td><td class="right">{{ \App\Support\Format::money($invoice->amount_paid) }}</td></tr>
            <tr><td><strong>Reste à payer</strong></td><td class="right"><strong>{{ \App\Support\Format::money($invoice->balance_due) }}</strong></td></tr>
        @endif
    </table>

    @if ($invoice->payments->isNotEmpty())
        <p style="margin-top: 14px;"><strong>Paiements reçus</strong></p>
        <table style="font-size: 10px;">
            @foreach ($invoice->payments as $payment)
                <tr>
                    <td>{{ $payment->payment_date->format('d/m/Y') }}</td>
                    <td>{{ $payment->method->label() }}{{ $payment->reference ? ' - '.$payment->reference : '' }}</td>
                    <td class="right">{{ \App\Support\Format::money($payment->amount) }}</td>
                </tr>
            @endforeach
        </table>
    @endif

    @if ($invoice->terms)
        <div class="terms"><strong>Mentions et conditions</strong><br>{{ $invoice->terms }}</div>
    @endif
</body>
</html>
