@extends('layouts.app')

@section('title', 'Devis')

@section('content')
    <div class="mb-4 flex flex-wrap items-end justify-between gap-3">
        <form method="GET" class="flex flex-wrap items-end gap-2">
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

        @can('create', \App\Models\Quote::class)
            <a href="{{ route('quotes.create') }}"
               class="rounded-lg bg-brand px-4 py-2 text-sm font-medium text-on-brand hover:bg-brand-hover">Nouveau devis</a>
        @endcan
    </div>

    <div class="overflow-x-auto rounded-xl bg-white shadow">
        <table class="min-w-full text-left text-sm">
            <thead class="bg-page text-xs uppercase text-muted">
                <tr>
                    <th class="px-4 py-3">Numéro</th>
                    <th class="px-4 py-3">Client</th>
                    <th class="px-4 py-3">Émis le</th>
                    <th class="px-4 py-3">Valide jusqu’au</th>
                    <th class="px-4 py-3">Statut</th>
                    <th class="px-4 py-3 text-right">Total</th>
                    <th class="px-4 py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-soft">
                @forelse ($quotes as $quote)
                    <tr>
                        <td class="px-4 py-3 font-mono text-xs">
                            <a href="{{ route('quotes.show', $quote) }}" class="hover:underline">{{ $quote->quote_number }}</a>
                        </td>
                        <td class="px-4 py-3">{{ $quote->customer->name }}</td>
                        <td class="px-4 py-3">{{ $quote->issue_date->format('d/m/Y') }}</td>
                        <td class="px-4 py-3">{{ $quote->valid_until->format('d/m/Y') }}</td>
                        <td class="px-4 py-3">
                            <span class="rounded-full px-2 py-1 text-xs {{ $quote->status->badgeClass() }}">{{ $quote->status->label() }}</span>
                        </td>
                        <td class="px-4 py-3 text-right">{{ \App\Support\Format::money($quote->total) }}</td>
                        <td class="px-4 py-3">
                            <div class="flex flex-wrap justify-end gap-x-3 gap-y-1">
                                <a href="{{ route('quotes.show', $quote) }}" class="text-link hover:underline">Voir</a>
                                <a href="{{ route('quotes.pdf', $quote) }}" target="_blank" class="text-link hover:underline">PDF</a>
                                @can('update', $quote)
                                    <a href="{{ route('quotes.edit', $quote) }}" class="text-link hover:underline">Modifier</a>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-6 text-center text-muted">Aucun devis.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $quotes->links() }}</div>
@endsection
