@props(['class' => 'h-10 w-auto'])

<img src="{{ asset('images/logo.png') }}" alt="Providence Toiture" {{ $attributes->merge(['class' => $class]) }}>
