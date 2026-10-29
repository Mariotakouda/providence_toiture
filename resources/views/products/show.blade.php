@extends('layouts.app')

@section('title', $product->name)

@section('content')
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <a href="{{ route('products.index') }}" class="text-sm text-link hover:underline">← Retour aux produits</a>

        <div class="flex gap-3">
            @can('update', $product)
                <a href="{{ route('products.edit', $product) }}"
                   class="rounded-lg border border-line bg-white px-4 py-2 text-sm hover:bg-page">Modifier</a>
            @endcan
            @can('archive', $product)
                <form method="POST" action="{{ route('products.toggle-status', $product) }}"
                      data-confirm="{{ $product->is_active ? 'Archiver' : 'Réactiver' }} ce produit ?">
                    @csrf
                    @method('PATCH')
                    <button class="rounded-lg px-4 py-2 text-sm text-on-brand {{ $product->is_active ? 'bg-red-600 hover:bg-red-700' : 'bg-brand hover:bg-brand-hover' }}">
                        {{ $product->is_active ? 'Archiver' : 'Réactiver' }}
                    </button>
                </form>
            @endcan
        </div>
    </div>

    <div class="rounded-xl bg-white p-6 shadow">
        <div class="mb-4 flex flex-wrap items-center gap-3">
            <h2 class="text-xl font-semibold">{{ $product->name }}</h2>
            @if ($product->is_active)
                <span class="rounded-full bg-green-100 px-2 py-1 text-xs text-green-700">Actif</span>
            @else
                <span class="rounded-full bg-soft px-2 py-1 text-xs text-muted">Archivé</span>
            @endif
        </div>

        <dl class="grid gap-4 text-sm md:grid-cols-3">
            <div><dt class="text-muted">Référence</dt><dd class="font-mono">{{ $product->sku }}</dd></div>
            <div><dt class="text-muted">Catégorie</dt><dd>{{ $product->category->name }}</dd></div>
            <div><dt class="text-muted">Unité</dt><dd>{{ $product->unit->label() }}</dd></div>
            <div><dt class="text-muted">Prix de vente</dt><dd>{{ \App\Support\Format::money($product->sale_price) }}</dd></div>
            @can('products.view_cost')
                <div><dt class="text-muted">Coût d’achat</dt><dd>{{ \App\Support\Format::money($product->purchase_price) }}</dd></div>
            @endcan
            <div><dt class="text-muted">Seuil d’alerte</dt><dd>{{ \App\Support\Format::quantity($product->min_stock) }}</dd></div>
            <div>
                <dt class="text-muted">Stock théorique</dt>
                <dd>
                    {{ \App\Support\Format::quantity($product->stock_quantity) }}
                    @if ($product->isLowStock())
                        <span class="ml-1 rounded-full bg-orange-100 px-2 py-0.5 text-xs text-orange-700">Sous le seuil</span>
                    @endif
                </dd>
            </div>
        </dl>

        @if ($product->description)
            <p class="mt-4 text-sm text-ink">{{ $product->description }}</p>
        @endif
    </div>

    <div class="mt-6 rounded-xl bg-white p-6 shadow">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <h3 class="text-lg font-semibold">Variantes</h3>
            @can('update', $product)
                <a href="{{ route('products.variants.create', $product) }}"
                   class="rounded-lg bg-brand px-3 py-2 text-sm font-medium text-on-brand hover:bg-brand-hover">
                    Ajouter une variante
                </a>
            @endcan
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="bg-page text-xs uppercase text-muted">
                    <tr>
                        <th class="px-4 py-3">Référence</th>
                        <th class="px-4 py-3">Caractéristiques</th>
                        <th class="px-4 py-3 text-right">Prix de vente</th>
                        <th class="px-4 py-3">Statut</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-soft">
                    @forelse ($product->variants as $variant)
                        <tr>
                            <td class="px-4 py-3 font-mono text-xs">{{ $variant->variant_sku }}</td>
                            <td class="px-4 py-3">{{ $variant->label() }}</td>
                            <td class="px-4 py-3 text-right">
                                @if ($variant->sale_price_override !== null)
                                    {{ \App\Support\Format::money($variant->sale_price_override) }}
                                @else
                                    <span class="text-muted">Prix du produit</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if ($variant->is_active)
                                    <span class="rounded-full bg-green-100 px-2 py-1 text-xs text-green-700">Active</span>
                                @else
                                    <span class="rounded-full bg-soft px-2 py-1 text-xs text-muted">Inactive</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @can('update', $product)
                                    <div class="flex flex-wrap justify-end gap-x-3 gap-y-1">
                                        <a href="{{ route('products.variants.edit', [$product, $variant]) }}" class="text-link hover:underline">Modifier</a>
                                        <form method="POST" action="{{ route('products.variants.toggle-status', [$product, $variant]) }}"
                                              data-confirm="{{ $variant->is_active ? 'Désactiver' : 'Activer' }} cette variante ?">
                                            @csrf
                                            @method('PATCH')
                                            <button class="{{ $variant->is_active ? 'text-red-600' : 'text-green-600' }} hover:underline">
                                                {{ $variant->is_active ? 'Désactiver' : 'Activer' }}
                                            </button>
                                        </form>
                                    </div>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-6 text-center text-muted">Aucune variante.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @can('stock.view')
    <div class="mt-6 rounded-xl bg-white p-6 shadow">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <h3 class="text-lg font-semibold">Derniers mouvements</h3>
            <div class="flex gap-3 text-sm">
                <a href="{{ route('stock-movements.index', ['product_id' => $product->id]) }}" class="text-link hover:underline">
                    Tout l’historique
                </a>
                @canany(['stock.move', 'stock.adjust'])
                    <a href="{{ route('stock-movements.create', ['product_id' => $product->id]) }}"
                       class="rounded-lg bg-brand px-3 py-1.5 font-medium text-on-brand hover:bg-brand-hover">
                        Nouveau mouvement
                    </a>
                @endcanany
            </div>
        </div>

        <table class="min-w-full text-left text-sm">
            <thead class="bg-page text-xs uppercase text-muted">
                <tr>
                    <th class="px-4 py-3">Date</th>
                    <th class="px-4 py-3">Type</th>
                    <th class="px-4 py-3 text-right">Quantité</th>
                    <th class="px-4 py-3">Motif</th>
                    <th class="px-4 py-3">Utilisateur</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-soft">
                @forelse ($movements as $movement)
                    <tr>
                        <td class="px-4 py-3">{{ $movement->occurred_at->format('d/m/Y H:i') }}</td>
                        <td class="px-4 py-3">{{ $movement->movement_type->label() }}</td>
                        <td class="px-4 py-3 text-right font-medium {{ $movement->movement_type->isIncoming() ? 'text-green-700' : 'text-red-700' }}">
                            {{ $movement->signedQuantity() }}
                        </td>
                        <td class="px-4 py-3">{{ $movement->reason ?? '-' }}</td>
                        <td class="px-4 py-3">{{ $movement->creator->name }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-6 text-center text-muted">Aucun mouvement.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endcan
@endsection
