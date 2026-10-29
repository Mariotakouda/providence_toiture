@php
    $settings = app(\App\Services\SettingService::class);
    $company = $settings->get('company_name', 'Providence Toiture');
    $address = implode(', ', array_filter([$settings->get('company_address'), $settings->get('company_city')]));
    $phone = $settings->get('company_phone');
    $email = $settings->get('company_email');
    $waBase = \App\Support\WhatsApp::link($settings->get('company_whatsapp'));
    $hasContact = $address !== '' || filled($phone) || filled($email);

    $gammes = \App\Models\Category::where('is_active', true)->orderBy('name')->get();

    $ranges = [
        'Tôles bac aluminium', 'Tôles zinc', 'Bac acier', 'Galva',
        'Tôles Metro', 'Portes de sécurité', 'Produits d’étanchéité',
    ];

    $services = [
        ['Devis détaillé', 'Un devis clair, ligne par ligne, établi avec nos équipes et valable pour la durée indiquée.'],
        ['Livraison suivie', 'Vos commandes sont préparées puis livrées, en une ou plusieurs fois, avec un bon de livraison.'],
        ['Facturation transparente', 'Des factures et des reçus de paiement détaillés pour suivre ce qui est payé et ce qui reste dû.'],
    ];

    $catalogData = ($catalog ?? collect())->map(fn ($c) => [
        'name' => $c->name,
        'products' => $c->products->map(fn ($p) => [
            'id' => $p->id,
            'name' => $p->name,
            'unit' => $p->unit->label(),
        ])->values(),
    ])->values();

    $initialItems = old('items') ? array_values(old('items')) : (request()->integer('produit') ? [['product_id' => request()->integer('produit'), 'quantity' => 1]] : []);
    $requestType = old('type', 'quote');
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $company }}</title>
    <meta name="description" content="{{ $company }} : tôles, bac acier, étanchéité et portes de sécurité. Demandez votre devis en ligne.">
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        .btn-brand { background: #A3C108; color: #181818; font-weight: 600; transition: background-color .15s ease; }
        .btn-brand:hover { background: #8DA906; }
        .btn-brand:focus-visible { outline: 2px solid #181818; outline-offset: 2px; }
        .text-brand-ink { color: #5C7000; }

        body { font-family: 'Barlow', 'Segoe UI', Arial, sans-serif; }
        h1, h2, h3, .display { font-family: 'Barlow Condensed', 'Arial Narrow', Arial, sans-serif; }

        /* Bandeau : photo en fond, voile noir léger (la photo reste bien visible) */
        .hero { position: relative; isolation: isolate; overflow: hidden; min-height: 36rem; display: flex; align-items: center; background: #181818; }
        .hero::before {
            content: ''; position: absolute; inset: 0; z-index: -2;
            background: var(--hero-image) center / cover no-repeat;
            animation: heroZoom 22s ease-out infinite alternate; transform-origin: center; will-change: transform;
        }
        .hero::after {
            content: ''; position: absolute; inset: 0; z-index: -1;
            background: linear-gradient(100deg, rgba(0,0,0,.72) 0%, rgba(0,0,0,.5) 45%, rgba(0,0,0,.28) 100%);
        }
        .hero h1 { color: #fff; font-weight: 700; line-height: 1.05; text-shadow: 0 2px 18px rgba(0,0,0,.45); }
        .hero-name { color: #A3C108; font-family: 'Barlow Condensed', 'Arial Narrow', Arial, sans-serif; font-weight: 700; text-transform: uppercase; letter-spacing: .14em; }
        .hero-line { height: 4px; width: 0; background: #A3C108; animation: lineGrow .9s .3s ease-out forwards; }

        @keyframes heroZoom { from { transform: scale(1); } to { transform: scale(1.09); } }
        @keyframes lineGrow { to { width: 5rem; } }
        @keyframes rise { from { opacity: 0; transform: translateY(26px); } to { opacity: 1; transform: none; } }
        .rise { opacity: 0; animation: rise .8s cubic-bezier(.2,.7,.2,1) forwards; }
        .d1 { animation-delay: .15s; } .d2 { animation-delay: .35s; } .d3 { animation-delay: .55s; } .d4 { animation-delay: .75s; }

        /* Apparition au défilement (activée seulement si le JavaScript fonctionne) */
        .js .reveal { opacity: 0; transform: translateY(24px); transition: opacity .7s ease, transform .7s cubic-bezier(.2,.7,.2,1); }
        .js .reveal.in { opacity: 1; transform: none; }

        /* Gammes : cartes photo */
        .gammes { display: grid; gap: 1.25rem; grid-template-columns: repeat(auto-fill, minmax(16rem, 1fr)); }
        .gamme { position: relative; display: block; aspect-ratio: 4 / 3; overflow: hidden; border-radius: .75rem; background: #181818; box-shadow: 0 1px 3px rgba(0,0,0,.15); }
        .gamme img { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; transition: transform .6s cubic-bezier(.2,.7,.2,1); }
        .gamme-ph { position: absolute; left: 50%; top: 40%; width: 4rem; height: 4rem; transform: translate(-50%, -50%); opacity: .8; }
        .gamme::after { content: ''; position: absolute; inset: 0; background: linear-gradient(to top, rgba(0,0,0,.82) 0%, rgba(0,0,0,.15) 55%, transparent 100%); }
        .gamme-label { position: absolute; left: 0; right: 0; bottom: 0; z-index: 1; padding: 1rem 1.1rem; color: #fff; }
        .gamme-label strong { display: block; font-family: 'Barlow Condensed', 'Arial Narrow', Arial, sans-serif; font-size: 1.5rem; font-weight: 700; text-transform: uppercase; line-height: 1.1; }
        .gamme-label em { display: block; max-height: 0; overflow: hidden; opacity: 0; font-style: normal; font-size: .8rem; color: #A3C108; transition: max-height .35s ease, opacity .35s ease, margin .35s ease; }
        .gamme-label::before { content: ''; display: block; width: 2rem; height: 3px; margin-bottom: .55rem; background: #A3C108; transition: width .35s ease; }
        @media (max-width: 639.98px) {
            .gammes { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .75rem; }
            .gamme { aspect-ratio: 1 / 1; }
            .gamme-label { padding: .65rem .7rem; }
            .gamme-label strong { font-size: 1.05rem; }
            .gamme-label em { display: none; }
        }
        .gamme:hover img { transform: scale(1.08); }
        .gamme:hover .gamme-label::before { width: 4rem; }
        .gamme:hover .gamme-label em, .gamme:focus-visible .gamme-label em { max-height: 1.5rem; opacity: 1; margin-top: .3rem; }

        .wa-float { position: fixed; right: max(1rem, env(safe-area-inset-right)); bottom: max(1rem, env(safe-area-inset-bottom)); z-index: 40; display: inline-flex; align-items: center; gap: .5rem; min-height: 3.25rem; padding: 0 1.1rem; border-radius: 999px; background: #25D366; color: #06361b; font-weight: 600; font-size: .9rem; text-decoration: none; box-shadow: 0 6px 18px rgba(0,0,0,.25); transition: transform .2s ease, box-shadow .2s ease; }
        .wa-float:hover { transform: translateY(-2px); box-shadow: 0 10px 24px rgba(0,0,0,.3); }
        body.login-open .wa-float { display: none; }
        @media (max-width: 520px) { .wa-float span { display: none; } .wa-float { width: 3.25rem; padding: 0; justify-content: center; } }

        .site-footer { background: #181818; color: #d4d4d4; }
        .site-footer h3 { color: #fff; font-size: 1.25rem; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; }
        .site-footer h3::after { content: ''; display: block; width: 2.5rem; height: 3px; margin-top: .5rem; background: #A3C108; }
        .site-footer a { transition: color .15s ease; }
        .site-footer a:hover { color: #A3C108; }

        @media (prefers-reduced-motion: reduce) {
            .hero::before, .hero-line, .rise { animation: none; opacity: 1; width: auto; }
            .hero-line { width: 5rem; }
            .js .reveal { opacity: 1; transform: none; transition: none; }
        }

        .split-panel {
            width: 0;
            opacity: 0;
            flex-shrink: 0;
            overflow: hidden;
            background: #fff;
            box-shadow: -12px 0 40px rgba(0, 0, 0, .08);
            transition: width .4s cubic-bezier(.4, 0, .2, 1), opacity .3s ease;
        }
        .split-inner { width: 28rem; max-width: 100vw; height: 100%; overflow-y: auto; }
        body.login-open .split-panel { width: 28rem; opacity: 1; }

        @media (max-width: 1023px) {
            .split-panel,
            body.login-open .split-panel {
                position: fixed;
                inset: 0;
                width: 100%;
                opacity: 1;
                z-index: 50;
                transform: translateX(100%);
                transition: transform .3s ease;
            }
            body.login-open .split-panel { transform: translateX(0); }
            .split-inner { width: 100%; }
        }

        .cards { display: grid; gap: 1rem; grid-template-columns: repeat(auto-fit, minmax(14rem, 1fr)); }
        .form-grid { display: grid; gap: 1rem; grid-template-columns: repeat(auto-fit, minmax(14rem, 1fr)); }
        .hp { position: absolute; left: -9999px; width: 1px; height: 1px; overflow: hidden; }

        @media (prefers-reduced-motion: reduce) {
            .split-panel { transition: none; }
        }
    </style>
</head>
<body class="h-dvh overflow-hidden bg-white text-[#181818] antialiased {{ $showLogin ? 'login-open' : '' }}">
<div class="flex h-full">

    {{-- Page d'accueil --}}
    <div id="landing" class="relative min-w-0 flex-1 scroll-smooth overflow-y-auto">

        <header class="sticky top-0 z-20 border-b border-neutral-200/70 bg-white/90 backdrop-blur">
            <div class="mx-auto flex max-w-6xl items-center justify-between gap-4 px-5 py-3">
                <a href="{{ route('home') }}" aria-label="{{ $company }}">
                    <img src="{{ asset('images/logo.png') }}" alt="{{ $company }}" class="h-9 w-auto sm:h-11">
                </a>

                <nav class="flex items-center gap-3 text-sm sm:gap-5">
                    <a href="#produits" class="hidden text-neutral-600 hover:text-neutral-900 md:inline">Nos gammes</a>
                    <a href="#services" class="hidden text-neutral-600 hover:text-neutral-900 md:inline">Services</a>
                    <a href="#demande" class="hidden font-medium text-neutral-800 hover:text-neutral-900 sm:inline">Demander un devis</a>
                    @if ($hasContact)
                        <a href="#contact" class="hidden text-neutral-600 hover:text-neutral-900 md:inline">Contact</a>
                    @endif
                    <a href="{{ route('login') }}" data-open-login
                       class="btn-brand rounded-full px-5 py-2 shadow-sm">Connexion</a>
                </nav>
            </div>
        </header>

        {{-- Bandeau d'accueil avec photo en fond --}}
        <section class="hero text-white" style="--hero-image: url('{{ asset('images/imgaccueil.jpg') }}')">
            <div class="relative mx-auto w-full max-w-6xl px-5 py-20 sm:py-28">
                <p class="hero-name rise d1 text-2xl sm:text-3xl">Providence Toiture</p>
                <div class="hero-line mt-3"></div>
                <h1 class="rise d2 mt-5 max-w-3xl text-5xl sm:text-6xl lg:text-7xl">
                    Le meilleur pour vos projets de construction
                </h1>
                <p class="rise d3 mt-6 max-w-2xl text-lg text-neutral-100">
                    Nous sommes une usine de production des tôles bacs aluminium &amp; accessoires, tôle métro de type shingle, band, classic et Milano.
                    Portes blindées et sécurisées, décoration intérieure des bâtiments, gouttières PVC, vente de lits &amp; bourrés
                    et luminaires de qualité, étanchéité toitures &amp; terrasses, service après vente.
                </p>

                <div class="rise d4 mt-8 flex flex-wrap items-center gap-3">
                    <a href="{{ route('login') }}" data-open-login
                       class="btn-brand inline-flex items-center rounded-full px-6 py-3 text-sm shadow-sm">
                        Accéder à l’espace professionnel
                    </a>
                    <a href="#produits"
                       class="inline-flex items-center rounded-full border border-white/60 px-6 py-3 text-sm font-medium text-white transition hover:bg-white/10">
                        Découvrir nos gammes
                    </a>
                    <a href="#demande" class="px-2 py-3 text-sm font-medium text-white underline decoration-[#A3C108] decoration-2 underline-offset-4 hover:text-[#A3C108]">
                        Demander un devis en ligne →
                    </a>
                </div>
            </div>
        </section>

        <section id="produits" class="mx-auto max-w-6xl scroll-mt-20 px-5 py-16">
            <h2 class="text-4xl font-bold uppercase">Nos gammes</h2>
            <p class="mt-2 max-w-xl text-neutral-600">Les produits de couverture et d’étanchéité que nous proposons.</p>

            <div class="gammes mt-8">
                @forelse ($gammes as $g)
                    <a href="{{ route('gammes.show', $g) }}" class="gamme">
                        @if ($g->image)
                            <img src="{{ asset('storage/'.$g->image) }}" alt="{{ $g->name }}" loading="lazy">
                        @else
                            <svg viewBox="0 0 24 24" class="gamme-ph" fill="none" stroke="#A3C108" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 15 L12 6 L21 15"/></svg>
                        @endif
                        <span class="gamme-label"><strong>{{ $g->name }}</strong><em>Voir les produits →</em></span>
                    </a>
                @empty
                    @foreach ($ranges as $range)
                        <a href="#demande" class="gamme">
                            <svg viewBox="0 0 24 24" class="gamme-ph" fill="none" stroke="#A3C108" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 15 L12 6 L21 15"/></svg>
                            <span class="gamme-label"><strong>{{ $range }}</strong><em>Demander un devis →</em></span>
                        </a>
                    @endforeach
                @endforelse
            </div>
        </section>

        <section id="services" class="scroll-mt-20 border-y border-neutral-100 bg-neutral-50">
            <div class="mx-auto max-w-6xl px-5 py-16">
                <h2 class="text-2xl font-bold">Un service du devis au paiement</h2>

                <div class="cards mt-8">
                    @foreach ($services as $i => $service)
                        <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-neutral-200">
                            <span class="btn-brand flex h-9 w-9 items-center justify-center rounded-full text-sm">{{ $i + 1 }}</span>
                            <h3 class="mt-4 font-semibold">{{ $service[0] }}</h3>
                            <p class="mt-2 text-sm text-neutral-600">{{ $service[1] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- Demande de devis ou de commande en ligne --}}
        <section id="demande" class="mx-auto max-w-4xl scroll-mt-20 px-5 py-16">
            <h2 class="text-2xl font-bold">Demander un devis ou passer commande</h2>
            <p class="mt-2 text-neutral-600">
                Indiquez vos coordonnées et les produits souhaités : notre équipe vous recontacte pour confirmer les prix, les disponibilités et la livraison.
            </p>

            @if (session('request_reference'))
                <div class="mt-6 rounded-2xl border border-[#A3C108] bg-[#f1f7d6] p-5 text-[#2f3b00]" role="status">
                    <p class="font-semibold">Merci, votre demande est bien enregistrée.</p>
                    <p class="mt-1 text-sm">Référence : <span class="font-mono font-semibold">{{ session('request_reference') }}</span>.
                        Notre équipe vous contactera très prochainement. Gardez cette référence si vous nous appelez.</p>
                    @php $waConfirm = \App\Support\WhatsApp::link($settings->get('company_whatsapp'), 'Bonjour, je viens d’envoyer la demande '.session('request_reference').' sur votre site. Pouvez-vous me confirmer sa réception ?'); @endphp
                    @if ($waConfirm)
                        <a href="{{ $waConfirm }}" target="_blank" rel="noopener"
                           class="mt-3 inline-flex items-center gap-2 rounded-full bg-[#25D366] px-4 py-2 text-sm font-semibold text-[#06361b] hover:brightness-95">
                            Nous écrire sur WhatsApp
                        </a>
                    @endif
                </div>
            @else
                @if ($errors->any())
                    <div class="mt-6 rounded-xl bg-red-50 p-4 text-sm text-red-700" role="alert">
                        Votre demande n’a pas pu être envoyée. Vérifiez les champs signalés ci-dessous.
                    </div>
                @endif

                <form method="POST" action="{{ route('public-requests.store') }}" class="mt-6 space-y-6 rounded-2xl bg-white p-6 shadow-sm ring-1 ring-neutral-200">
                    @csrf

                    {{-- Champ piège pour les robots : laissé vide par les visiteurs --}}
                    <div class="hp" aria-hidden="true">
                        <label>Ne pas remplir <input type="text" name="website" tabindex="-1" autocomplete="off"></label>
                    </div>

                    <fieldset>
                        <legend class="mb-2 text-sm font-medium">Votre demande</legend>
                        <div class="grid gap-3 sm:grid-cols-2">
                            @foreach (\App\Enums\OnlineRequestType::cases() as $type)
                                <label class="flex cursor-pointer items-center gap-3 rounded-xl border p-4 transition has-[:checked]:border-[#A3C108] has-[:checked]:bg-[#f1f7d6] border-neutral-200">
                                    <input type="radio" name="type" value="{{ $type->value }}" class="accent-[#8DA906]" @checked($requestType === $type->value)>
                                    <span class="font-medium">{{ $type->label() }}</span>
                                </label>
                            @endforeach
                        </div>
                        @error('type') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </fieldset>

                    <div class="form-grid">
                        <div>
                            <label for="req-name" class="block text-sm font-medium">Nom ou raison sociale *</label>
                            <input id="req-name" name="name" type="text" value="{{ old('name') }}" required maxlength="255" autocomplete="name"
                                   class="mt-1 w-full rounded-lg border border-neutral-300 px-3 py-2.5 outline-none transition focus:border-[#A3C108] focus:ring-2 focus:ring-[#A3C108]/40">
                            @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="req-phone" class="block text-sm font-medium">Téléphone *</label>
                            <input id="req-phone" name="phone" type="tel" value="{{ old('phone') }}" required maxlength="30" autocomplete="tel"
                                   class="mt-1 w-full rounded-lg border border-neutral-300 px-3 py-2.5 outline-none transition focus:border-[#A3C108] focus:ring-2 focus:ring-[#A3C108]/40">
                            @error('phone') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="req-email" class="block text-sm font-medium">E-mail (facultatif)</label>
                            <input id="req-email" name="email" type="email" value="{{ old('email') }}" maxlength="255" autocomplete="email"
                                   class="mt-1 w-full rounded-lg border border-neutral-300 px-3 py-2.5 outline-none transition focus:border-[#A3C108] focus:ring-2 focus:ring-[#A3C108]/40">
                            @error('email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="req-city" class="block text-sm font-medium">Ville</label>
                            <input id="req-city" name="city" type="text" value="{{ old('city') }}" maxlength="100" autocomplete="address-level2"
                                   class="mt-1 w-full rounded-lg border border-neutral-300 px-3 py-2.5 outline-none transition focus:border-[#A3C108] focus:ring-2 focus:ring-[#A3C108]/40">
                            @error('city') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div>
                        <label for="req-address" class="block text-sm font-medium">Adresse de livraison ou du chantier (facultatif)</label>
                        <input id="req-address" name="address" type="text" value="{{ old('address') }}" maxlength="255" autocomplete="street-address"
                               class="mt-1 w-full rounded-lg border border-neutral-300 px-3 py-2.5 outline-none transition focus:border-[#A3C108] focus:ring-2 focus:ring-[#A3C108]/40">
                        @error('address') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium">Produits souhaités</span>
                            <button type="button" id="add-item" class="text-sm font-medium text-brand-ink hover:underline">+ Ajouter un produit</button>
                        </div>

                        @foreach ($errors->getMessages() as $key => $messages)
                            @if (str_starts_with($key, 'items'))
                                <p class="mt-1 text-sm text-red-600">{{ $messages[0] }}</p>
                            @endif
                        @endforeach

                        <div id="items" class="mt-2 space-y-2"></div>
                        <p id="items-empty" class="mt-1 hidden text-sm text-neutral-500">Aucun produit choisi : décrivez votre besoin dans le message ci-dessous.</p>
                    </div>

                    <div>
                        <label for="req-message" class="block text-sm font-medium">Message</label>
                        <textarea id="req-message" name="message" rows="4" maxlength="3000"
                                  placeholder="Couleur, épaisseur, longueurs, surface à couvrir, délai souhaité…"
                                  class="mt-1 w-full rounded-lg border border-neutral-300 px-3 py-2.5 outline-none transition focus:border-[#A3C108] focus:ring-2 focus:ring-[#A3C108]/40">{{ old('message') }}</textarea>
                        @error('message') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="flex items-start gap-2 text-sm">
                            <input type="checkbox" name="consent" value="1" required class="mt-1 accent-[#8DA906]" @checked(old('consent'))>
                            <span>J’accepte que {{ $company }} utilise ces informations pour traiter ma demande et me recontacter.</span>
                        </label>
                        @error('consent') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <button type="submit" class="btn-brand rounded-lg px-6 py-3 shadow-sm">Envoyer ma demande</button>
                    <p class="text-xs text-neutral-500">Aucun paiement n’est demandé à cette étape. Les prix et la disponibilité vous sont confirmés par notre équipe.</p>
                </form>
            @endif
        </section>

        @if ($hasContact)
            <section id="contact" class="mx-auto max-w-6xl scroll-mt-20 px-5 pb-16">
                <div class="rounded-3xl bg-[#181818] p-8 text-white sm:p-10">
                    <h2 class="text-2xl font-bold">Nous contacter</h2>
                    <dl class="mt-6 grid gap-6 sm:grid-cols-3">
                        @if ($address !== '')
                            <div>
                                <dt class="text-xs uppercase tracking-wide text-neutral-400">Adresse</dt>
                                <dd class="mt-1">{{ $address }}</dd>
                            </div>
                        @endif
                        @if (filled($phone))
                            <div>
                                <dt class="text-xs uppercase tracking-wide text-neutral-400">Téléphone</dt>
                                <dd class="mt-1"><a href="tel:{{ preg_replace('/[^\d+]/', '', $phone) }}" class="hover:underline">{{ $phone }}</a></dd>
                            </div>
                        @endif
                        @if (filled($email))
                            <div>
                                <dt class="text-xs uppercase tracking-wide text-neutral-400">E-mail</dt>
                                <dd class="mt-1"><a href="mailto:{{ $email }}" class="hover:underline">{{ $email }}</a></dd>
                            </div>
                        @endif
                    </dl>
                </div>
            </section>
        @endif

        <footer class="site-footer">
            <div class="mx-auto grid max-w-6xl gap-10 px-5 py-14 md:grid-cols-3">
                <div>
                    <img src="{{ asset('images/logo.png') }}" alt="{{ $company }}" class="h-14 w-auto rounded bg-white p-1.5">
                    <p class="mt-5 text-sm leading-relaxed">
                        Nous sommes une usine de production des tôles bacs aluminium &amp; accessoires, tôle métro de type shingle, band, classic et Milano.
                        Portes blindées et sécurisées, décoration intérieure des bâtiments, gouttières PVC, vente de lits &amp; bourrés
                        et luminaires de qualité. Service après vente.
                    </p>
                </div>

                <div>
                    <h3>Liens utiles</h3>
                    <ul class="mt-5 space-y-2 text-sm">
                        <li><a href="https://providencetoiture.com/" target="_blank" rel="noopener">Accueil</a></li>
                        <li><a href="https://www.goafricaonline.com/tg/582097-ets-providence-toiture-materiaux-construction-lome-togo" target="_blank" rel="noopener">Nos produits et services</a></li>
                        <li><a href="https://www.goafricaonline.com/tg/582097-ets-providence-toiture-materiaux-construction-lome-togo" target="_blank" rel="noopener">Portfolio</a></li>
                        <li><a href="https://providencetoiture.com/presentation/" target="_blank" rel="noopener">Présentation</a></li>
                        <li><a href="https://providencetoiture.com/contact/" target="_blank" rel="noopener">Contact</a></li>
                    </ul>
                </div>

                <div>
                    <h3>Contact Info</h3>
                    <ul class="mt-5 space-y-3 text-sm leading-relaxed">
                        <li><strong class="text-white">Siège :</strong> Adidogomé derrière le Lycée Moderne à côté de l’Église ZIONTO, Cél : (+228) 92 41 30 01 / WhatsApp : 91 61 70 55</li>
                        <li><strong class="text-white">Annexe :</strong> Derrière CCT BATIMAT, en face de Câblerie BORSAN, à côté de MIRNA GROUP, Assivito, Cél : (+228) 92 38 51 41 / WhatsApp : (+228) 92 10 85 85</li>
                        <li><a href="tel:+22896541769">(+228) 96 54 17 69</a> / <a href="tel:+22890844804">90 84 48 04</a></li>
                        <li><a href="mailto:providencetoiturecontact@gmail.com">providencetoiturecontact@gmail.com</a></li>
                        <li><a href="mailto:contact@providencetoiture.com">contact@providencetoiture.com</a></li>
                    </ul>
                </div>
            </div>
            <div class="border-t border-white/10">
                <p class="mx-auto max-w-6xl px-5 py-5 text-xs text-neutral-400">© {{ date('Y') }} {{ $company }}. Tous droits réservés.</p>
            </div>
        </footer>
    </div>

    {{-- Volet de connexion --}}
    <aside id="login-panel" class="split-panel" aria-label="Connexion">
        <div class="split-inner flex flex-col">
            <div class="flex items-center justify-between px-8 pt-6">
                <a href="{{ route('home') }}" data-close-login class="text-sm text-neutral-500 hover:text-neutral-900">← Retour à l’accueil</a>
                <a href="{{ route('home') }}" data-close-login aria-label="Fermer"
                   class="rounded-md p-1.5 text-neutral-400 hover:bg-neutral-100 hover:text-neutral-900">✕</a>
            </div>

            <div class="flex flex-1 flex-col justify-center px-8 pb-10 pt-4">
                @include('partials.login-form')
            </div>
        </div>
    </aside>
</div>

<script>
(function () {
    const body = document.body;
    const panel = document.getElementById('login-panel');
    const homeUrl = @json(route('home', [], false));
    const loginUrl = @json(route('login', [], false));

    // ---------- Volet de connexion ----------
    function sync() {
        const open = body.classList.contains('login-open');
        panel.setAttribute('aria-hidden', open ? 'false' : 'true');
        if ('inert' in panel) panel.inert = !open;
        return open;
    }

    function setOpen(open, push) {
        body.classList.toggle('login-open', open);
        sync();
        if (push) history.pushState({ login: open }, '', open ? loginUrl : homeUrl);
        if (open) setTimeout(() => document.getElementById('email')?.focus({ preventScroll: true }), 380);
    }

    document.querySelectorAll('[data-open-login]').forEach((el) =>
        el.addEventListener('click', (e) => { e.preventDefault(); setOpen(true, true); }));
    document.querySelectorAll('[data-close-login]').forEach((el) =>
        el.addEventListener('click', (e) => { e.preventDefault(); setOpen(false, true); }));

    window.addEventListener('popstate', () => setOpen(location.pathname.replace(/\/$/, '') === loginUrl, false));
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && body.classList.contains('login-open')) setOpen(false, true);
    });

    // Double-clic sur une zone libre de l'accueil : ferme le volet de connexion
    document.getElementById('landing')?.addEventListener('dblclick', (e) => {
        if (!body.classList.contains('login-open')) return;
        if (e.target.closest('a, button, input, select, textarea, label, form, #login-panel')) return;
        setOpen(false, true);
    });

    document.querySelectorAll('[data-toggle-password]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const input = document.getElementById(btn.dataset.togglePassword);
            const show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            btn.textContent = show ? 'Masquer' : 'Afficher';
        });
    });

    if (sync()) setTimeout(() => document.getElementById('email')?.focus({ preventScroll: true }), 50);

    // ---------- Lignes de produits de la demande ----------
    const catalog = @json($catalogData);
    const initial = @json($initialItems);
    const itemsBox = document.getElementById('items');
    const emptyHint = document.getElementById('items-empty');
    const addBtn = document.getElementById('add-item');
    let counter = 0;

    if (!itemsBox) return;

    const fieldClass = 'rounded-lg border border-neutral-300 px-3 py-2.5 outline-none transition focus:border-[#A3C108] focus:ring-2 focus:ring-[#A3C108]/40';

    function refreshHint() {
        emptyHint.classList.toggle('hidden', itemsBox.children.length > 0);
        addBtn.classList.toggle('hidden', itemsBox.children.length >= 10);
    }

    function addRow(data) {
        const i = counter++;
        const row = document.createElement('div');
        row.className = 'flex flex-wrap items-center gap-2';

        const select = document.createElement('select');
        select.name = 'items[' + i + '][product_id]';
        select.required = true;
        select.className = fieldClass + ' min-w-0 flex-1';
        select.setAttribute('aria-label', 'Produit');

        const placeholder = document.createElement('option');
        placeholder.value = '';
        placeholder.textContent = 'Choisir un produit';
        select.appendChild(placeholder);

        catalog.forEach((category) => {
            const group = document.createElement('optgroup');
            group.label = category.name;
            category.products.forEach((product) => {
                const o = document.createElement('option');
                o.value = product.id;
                o.textContent = product.name + ' (' + product.unit + ')';
                if (String(product.id) === String(data.product_id)) o.selected = true;
                group.appendChild(o);
            });
            select.appendChild(group);
        });

        const qty = document.createElement('input');
        qty.type = 'number';
        qty.name = 'items[' + i + '][quantity]';
        qty.min = '0.01';
        qty.step = '0.01';
        qty.required = true;
        qty.value = data.quantity || '1';
        qty.className = fieldClass + ' w-28';
        qty.setAttribute('aria-label', 'Quantité');

        const remove = document.createElement('button');
        remove.type = 'button';
        remove.textContent = '✕';
        remove.className = 'rounded-md p-2 text-neutral-400 hover:bg-neutral-100 hover:text-red-600';
        remove.setAttribute('aria-label', 'Retirer ce produit');
        remove.addEventListener('click', () => { row.remove(); refreshHint(); });

        row.append(select, qty, remove);
        itemsBox.appendChild(row);
        refreshHint();
    }

    addBtn.addEventListener('click', () => addRow({}));
    initial.forEach((item) => addRow(item));
    if (!initial.length) addRow({});
})();
</script>
    @if ($waBase)
        <a class="wa-float" href="{{ $waBase }}?text={{ rawurlencode('Bonjour '.$company.', je souhaite un devis.') }}" target="_blank" rel="noopener" aria-label="Écrire sur WhatsApp">
            <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3a9 9 0 0 0-7.8 13.5L3 21l4.6-1.2A9 9 0 1 0 12 3z"/><path d="M9 8.5c0 3 2.5 5.5 5.5 5.5l1-1.5-2-1-1 .8a3.5 3.5 0 0 1-1.6-1.6l.8-1-1-2z"/></svg>
            <span>WhatsApp</span>
        </a>
    @endif
<script>
(function () {
    var d = document.documentElement;
    if (!('IntersectionObserver' in window) || window.matchMedia('(prefers-reduced-motion: reduce)').matches) { return; }
    d.classList.add('js');
    var els = document.querySelectorAll('#produits h2, #produits p, #produits .gamme, #services h2, #services .cards > div, #demande > *, #contact > *, .site-footer .grid > div');
    var io = new IntersectionObserver(function (entries) {
        entries.forEach(function (e) { if (e.isIntersecting) { e.target.classList.add('in'); io.unobserve(e.target); } });
    }, { threshold: 0.12 });
    els.forEach(function (el, i) { el.classList.add('reveal'); el.style.transitionDelay = ((i % 4) * 90) + 'ms'; io.observe(el); });
})();
</script>
</body>
</html>
