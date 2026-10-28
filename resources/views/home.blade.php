@php
    $settings = app(\App\Services\SettingService::class);
    $company = $settings->get('company_name', 'Providence Toiture');
    $address = implode(', ', array_filter([$settings->get('company_address'), $settings->get('company_city')]));
    $phone = $settings->get('company_phone');
    $email = $settings->get('company_email');

    // Réseaux sociaux : renseigner ces URL dans les paramètres de l'application.
    $socialLinks = array_filter([
        'Facebook'  => $settings->get('company_facebook'),
        'Instagram' => $settings->get('company_instagram'),
        'TikTok'    => $settings->get('company_tiktok'),
        'YouTube'   => $settings->get('company_youtube'),
        'LinkedIn'  => $settings->get('company_linkedin'),
        'WhatsApp'  => $settings->get('company_whatsapp'),
    ], fn ($url) => filled($url));

    $hasContact = $address !== '' || filled($phone) || filled($email);

    $gammes = \App\Models\Category::where('is_active', true)->orderBy('name')->get();

    $ranges = [
        'Tôles bac aluminium', 'Tôles zinc', 'Bac acier', 'Galva',
        'Tôles Metro', 'Portes de sécurité', 'Produits d’étanchéité',
    ];

    $services = [
        ['Devis détaillé', 'Un devis clair, ligne par ligne, établi avec nos équipes et valable pour la durée indiquée.',
            '<path d="M7 3h7l5 5v13H7z"/><path d="M14 3v5h5"/><path d="M10 13h6M10 17h6"/>'],
        ['Livraison suivie', 'Vos commandes sont préparées puis livrées, en une ou plusieurs fois, avec un bon de livraison.',
            '<path d="M2 6h12v10H2z"/><path d="M14 10h4l3 3v3h-7z"/><circle cx="6.5" cy="17.5" r="1.8"/><circle cx="17.5" cy="17.5" r="1.8"/>'],
        ['Facturation transparente', 'Des factures et des reçus de paiement détaillés pour suivre ce qui est payé et ce qui reste dû.',
            '<rect x="3" y="6" width="18" height="12" rx="2"/><circle cx="12" cy="12" r="2.5"/><path d="M6 9v.01M18 15v.01"/>'],
    ];

    $catalogData = ($catalog ?? collect())->map(fn ($c) => [
        'name' => $c->name,
        'products' => $c->products->map(fn ($p) => [
            'id' => $p->id,
            'name' => $p->name,
            'unit' => $p->unit->label(),
        ])->values(),
    ])->values();

    $initialItems = old('items') ? array_values(old('items')) : [];
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

        /* Les boutons de connexion disparaissent quand le volet est ouvert */
        body.login-open .login-btn { display: none !important; }

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
        .gamme { position: relative; display: block; aspect-ratio: 4 / 3; overflow: hidden; border-radius: .75rem; background: #181818; box-shadow: 0 1px 3px rgba(0,0,0,.15); isolation: isolate; }
        .gamme img { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; transition: transform .6s cubic-bezier(.2,.7,.2,1); }
        .gamme:focus-visible { outline: 3px solid #A3C108; outline-offset: 3px; }
        .gamme::before { content: ''; position: absolute; inset: .65rem; z-index: 1; border: 1px solid rgba(255,255,255,.22); border-radius: .45rem; pointer-events: none; opacity: 0; transform: scale(.97); transition: opacity .35s ease, transform .35s ease; }
        .gamme:hover::before, .gamme:focus-visible::before { opacity: 1; transform: scale(1); }
        .gamme-ph { position: absolute; left: 50%; top: 40%; width: 4rem; height: 4rem; transform: translate(-50%, -50%); opacity: .8; }
        .gamme::after { content: ''; position: absolute; inset: 0; background: linear-gradient(to top, rgba(0,0,0,.82) 0%, rgba(0,0,0,.15) 55%, transparent 100%); }
        .gamme-label { position: absolute; left: 0; right: 0; bottom: 0; z-index: 1; padding: 1rem 1.1rem; color: #fff; }
        .gamme-label strong { display: block; font-family: 'Barlow Condensed', 'Arial Narrow', Arial, sans-serif; font-size: 1.5rem; font-weight: 700; text-transform: uppercase; line-height: 1.1; }
        .gamme-label em { display: block; max-height: 0; overflow: hidden; opacity: 0; font-style: normal; font-size: .8rem; color: #A3C108; transition: max-height .35s ease, opacity .35s ease, margin .35s ease; }
        .gamme-label::before { content: ''; display: block; width: 2rem; height: 3px; margin-bottom: .55rem; background: #A3C108; transition: width .35s ease; }
        .gamme:hover img { transform: scale(1.08); }
        .gamme:hover .gamme-label::before { width: 4rem; }
        .gamme:hover .gamme-label em, .gamme:focus-visible .gamme-label em { max-height: 1.5rem; opacity: 1; margin-top: .3rem; }

        /* Services : un service du devis au paiement */
        .services {
            background:
                radial-gradient(circle at 90% 0%, rgba(163,193,8,.12), transparent 40%),
                #f7f7f5;
            border-block: 1px solid #ececea;
        }
        .services-eyebrow {
            display: inline-flex; align-items: center; gap: .6rem;
            font-family: 'Barlow Condensed', 'Arial Narrow', Arial, sans-serif;
            font-weight: 700; text-transform: uppercase; letter-spacing: .16em; color: #5C7000;
        }
        .services-eyebrow::before { content: ''; width: 2rem; height: 3px; background: #A3C108; }

        .steps { display: grid; gap: 1.5rem; grid-template-columns: repeat(auto-fit, minmax(16rem, 1fr)); }
        .step { display: flex; }
        .step-card {
            position: relative; overflow: hidden; width: 100%;
            background: #fff; border: 1px solid #e7e7e4; border-radius: 1rem;
            padding: 2rem 1.75rem 1.9rem;
            box-shadow: 0 1px 2px rgba(0,0,0,.05);
            transition: transform .35s cubic-bezier(.2,.7,.2,1), box-shadow .35s ease, border-color .35s ease;
        }
        .step-card::before {
            content: ''; position: absolute; left: 0; right: 0; top: 0; height: 4px;
            background: #A3C108; transform: scaleX(.25); transform-origin: left;
            transition: transform .45s cubic-bezier(.2,.7,.2,1);
        }
        .step-card:hover { transform: translateY(-6px); border-color: #A3C108; box-shadow: 0 18px 40px -14px rgba(0,0,0,.2); }
        .step-card:hover::before { transform: scaleX(1); }

        .step-num {
            position: absolute; right: 1.1rem; top: .2rem;
            font-family: 'Barlow Condensed', 'Arial Narrow', Arial, sans-serif;
            font-size: 5.5rem; font-weight: 700; line-height: 1;
            color: rgba(163,193,8,.2); pointer-events: none;
            transition: color .35s ease;
        }
        .step-card:hover .step-num { color: rgba(163,193,8,.4); }

        .step-icon {
            display: flex; align-items: center; justify-content: center;
            width: 3.4rem; height: 3.4rem; border-radius: .9rem; background: #181818;
            transition: transform .35s ease;
        }
        .step-icon svg { width: 1.6rem; height: 1.6rem; }
        .step-card:hover .step-icon { transform: rotate(-6deg) scale(1.06); }

        .step-title {
            margin-top: 1.4rem; font-size: 1.6rem; font-weight: 700;
            text-transform: uppercase; line-height: 1.1;
        }
        .step-text { margin-top: .6rem; font-size: .95rem; line-height: 1.6; color: #525252; }
        .step-card:focus-within { border-color: #A3C108; }
        .step-card::after { content: ''; position: absolute; right: -2.5rem; bottom: -3rem; width: 7rem; height: 7rem; border: 1px solid rgba(163,193,8,.18); border-radius: 50%; pointer-events: none; }
        .section-intro { max-width: 46rem; }
        .section-title-mark { width: 3.25rem; height: 4px; margin-top: .8rem; background: #A3C108; }
        .request-panel { border-top: 4px solid #A3C108; }
        .social-links { display: flex; flex-wrap: wrap; gap: .6rem; margin-top: 1.25rem; }
        .social-link { display: inline-flex; align-items: center; gap: .5rem; min-height: 2.5rem; padding: .55rem .8rem; border: 1px solid rgba(255,255,255,.18); border-radius: .5rem; color: #fff; font-size: .8rem; font-weight: 600; transition: background-color .15s ease, border-color .15s ease, color .15s ease; }
        .social-link:hover { color: #A3C108; border-color: #A3C108; background: rgba(163,193,8,.08); }
        .social-link svg { width: 1rem; height: 1rem; flex: none; }
        .footer-bottom { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: .75rem; }

        .site-footer { background: #181818; color: #d4d4d4; }
        .site-footer h3 { color: #fff; font-size: 1.25rem; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; }
        .site-footer h3::after { content: ''; display: block; width: 2.5rem; height: 3px; margin-top: .5rem; background: #A3C108; }
        .site-footer a { transition: color .15s ease; }
        .site-footer a:hover { color: #A3C108; }

        @media (prefers-reduced-motion: reduce) {
            .hero::before, .hero-line, .rise { animation: none; opacity: 1; width: auto; }
            .hero-line { width: 5rem; }
            .js .reveal { opacity: 1; transform: none; transition: none; }
            .step-card, .step-card::before, .step-icon, .step-num { transition: none; }
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

        /* --- Direction artistique premium : section Services --- */
        .services-premium {
            position: relative;
            overflow: hidden;
            background:
                radial-gradient(ellipse at 92% 5%, rgba(163,193,8,.13), transparent 31rem),
                radial-gradient(ellipse at 0% 100%, rgba(163,193,8,.07), transparent 27rem),
                #f8f9f6;
        }
        .services-premium::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(163,193,8,.5), transparent);
        }
        .services-heading {
            display: flex;
            align-items: end;
            justify-content: space-between;
            gap: 2rem;
            margin-bottom: 2.5rem;
        }
        .services-heading-copy { max-width: 49rem; }
        .services-main-title {
            margin-top: .55rem;
            font-size: clamp(2.25rem, 4.2vw, 3.65rem);
            font-weight: 800;
            line-height: .98;
            letter-spacing: -.035em;
            color: #171b18;
            text-transform: uppercase;
        }
        .services-main-title span { color: #83a000; }
        .services-lead {
            margin-top: 1rem;
            max-width: 43rem;
            color: #5b625b;
            font-size: 1.05rem;
            line-height: 1.75;
        }
        .services-side-note {
            display: flex;
            align-items: center;
            gap: .8rem;
            flex: 0 0 auto;
            padding: 1rem 1.15rem;
            border: 1px solid rgba(131,160,0,.18);
            border-radius: 1rem;
            background: rgba(255,255,255,.72);
            box-shadow: 0 10px 30px rgba(25,35,10,.04);
        }
        .services-side-icon {
            display: grid;
            place-items: center;
            width: 2.8rem;
            height: 2.8rem;
            border-radius: .85rem;
            background: #eff6d5;
            color: #729000;
        }
        .services-side-icon svg { width: 1.5rem; height: 1.5rem; }
        .services-side-note strong, .services-side-note small { display: block; }
        .services-side-note strong { font-size: .85rem; color: #252b22; }
        .services-side-note small { margin-top: .2rem; font-size: .75rem; color: #73796e; }
        .steps-premium { gap: 1.35rem; grid-template-columns: repeat(3, minmax(0, 1fr)); }
        .step-premium { min-width: 0; }
        .step-card-premium {
            min-height: 20rem;
            display: flex;
            flex-direction: column;
            padding: 1.65rem 1.55rem 1.45rem;
            border-color: #e8ebe3;
            border-radius: 1.25rem;
            box-shadow: 0 8px 24px rgba(24,32,12,.045);
            isolation: isolate;
        }
        .step-card-premium::after {
            right: -3.3rem;
            bottom: -3.6rem;
            width: 9rem;
            height: 9rem;
            border: 1.2rem solid rgba(163,193,8,.08);
            border-radius: 50%;
            z-index: -1;
        }
        .step-card-premium::before { height: 5px; transform: scaleX(.35); }
        .step-card-premium:hover {
            transform: translateY(-7px);
            border-color: rgba(141,169,6,.65);
            box-shadow: 0 22px 48px -20px rgba(43,57,8,.28);
        }
        .step-card-top { display: flex; align-items: center; gap: .8rem; position: relative; min-height: 4.3rem; }
        .step-icon-premium {
            width: 3.65rem;
            height: 3.65rem;
            border-radius: 1rem;
            box-shadow: 0 7px 16px rgba(0,0,0,.13);
        }
        .step-icon-premium svg { width: 1.8rem; height: 1.8rem; }
        .step-tag {
            display: inline-flex;
            align-items: center;
            border-radius: 999px;
            padding: .38rem .7rem;
            background: #f2f8df;
            color: #668000;
            font-size: .68rem;
            font-weight: 800;
            letter-spacing: .06em;
        }
        .step-num-premium {
            top: -.35rem;
            right: -.15rem;
            font-size: 4.8rem;
            line-height: 1;
            color: rgba(163,193,8,.19);
            z-index: -1;
        }
        .step-title-premium {
            margin-top: 1.45rem;
            max-width: 17rem;
            font-size: clamp(1.35rem, 1.8vw, 1.7rem);
            letter-spacing: -.015em;
            color: #171b18;
        }
        .step-text-premium {
            margin-top: .65rem;
            max-width: 24rem;
            font-size: .94rem;
            line-height: 1.7;
            color: #62685f;
        }
        .step-link {
            display: inline-flex;
            align-items: center;
            gap: .65rem;
            align-self: flex-start;
            margin-top: auto;
            padding-top: 1.2rem;
            color: #343b2a;
            font-size: .85rem;
            font-weight: 600;
            text-decoration: none;
        }
        .step-link > span:last-child { border-bottom: 1px solid #a3c108; padding-bottom: .2rem; }
        .step-link-arrow {
            display: grid;
            place-items: center;
            width: 2.2rem;
            height: 2.2rem;
            border-radius: 50%;
            background: #91b300;
            color: #fff;
            font-size: 1.35rem;
            line-height: 1;
            transition: transform .25s ease, background .25s ease;
        }
        .step-link:hover .step-link-arrow { transform: translateX(4px); background: #718d00; }
        .services-trust {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            margin-top: 2.6rem;
            padding: 1.35rem 0 0;
            border-top: 1px solid #e4e8dd;
        }
        .trust-item {
            display: flex;
            align-items: center;
            gap: .75rem;
            min-width: 0;
            padding: .35rem 1.1rem;
            border-right: 1px solid #e3e7df;
        }
        .trust-item:first-child { padding-left: 0; }
        .trust-item:last-child { border-right: 0; }
        .trust-icon {
            display: grid;
            place-items: center;
            flex: 0 0 auto;
            width: 3.1rem;
            height: 3.1rem;
            border-radius: 50%;
            background: #eff6dc;
            color: #83a900;
        }
        .trust-icon svg { width: 1.65rem; height: 1.65rem; }
        .trust-item strong, .trust-item small { display: block; }
        .trust-item strong { color: #30372a; font-size: .76rem; font-weight: 800; text-transform: uppercase; letter-spacing: .025em; }
        .trust-item small { margin-top: .28rem; color: #7a8075; font-size: .72rem; line-height: 1.4; }
        @media (max-width: 900px) {
            .services-heading { align-items: flex-start; flex-direction: column; gap: 1.25rem; }
            .steps-premium { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .services-trust { grid-template-columns: repeat(2, minmax(0, 1fr)); row-gap: 1rem; }
            .trust-item:nth-child(2) { border-right: 0; }
            .trust-item:nth-child(3) { padding-left: 0; }
        }
        @media (max-width: 600px) {
            .steps-premium { grid-template-columns: 1fr; gap: 1rem; }
            .step-card-premium { min-height: 0; padding: 1.4rem; }
            .step-link { margin-top: 1.1rem; padding-top: .2rem; }
            .services-trust { grid-template-columns: 1fr; gap: .7rem; margin-top: 2rem; }
            .trust-item, .trust-item:first-child, .trust-item:nth-child(3) { padding: .55rem 0; border-right: 0; }
            .trust-icon { width: 2.7rem; height: 2.7rem; }
            .services-main-title { line-height: 1.02; }
            .services-lead { font-size: .98rem; }
        }
        @media (prefers-reduced-motion: reduce) {
            .step-link-arrow { transition: none; }
        }


        /* --- Ajustements mobiles : catalogue plus compact et lecture confortable --- */
        @media (max-width: 640px) {
            /* Bandeau d'accueil : éviter qu'il occupe tout le premier écran */
            .hero {
                min-height: 0;
                align-items: center;
            }
            .hero > div {
                padding-top: 3.25rem;
                padding-bottom: 3.25rem;
            }
            .hero h1 {
                font-size: clamp(2.15rem, 9vw, 2.8rem);
                line-height: 1.04;
                letter-spacing: -.025em;
            }
            .hero p {
                margin-top: 1rem;
                font-size: .95rem;
                line-height: 1.6;
            }
            .hero .rise.d4 {
                margin-top: 1.35rem;
                align-items: stretch;
                gap: .65rem;
            }
            .hero .rise.d4 a {
                max-width: 100%;
                justify-content: center;
                text-align: center;
            }

            /* Cartes de gammes : format paysage plutôt que de grandes affiches */
            #produits {
                padding-top: 2.5rem;
                padding-bottom: 2.5rem;
                padding-left: 1rem;
                padding-right: 1rem;
            }
            #produits .section-intro h2 {
                font-size: 2rem;
                line-height: 1.05;
            }
            #produits .gammes {
                grid-template-columns: minmax(0, 1fr);
                gap: .85rem;
                margin-top: 1.25rem;
            }
            #produits .gamme {
                aspect-ratio: 16 / 9;
                min-height: 0;
                border-radius: .85rem;
            }
            #produits .gamme img {
                object-position: center;
            }
            #produits .gamme::before {
                inset: .4rem;
            }
            #produits .gamme-label {
                padding: .8rem .9rem;
            }
            #produits .gamme-label::before {
                width: 1.7rem;
                height: 3px;
                margin-bottom: .4rem;
            }
            #produits .gamme-label strong {
                font-size: clamp(1.05rem, 4.5vw, 1.3rem);
                line-height: 1.1;
            }
            /* Sur écran tactile, ne pas cacher l'appel à l'action derrière un hover */
            #produits .gamme-label em {
                max-height: 1.5rem;
                margin-top: .25rem;
                opacity: 1;
                font-size: .75rem;
            }

            /* Services : marges et typographie adaptées aux écrans étroits */
            #services > div {
                padding-top: 2.75rem;
                padding-bottom: 2.75rem;
                padding-left: 1rem;
                padding-right: 1rem;
            }
            .services-heading {
                margin-bottom: 1.5rem;
                gap: 1rem;
            }
            .services-main-title {
                font-size: clamp(2rem, 8.5vw, 2.65rem);
                line-height: 1.02;
                letter-spacing: -.025em;
                overflow-wrap: anywhere;
            }
            .services-lead {
                margin-top: .8rem;
                font-size: .94rem;
                line-height: 1.6;
            }
            .services-side-note {
                width: 100%;
                padding: .8rem;
                border-radius: .85rem;
            }
            .steps-premium {
                grid-template-columns: minmax(0, 1fr);
                gap: .85rem;
            }
            .step-card-premium {
                min-height: 0;
                padding: 1.2rem 1.1rem 1.15rem;
                border-radius: 1rem;
            }
            .step-card-top {
                min-height: 3.5rem;
                gap: .6rem;
            }
            .step-icon-premium {
                width: 3rem;
                height: 3rem;
                border-radius: .8rem;
            }
            .step-icon-premium svg {
                width: 1.5rem;
                height: 1.5rem;
            }
            .step-num-premium {
                top: -.25rem;
                right: 0;
                font-size: 3.8rem;
            }
            .step-title-premium {
                margin-top: 1rem;
                font-size: 1.35rem;
                line-height: 1.12;
            }
            .step-text-premium {
                margin-top: .45rem;
                font-size: .9rem;
                line-height: 1.6;
            }
            .step-link {
                margin-top: .8rem;
                padding-top: .35rem;
                font-size: .82rem;
            }
            .services-trust {
                margin-top: 1.5rem;
                gap: .5rem;
            }

            /* Formulaires : empêcher les champs et boutons de déborder */
            .cards,
            .form-grid {
                grid-template-columns: minmax(0, 1fr);
            }
            #demande,
            #contact {
                padding-left: 1rem;
                padding-right: 1rem;
            }
            #demande input,
            #demande select,
            #demande textarea {
                min-width: 0;
                max-width: 100%;
            }
            #items > div {
                align-items: stretch;
            }
            #items select {
                flex-basis: 100%;
                width: 100%;
            }
            #items input[type="number"] {
                width: 6rem;
            }
            .site-footer .grid {
                gap: 2rem;
                padding-top: 2.5rem;
                padding-bottom: 2.5rem;
            }
            .footer-bottom {
                align-items: flex-start;
                flex-direction: column;
            }
        }

        @media (min-width: 420px) and (max-width: 640px) {
            #produits .gamme-label strong { font-size: 1.35rem; }
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
                       class="login-btn btn-brand rounded-full px-5 py-2 shadow-sm">Connexion</a>
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
                       class="login-btn btn-brand inline-flex items-center rounded-full px-6 py-3 text-sm shadow-sm">
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
            <div class="section-intro">
                <h2 class="text-4xl font-bold uppercase">Nos gammes</h2>
                <div class="section-title-mark" aria-hidden="true"></div>
                <p class="mt-3 max-w-xl text-neutral-600">Les produits de couverture et d’étanchéité que nous proposons, sélectionnés pour accompagner vos chantiers.</p>
            </div>

            <div class="gammes mt-8">
                @forelse ($gammes as $g)
                    <a href="#demande" class="gamme">
                        @if ($g->image)
                            <img src="{{ asset('storage/'.$g->image) }}" alt="{{ $g->name }}" loading="lazy">
                        @else
                            <svg viewBox="0 0 24 24" class="gamme-ph" fill="none" stroke="#A3C108" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 15 L12 6 L21 15"/></svg>
                        @endif
                        <span class="gamme-label"><strong>{{ $g->name }}</strong><em>Demander un devis →</em></span>
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

        <section id="services" class="services services-premium scroll-mt-20">
            <div class="mx-auto max-w-6xl px-5 py-16 sm:py-20 lg:py-24">
                <div class="services-heading">
                    <div class="services-heading-copy">
                        <p class="services-eyebrow">Notre engagement</p>
                        <h2 class="services-main-title">Un service du devis <span>au paiement</span></h2>
                        <div class="section-title-mark" aria-hidden="true"></div>
                        <p class="services-lead">
                            De la première demande jusqu’au règlement, chaque étape est claire et suivie.
                            Vous gardez une vision simple de votre commande et de son avancement.
                        </p>
                    </div>
                    <div class="services-side-note" aria-label="Notre promesse">
                        <span class="services-side-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-11V5l-8-3-8 3v6c0 7 8 11 8 11Z"/><path d="m9 12 2 2 4-4"/></svg>
                        </span>
                        <span><strong>Votre projet, notre priorité</strong><small>Un suivi clair à chaque étape.</small></span>
                    </div>
                </div>

                <div class="steps steps-premium">
                    @foreach ($services as $i => $service)
                        <article class="step step-premium">
                            <div class="step-card step-card-premium">
                                <div class="step-card-top">
                                    <span class="step-icon step-icon-premium">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="#A3C108" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $service[2] !!}</svg>
                                    </span>
                                    <span class="step-tag">ÉTAPE 0{{ $i + 1 }}</span>
                                    <span class="step-num step-num-premium" aria-hidden="true">0{{ $i + 1 }}</span>
                                </div>
                                <h3 class="step-title step-title-premium">{{ $service[0] }}</h3>
                                <p class="step-text step-text-premium">{{ $service[1] }}</p>
                                <a href="#demande" class="step-link">
                                    <span class="step-link-arrow" aria-hidden="true">→</span>
                                    <span>En savoir plus</span>
                                </a>
                            </div>
                        </article>
                    @endforeach
                </div>

                <div class="services-trust" aria-label="Nos engagements">
                    <div class="trust-item">
                        <span class="trust-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="m12 22 2-1c4-2 7-6 7-11V5l-9-3-9 3v5c0 5 3 9 7 11l2 1Z"/><path d="m8 12 2.5 2.5L16 9"/></svg></span>
                        <span><strong>Transparence</strong><small>Aucune mauvaise surprise</small></span>
                    </div>
                    <div class="trust-item">
                        <span class="trust-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg></span>
                        <span><strong>Suivi clair</strong><small>Informé à chaque étape</small></span>
                    </div>
                    <div class="trust-item">
                        <span class="trust-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="m16 11 2 2 4-4"/><path d="M2 12h5l3 3a2 2 0 0 0 3-3l-2-2"/><path d="m8 9 2-2a3 3 0 0 1 4 0l2 2"/><path d="m2 12 3-5h4"/><path d="m22 12-3 5h-4"/></svg></span>
                        <span><strong>À vos côtés</strong><small>Une équipe à votre écoute</small></span>
                    </div>
                    <div class="trust-item">
                        <span class="trust-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 10h18"/><path d="M7 15h3"/></svg></span>
                        <span><strong>Paiement lisible</strong><small>Factures et reçus détaillés</small></span>
                    </div>
                </div>
            </div>
        </section>

        {{-- Demande de devis ou de commande en ligne --}}
        <section id="demande" class="mx-auto max-w-4xl scroll-mt-20 px-5 py-16">
            <div class="section-intro">
                <p class="services-eyebrow">Parlons de votre projet</p>
                <h2 class="mt-2 text-3xl font-bold uppercase sm:text-4xl">Demander un devis ou passer commande</h2>
                <div class="section-title-mark" aria-hidden="true"></div>
                <p class="mt-3 text-neutral-600">
                    Indiquez vos coordonnées et les produits souhaités : notre équipe vous recontacte pour confirmer les prix, les disponibilités et la livraison.
                </p>
            </div>

            @if (session('request_reference'))
                <div class="mt-6 rounded-2xl border border-[#A3C108] bg-[#f1f7d6] p-5 text-[#2f3b00]" role="status">
                    <p class="font-semibold">Merci, votre demande est bien enregistrée.</p>
                    <p class="mt-1 text-sm">Référence : <span class="font-mono font-semibold">{{ session('request_reference') }}</span>.
                        Notre équipe vous contactera très prochainement. Gardez cette référence si vous nous appelez.</p>
                </div>
            @else
                @if ($errors->any())
                    <div class="mt-6 rounded-xl bg-red-50 p-4 text-sm text-red-700" role="alert">
                        Votre demande n’a pas pu être envoyée. Vérifiez les champs signalés ci-dessous.
                    </div>
                @endif

                <form method="POST" action="{{ route('public-requests.store') }}" class="request-panel mt-6 space-y-6 rounded-2xl bg-white p-6 shadow-sm ring-1 ring-neutral-200 sm:p-8">
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

                    @if (count($socialLinks))
                        <div class="mt-6">
                            <h3 class="text-base">Suivez-nous</h3>
                            <div class="social-links" aria-label="Réseaux sociaux">
                                @foreach ($socialLinks as $network => $url)
                                    <a class="social-link" href="{{ $url }}" target="_blank" rel="noopener noreferrer" aria-label="{{ $network }} (nouvel onglet)">
                                        @switch($network)
                                            @case('Facebook')
                                                <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M13.5 21v-8h2.7l.4-3h-3.1V8.1c0-.9.3-1.5 1.6-1.5h1.7V3.9c-.3 0-1.3-.1-2.4-.1-2.4 0-4.1 1.5-4.1 4.2V10H7.5v3h2.8v8z"/></svg>
                                                @break
                                            @case('Instagram')
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r=".8" fill="currentColor" stroke="none"/></svg>
                                                @break
                                            @case('TikTok')
                                                <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M19.6 8.2a7.1 7.1 0 0 1-4.3-1.4v7.1a5.5 5.5 0 1 1-4.8-5.4v3.1a2.5 2.5 0 1 0 1.8 2.4V2.8h3a4.2 4.2 0 0 0 4.3 3.8z"/></svg>
                                                @break
                                            @case('YouTube')
                                                <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M23 7.1a3 3 0 0 0-2.1-2.2C19 4.4 12 4.4 12 4.4s-7 0-8.9.5A3 3 0 0 0 1 7.1a31 31 0 0 0-.5 4.9 31 31 0 0 0 .5 4.9 3 3 0 0 0 2.1 2.2c1.9.5 8.9.5 8.9.5s7 0 8.9-.5a3 3 0 0 0 2.1-2.2 31 31 0 0 0 .5-4.9 31 31 0 0 0-.5-4.9zM9.6 15.6V8.4l6.2 3.6z"/></svg>
                                                @break
                                            @case('LinkedIn')
                                                <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M5.2 3.5a1.8 1.8 0 1 0 0 3.6 1.8 1.8 0 0 0 0-3.6zM3.6 8.5h3.2V20H3.6zM9 8.5h3.1v1.6h.1a3.4 3.4 0 0 1 3.1-1.8c3.3 0 3.9 2.1 3.9 4.8V20H16v-6.1c0-1.5 0-3.3-2-3.3s-2.3 1.6-2.3 3.2V20H9z"/></svg>
                                                @break
                                            @case('WhatsApp')
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20.5 11.7a8.5 8.5 0 0 1-12.6 7.5L3 20.5l1.3-4.7A8.5 8.5 0 1 1 20.5 11.7Z"/><path d="M8.2 7.8c.3-.6.6-.6 1-.6h.4c.2 0 .4.1.5.4l.8 1.8c.1.2.1.4-.1.6l-.6.7c-.2.2-.2.4 0 .7.6 1 1.5 1.8 2.5 2.3.2.1.4.1.6-.1l.8-.9c.2-.2.4-.2.7-.1l1.7.8c.3.1.4.3.4.5 0 .3-.2 1.2-.8 1.6-.5.4-1.2.6-2 .4-1-.2-2.2-.7-3.6-1.9-1.2-1-2.1-2.4-2.3-3.4-.3-.9 0-1.8.5-2.8z"/></svg>
                                                @break
                                        @endswitch
                                        <span>{{ $network }}</span>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif
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
                <div class="footer-bottom mx-auto max-w-6xl px-5 py-5">
                    <p class="text-xs text-neutral-400">© {{ date('Y') }} {{ $company }}. Tous droits réservés.</p>
                    <a href="#landing" class="text-xs font-medium text-neutral-300 hover:text-[#A3C108]">Retour en haut ↑</a>
                </div>
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
    let openedAt = Date.now();

    // ---------- Volet de connexion ----------
    function sync() {
        const open = body.classList.contains('login-open');
        panel.setAttribute('aria-hidden', open ? 'false' : 'true');
        if ('inert' in panel) panel.inert = !open;
        return open;
    }

    function setOpen(open, push) {
        body.classList.toggle('login-open', open);
        if (open) openedAt = Date.now();
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

    // Double-clic en dehors du volet : on le ferme
    document.addEventListener('dblclick', (e) => {
        if (!body.classList.contains('login-open')) return;
        if (e.target.closest('#login-panel')) return; // pas de fermeture en double-cliquant dans le formulaire
        if (Date.now() - openedAt < 600) return;      // évite la fermeture immédiate après un double-clic sur « Connexion »
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
<script>
(function () {
    var d = document.documentElement;
    if (!('IntersectionObserver' in window) || window.matchMedia('(prefers-reduced-motion: reduce)').matches) { return; }
    d.classList.add('js');
    var els = document.querySelectorAll('#produits h2, #produits p, #produits .gamme, #services h2, #services .step, #demande > *, #contact > *, .site-footer .grid > div');
    var io = new IntersectionObserver(function (entries) {
        entries.forEach(function (e) { if (e.isIntersecting) { e.target.classList.add('in'); io.unobserve(e.target); } });
    }, { threshold: 0.12 });
    els.forEach(function (el, i) { el.classList.add('reveal'); el.style.transitionDelay = ((i % 4) * 90) + 'ms'; io.observe(el); });
})();
</script>
</body>
</html>
