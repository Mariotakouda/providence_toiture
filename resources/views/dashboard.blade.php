@extends('layouts.app')

@section('title', 'Tableau de bord')

@section('content')
    <div class="mb-6 flex items-center gap-4 rounded-xl border border-line bg-white p-5 shadow">
        <x-logo class="h-16 w-auto max-w-[8rem] shrink-0 object-contain"/>
        <div class="min-w-0">
            <p class="text-sm text-muted">Bienvenue,</p>
            <h2 class="truncate text-xl font-semibold text-ink">{{ auth()->user()->name }}</h2>
            <p class="truncate text-sm text-muted">{{ app(\App\Services\SettingService::class)->get('company_name', 'Providence Toiture') }}</p>
        </div>
    </div>

    <form method="GET" class="mb-2 flex flex-wrap items-end gap-2">
        @include('partials.period-filter')
        <button class="rounded-lg bg-soft px-4 py-2 text-sm text-ink hover:bg-soft-hover">Appliquer</button>
    </form>
    <p class="mb-6 text-sm text-muted">
        Période affichée : du {{ $from->format('d/m/Y') }} au {{ $to->format('d/m/Y') }}.
    </p>

    @if ($sales || $finance)
        <div class="mb-6 grid gap-4 md:grid-cols-3">
            @if ($sales)
                <div class="rounded-xl bg-white p-5 shadow">
                    <p class="text-xs uppercase text-muted">Chiffre d’affaires facturé</p>
                    <p class="mt-1 text-2xl font-semibold">{{ \App\Support\Format::money($sales['invoiced']) }}</p>
                    <p class="mt-1 text-xs text-muted">
                        {{ $sales['count'] }} facture(s) émise(s), taxes et frais compris, hors brouillons et annulées.
                    </p>
                </div>
            @endif

            @if ($finance)
                <div class="rounded-xl bg-white p-5 shadow">
                    <p class="text-xs uppercase text-muted">Encaissements</p>
                    <p class="mt-1 text-2xl font-semibold">{{ \App\Support\Format::money($finance['collected']) }}</p>
                    <p class="mt-1 text-xs text-muted">Paiements validés reçus sur la période, quelle que soit la date de la facture.</p>
                </div>

                @if ($finance['margin'])
                    <div class="rounded-xl bg-white p-5 shadow">
                        <p class="text-xs uppercase text-muted">Marge estimée</p>
                        <p class="mt-1 text-2xl font-semibold">{{ \App\Support\Format::money($finance['margin']['margin']) }}</p>
                        <p class="mt-1 text-xs text-muted">
                            Hors taxes, d’après le coût d’achat actuel du catalogue.
                            @if ($finance['margin']['missing'] > 0)
                                {{ $finance['margin']['missing'] }} ligne(s) sans coût exclue(s).
                            @endif
                        </p>
                    </div>
                @endif
            @endif
        </div>
    @endif

    <div class="grid gap-6 md:grid-cols-2">
        @if ($quotes)
            <div class="rounded-xl bg-white p-5 shadow">
                <h3 class="mb-2 font-semibold">Devis en attente</h3>
                <p class="text-sm">
                    <span class="text-xl font-semibold">{{ $quotes['sent_count'] }}</span> envoyé(s) sans réponse,
                    pour {{ \App\Support\Format::money($quotes['sent_total']) }}.
                </p>
                <p class="mt-1 text-sm text-muted">{{ $quotes['draft_count'] }} brouillon(s).</p>
                <a href="{{ route('quotes.index', ['status' => 'sent']) }}" class="mt-2 inline-block text-sm text-link hover:underline">Voir les devis envoyés</a>
            </div>
        @endif

        @if ($orders !== null)
            <div class="rounded-xl bg-white p-5 shadow">
                <h3 class="mb-2 font-semibold">Commandes en cours</h3>
                <ul class="space-y-1 text-sm">
                    @foreach ([\App\Enums\OrderStatus::ToConfirm, \App\Enums\OrderStatus::Confirmed, \App\Enums\OrderStatus::Preparing, \App\Enums\OrderStatus::PartiallyDelivered] as $status)
                        <li class="flex justify-between">
                            <a href="{{ route('orders.index', ['status' => $status->value]) }}" class="hover:underline">{{ $status->label() }}</a>
                            <span class="font-medium">{{ $orders[$status->value] ?? 0 }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if ($deliveries)
            <div class="rounded-xl bg-white p-5 shadow">
                <h3 class="mb-2 font-semibold">Livraisons à effectuer ({{ $deliveries['count'] }})</h3>
                <ul class="space-y-1 text-sm">
                    @forelse ($deliveries['next'] as $delivery)
                        <li class="flex justify-between gap-3">
                            <a href="{{ route('deliveries.show', $delivery) }}" class="font-mono text-xs text-link hover:underline">{{ $delivery->delivery_number }}</a>
                            <span class="truncate">{{ $delivery->order->customer->name }}</span>
                            <span class="whitespace-nowrap text-muted">{{ $delivery->planned_at?->format('d/m H:i') ?? 'à planifier' }}</span>
                        </li>
                    @empty
                        <li class="text-muted">Aucune livraison en attente.</li>
                    @endforelse
                </ul>
            </div>
        @endif

        @if ($receivables)
            <div class="rounded-xl bg-white p-5 shadow">
                <h3 class="mb-2 font-semibold">Factures impayées et soldes clients</h3>
                <p class="text-sm">
                    Reste à encaisser : <span class="font-semibold">{{ \App\Support\Format::money($receivables['outstanding']['due']) }}</span>
                    ({{ $receivables['outstanding']['count'] }} facture(s)).
                </p>
                <p class="text-sm {{ $receivables['outstanding']['overdue_count'] > 0 ? 'text-red-700' : 'text-muted' }}">
                    En retard : {{ \App\Support\Format::money($receivables['outstanding']['overdue_due']) }}
                    ({{ $receivables['outstanding']['overdue_count'] }}).
                    <a href="{{ route('invoices.index', ['overdue' => 1]) }}" class="text-link hover:underline">Voir</a>
                </p>
                <ul class="mt-3 space-y-1 text-sm">
                    @foreach ($receivables['balances'] as $balance)
                        <li class="flex justify-between gap-3">
                            <span class="truncate">{{ $balance->name }}</span>
                            <span class="font-medium">{{ \App\Support\Format::money($balance->due) }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if ($lowStock)
            <div class="rounded-xl bg-white p-5 shadow">
                <h3 class="mb-2 font-semibold">Produits sous le seuil d’alerte ({{ $lowStock['count'] }})</h3>
                <ul class="space-y-1 text-sm">
                    @forelse ($lowStock['items'] as $product)
                        <li class="flex justify-between gap-3">
                            <a href="{{ route('products.show', $product) }}" class="truncate hover:underline">{{ $product->name }}</a>
                            <span class="whitespace-nowrap">
                                <span class="font-medium text-orange-700">{{ \App\Support\Format::quantity($product->stock_quantity) }}</span>
                                / seuil {{ \App\Support\Format::quantity($product->min_stock) }}
                            </span>
                        </li>
                    @empty
                        <li class="text-muted">Aucun produit sous le seuil.</li>
                    @endforelse
                </ul>
            </div>
        @endif

        @if ($movements)
            <div class="rounded-xl bg-white p-5 shadow">
                <h3 class="mb-2 font-semibold">Mouvements de stock récents</h3>
                <ul class="space-y-1 text-sm">
                    @forelse ($movements as $movement)
                        <li class="flex justify-between gap-3">
                            <span class="truncate">{{ $movement->product->name }}</span>
                            <span class="whitespace-nowrap {{ $movement->movement_type->isIncoming() ? 'text-green-700' : 'text-red-700' }}">
                                {{ $movement->signedQuantity() }}
                            </span>
                            <span class="whitespace-nowrap text-muted">{{ $movement->occurred_at->format('d/m H:i') }}</span>
                        </li>
                    @empty
                        <li class="text-muted">Aucun mouvement.</li>
                    @endforelse
                </ul>
            </div>
        @endif
    </div>

    @if (! $sales && ! $finance && ! $quotes && $orders === null && ! $deliveries && ! $receivables && ! $lowStock)
        <p class="text-muted">Bienvenue, {{ auth()->user()->name }}. Utilisez le menu pour accéder à vos modules.</p>
    @endif
@endsection
