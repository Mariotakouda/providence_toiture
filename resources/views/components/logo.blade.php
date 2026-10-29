@props(['class' => 'h-10 w-auto', 'light' => false])

<img src="{{ asset($light ? 'images/logo-blanc.png' : 'images/logo.png') }}" alt="Providence Toiture" {{ $attributes->merge(['class' => $class]) }}>
