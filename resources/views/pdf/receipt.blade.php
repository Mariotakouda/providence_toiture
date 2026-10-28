<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>{{ $payment->payment_number }}</title>
    <style>
        @page { margin: 24px 28px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #222; }
        h1 { font-size: 18px; margin: 0; }
        table { width: 100%; border-collapse: collapse; }
        .muted { color: #6B7280; }
        .right { text-align: right; }
        .box { border: 1px solid #D5DACE; border-left: 3px solid #96C11F; padding: 10px; margin-top: 14px; }
        .amount { font-size: 20px; font-weight: bold; }
        .cancelled { color: #b00020; font-weight: bold; font-size: 14px; }
        td { padding: 3px 0; vertical-align: top; }
    </style>
</head>
<body>
    <table>
        <tr>
            <td style="width: 55%;">
                @if ($logo)
                    <img src="{{ $logo }}" style="height: 46px; margin-bottom: 4px;"><br>
                @endif
                <strong>{{ $company['company_name'] }}</strong><br>
                @if ($company['company_phone']) Tél. : {{ $company['company_phone'] }}<br> @endif
                @if ($company['company_tax_id']) {{ $company['company_tax_id'] }} @endif
            </td>
            <td class="right">
                <h1>REÇU DE PAIEMENT</h1>
                <div>{{ $payment->payment_number }}</div>
                @if ($payment->status === \App\Enums\PaymentStatus::Cancelled)
                    <div class="cancelled">ANNULÉ</div>
                @endif
                <div class="muted">Le {{ $payment->payment_date->format('d/m/Y') }}</div>
            </td>
        </tr>
    </table>

    <div class="box">
        <table>
            <tr><td class="muted" style="width: 35%;">Reçu de</td><td>{{ $payment->invoice->customer_snapshot['name'] ?? '' }}</td></tr>
            <tr><td class="muted">Pour la facture</td><td>{{ $payment->invoice->invoice_number }}</td></tr>
            <tr><td class="muted">Mode de paiement</td><td>{{ $payment->method->label() }}{{ $payment->reference ? ' (réf. '.$payment->reference.')' : '' }}</td></tr>
            <tr><td class="muted">Montant reçu</td><td class="amount">{{ \App\Support\Format::money($payment->amount) }}</td></tr>
            <tr><td class="muted">Total de la facture</td><td>{{ \App\Support\Format::money($payment->invoice->total) }}</td></tr>
            <tr><td class="muted">Reste à payer après ce paiement</td><td>{{ \App\Support\Format::money($remaining) }}</td></tr>
        </table>
    </div>

    <p class="muted" style="margin-top: 14px;">Reçu par : {{ $payment->receiver->name }}</p>
</body>
</html>
