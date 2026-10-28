@extends('layouts.app')

@section('title', 'Produits')

@section('content')
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <form method="GET" class="flex w-full flex-wrap gap-2 sm:w-auto">
            <input type="text" name="q" value="{{ request('q') }}" placeholder="Référence, nom, catégorie, variante"
                   class="w-full rounded-lg border border-line px-3 py-2 text-sm sm:w-72">

            <select name="category_id" class="rounded-lg border border-line px-3 py-2 text-sm">
                <option value="">Toutes les catégories</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}" @selected((string) request('category_id') === (string) $category->id)>
                        {{ $category->name }}
                    </option>
                @endforeach
            </select>

            <select name="status" class="rounded-lg border border-line px-3 py-2 text-sm">
                <option value="active" @selected($status === 'active')>Actifs</option>
                <option value="archived" @selected($status === 'archived')>Archivés</option>
                <option value="all" @selected($status === 'all')>Tous</option>
            </select>

                    <select name="stock" class="rounded-lg border border-line px-3 py-2 text-sm">
            <option value="">Tout le stock</option>
            <option value="low" @selected(request('stock') === 'low')>Sous le seuil d’alerte</option>
        </select>

            <button class="rounded-lg bg-soft px-4 py-2 text-sm text-ink hover:bg-soft-hover">Filtrer</button>
        </form>

        @can('create', \App\Models\Product::class)
            <a href="{{ route('products.create') }}"
               class="rounded-lg bg-brand px-4 py-2 text-sm font-medium text-on-brand hover:bg-brand-hover">
                Nouveau produit
            </a>
        @endcan
    </div>

    <div class="overflow-x-auto rounded-xl bg-white shadow">
        <table class="min-w-full text-left text-sm">
            <thead class="bg-page text-xs uppercase text-muted">
                <tr>
                    <th class="px-4 py-3">Référence</th>
                    <th class="px-4 py-3">Nom</th>
                    <th class="px-4 py-3">Catégorie</th>
                    <th class="px-4 py-3">Unité</th>
                    <th class="px-4 py-3 text-right">Prix de vente</th>
                    @can('products.view_cost')
                        <th class="px-4 py-3 text-right">Coût d’achat</th>
                    @endcan
                    <th class="px-4 py-3 text-right">Stock</th>
                    <th class="px-4 py-3">Statut</th>
                    <th class="px-4 py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-soft">
                @forelse ($products as $product)
                    <tr>
                        <td class="px-4 py-3 font-mono text-xs">{{ $product->sku }}</td>
                        <td class="px-4 py-3 font-medium">
                            <a href="{{ route('products.show', $product) }}" class="hover:underline">{{ $product->name }}</a>
                            @if ($product->variants_count > 0)
                                <span class="ml-1 text-xs text-muted">({{ $product->variants_count }} variante(s))</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">{{ $product->category->name }}</td>
                        <td class="px-4 py-3">{{ $product->unit->label() }}</td>
                        <td class="px-4 py-3 text-right">{{ \App\Support\Format::money($product->sale_price) }}</td>
                        @can('products.view_cost')
                            <td class="px-4 py-3 text-right">{{ \App\Support\Format::money($product->purchase_price) }}</td>
                        @endcan
                        <td class="px-4 py-3 text-right">
                            {{ \App\Support\Format::quantity($product->stock_quantity) }}
                            @if ($product->isLowStock())
                                <span class="ml-1 rounded-full bg-orange-100 px-2 py-0.5 text-xs text-orange-700">Sous le seuil</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            @if ($product->is_active)
                                <span class="rounded-full bg-green-100 px-2 py-1 text-xs text-green-700">Actif</span>
                            @else
                                <span class="rounded-full bg-soft px-2 py-1 text-xs text-muted">Archivé</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex flex-wrap justify-end gap-x-3 gap-y-1">
                                <a href="{{ route('products.show', $product) }}" class="text-link hover:underline">Voir</a>
                                @can('update', $product)
                                    <a href="{{ route('products.edit', $product) }}" class="text-link hover:underline">Modifier</a>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="px-4 py-6 text-center text-muted">Aucun produit trouvé.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $products->links() }}</div>
@endsection
