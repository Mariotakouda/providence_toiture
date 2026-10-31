<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>@hasSection('title')@yield('title') - @endif{{ config('app.name') }}</title>
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style nonce="{{ Vite::cspNonce() }}">
        .nav-panel { display: grid; grid-template-rows: 0fr; }
        .nav-group[data-open="true"] > .nav-panel { grid-template-rows: 1fr; }
        .nav-panel > div { min-height: 0; overflow: hidden; }
        .nav-chevron { transition: transform .2s ease; }
        .nav-group[data-open="true"] > button .nav-chevron { transform: rotate(90deg); }
        .nav-ready .nav-panel { transition: grid-template-rows .22s ease; }
        .nav-scroll { scrollbar-width: thin; scrollbar-color: #444444 transparent; }
        @media (prefers-reduced-motion: reduce) {
            .nav-ready .nav-panel, .nav-chevron { transition: none; }
        }
    </style>
</head>
<body class="h-dvh overflow-hidden bg-page text-ink antialiased">
@php
    $user = auth()->user();
    $initials = \Illuminate\Support\Str::of($user->name)->explode(' ')->filter()
        ->map(fn ($w) => \Illuminate\Support\Str::substr($w, 0, 1))->take(2)->implode('');
    $roleLabel = \App\Enums\RoleName::labelFor($user->roles->first()?->name);
    $companyName = app(\App\Services\SettingService::class)->get('company_name', 'Providence Toiture');
    $onAlerts = request()->routeIs('products.index') && request('stock') === 'low';
    $newRequests = $user->can('requests.view') ? \App\Models\OnlineRequest::where('status', 'new')->count() : 0;
@endphp

<div class="flex h-full">

    <div id="backdrop" class="fixed inset-0 z-30 hidden bg-sidebar/60 backdrop-blur-sm lg:hidden"></div>

    <aside id="sidebar"
           class="fixed inset-y-0 left-0 z-40 flex w-64 -translate-x-full flex-col bg-sidebar text-slate-200 transition-transform duration-200 lg:static lg:z-auto lg:translate-x-0">

        <div class="shrink-0 px-4 pb-2 pt-4">
            <a href="{{ route('dashboard') }}" class="flex flex-col items-center gap-1 rounded-xl px-3 py-2 transition hover:bg-white/5">
                <x-logo :light="true" class="h-10 w-auto max-w-full object-contain"/>
                <p class="truncate text-[0.7rem] font-medium tracking-wide text-slate-400">{{ $companyName }}</p>
            </a>
        </div>

        <nav class="nav-scroll flex-1 space-y-1 overflow-y-auto px-3 pb-4">
            <x-nav-link :href="route('dashboard')" match="dashboard" icon="dashboard">Tableau de bord</x-nav-link>

            @canany(['quotes.view', 'orders.view', 'requests.view'])
                <x-nav-group title="Ventes" icon="sales" :routes="['quotes.*', 'orders.*', 'online-requests.*']">                    @can('quotes.view')
                        <x-nav-link :href="route('quotes.index')" match="quotes.*">Devis</x-nav-link>
                    @endcan
                    @can('orders.view')
                        <x-nav-link :href="route('orders.index')" match="orders.*">Commandes</x-nav-link>
                    @endcan

                    @can('requests.view')
                    <x-nav-link :href="route('online-requests.index')" match="online-requests.*">
                        Demandes en ligne
                        @if ($newRequests > 0)
                            <span class="ml-2 inline-flex min-w-5 items-center justify-center rounded-full bg-brand px-1.5 text-xs font-semibold text-on-brand">{{ $newRequests }}</span>
                        @endif
                    </x-nav-link>
                @endcan
                </x-nav-group>
            @endcanany

            @canany(['customers.view', 'suppliers.view'])
                <x-nav-group title="Clients et fournisseurs" icon="users" :routes="['customers.*', 'suppliers.*']">
                    @can('customers.view')
                        <x-nav-link :href="route('customers.index')" match="customers.*">Clients</x-nav-link>
                    @endcan
                    @can('suppliers.view')
                        <x-nav-link :href="route('suppliers.index')" match="suppliers.*">Fournisseurs</x-nav-link>
                    @endcan
                </x-nav-group>
            @endcanany

            @can('products.view')
                <x-nav-group title="Catalogue" icon="catalog"
                             :active="request()->routeIs('products.*', 'categories.*') && ! $onAlerts">
                    <x-nav-link :href="route('products.index')" :active="request()->routeIs('products.*') && ! $onAlerts">Produits</x-nav-link>
                    <x-nav-link :href="route('categories.index')" match="categories.*">Catégories</x-nav-link>
                </x-nav-group>
            @endcan

            @canany(['stock.view', 'stock.adjust'])
                <x-nav-group title="Stock" icon="stock"
                             :active="request()->routeIs('stock-movements.*', 'stock.*') || $onAlerts">
                    @can('stock.view')
                        <x-nav-link :href="route('stock-movements.index')" match="stock-movements.*">Mouvements</x-nav-link>
                        <x-nav-link :href="route('products.index', ['stock' => 'low'])" :active="$onAlerts">Alertes de stock</x-nav-link>
                    @endcan
                    @can('stock.adjust')
                        <x-nav-link :href="route('stock.inventory.create')" match="stock.inventory.*">Inventaire</x-nav-link>
                    @endcan
                </x-nav-group>
            @endcanany

            @can('deliveries.view')
                <x-nav-group title="Logistique" icon="truck" :routes="['deliveries.*']">
                    <x-nav-link :href="route('deliveries.index')" match="deliveries.*">Livraisons</x-nav-link>
                </x-nav-group>
            @endcan

            @canany(['invoices.view', 'payments.view'])
                <x-nav-group title="Facturation" icon="invoice" :routes="['invoices.*', 'payments.*']">
                    @can('invoices.view')
                        <x-nav-link :href="route('invoices.index')" match="invoices.*">Factures</x-nav-link>
                    @endcan
                    @can('payments.view')
                        <x-nav-link :href="route('payments.index')" match="payments.*">Paiements</x-nav-link>
                    @endcan
                </x-nav-group>
            @endcanany

            @canany(['reports.sales', 'reports.financial'])
                <x-nav-group title="Rapports" icon="reports" :routes="['reports.*']">
                    @can('reports.sales')
                        <x-nav-link :href="route('reports.sales')" match="reports.sales*">Ventes</x-nav-link>
                    @endcan
                    @can('reports.financial')
                        <x-nav-link :href="route('reports.financial')" match="reports.financial*">Financier</x-nav-link>
                    @endcan
                </x-nav-group>
            @endcanany

            @canany(['users.manage', 'settings.manage', 'audit.view'])
                <x-nav-group title="Administration" icon="admin" :routes="['users.*', 'roles.*', 'settings.*', 'audit-logs.*']">
                    @can('users.manage')
                        <x-nav-link :href="route('users.index')" match="users.*">Utilisateurs</x-nav-link>
                        <x-nav-link :href="route('roles.index')" match="roles.*">Rôles et permissions</x-nav-link>
                    @endcan
                    @can('settings.manage')
                        <x-nav-link :href="route('settings.edit')" match="settings.*">Paramètres</x-nav-link>
                    @endcan
                    @can('audit.view')
                        <x-nav-link :href="route('audit-logs.index')" match="audit-logs.*">Journal d’audit</x-nav-link>
                    @endcan
                </x-nav-group>
            @endcanany
        </nav>

        <div class="shrink-0 border-t border-white/10 p-3">
            <div class="flex items-center gap-3 rounded-lg px-2 py-1.5">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-brand text-sm font-semibold text-on-brand">
                    {{ $initials }}
                </div>
                <div class="min-w-0 flex-1 leading-tight">
                    <p class="truncate text-sm font-medium text-white">{{ $user->name }}</p>
                    <p class="truncate text-xs text-slate-400">{{ $roleLabel }}</p>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" title="Déconnexion" aria-label="Déconnexion"
                            class="rounded-md p-2 text-slate-400 transition-colors hover:bg-white/10 hover:text-red-400">
                        <x-icon name="logout" class="h-5 w-5"/>
                    </button>
                </form>
            </div>
        </div>
    </aside>

    <div class="flex min-w-0 flex-1 flex-col">
        <header class="flex shrink-0 items-center gap-3 border-b border-line bg-white px-4 py-3 sm:px-6">
            <button id="menu-toggle" type="button" aria-label="Ouvrir le menu"
                    class="rounded-md p-1.5 text-ink hover:bg-soft lg:hidden">
                <x-icon name="menu" class="h-6 w-6"/>
            </button>
            <h1 class="truncate text-lg font-semibold text-ink">@yield('title')</h1>
            {{-- Le logo est déjà dans le menu sur ordinateur : on ne l'affiche ici que sur mobile. --}}
            <x-logo class="ml-auto h-8 w-auto object-contain lg:hidden"/>
        </header>

        <main class="flex-1 overflow-y-auto p-3 sm:p-5 lg:p-8">
            <div class="mx-auto w-full max-w-[100rem]">
                @include('partials.flash')
                @include('partials.form-errors')
                @yield('content')
            </div>
        </main>
    </div>
</div>

<script nonce="{{ Vite::cspNonce() }}">
(function () {
    // Mode accordéon : un seul groupe ouvert à la fois, mémorisé d'une page à l'autre.
    const KEY = 'pm.nav.current';
    const groups = Array.from(document.querySelectorAll('[data-nav-group]'));

    let saved = null;
    try { saved = localStorage.getItem(KEY) || null; } catch (e) {}

    function save(id) {
        try { localStorage.setItem(KEY, id || ''); } catch (e) {}
    }

    function apply(group, isOpen) {
        group.dataset.open = isOpen ? 'true' : 'false';
        group.querySelector('[data-nav-toggle]').setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        const panel = group.querySelector('.nav-panel');
        if ('inert' in panel) panel.inert = !isOpen; // les liens d'un groupe fermé ne sont plus focalisables
    }

    // À l'ouverture de la page : le groupe de la page courante, sinon le dernier groupe ouvert.
    const activeGroup = groups.find((g) => g.dataset.active === 'true');
    let current = activeGroup ? activeGroup.dataset.navGroup : saved;

    groups.forEach((group) => apply(group, group.dataset.navGroup === current));
    save(current);

    groups.forEach((group) => {
        const id = group.dataset.navGroup;

        group.querySelector('[data-nav-toggle]').addEventListener('click', () => {
            const isOpen = group.dataset.open !== 'true';

            if (isOpen) {
                groups.forEach((other) => { if (other !== group) apply(other, false); });
                current = id;
            } else {
                current = null;
            }

            apply(group, isOpen);
            save(current);
        });
    });

    // Active les animations seulement après le premier affichage (pas de saut au chargement).
    requestAnimationFrame(() => requestAnimationFrame(() => document.body.classList.add('nav-ready')));

    document.querySelector('[aria-current="page"]')?.scrollIntoView({ block: 'nearest' });

    // Tiroir mobile
    const sidebar = document.getElementById('sidebar');
    const backdrop = document.getElementById('backdrop');
    const toggle = document.getElementById('menu-toggle');

    function setDrawer(isOpen) {
        sidebar.classList.toggle('-translate-x-full', !isOpen);
        backdrop.classList.toggle('hidden', !isOpen);
    }

    toggle.addEventListener('click', () => setDrawer(sidebar.classList.contains('-translate-x-full')));
    backdrop.addEventListener('click', () => setDrawer(false));
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape') setDrawer(false); });
})();
</script>
<script nonce="{{ Vite::cspNonce() }}">
(function () {
    // Smartphone : donne à chaque cellule le libellé de sa colonne (affiché en carte, voir app.css).
    var main = document.querySelector('main');
    if (!main) return;
    function label() {
        main.querySelectorAll('table:not(.no-cards)').forEach(function (t) {
            if (!t.tHead || !t.tHead.rows.length) return;
            var heads = Array.from(t.tHead.rows[t.tHead.rows.length - 1].cells).map(function (h) { return h.textContent.trim(); });
            t.classList.add('rt');
            t.querySelectorAll('tbody tr, tfoot tr').forEach(function (tr) {
                Array.from(tr.cells).forEach(function (td, i) {
                    if (td.dataset.rt) return;
                    td.dataset.rt = '1';
                    if (heads[i] && !td.hasAttribute('colspan')) td.setAttribute('data-label', heads[i]);
                    var w = document.createElement('span'); w.className = 'rt-v';
                    while (td.firstChild) w.appendChild(td.firstChild);
                    td.appendChild(w);
                });
            });
        });
    }
    label();
    var queued = false;
    new MutationObserver(function () {
        if (queued) return; queued = true;
        requestAnimationFrame(function () { queued = false; label(); });
    }).observe(main, { childList: true, subtree: true });
})();
</script>
@include('partials.form-validation')
</body>
</html>
