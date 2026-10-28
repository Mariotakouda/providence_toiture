@extends('layouts.guest')

@section('content')
    <h1 class="mb-2 text-center text-xl font-bold">Mot de passe oublié</h1>
    <p class="mb-6 text-center text-sm text-muted">
        Saisissez votre adresse e-mail pour recevoir un lien de réinitialisation.
    </p>

    @if (session('status'))
        <div class="mb-4 rounded-lg bg-green-50 p-3 text-sm text-green-700">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
        @csrf

        <div>
            <label for="email" class="block text-sm font-medium">Adresse e-mail</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="email"
                   class="mt-1 w-full rounded-lg border px-3 py-2.5 outline-none transition focus:border-brand focus:ring-2 focus:ring-brand/40 {{ $errors->has('email') ? 'border-red-400' : 'border-line' }}">
            @error('email')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <button type="submit"
                class="w-full rounded-lg bg-brand py-3 font-semibold text-on-brand shadow-sm transition-colors hover:bg-brand-hover">
            Envoyer le lien
        </button>

        <p class="text-center text-sm">
            <a href="{{ route('login') }}" class="text-link hover:underline">Retour à la connexion</a>
        </p>
    </form>
@endsection
