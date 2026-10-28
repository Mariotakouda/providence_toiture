@php($isEdit = $product !== null)

<div class="grid gap-4 md:grid-cols-2">
    <div>
        <label for="sku" class="block text-sm font-medium">Référence (SKU)</label>
        <input id="sku" name="sku" type="text" value="{{ old('sku', $product?->sku) }}" required
               class="mt-1 w-full rounded-lg border border-line px-3 py-2 uppercase">
        @error('sku') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="name" class="block text-sm font-medium">Nom</label>
        <input id="name" name="name" type="text" value="{{ old('name', $product?->name) }}" required
               class="mt-1 w-full rounded-lg border border-line px-3 py-2">
        @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="category_id" class="block text-sm font-medium">Catégorie</label>
        <select id="category_id" name="category_id" required class="mt-1 w-full rounded-lg border border-line px-3 py-2">
            <option value="">Choisir une catégorie</option>
            @foreach ($categories as $category)
                <option value="{{ $category->id }}" @selected((string) old('category_id', $product?->category_id) === (string) $category->id)>
                    {{ $category->name }}
                </option>
            @endforeach
        </select>
        @error('category_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="unit" class="block text-sm font-medium">Unité de vente</label>
        <select id="unit" name="unit" required class="mt-1 w-full rounded-lg border border-line px-3 py-2">
            <option value="">Choisir une unité</option>
            @foreach ($units as $unit)
                <option value="{{ $unit->value }}" @selected(old('unit', $product?->unit?->value) === $unit->value)>
                    {{ $unit->label() }}
                </option>
            @endforeach
        </select>
        @error('unit') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="sale_price" class="block text-sm font-medium">Prix de vente</label>
        <input id="sale_price" name="sale_price" type="number" step="0.01" min="0"
               value="{{ old('sale_price', $product?->sale_price) }}" required
               class="mt-1 w-full rounded-lg border border-line px-3 py-2">
        @error('sale_price') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

    @can('products.view_cost')
        <div>
            <label for="purchase_price" class="block text-sm font-medium">Coût d’achat (si connu)</label>
            <input id="purchase_price" name="purchase_price" type="number" step="0.01" min="0"
                   value="{{ old('purchase_price', $product?->purchase_price) }}"
                   class="mt-1 w-full rounded-lg border border-line px-3 py-2">
            @error('purchase_price') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
    @endcan

    <div>
        <label for="min_stock" class="block text-sm font-medium">Seuil d’alerte de stock</label>
        <input id="min_stock" name="min_stock" type="number" step="0.01" min="0"
               value="{{ old('min_stock', $product?->min_stock ?? 0) }}"
               class="mt-1 w-full rounded-lg border border-line px-3 py-2">
        @error('min_stock') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
</div>

<div class="mt-4">
    <label for="description" class="block text-sm font-medium">Description</label>
    <textarea id="description" name="description" rows="3"
              class="mt-1 w-full rounded-lg border border-line px-3 py-2">{{ old('description', $product?->description) }}</textarea>
    @error('description') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
</div>

<div class="mt-4">
    <input type="hidden" name="is_active" value="0">
    <label class="inline-flex items-center gap-2 text-sm">
        <input type="checkbox" name="is_active" value="1" @checked((bool) old('is_active', $product?->is_active ?? true))>
        Produit actif
    </label>
</div>

<div class="mt-6 flex gap-3">
    <button type="submit" class="rounded-lg bg-brand px-4 py-2 text-sm font-medium text-on-brand hover:bg-brand-hover">
        {{ $isEdit ? 'Enregistrer' : 'Créer le produit' }}
    </button>
    <a href="{{ $isEdit ? route('products.show', $product) : route('products.index') }}"
       class="rounded-lg border border-line px-4 py-2 text-sm hover:bg-page">Annuler</a>
</div>
