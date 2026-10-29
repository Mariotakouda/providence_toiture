@extends('layouts.app')

@section('title', 'Inventaire et rapprochement')

@section('content')
    <p class="mb-4 max-w-3xl text-sm text-muted">
        Saisissez la quantité réellement comptée. Les produits laissés vides ne sont pas touchés.
        Pour chaque écart, un ajustement est créé et journalisé avec le motif ci-dessous.
    </p>

    <form method="GET" class="mb-4 flex gap-2">
        <select name="category_id" class="rounded-lg border border-line px-3 py-2 text-sm">
            <option value="">Toutes les catégories</option>
            @foreach ($categories as $category)
                <option value="{{ $category->id }}" @selected((string) request('category_id') === (string) $category->id)>
                    {{ $category->name }}
                </option>
            @endforeach
        </select>
        <button class="rounded-lg bg-soft px-4 py-2 text-sm text-ink hover:bg-soft-hover">Filtrer</button>
    </form>

    @if ($errors->any())
        <div class="mb-4 rounded-lg bg-red-50 p-3 text-sm text-red-700">
            {{ $errors->first('inventory') ?: $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('stock.inventory.store') }}">
        @csrf

        <div class="mb-4 max-w-xl">
            <label for="reason" class="block text-sm font-medium">Motif de l’inventaire</label>
            <input id="reason" name="reason" type="text" maxlength="255" required
                   value="{{ old('reason', 'Inventaire physique du '.now()->format('d/m/Y')) }}"
                   class="mt-1 w-full rounded-lg border border-line px-3 py-2">
        </div>

        <div class="overflow-x-auto rounded-xl bg-white shadow">
            <table class="min-w-full text-left text-sm">
                <thead class="bg-page text-xs uppercase text-muted">
                    <tr>
                        <th class="px-4 py-3">Référence</th>
                        <th class="px-4 py-3">Produit</th>
                        <th class="px-4 py-3">Catégorie</th>
                        <th class="px-4 py-3 text-right">Stock système</th>
                        <th class="px-4 py-3 text-right">Quantité comptée</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-soft">
                    @forelse ($products as $product)
                        <tr>
                            <td class="px-4 py-3 font-mono text-xs">{{ $product->sku }}</td>
                            <td class="px-4 py-3 font-medium">{{ $product->name }}</td>
                            <td class="px-4 py-3">{{ $product->category->name }}</td>
                            <td class="px-4 py-3 text-right">
                                {{ \App\Support\Format::quantity($product->stock_quantity) }} {{ $product->unit->label() }}
                                <input type="hidden" name="expected[{{ $product->id }}]"
                                       value="{{ number_format((float) $product->stock_quantity, 2, '.', '') }}">
                            </td>
                            <td class="px-4 py-3 text-right">
                                <input type="number" step="0.01" min="0" name="counts[{{ $product->id }}]"
                                       value="{{ old('counts.'.$product->id) }}"
                                       class="w-32 rounded-lg border border-line px-2 py-1 text-right">
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-6 text-center text-muted">Aucun produit actif.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4 flex gap-3">
            <button type="submit"
                    data-confirm="Appliquer cet inventaire ? Un ajustement sera créé pour chaque écart."
                    class="rounded-lg bg-brand px-4 py-2 text-sm font-medium text-on-brand hover:bg-brand-hover">
                Appliquer l’inventaire
            </button>
            <a href="{{ route('stock-movements.index') }}" class="rounded-lg border border-line px-4 py-2 text-sm hover:bg-page">Annuler</a>
        </div>
    </form>
@endsection
