@extends('layouts.app')

@section('title', 'Permissions : '.\App\Enums\RoleName::labelFor($role->name))

@section('content')
    <form method="POST" action="{{ route('roles.update', $role) }}" class="w-full max-w-6xl space-y-4">
        @csrf
        @method('PUT')

        @if ($locked)
            <div class="rounded-lg bg-blue-50 p-3 text-sm text-blue-800">
                Le rôle administrateur a toutes les permissions et ne peut pas être modifié.
            </div>
        @endif

        @error('permissions.*')
            <div class="rounded-lg bg-red-50 p-3 text-sm text-red-700">{{ $message }}</div>
        @enderror

        @foreach ($groups as $group => $permissions)
            @php $visible = array_filter($permissions, fn ($label, $name) => in_array($name, $existing, true), ARRAY_FILTER_USE_BOTH); @endphp

            @if ($visible)
                <section class="rounded-xl bg-white p-5 shadow">
                    <h3 class="mb-3 font-semibold">{{ $group }}</h3>
                    <div class="grid gap-2 md:grid-cols-2">
                        @foreach ($visible as $name => $label)
                            <label class="flex items-start gap-2 text-sm">
                                <input type="checkbox" name="permissions[]" value="{{ $name }}"
                                       class="mt-1"
                                       @checked($locked || in_array($name, old('permissions', $current), true))
                                       @disabled($locked)>
                                <span>
                                    {{ $label }}
                                    <span class="block font-mono text-xs text-muted">{{ $name }}</span>
                                    @if ($name === 'users.manage')
                                        <span class="block text-xs text-orange-700">Attention : ce droit permet de modifier les rôles et les comptes.</span>
                                    @endif
                                </span>
                            </label>
                        @endforeach
                    </div>
                </section>
            @endif
        @endforeach

        @if ($others)
            <section class="rounded-xl bg-white p-5 shadow">
                <h3 class="mb-3 font-semibold">Autres</h3>
                <div class="grid gap-2 md:grid-cols-2">
                    @foreach ($others as $name)
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" name="permissions[]" value="{{ $name }}"
                                   @checked($locked || in_array($name, old('permissions', $current), true)) @disabled($locked)>
                            <span class="font-mono text-xs">{{ $name }}</span>
                        </label>
                    @endforeach
                </div>
            </section>
        @endif

        <div class="flex gap-3">
            @unless ($locked)
                <button class="rounded-lg bg-brand px-4 py-2 text-sm font-medium text-on-brand hover:bg-brand-hover">Enregistrer</button>
            @endunless
            <a href="{{ route('roles.index') }}" class="rounded-lg border border-line px-4 py-2 text-sm hover:bg-page">Retour</a>
        </div>
    </form>
@endsection
