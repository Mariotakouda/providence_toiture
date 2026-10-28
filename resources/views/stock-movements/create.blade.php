@extends('layouts.app')

@section('title', 'Nouveau mouvement de stock')

@section('content')
    <form method="POST" action="{{ route('stock-movements.store') }}" class="w-full max-w-6xl rounded-xl bg-white p-4 shadow sm:p-6">
        @csrf

        <div class="grid gap-4 md:grid-cols-2">
            <div class="md:col-span-2">
                <label for="product_id" class="block text-sm font-medium">Produit</label>
                <select id="product_id" name="product_id" required class="mt-1 w-full rounded-lg border border-line px-3 py-2">
                    <option value="">Choisir un produit</option>
                    @foreach ($products as $product)
                        <option value="{{ $product->id }}"
                                @selected((string) old('product_id', $selectedProduct) === (string) $product->id)>
                            {{ $product->sku }} : {{ $product->name }}
                            (stock : {{ \App\Support\Format::quantity($product->stock_quantity) }} {{ $product->unit->label() }})
                        </option>
                    @endforeach
                </select>
                @error('product_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="movement_type" class="block text-sm font-medium">Type de mouvement</label>
                <select id="movement_type" name="movement_type" required class="mt-1 w-full rounded-lg border border-line px-3 py-2">
                    <option value="">Choisir un type</option>
                    @foreach ($types as $type)
                        <option value="{{ $type->value }}" @selected(old('movement_type') === $type->value)>
                            {{ $type->label() }} ({{ $type->isIncoming() ? 'entrée' : 'sortie' }})
                        </option>
                    @endforeach
                </select>
                @error('movement_type') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="quantity" class="block text-sm font-medium">Quantité</label>
                <input id="quantity" name="quantity" type="number" step="0.01" min="0.01" value="{{ old('quantity') }}" required
                       class="mt-1 w-full rounded-lg border border-line px-3 py-2">
                @error('quantity') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            @if ($suppliers->isNotEmpty())
    <div>
        <label for="supplier_id" class="block text-sm font-medium">Fournisseur (achats)</label>
        <select id="supplier_id" name="supplier_id" class="mt-1 w-full rounded-lg border border-line px-3 py-2">
            <option value="">Aucun</option>
            @foreach ($suppliers as $supplier)
                <option value="{{ $supplier->id }}" @selected((string) old('supplier_id') === (string) $supplier->id)>
                    {{ $supplier->name }}
                </option>
            @endforeach
        </select>
        @error('supplier_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
@endif

            @can('products.view_cost')
                <div>
                    <label for="unit_cost" class="block text-sm font-medium">Coût unitaire (entrées, facultatif)</label>
                    <input id="unit_cost" name="unit_cost" type="number" step="0.01" min="0" value="{{ old('unit_cost') }}"
                           class="mt-1 w-full rounded-lg border border-line px-3 py-2">
                    @error('unit_cost') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            @endcan

            <div>
                <label for="occurred_at" class="block text-sm font-medium">Date et heure</label>
                <input id="occurred_at" name="occurred_at" type="datetime-local"
                       value="{{ old('occurred_at', now()->format('Y-m-d\TH:i')) }}"
                       class="mt-1 w-full rounded-lg border border-line px-3 py-2">
                @error('occurred_at') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="md:col-span-2">
                <label for="reason" class="block text-sm font-medium">Motif / document associé</label>
                <input id="reason" name="reason" type="text" maxlength="255" value="{{ old('reason') }}"
                       placeholder="Ex. : bon de livraison fournisseur n° 123, tôles abîmées au déchargement…"
                       class="mt-1 w-full rounded-lg border border-line px-3 py-2">
                @error('reason') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                <p class="mt-1 text-xs text-muted">Obligatoire pour la casse, l’usage interne et les ajustements.</p>
            </div>
        </div>

        <div class="mt-6 flex gap-3">
            <button type="submit" class="rounded-lg bg-brand px-4 py-2 text-sm font-medium text-on-brand hover:bg-brand-hover">
                Enregistrer le mouvement
            </button>
            <a href="{{ route('stock-movements.index') }}" class="rounded-lg border border-line px-4 py-2 text-sm hover:bg-page">Annuler</a>
        </div>
    </form>
@endsection
