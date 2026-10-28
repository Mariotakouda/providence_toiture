@extends('layouts.app')

@section('title', 'Commandes')

@section('content')
    <form method="GET" class="mb-4 flex flex-wrap items-end gap-2">
        <input type="text" name="q" value="{{ request('q') }}" placeholder="Numéro ou client"
               class="rounded-lg border border-line px-3 py-2 text-sm">

        <select name="status" class="rounded-lg border border-line px-3 py-2 text-sm">
            <option value="">Tous les statuts</option>
            @foreach ($statuses as $status)
                <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>
            @endforeach
        </select>

        <div>
            <label class="block text-xs text-muted">Du</label>
            <input type="date" name="from" value="{{ request('from') }}" class="rounded-lg border border-line px-3 py-2 text-sm">
        </div>
        <div>
            <label class="block text-xs text-muted">Au</label>
            <input type="date" name="to" value="{{ request('to') }}" class="rounded-lg border border-line px-3 py-2 text-sm">
        </div>

        <button class="rounded-lg bg-soft px-4 py-2 text-sm text-ink hover:bg-soft-hover">Filtrer</button>
    </form>

    <div class="overflow-x-auto rounded-xl bg-white shadow">
        <table class="min-w-full text-left text-sm">
            <thead class="bg-page text-xs uppercase text-muted">
                <tr>
                    <th class="px-4 py-3">Numéro</th>
                    <th class="px-4 py-3">Client</th>
                    <th class="px-4 py-3">Date</th>
                    <th class="px-4 py-3">Statut</th>
                    <th class="px-4 py-3 text-right">Total</th>
                    <th class="px-4 py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-soft">
                @forelse ($orders as $order)
                    <tr>
                        <td class="px-4 py-3 font-mono text-xs">
                            <a href="{{ route('orders.show', $order) }}" class="hover:underline">{{ $order->order_number }}</a>
                        </td>
                        <td class="px-4 py-3">{{ $order->customer->name }}</td>
                        <td class="px-4 py-3">{{ $order->order_date->format('d/m/Y') }}</td>
                        <td class="px-4 py-3">
                            <span class="rounded-full px-2 py-1 text-xs {{ $order->status->badgeClass() }}">{{ $order->status->label() }}</span>
                        </td>
                        <td class="px-4 py-3 text-right">{{ \App\Support\Format::money($order->total) }}</td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('orders.show', $order) }}" class="text-link hover:underline">Voir</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-6 text-center text-muted">Aucune commande.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $orders->links() }}</div>
@endsection
