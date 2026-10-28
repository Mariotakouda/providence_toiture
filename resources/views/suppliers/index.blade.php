@extends('layouts.app')

@section('title', 'Fournisseurs')

@section('content')
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <form method="GET" class="flex flex-wrap gap-2">
            <input type="text" name="q" value="{{ request('q') }}" placeholder="Nom, téléphone, e-mail, ville"
                   class="w-64 rounded-lg border border-line px-3 py-2 text-sm">

            <select name="status" class="rounded-lg border border-line px-3 py-2 text-sm">
                <option value="active" @selected($status === 'active')>Actifs</option>
                <option value="inactive" @selected($status === 'inactive')>Inactifs</option>
                <option value="all" @selected($status === 'all')>Tous</option>
            </select>

            <button class="rounded-lg bg-soft px-4 py-2 text-sm text-ink hover:bg-soft-hover">Filtrer</button>
        </form>

        @can('create', \App\Models\Supplier::class)
            <a href="{{ route('suppliers.create') }}"
               class="rounded-lg bg-brand px-4 py-2 text-sm font-medium text-on-brand hover:bg-brand-hover">Nouveau fournisseur</a>
        @endcan
    </div>

    <div class="overflow-x-auto rounded-xl bg-white shadow">
        <table class="min-w-full text-left text-sm">
            <thead class="bg-page text-xs uppercase text-muted">
                <tr>
                    <th class="px-4 py-3">Nom</th>
                    <th class="px-4 py-3">Téléphone</th>
                    <th class="px-4 py-3">E-mail</th>
                    <th class="px-4 py-3">Ville</th>
                    <th class="px-4 py-3">Statut</th>
                    <th class="px-4 py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-soft">
                @forelse ($suppliers as $supplier)
                    <tr>
                        <td class="px-4 py-3 font-medium">
                            <a href="{{ route('suppliers.show', $supplier) }}" class="hover:underline">{{ $supplier->name }}</a>
                        </td>
                        <td class="px-4 py-3">{{ $supplier->phone ?? '-' }}</td>
                        <td class="px-4 py-3">{{ $supplier->email ?? '-' }}</td>
                        <td class="px-4 py-3">{{ $supplier->city ?? '-' }}</td>
                        <td class="px-4 py-3">
                            @if ($supplier->is_active)
                                <span class="rounded-full bg-green-100 px-2 py-1 text-xs text-green-700">Actif</span>
                            @else
                                <span class="rounded-full bg-soft px-2 py-1 text-xs text-muted">Inactif</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex flex-wrap justify-end gap-x-3 gap-y-1">
                                <a href="{{ route('suppliers.show', $supplier) }}" class="text-link hover:underline">Voir</a>
                                @can('update', $supplier)
                                    <a href="{{ route('suppliers.edit', $supplier) }}" class="text-link hover:underline">Modifier</a>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-6 text-center text-muted">Aucun fournisseur trouvé.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $suppliers->links() }}</div>
@endsection
