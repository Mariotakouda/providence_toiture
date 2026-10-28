@php($isEdit = $customer !== null)

@include('partials.duplicate-warning', ['routePrefix' => 'customers'])

<div class="mb-4">
    <label for="customer_type" class="block text-sm font-medium">Type de client</label>
    <select id="customer_type" name="customer_type" required class="mt-1 w-full rounded-lg border border-line px-3 py-2 md:w-1/2">
        @foreach ($types as $type)
            <option value="{{ $type->value }}"
                    @selected(old('customer_type', $customer?->customer_type?->value ?? 'individual') === $type->value)>
                {{ $type->label() }}
            </option>
        @endforeach
    </select>
    @error('customer_type') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
</div>

@include('partials.contact-fields', ['entity' => $customer, 'phoneRequired' => true])

<div class="mt-6 flex gap-3">
    <button type="submit" class="rounded-lg bg-brand px-4 py-2 text-sm font-medium text-on-brand hover:bg-brand-hover">
        {{ $isEdit ? 'Enregistrer' : 'Créer le client' }}
    </button>
    <a href="{{ $isEdit ? route('customers.show', $customer) : route('customers.index') }}"
       class="rounded-lg border border-line px-4 py-2 text-sm hover:bg-page">Annuler</a>
</div>
