<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>{{ $delivery->delivery_number }}</title>
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
        .sign td { height: 70px; vertical-align: top; border: 1px solid #ccc; padding: 6px; }
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
                @if ($company['company_email']) {{ $company['company_email'] }} @endif
            </td>
            <td class="right">
                <h1>BON DE LIVRAISON</h1>
                <div style="font-size: 14px;">{{ $delivery->delivery_number }}</div>
                <div class="muted" style="margin-top: 6px;">
                    Commande {{ $delivery->order->order_number }}<br>
                    @if ($delivery->planned_at) Prévue le {{ $delivery->planned_at->format('d/m/Y H:i') }}<br> @endif
                    @if ($delivery->driver_name) Livreur : {{ $delivery->driver_name }} @endif
                </div>
            </td>
        </tr>
    </table>

    <div class="box" style="margin-top: 16px; width: 55%;">
        <span class="muted">Destinataire</span><br>
        <strong>{{ $delivery->order->customer->name }}</strong><br>
        @if ($delivery->order->customer->address) {{ $delivery->order->customer->address }}<br> @endif
        @if ($delivery->order->customer->city) {{ $delivery->order->customer->city }}<br> @endif
        Tél. : {{ $delivery->order->customer->phone }}
    </div>

    <table class="lines">
        <thead>
            <tr>
                <th>Désignation</th>
                <th>Référence</th>
                <th class="right">Quantité livrée</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($delivery->items as $item)
                <tr>
                    <td>{{ $item->orderItem->product_name_snapshot }}</td>
                    <td>{{ $item->orderItem->sku_snapshot }}</td>
                    <td class="right">{{ \App\Support\Format::quantity($item->quantity_delivered) }} {{ $item->orderItem->unit_snapshot }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    @if ($delivery->notes)
        <p style="margin-top: 14px;"><strong>Observations :</strong> {{ $delivery->notes }}</p>
    @endif

    <table class="sign" style="margin-top: 24px;">
        <tr>
            <td style="width: 50%;">Remis par : {{ $delivery->driver_name ?? $delivery->creator->name }}<br><span class="muted">Date et signature</span></td>
            <td>
                Reçu par : {{ $delivery->receiver_name }}<br>
                <span class="muted">Nom, date et signature du réceptionnaire</span>
            </td>
        </tr>
    </table>
</body>
</html>
