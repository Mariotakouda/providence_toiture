@php
    $settings = app(\App\Services\SettingService::class);
    $company = $settings->get('company_name', 'Providence Toiture');
    $phone = $settings->get('company_phone');
    $waBase = \App\Support\WhatsApp::link($settings->get('company_whatsapp'));
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $category->name }} – {{ $company }}</title>
    <meta name="description" content="{{ $company }} : tôles, bac acier, étanchéité et portes de sécurité. Demandez votre devis en ligne.">
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style nonce="{{ Vite::cspNonce() }}">
        body { font-family: 'Barlow', 'Segoe UI', Arial, sans-serif; }
        h1, h2, h3, .display { font-family: 'Barlow Condensed', 'Arial Narrow', Arial, sans-serif; }
        .g-hero { position: relative; isolation: isolate; overflow: hidden; background: #181818; color: #fff; }
        .g-hero::before { content: ''; position: absolute; inset: 0; z-index: -2; background: var(--bg, none) center / cover no-repeat; }
        .g-hero::after { content: ''; position: absolute; inset: 0; z-index: -1; background: linear-gradient(100deg, rgba(0,0,0,.78), rgba(0,0,0,.45)); }
        .g-hero h1 { color: #fff; }
        @keyframes rise { from { opacity: 0; transform: translateY(22px); } to { opacity: 1; transform: none; } }
        .pcard { animation: rise .6s cubic-bezier(.2,.7,.2,1) both; }
        .pcard:nth-child(3n+2) { animation-delay: .08s; } .pcard:nth-child(3n) { animation-delay: .16s; }
        .pcard img { transition: transform .5s cubic-bezier(.2,.7,.2,1); }
        .pcard:hover img { transform: scale(1.06); }
        @media (prefers-reduced-motion: reduce) { .pcard { animation: none; } .pcard img { transition: none; } }
    </style>
</head>
<body class="bg-white text-[#181818] antialiased">
<header class="sticky top-0 z-30 border-b-4 border-[#A3C108] bg-white">
    <div class="mx-auto flex max-w-6xl items-center justify-between gap-2 px-4 py-2.5 sm:px-5 sm:py-3">
        <a href="{{ route('home') }}" aria-label="Accueil"><x-logo class="h-8 w-auto sm:h-11"/></a>
        <nav class="flex items-center gap-2 whitespace-nowrap text-[0.8rem] sm:gap-5 sm:text-sm">
            <a href="{{ route('home') }}#produits"
               class="rounded-full border border-neutral-300 px-3 py-1.5 font-medium hover:border-[#A3C108] sm:border-0 sm:px-0 sm:py-0 sm:hover:text-[#5C7000]">← <span class="sm:hidden">Gammes</span><span class="hidden sm:inline">Toutes les gammes</span></a>
            <a href="{{ route('home') }}#demande"
               class="rounded-full bg-[#A3C108] px-3.5 py-1.5 font-semibold text-[#181818] hover:brightness-95 sm:px-4 sm:py-2"><span class="sm:hidden">Devis</span><span class="hidden sm:inline">Demander un devis</span></a>
        </nav>
    </div>
</header>

<section class="g-hero" @if ($category->image) style="--bg: url('{{ asset('storage/'.$category->image) }}')" @endif>
    <div class="mx-auto max-w-6xl px-4 py-10 sm:px-5 sm:py-20">
        <p class="display text-lg font-semibold uppercase tracking-[0.18em] text-[#A3C108]">Nos gammes</p>
        <h1 class="mt-2 text-4xl font-bold uppercase leading-none sm:text-6xl">{{ $category->name }}</h1>
        @if ($category->description)
            <p class="mt-4 max-w-2xl text-lg text-neutral-200">{{ $category->description }}</p>
        @endif
    </div>
</section>

<nav class="border-b border-neutral-200 bg-neutral-50" aria-label="Autres gammes">
    <div class="mx-auto flex max-w-6xl flex-wrap gap-1.5 px-4 py-3 text-xs sm:gap-2 sm:px-5 sm:text-sm">
        @foreach ($gammes as $g)
            <a href="{{ route('gammes.show', $g) }}"
               class="whitespace-nowrap rounded-full border px-3 py-1 font-medium sm:px-4 sm:py-1.5 {{ $g->id === $category->id ? 'border-[#181818] bg-[#181818] text-white' : 'border-neutral-300 bg-white hover:border-[#A3C108]' }}">{{ $g->name }}</a>
        @endforeach
    </div>
</nav>

<main class="mx-auto max-w-6xl px-4 py-8 sm:px-5 sm:py-12">
    @forelse ($products as $p)
        @if ($loop->first)<div class="grid grid-cols-2 gap-3 sm:gap-6 lg:grid-cols-3">@endif
        <article class="pcard flex flex-col overflow-hidden rounded-xl border border-neutral-200 bg-white shadow-sm">
            <div class="aspect-[4/3] overflow-hidden bg-neutral-100">
                @if ($p->image)
                    <img src="{{ asset('storage/'.$p->image) }}" alt="{{ $p->name }}" loading="lazy" class="h-full w-full object-cover">
                @else
                    <div class="flex h-full items-center justify-center bg-[#181818]">
                        <svg viewBox="0 0 24 24" class="h-14 w-14" fill="none" stroke="#A3C108" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 15 L12 6 L21 15"/></svg>
                    </div>
                @endif
            </div>
            <div class="flex flex-1 flex-col p-3 sm:p-5">
                <h2 class="text-lg font-bold uppercase leading-tight sm:text-2xl">{{ $p->name }}</h2>
                <p class="mt-1 text-[0.7rem] text-neutral-500 sm:text-xs">Réf. {{ $p->sku }} · Vendu au/à : {{ $p->unit->label() }}</p>
                @if ($p->description)
                    <p class="mt-2 line-clamp-2 text-xs text-neutral-600 sm:mt-3 sm:line-clamp-3 sm:text-sm">{{ $p->description }}</p>
                @endif
                <p class="mt-2 text-xs font-medium text-[#5C7000] sm:mt-3 sm:text-sm">Prix sur devis</p>
                <div class="mt-auto flex flex-col gap-2 pt-3 sm:flex-row sm:flex-wrap sm:pt-4">
                    <a href="{{ route('home', ['produit' => $p->id]) }}#demande"
                       class="rounded-lg bg-[#A3C108] px-3 py-2 text-center text-xs font-semibold text-[#181818] hover:brightness-95 sm:px-4 sm:text-sm"><span class="sm:hidden">Ajouter</span><span class="hidden sm:inline">Ajouter à ma demande</span></a>
                    @if ($waLink = \App\Support\WhatsApp::link($settings->get('company_whatsapp'), 'Bonjour, je suis intéressé par : '.$p->name.' ('.$p->sku.'). Pouvez-vous m’envoyer un devis ?'))
                        <a href="{{ $waLink }}" target="_blank" rel="noopener"
                           class="rounded-lg border border-neutral-300 px-3 py-2 text-center text-xs font-medium hover:border-[#25D366] hover:bg-[#25D366]/10 sm:px-4 sm:text-sm">WhatsApp</a>
                    @endif
                </div>
            </div>
        </article>
        @if ($loop->last)</div>@endif
    @empty
        <div class="rounded-xl border border-dashed border-neutral-300 p-10 text-center">
            <p class="text-lg font-semibold">Les produits de cette gamme arrivent bientôt.</p>
            <p class="mt-2 text-neutral-600">Demandez-nous un devis, nous vous répondons rapidement.</p>
            <a href="{{ route('home') }}#demande" class="mt-5 inline-block rounded-full bg-[#A3C108] px-6 py-3 text-sm font-semibold text-[#181818]">Demander un devis</a>
        </div>
    @endforelse
</main>

<footer class="bg-[#181818] text-neutral-300">
    <div class="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-3 px-5 py-6 text-sm">
        <span>© {{ date('Y') }} {{ $company }}. Tous droits réservés.</span>
        <span class="flex flex-wrap gap-x-5 gap-y-1">
            @if (filled($phone))<a href="tel:{{ preg_replace('/[^\d+]/', '', $phone) }}" class="hover:text-[#A3C108]">{{ $phone }}</a>@endif
            @if ($waBase)<a href="{{ $waBase }}" target="_blank" rel="noopener" class="hover:text-[#25D366]">WhatsApp</a>@endif
        </span>
    </div>
</footer>
</body>
</html>
