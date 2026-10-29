<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('title') — Providence Toiture</title>
    <style nonce="{{ Vite::cspNonce() }}">
        body { margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center;
               font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif; background: #f8fafc; color: #1e293b; }
        main { text-align: center; padding: 2rem; max-width: 28rem; }
        .code { font-size: 4rem; font-weight: 700; color: #94a3b8; line-height: 1; }
        h1 { font-size: 1.25rem; margin: 1rem 0 .5rem; }
        p { margin: 0 0 1.5rem; color: #475569; }
        a { color: #1d4ed8; }
    </style>
</head>
<body>
<main>
    <div class="code">@yield('code')</div>
    <h1>@yield('title')</h1>
    <p>@yield('message')</p>
    <a href="/">Retour à l’accueil</a>
</main>
</body>
</html>
