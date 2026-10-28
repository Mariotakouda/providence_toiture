@extends('layouts.guest')

@section('content')
    <h1 class="mb-6 text-center text-xl font-bold">Nouveau mot de passe</h1>

    <form method="POST" action="{{ route('password.store') }}" class="space-y-4">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">

        <div>
            <label for="email" class="block text-sm font-medium">Adresse e-mail</label>
            {{-- L'adresse vient du lien reçu par e-mail : la modifier invaliderait la demande. --}}
            <input id="email" name="email" type="email" value="{{ old('email', $email) }}" required readonly autocomplete="username"
                   class="mt-1 w-full cursor-not-allowed rounded-lg border bg-soft px-3 py-2.5 text-muted outline-none {{ $errors->has('email') ? 'border-red-400' : 'border-line' }}">
            @error('email')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="password" class="block text-sm font-medium">Nouveau mot de passe</label>
            <input id="password" name="password" type="password" required autofocus autocomplete="new-password"
                   class="mt-1 w-full rounded-lg border px-3 py-2.5 outline-none transition focus:border-brand focus:ring-2 focus:ring-brand/40 {{ $errors->has('password') ? 'border-red-400' : 'border-line' }}">
            @error('password')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
            <p class="mt-1 text-xs text-muted">8 caractères minimum, avec lettres et chiffres.</p>
        </div>

        <div>
            <label for="password_confirmation" class="block text-sm font-medium">Confirmer le mot de passe</label>
            <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password"
                   class="mt-1 w-full rounded-lg border border-line px-3 py-2.5 outline-none transition focus:border-brand focus:ring-2 focus:ring-brand/40">
        </div>

        <button type="submit"
                class="w-full rounded-lg bg-brand py-3 font-semibold text-on-brand shadow-sm transition-colors hover:bg-brand-hover">
            Modifier le mot de passe
        </button>
    </form>
@endsection
