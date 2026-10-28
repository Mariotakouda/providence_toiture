@php($isEdit = $supplier !== null)

@include('partials.duplicate-warning', ['routePrefix' => 'suppliers'])

@include('partials.contact-fields', ['entity' => $supplier, 'phoneRequired' => false])

<div class="mt-6 flex gap-3">
    <button type="submit" class="rounded-lg bg-brand px-4 py-2 text-sm font-medium text-on-brand hover:bg-brand-hover">
        {{ $isEdit ? 'Enregistrer' : 'Créer le fournisseur' }}
    </button>
    <a href="{{ $isEdit ? route('suppliers.show', $supplier) : route('suppliers.index') }}"
       class="rounded-lg border border-line px-4 py-2 text-sm hover:bg-page">Annuler</a>
</div>
