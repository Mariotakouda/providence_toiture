@extends('layouts.app')

@section('title', 'Bon de livraison '.$delivery->delivery_number)

@section('content')
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <a href="{{ route('deliveries.index') }}" class="text-sm text-link hover:underline">← Retour aux livraisons</a>
        <a href="{{ route('deliveries.pdf', $delivery) }}" target="_blank"
           class="rounded-lg border border-line bg-white px-4 py-2 text-sm hover:bg-page">Exporter en PDF</a>
    </div>

    @if ($errors->has('status'))
        <div class="mb-4 rounded-lg bg-red-50 p-3 text-sm text-red-700">{{ $errors->first('status') }}</div>
    @endif

    <div class="rounded-xl bg-white p-6 shadow">
        <div class="mb-4 flex flex-wrap items-center gap-3">
            <h2 class="font-mono text-xl font-semibold">{{ $delivery->delivery_number }}</h2>
            <span class="rounded-full px-2 py-1 text-xs {{ $delivery->status->badgeClass() }}">{{ $delivery->status->label() }}</span>
        </div>

        <dl class="grid gap-4 text-sm md:grid-cols-4">
            <div>
                <dt class="text-muted">Commande</dt>
                <dd>
                    @can('orders.view')
                        <a href="{{ route('orders.show', $delivery->order) }}" class="font-mono text-link hover:underline">{{ $delivery->order->order_number }}</a>
                    @else
                        <span class="font-mono">{{ $delivery->order->order_number }}</span>
                    @endcan
                </dd>
            </div>
            <div><dt class="text-muted">Prévue le</dt><dd>{{ $delivery->planned_at?->format('d/m/Y H:i') ?? '-' }}</dd></div>
            <div><dt class="text-muted">Livreur</dt><dd>{{ $delivery->driver_name ?? '-' }}</dd></div>
            <div><dt class="text-muted">Créé par</dt><dd>{{ $delivery->creator->name }}</dd></div>
            @if ($delivery->delivered_at)
                <div><dt class="text-muted">Livrée le</dt><dd>{{ $delivery->delivered_at->format('d/m/Y H:i') }}</dd></div>
                <div><dt class="text-muted">Réceptionnaire</dt><dd>{{ $delivery->receiver_name ?? '-' }}</dd></div>
            @endif
        </dl>

        <div class="mt-4 rounded-lg bg-page p-4 text-sm">
            <p class="text-xs uppercase text-muted">Destinataire</p>
            <p class="font-medium">{{ $delivery->order->customer->name }}</p>
            <p>Tél. : {{ $delivery->order->customer->phone }}</p>
            <p>{{ $delivery->order->customer->address ?? '' }} {{ $delivery->order->customer->city ?? '' }}</p>
        </div>

        @if ($delivery->notes)
            <p class="mt-4 whitespace-pre-line text-sm text-ink">{{ $delivery->notes }}</p>
        @endif
    </div>

    <div class="mt-6 overflow-x-auto rounded-xl bg-white p-6 shadow">
        <table class="min-w-full text-left text-sm">
            <thead class="bg-page text-xs uppercase text-muted">
                <tr>
                    <th class="px-4 py-3">Produit</th>
                    <th class="px-4 py-3 text-right">Quantité</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-soft">
                @foreach ($delivery->items as $item)
                    <tr>
                        <td class="px-4 py-3">
                            {{ $item->orderItem->product_name_snapshot }}
                            <span class="block font-mono text-xs text-muted">{{ $item->orderItem->sku_snapshot }}</span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            {{ \App\Support\Format::quantity($item->quantity_delivered) }} {{ $item->orderItem->unit_snapshot }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @if ($delivery->status->allowedTransitions() !== [])
        <div class="mt-6 flex flex-wrap items-start gap-4">
            @can('transition', [$delivery, \App\Enums\DeliveryStatus::InTransit])
                @if ($delivery->status === \App\Enums\DeliveryStatus::Planned)
                    <form method="POST" action="{{ route('deliveries.transition', $delivery) }}">
                        @csrf
                        <input type="hidden" name="status" value="in_transit">
                        <button class="rounded-lg bg-soft px-4 py-2 text-sm text-ink hover:bg-soft-hover">Marquer en cours de livraison</button>
                    </form>
                @endif
            @endcan

            @can('transition', [$delivery, \App\Enums\DeliveryStatus::Delivered])
                <form method="POST" action="{{ route('deliveries.transition', $delivery) }}"
                      onsubmit="return confirm('Confirmer la livraison ? Le stock sera déduit.')"
                      class="rounded-xl bg-white p-4 shadow">
                    @csrf
                    <input type="hidden" name="status" value="delivered">
                    <p class="mb-2 text-sm font-medium">Confirmer la livraison</p>
                    <div class="flex flex-wrap items-end gap-3">
                        <div>
                            <label class="block text-xs text-muted" for="receiver_name">Nom du réceptionnaire (facultatif)</label>
                            <input id="receiver_name" name="receiver_name" type="text" maxlength="255" value="{{ old('receiver_name') }}"
                                   class="rounded-lg border border-line px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs text-muted" for="delivered_at">Date et heure (maintenant par défaut)</label>
                            <input id="delivered_at" name="delivered_at" type="datetime-local" value="{{ old('delivered_at') }}"
                                   class="rounded-lg border border-line px-3 py-2 text-sm">
                        </div>
                        <button class="rounded-lg bg-brand px-4 py-2 text-sm text-on-brand hover:bg-brand-hover">Marquer comme livrée</button>
                    </div>
                    @error('receiver_name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    @error('delivered_at') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </form>
            @endcan

            @can('transition', [$delivery, \App\Enums\DeliveryStatus::Cancelled])
                <form method="POST" action="{{ route('deliveries.transition', $delivery) }}"
                      onsubmit="return confirm('Annuler ce bon de livraison ?')">
                    @csrf
                    <input type="hidden" name="status" value="cancelled">
                    <button class="rounded-lg bg-red-600 px-4 py-2 text-sm text-white hover:bg-red-700">Annuler le bon</button>
                </form>
            @endcan
        </div>
    @endif
@endsection
