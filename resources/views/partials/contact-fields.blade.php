<div class="grid gap-4 md:grid-cols-2">
    <div class="md:col-span-2">
        <label for="name" class="block text-sm font-medium">Nom ou raison sociale</label>
        <input id="name" name="name" type="text" value="{{ old('name', $entity?->name) }}" required
               class="mt-1 w-full rounded-lg border border-line px-3 py-2">
        @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="phone" class="block text-sm font-medium">Téléphone{{ $phoneRequired ? '' : ' (facultatif)' }}</label>
        <input id="phone" name="phone" type="text" value="{{ old('phone', $entity?->phone) }}" {{ $phoneRequired ? 'required' : '' }}
               class="mt-1 w-full rounded-lg border border-line px-3 py-2">
        @error('phone') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="email" class="block text-sm font-medium">E-mail (facultatif)</label>
        <input id="email" name="email" type="email" value="{{ old('email', $entity?->email) }}"
               class="mt-1 w-full rounded-lg border border-line px-3 py-2">
        @error('email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="address" class="block text-sm font-medium">Adresse</label>
        <input id="address" name="address" type="text" value="{{ old('address', $entity?->address) }}"
               class="mt-1 w-full rounded-lg border border-line px-3 py-2">
        @error('address') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="city" class="block text-sm font-medium">Ville</label>
        <input id="city" name="city" type="text" value="{{ old('city', $entity?->city) }}"
               class="mt-1 w-full rounded-lg border border-line px-3 py-2">
        @error('city') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

    <div class="md:col-span-2">
        <label for="notes" class="block text-sm font-medium">Notes</label>
        <textarea id="notes" name="notes" rows="3"
                  class="mt-1 w-full rounded-lg border border-line px-3 py-2">{{ old('notes', $entity?->notes) }}</textarea>
        @error('notes') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
</div>

<div class="mt-4">
    <input type="hidden" name="is_active" value="0">
    <label class="inline-flex items-center gap-2 text-sm">
        <input type="checkbox" name="is_active" value="1" @checked((bool) old('is_active', $entity?->is_active ?? true))>
        Actif
    </label>
</div>
