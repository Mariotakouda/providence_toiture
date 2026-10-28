@extends('layouts.app')

@section('title', 'Rôles et permissions')

@section('content')
    <p class="mb-4 max-w-3xl text-sm text-muted">
        Chaque rôle regroupe des permissions appliquées côté serveur. Le rôle administrateur a toujours toutes les permissions et n’est pas modifiable.
    </p>

    <div class="overflow-x-auto rounded-xl bg-white shadow">
        <table class="min-w-full text-left text-sm">
            <thead class="bg-page text-xs uppercase text-muted">
                <tr>
                    <th class="px-4 py-3">Rôle</th>
                    <th class="px-4 py-3 text-right">Utilisateurs</th>
                    <th class="px-4 py-3 text-right">Permissions</th>
                    <th class="px-4 py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-soft">
                @foreach ($roles as $role)
                    <tr>
                        <td class="px-4 py-3 font-medium">{{ \App\Enums\RoleName::labelFor($role->name) }}</td>
                        <td class="px-4 py-3 text-right">{{ $role->users_count }}</td>
                        <td class="px-4 py-3 text-right">{{ $role->permissions_count }} / {{ $total }}</td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('roles.edit', $role) }}" class="text-link hover:underline">
                                {{ $role->name === 'admin' ? 'Voir' : 'Modifier' }}
                            </a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection
