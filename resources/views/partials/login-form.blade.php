<div class="mb-8">
    <img src="{{ asset('images/logo.png') }}" alt="Providence Toiture" class="mb-6 h-14 w-auto">
    <h2 class="text-2xl font-bold">Connexion</h2>
    <p class="mt-1 text-sm text-neutral-500">Espace réservé au personnel de l’entreprise.</p>
</div>

@if (session('status'))
    <div class="mb-5 rounded-lg bg-green-50 p-3 text-sm text-green-800">{{ session('status') }}</div>
@endif

<form method="POST" action="{{ route('login') }}" class="space-y-5">
    @csrf

    <div>
        <label for="email" class="block text-sm font-medium">Adresse e-mail</label>
        <input id="email" name="email" type="email" value="{{ old('email') }}" required autocomplete="username"
               class="mt-1 w-full rounded-lg border px-3 py-2.5 outline-none transition focus:border-[#A3C108] focus:ring-2 focus:ring-[#A3C108]/40 {{ $errors->has('email') ? 'border-red-400' : 'border-neutral-300' }}">
        @error('email')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <div class="flex items-center justify-between">
            <label for="password" class="block text-sm font-medium">Mot de passe</label>
            <button type="button" data-toggle-password="password" class="text-xs text-neutral-500 hover:text-neutral-900">Afficher</button>
        </div>
        <input id="password" name="password" type="password" required autocomplete="current-password"
               class="mt-1 w-full rounded-lg border border-neutral-300 px-3 py-2.5 outline-none transition focus:border-[#A3C108] focus:ring-2 focus:ring-[#A3C108]/40">
        @error('password')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="flex items-center justify-between text-sm">
        <label class="flex items-center gap-2">
            <input type="checkbox" name="remember" class="rounded border-neutral-300 accent-[#8DA906]">
            Se souvenir de moi
        </label>
        <a href="{{ route('password.request') }}" class="text-brand-ink hover:underline">Mot de passe oublié ?</a>
    </div>

    <button type="submit" class="btn-brand w-full rounded-lg py-3 shadow-sm">Se connecter</button>
</form>

<p class="mt-8 text-center text-xs text-neutral-400">
    Pas de compte ? Les accès sont créés par l’administrateur de l’entreprise.
</p>
