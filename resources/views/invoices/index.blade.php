@extends('layouts.app')

@section('title', 'Factures')

@section('content')
    <div class="mb-4 rounded-xl bg-white p-4 shadow md:w-1/3">
        <p class="text-xs uppercase text-muted">Reste à encaisser (factures émises)</p>
        <p class="mt-1 text-lg font-semibold">{{ \App\Support\Format::money($outstanding) }}</p>
    </div>

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

        <label class="flex items-center gap-2 pb-2 text-sm">
            <input type="checkbox" name="overdue" value="1" @checked(request()->boolean('overdue'))> En retard
        </label>

        <button class="rounded-lg bg-soft px-4 py-2 text-sm text-ink hover:bg-soft-hover">Filtrer</button>
    </form>

    <div class="overflow-x-auto rounded-xl bg-white shadow">
        <table class="min-w-full text-left text-sm">
            <thead class="bg-page text-xs uppercase text-muted">
                <tr>
                    <th class="px-4 py-3">Numéro</th>
                    <th class="px-4 py-3">Client</th>
                    <th class="px-4 py-3">Émise le</th>
                    <th class="px-4 py-3">Échéance</th>
                    <th class="px-4 py-3">Statut</th>
                    <th class="px-4 py-3 text-right">Total</th>
                    <th class="px-4 py-3 text-right">Reste à payer</th>
                    <th class="px-4 py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-soft">
                @forelse ($invoices as $invoice)
                    <tr>
                        <td class="px-4 py-3 font-mono text-xs">
                            <a href="{{ route('invoices.show', $invoice) }}" class="hover:underline">{{ $invoice->invoice_number }}</a>
                        </td>
                        <td class="px-4 py-3">{{ $invoice->customer_snapshot['name'] ?? $invoice->customer->name }}</td>
                        <td class="px-4 py-3">{{ $invoice->issue_date->format('d/m/Y') }}</td>
                        <td class="px-4 py-3">
                            {{ $invoice->due_date?->format('d/m/Y') ?? '-' }}
                            @if ($invoice->isOverdue())
                                <span class="ml-1 rounded-full bg-red-100 px-2 py-0.5 text-xs text-red-700">En retard</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <span class="rounded-full px-2 py-1 text-xs {{ $invoice->status->badgeClass() }}">{{ $invoice->status->label() }}</span>
                        </td>
                        <td class="px-4 py-3 text-right">{{ \App\Support\Format::money($invoice->total) }}</td>
                        <td class="px-4 py-3 text-right">
                            {{ $invoice->status === \App\Enums\InvoiceStatus::Cancelled ? '-' : \App\Support\Format::money($invoice->balance_due) }}
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex flex-wrap justify-end gap-x-3 gap-y-1">
                                <a href="{{ route('invoices.show', $invoice) }}" class="text-link hover:underline">Voir</a>
                                <a href="{{ route('invoices.pdf', $invoice) }}" target="_blank" class="text-link hover:underline">PDF</a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="px-4 py-6 text-center text-muted">Aucune facture.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $invoices->links() }}</div>
@endsection
