@php
    $isEdit = $user !== null;
@endphp

<div class="grid gap-4 md:grid-cols-2">
    <div>
        <label for="name" class="block text-sm font-medium">Nom complet</label>
        <input id="name" name="name" type="text" value="{{ old('name', $user?->name) }}" required
               class="mt-1 w-full rounded-lg border border-line px-3 py-2">
        @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="email" class="block text-sm font-medium">E-mail</label>
        <input id="email" name="email" type="email" value="{{ old('email', $user?->email) }}" required
               class="mt-1 w-full rounded-lg border border-line px-3 py-2">
        @error('email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="phone" class="block text-sm font-medium">Téléphone</label>
        <input id="phone" name="phone" type="text" value="{{ old('phone', $user?->phone) }}"
               class="mt-1 w-full rounded-lg border border-line px-3 py-2">
        @error('phone') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="role" class="block text-sm font-medium">Rôle</label>
        <select id="role" name="role" required class="mt-1 w-full rounded-lg border border-line px-3 py-2">
            <option value="">Choisir un rôle</option>
            @foreach ($roles as $role)
                <option value="{{ $role }}" @selected(old('role', $currentRole ?? null) === $role)>
                    {{ \App\Enums\RoleName::labelFor($role) }}
                </option>
            @endforeach
        </select>
        @error('role') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="password" class="block text-sm font-medium">
            {{ $isEdit ? 'Nouveau mot de passe (facultatif)' : 'Mot de passe' }}
        </label>
        <input id="password" name="password" type="password" {{ $isEdit ? '' : 'required' }}
               class="mt-1 w-full rounded-lg border border-line px-3 py-2">
        @error('password') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        <p class="mt-1 text-xs text-muted">8 caractères minimum, avec lettres et chiffres.</p>
    </div>

    <div>
        <label for="password_confirmation" class="block text-sm font-medium">Confirmer le mot de passe</label>
        <input id="password_confirmation" name="password_confirmation" type="password" {{ $isEdit ? '' : 'required' }}
               class="mt-1 w-full rounded-lg border border-line px-3 py-2">
    </div>
</div>

<div class="mt-4">
    <input type="hidden" name="is_active" value="0">
    <label class="inline-flex items-center gap-2 text-sm">
        <input type="checkbox" name="is_active" value="1" @checked((bool) old('is_active', $user?->is_active ?? true))>
        Compte actif
    </label>
</div>

<div class="mt-6 flex gap-3">
    <button type="submit" class="rounded-lg bg-brand px-4 py-2 text-sm font-medium text-on-brand hover:bg-brand-hover">
        {{ $isEdit ? 'Enregistrer' : 'Créer l’utilisateur' }}
    </button>
    <a href="{{ route('users.index') }}" class="rounded-lg border border-line px-4 py-2 text-sm hover:bg-page">
        Annuler
    </a>
</div>
