@extends('layouts.app')

@section('title', 'Commande '.$order->order_number)

@section('content')
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <a href="{{ route('orders.index') }}" class="text-sm text-link hover:underline">← Retour aux commandes</a>

        <div class="flex flex-wrap gap-3">
            @can('createDelivery', $order)
                <a href="{{ route('deliveries.create', $order) }}"
                   class="rounded-lg bg-brand px-4 py-2 text-sm text-on-brand hover:bg-brand-hover">Créer un bon de livraison</a>
            @endcan

            @can('createInvoice', $order)
    <a href="{{ route('invoices.create', $order) }}"
       class="rounded-lg bg-brand px-4 py-2 text-sm text-on-brand hover:bg-brand-hover">Créer la facture</a>
@endcan

            @foreach ($order->status->manualTransitions() as $target)
                @can('transition', [$order, $target])
                    <form method="POST" action="{{ route('orders.transition', $order) }}"
                          data-confirm="{{ $target->actionLabel() }} ?">
                        @csrf
                        <input type="hidden" name="status" value="{{ $target->value }}">
                        <button class="rounded-lg px-4 py-2 text-sm text-ink {{ $target === \App\Enums\OrderStatus::Cancelled ? 'bg-red-600 hover:bg-red-700' : 'bg-soft hover:bg-soft-hover' }}">
                            {{ $target->actionLabel() }}
                        </button>
                    </form>
                @endcan
            @endforeach
        </div>
    </div>

    @if ($errors->has('status'))
        <div class="mb-4 rounded-lg bg-red-50 p-3 text-sm text-red-700">{{ $errors->first('status') }}</div>
    @endif

    <div class="rounded-xl bg-white p-6 shadow">
        <div class="mb-4 flex flex-wrap items-center gap-3">
            <h2 class="font-mono text-xl font-semibold">{{ $order->order_number }}</h2>
            <span class="rounded-full px-2 py-1 text-xs {{ $order->status->badgeClass() }}">{{ $order->status->label() }}</span>
        </div>

        <dl class="grid gap-4 text-sm md:grid-cols-4">
            <div>
                <dt class="text-muted">Client</dt>
                <dd>
                    @can('customers.view')
                        <a href="{{ route('customers.show', $order->customer) }}" class="text-link hover:underline">{{ $order->customer->name }}</a>
                    @else
                        {{ $order->customer->name }}
                    @endcan
                </dd>
            </div>
            <div><dt class="text-muted">Date</dt><dd>{{ $order->order_date->format('d/m/Y') }}</dd></div>
            <div>
                <dt class="text-muted">Devis d’origine</dt>
                <dd>
                    @if ($order->quote)
                        @can('quotes.view')
                            <a href="{{ route('quotes.show', $order->quote) }}" class="text-link hover:underline">{{ $order->quote->quote_number }}</a>
                        @else
                            {{ $order->quote->quote_number }}
                        @endcan
                    @else
                        -
                    @endif
                </dd>
            </div>
            <div><dt class="text-muted">Créée par</dt><dd>{{ $order->creator->name }}</dd></div>
        </dl>
    </div>

    <div class="mt-6 overflow-x-auto rounded-xl bg-white p-6 shadow">
        <h3 class="mb-3 text-lg font-semibold">Lignes</h3>
        <table class="min-w-full text-left text-sm">
            <thead class="bg-page text-xs uppercase text-muted">
                <tr>
                    <th class="px-4 py-3">Produit</th>
                    <th class="px-4 py-3 text-right">Commandée</th>
                    <th class="px-4 py-3 text-right">Préparée</th>
                    <th class="px-4 py-3 text-right">Livrée</th>
                    <th class="px-4 py-3 text-right">Restante</th>
                    @if ($stockByProduct !== null)
                        <th class="px-4 py-3 text-right">Stock actuel</th>
                    @endif
                    <th class="px-4 py-3 text-right">Total ligne</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-soft">
                @foreach ($order->items as $item)
                    @php
                        $prepared = (string) ($item->prepared_quantity ?? '0');
                        $left = bcsub((string) $item->quantity_ordered, (string) $item->quantity_delivered, 2);
                        $stock = $stockByProduct?->get($item->product_id);
                        $short = $stock !== null && bccomp((string) $stock, $left, 2) < 0 && bccomp($left, '0', 2) > 0;
                    @endphp
                    <tr>
                        <td class="px-4 py-3">
                            {{ $item->product_name_snapshot }}
                            <span class="block font-mono text-xs text-muted">{{ $item->sku_snapshot }}</span>
                        </td>
                        <td class="px-4 py-3 text-right">{{ \App\Support\Format::quantity($item->quantity_ordered) }} {{ $item->unit_snapshot }}</td>
                        <td class="px-4 py-3 text-right">{{ \App\Support\Format::quantity($prepared) }}</td>
                        <td class="px-4 py-3 text-right">{{ \App\Support\Format::quantity($item->quantity_delivered) }}</td>
                        <td class="px-4 py-3 text-right font-medium">{{ \App\Support\Format::quantity($left) }}</td>
                        @if ($stockByProduct !== null)
                            <td class="px-4 py-3 text-right {{ $short ? 'font-medium text-red-700' : '' }}">
                                {{ $stock !== null ? \App\Support\Format::quantity($stock) : '-' }}
                                @if ($short) <span class="block text-xs">insuffisant</span> @endif
                            </td>
                        @endif
                        <td class="px-4 py-3 text-right">{{ \App\Support\Format::money($item->line_total) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <dl class="ml-auto mt-4 w-full max-w-sm space-y-1 text-sm">
            <div class="flex justify-between"><dt>Sous-total</dt><dd>{{ \App\Support\Format::money($order->subtotal) }}</dd></div>
            @if ((float) $order->discount_total > 0)
                <div class="flex justify-between"><dt>Remises</dt><dd>- {{ \App\Support\Format::money($order->discount_total) }}</dd></div>
            @endif
            @if ((float) $order->tax_total > 0)
                <div class="flex justify-between"><dt>Taxe</dt><dd>{{ \App\Support\Format::money($order->tax_total) }}</dd></div>
            @endif
            @if ((float) $order->delivery_fee > 0)
                <div class="flex justify-between"><dt>Frais annexes</dt><dd>{{ \App\Support\Format::money($order->delivery_fee) }}</dd></div>
            @endif
            <div class="flex justify-between border-t pt-2 text-base font-semibold"><dt>Total</dt><dd>{{ \App\Support\Format::money($order->total) }}</dd></div>
        </dl>
    </div>

    @can('deliveries.view')
        <div class="mt-6 rounded-xl bg-white p-6 shadow">
            <h3 class="mb-3 text-lg font-semibold">Bons de livraison</h3>
            <table class="min-w-full text-left text-sm">
                <thead class="bg-page text-xs uppercase text-muted">
                    <tr>
                        <th class="px-4 py-3">Numéro</th>
                        <th class="px-4 py-3">Prévue le</th>
                        <th class="px-4 py-3">Livrée le</th>
                        <th class="px-4 py-3">Statut</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-soft">
                    @forelse ($order->deliveries as $delivery)
                        <tr>
                            <td class="px-4 py-3 font-mono text-xs">
                                <a href="{{ route('deliveries.show', $delivery) }}" class="text-link hover:underline">{{ $delivery->delivery_number }}</a>
                            </td>
                            <td class="px-4 py-3">{{ $delivery->planned_at?->format('d/m/Y H:i') ?? '-' }}</td>
                            <td class="px-4 py-3">{{ $delivery->delivered_at?->format('d/m/Y H:i') ?? '-' }}</td>
                            <td class="px-4 py-3">
                                <span class="rounded-full px-2 py-1 text-xs {{ $delivery->status->badgeClass() }}">{{ $delivery->status->label() }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-4 py-4 text-center text-muted">Aucun bon de livraison.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @endcan

    @can('invoices.view')
    <div class="mt-6 rounded-xl bg-white p-6 shadow">
        <h3 class="mb-3 text-lg font-semibold">Factures</h3>
        <ul class="space-y-2 text-sm">
            @forelse ($order->invoices as $invoice)
                <li class="flex items-center gap-3">
                    <a href="{{ route('invoices.show', $invoice) }}" class="font-mono text-link hover:underline">{{ $invoice->invoice_number }}</a>
                    <span class="rounded-full px-2 py-1 text-xs {{ $invoice->status->badgeClass() }}">{{ $invoice->status->label() }}</span>
                    <span>{{ \App\Support\Format::money($invoice->total) }}</span>
                </li>
            @empty
                <li class="text-muted">Aucune facture.</li>
            @endforelse
        </ul>
    </div>
@endcan

    <div class="mt-6 rounded-xl bg-white p-6 shadow">
        <h3 class="mb-3 text-lg font-semibold">Historique</h3>
        <ul class="space-y-2 text-sm">
            @foreach ($history as $entry)
                <li class="flex flex-wrap gap-x-3">
                    <span class="text-muted">{{ $entry->created_at->format('d/m/Y H:i') }}</span>
                    <span class="font-medium">{{ $entry->user?->name ?? 'Système' }}</span>
                    <span>
                        @if ($entry->action === 'order.created')
                            Commande créée
                        @elseif ($entry->action === 'order.status_changed')
                            Statut : {{ \App\Enums\OrderStatus::tryFrom($entry->old_values['status'] ?? '')?->label() }}
                            → {{ \App\Enums\OrderStatus::tryFrom($entry->new_values['status'] ?? '')?->label() }}
                            @if (! empty($entry->new_values['auto'])) <span class="text-muted">(automatique)</span> @endif
                        @else
                            {{ $entry->action }}
                        @endif
                    </span>
                </li>
            @endforeach
        </ul>
    </div>
@endsection
