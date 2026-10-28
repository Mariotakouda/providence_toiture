@extends('layouts.app')

@section('title', 'Livraisons')

@section('content')
    <form method="GET" class="mb-4 flex flex-wrap items-end gap-2">
        <input type="text" name="q" value="{{ request('q') }}" placeholder="Bon, commande ou client"
               class="rounded-lg border border-line px-3 py-2 text-sm">

        <select name="status" class="rounded-lg border border-line px-3 py-2 text-sm">
            <option value="">Tous les statuts</option>
            @foreach ($statuses as $status)
                <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>
            @endforeach
        </select>

        <button class="rounded-lg bg-soft px-4 py-2 text-sm text-ink hover:bg-soft-hover">Filtrer</button>
    </form>

    <div class="overflow-x-auto rounded-xl bg-white shadow">
        <table class="min-w-full text-left text-sm">
            <thead class="bg-page text-xs uppercase text-muted">
                <tr>
                    <th class="px-4 py-3">Bon</th>
                    <th class="px-4 py-3">Commande</th>
                    <th class="px-4 py-3">Client</th>
                    <th class="px-4 py-3">Prévue le</th>
                    <th class="px-4 py-3">Livreur</th>
                    <th class="px-4 py-3">Statut</th>
                    <th class="px-4 py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-soft">
                @forelse ($deliveries as $delivery)
                    <tr>
                        <td class="px-4 py-3 font-mono text-xs">
                            <a href="{{ route('deliveries.show', $delivery) }}" class="hover:underline">{{ $delivery->delivery_number }}</a>
                        </td>
                        <td class="px-4 py-3 font-mono text-xs">{{ $delivery->order->order_number }}</td>
                        <td class="px-4 py-3">{{ $delivery->order->customer->name }}</td>
                        <td class="px-4 py-3">{{ $delivery->planned_at?->format('d/m/Y H:i') ?? '-' }}</td>
                        <td class="px-4 py-3">{{ $delivery->driver_name ?? '-' }}</td>
                        <td class="px-4 py-3">
                            <span class="rounded-full px-2 py-1 text-xs {{ $delivery->status->badgeClass() }}">{{ $delivery->status->label() }}</span>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex flex-wrap justify-end gap-x-3 gap-y-1">
                                <a href="{{ route('deliveries.show', $delivery) }}" class="text-link hover:underline">Voir</a>
                                <a href="{{ route('deliveries.pdf', $delivery) }}" target="_blank" class="text-link hover:underline">PDF</a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-6 text-center text-muted">Aucun bon de livraison.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $deliveries->links() }}</div>
@endsection
