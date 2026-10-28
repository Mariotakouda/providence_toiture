@php($isEdit = $variant !== null)

<div class="grid gap-4 md:grid-cols-2">
    <div>
        <label for="variant_sku" class="block text-sm font-medium">Référence de la variante</label>
        <input id="variant_sku" name="variant_sku" type="text" value="{{ old('variant_sku', $variant?->variant_sku) }}" required
               class="mt-1 w-full rounded-lg border border-line px-3 py-2 uppercase">
        @error('variant_sku') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="color" class="block text-sm font-medium">Couleur</label>
        <input id="color" name="color" type="text" value="{{ old('color', $variant?->color) }}"
               class="mt-1 w-full rounded-lg border border-line px-3 py-2">
        @error('color') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="thickness" class="block text-sm font-medium">Épaisseur (mm)</label>
        <input id="thickness" name="thickness" type="number" step="0.01" min="0" value="{{ old('thickness', $variant?->thickness) }}"
               class="mt-1 w-full rounded-lg border border-line px-3 py-2">
        @error('thickness') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="length" class="block text-sm font-medium">Longueur (m)</label>
        <input id="length" name="length" type="number" step="0.01" min="0" value="{{ old('length', $variant?->length) }}"
               class="mt-1 w-full rounded-lg border border-line px-3 py-2">
        @error('length') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="width" class="block text-sm font-medium">Largeur utile (m)</label>
        <input id="width" name="width" type="number" step="0.01" min="0" value="{{ old('width', $variant?->width) }}"
               class="mt-1 w-full rounded-lg border border-line px-3 py-2">
        @error('width') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="sale_price_override" class="block text-sm font-medium">Prix de vente spécifique (facultatif)</label>
        <input id="sale_price_override" name="sale_price_override" type="number" step="0.01" min="0"
               value="{{ old('sale_price_override', $variant?->sale_price_override) }}"
               class="mt-1 w-full rounded-lg border border-line px-3 py-2">
        @error('sale_price_override') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        <p class="mt-1 text-xs text-muted">Laissez vide pour utiliser le prix du produit.</p>
    </div>
</div>

<div class="mt-4">
    <input type="hidden" name="is_active" value="0">
    <label class="inline-flex items-center gap-2 text-sm">
        <input type="checkbox" name="is_active" value="1" @checked((bool) old('is_active', $variant?->is_active ?? true))>
        Variante active
    </label>
</div>

<div class="mt-6 flex gap-3">
    <button type="submit" class="rounded-lg bg-brand px-4 py-2 text-sm font-medium text-on-brand hover:bg-brand-hover">
        {{ $isEdit ? 'Enregistrer' : 'Ajouter la variante' }}
    </button>
    <a href="{{ route('products.show', $product) }}" class="rounded-lg border border-line px-4 py-2 text-sm hover:bg-page">Annuler</a>
</div>
