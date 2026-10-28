@extends('layouts.app')

@section('title', 'Utilisateurs')

@section('content')
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <form method="GET" class="flex flex-wrap gap-2">
            <input type="text" name="q" value="{{ request('q') }}" placeholder="Nom, e-mail, téléphone"
                   class="rounded-lg border border-line px-3 py-2 text-sm">

            <select name="role" class="rounded-lg border border-line px-3 py-2 text-sm">
                <option value="">Tous les rôles</option>
                @foreach ($roles as $role)
                    <option value="{{ $role }}" @selected(request('role') === $role)>
                        {{ \App\Enums\RoleName::labelFor($role) }}
                    </option>
                @endforeach
            </select>

            <select name="status" class="rounded-lg border border-line px-3 py-2 text-sm">
                <option value="">Tous les statuts</option>
                <option value="active" @selected(request('status') === 'active')>Actifs</option>
                <option value="inactive" @selected(request('status') === 'inactive')>Inactifs</option>
            </select>

            <button class="rounded-lg bg-soft px-4 py-2 text-sm text-ink hover:bg-soft-hover">Filtrer</button>
        </form>

        <a href="{{ route('users.create') }}"
           class="rounded-lg bg-brand px-4 py-2 text-sm font-medium text-on-brand hover:bg-brand-hover">
            Nouvel utilisateur
        </a>
    </div>

    <div class="overflow-x-auto rounded-xl bg-white shadow">
        <table class="min-w-full text-left text-sm">
            <thead class="bg-page text-xs uppercase text-muted">
                <tr>
                    <th class="px-4 py-3">Nom</th>
                    <th class="px-4 py-3">E-mail</th>
                    <th class="px-4 py-3">Téléphone</th>
                    <th class="px-4 py-3">Rôle</th>
                    <th class="px-4 py-3">Statut</th>
                    <th class="px-4 py-3">Dernière connexion</th>
                    <th class="px-4 py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-soft">
                @forelse ($users as $user)
                    <tr>
                        <td class="px-4 py-3 font-medium">{{ $user->name }}</td>
                        <td class="px-4 py-3">{{ $user->email }}</td>
                        <td class="px-4 py-3">{{ $user->phone ?? '-' }}</td>
                        <td class="px-4 py-3">{{ \App\Enums\RoleName::labelFor($user->roles->first()?->name) }}</td>
                        <td class="px-4 py-3">
                            @if ($user->is_active)
                                <span class="rounded-full bg-green-100 px-2 py-1 text-xs text-green-700">Actif</span>
                            @else
                                <span class="rounded-full bg-soft px-2 py-1 text-xs text-muted">Inactif</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-muted">
                            {{ $user->last_login_at?->diffForHumans() ?? 'Jamais' }}
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex flex-wrap justify-end gap-x-3 gap-y-1">
                                <a href="{{ route('users.edit', $user) }}" class="text-link hover:underline">Modifier</a>

                                @if (! $user->is(auth()->user()))
                                    <form method="POST" action="{{ route('users.toggle-status', $user) }}"
                                          onsubmit="return confirm('{{ $user->is_active ? 'Désactiver' : 'Activer' }} ce compte ?')">
                                        @csrf
                                        @method('PATCH')
                                        <button class="{{ $user->is_active ? 'text-red-600' : 'text-green-600' }} hover:underline">
                                            {{ $user->is_active ? 'Désactiver' : 'Activer' }}
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-6 text-center text-muted">Aucun utilisateur trouvé.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $users->links() }}</div>
@endsection
