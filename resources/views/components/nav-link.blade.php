@props(['href', 'match' => null, 'active' => null, 'icon' => null])

@php
    $isActive = $active ?? ($match ? request()->routeIs($match) : false);

    $base = $icon
        ? 'flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors'
        : 'block rounded-md px-3 py-1.5 text-sm transition-colors';

    $state = $isActive
        ? ($icon ? 'bg-brand text-on-brand' : 'bg-brand/20 font-medium text-white')
        : 'text-slate-300 hover:bg-white/10 hover:text-white';
@endphp

<a href="{{ $href }}" @if ($isActive) aria-current="page" @endif class="{{ $base }} {{ $state }}">
    @if ($icon)
        <x-icon :name="$icon" class="h-5 w-5 shrink-0 {{ $isActive ? 'text-on-brand' : 'text-slate-400' }}"/>
    @endif
    <span>{{ $slot }}</span>
</a>
