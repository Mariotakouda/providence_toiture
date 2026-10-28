@props(['title', 'icon', 'routes' => [], 'active' => null])

@php
    $isActive = $active ?? (! empty($routes) && request()->routeIs(...$routes));
    $id = \Illuminate\Support\Str::slug($title);
@endphp

<div class="nav-group" data-nav-group="{{ $id }}" data-active="{{ $isActive ? 'true' : 'false' }}" data-open="{{ $isActive ? 'true' : 'false' }}">
    <button type="button" data-nav-toggle aria-expanded="{{ $isActive ? 'true' : 'false' }}" aria-controls="nav-{{ $id }}"
            class="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors
                   {{ $isActive ? 'text-white' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">
        <x-icon :name="$icon" class="h-5 w-5 shrink-0 {{ $isActive ? 'text-brand' : 'text-slate-400' }}"/>
        <span class="flex-1 text-left">{{ $title }}</span>
        <x-icon name="chevron" class="nav-chevron h-4 w-4 shrink-0 text-slate-500"/>
    </button>

    <div id="nav-{{ $id }}" class="nav-panel">
        <div>
            <div class="ml-[1.35rem] mt-1 space-y-0.5 border-l border-white/15 pl-3">
                {{ $slot }}
            </div>
        </div>
    </div>
</div>
