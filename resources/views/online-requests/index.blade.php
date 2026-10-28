@extends('layouts.app')

@section('title', 'Demandes en ligne')

@section('content')
    <form method="GET" class="mb-4 flex flex-wrap items-end gap-2">
        <input type="text" name="q" value="{{ request('q') }}" placeholder="Référence, nom, téléphone, ville"
               class="w-64 rounded-lg border border-line px-3 py-2 text-sm">

        <select name="status" class="rounded-lg border border-line px-3 py-2 text-sm">
            <option value="open" @selected($status === 'open')>À traiter (nouvelles et en cours)</option>
            <option value="new" @selected($status === 'new')>Nouvelles</option>
            <option value="in_progress" @selected($status === 'in_progress')>En cours</option>
            <option value="converted" @selected($status === 'converted')>Converties en devis</option>
            <option value="closed" @selected($status === 'closed')>Clôturées</option>
            <option value="all" @selected($status === 'all')>Toutes</option>
        </select>

        <select name="type" class="rounded-lg border border-line px-3 py-2 text-sm">
            <option value="">Tous les types</option>
            @foreach ($types as $type)
                <option value="{{ $type->value }}" @selected(request('type') === $type->value)>{{ $type->label() }}</option>
            @endforeach
        </select>

        <button class="rounded-lg bg-sidebar px-4 py-2 text-sm text-white hover:opacity-90">Filtrer</button>
    </form>

    <div class="overflow-x-auto rounded-xl bg-white shadow">
        <table class="min-w-full text-left text-sm">
            <thead class="bg-soft text-xs uppercase text-muted">
                <tr>
                    <th class="px-4 py-3">Référence</th>
                    <th class="px-4 py-3">Reçue le</th>
                    <th class="px-4 py-3">Type</th>
                    <th class="px-4 py-3">Nom</th>
                    <th class="px-4 py-3">Téléphone</th>
                    <th class="px-4 py-3">Ville</th>
                    <th class="px-4 py-3 text-right">Produits</th>
                    <th class="px-4 py-3">Statut</th>
                    <th class="px-4 py-3">Traitée par</th>
                    <th class="px-4 py-3 text-right"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-line">
                @forelse ($requests as $item)
                    <tr class="{{ $item->status === \App\Enums\OnlineRequestStatus::New ? 'bg-yellow-50/50' : '' }}">
                        <td class="px-4 py-3 font-mono text-xs">
                            <a href="{{ route('online-requests.show', $item) }}" class="hover:underline">{{ $item->reference }}</a>
                        </td>
                        <td class="whitespace-nowrap px-4 py-3">{{ $item->created_at->format('d/m/Y H:i') }}</td>
                        <td class="px-4 py-3">{{ $item->type->label() }}</td>
                        <td class="px-4 py-3 font-medium">{{ $item->name }}</td>
                        <td class="px-4 py-3">{{ $item->phone }}</td>
                        <td class="px-4 py-3">{{ $item->city ?? '-' }}</td>
                        <td class="px-4 py-3 text-right">{{ $item->items_count }}</td>
                        <td class="px-4 py-3">
                            <span class="rounded-full px-2 py-1 text-xs {{ $item->status->badgeClass() }}">{{ $item->status->label() }}</span>
                        </td>
                        <td class="px-4 py-3">{{ $item->assignee?->name ?? '-' }}</td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('online-requests.show', $item) }}" class="text-link hover:underline">Ouvrir</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="10" class="px-4 py-6 text-center text-muted">Aucune demande.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $requests->links() }}</div>
@endsection
