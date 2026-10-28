<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Providence Toiture')</title>
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-dvh bg-gradient-to-br from-white via-white to-[#f1f7d6] antialiased">
<div class="flex min-h-dvh items-center justify-center p-4">
    <div class="w-full max-w-md">
        <a href="{{ route('home') }}" class="mb-6 block">
            <img src="{{ asset('images/logo.png') }}" alt="Providence Toiture" class="mx-auto h-16 w-auto">
        </a>

        <div class="rounded-2xl bg-white p-8 shadow-xl ring-1 ring-black/5">
            @yield('content')
        </div>

        <p class="mt-6 text-center text-xs text-neutral-400">© {{ date('Y') }} Providence Toiture</p>
    </div>
</div>
</body>
</html>
