@extends('layouts.app')

@section('title', 'Paiements')

@section('content')
    <div class="mb-4 rounded-xl bg-white p-4 shadow md:w-1/3">
        <p class="text-xs uppercase text-muted">Encaissements (paiements validés du filtre)</p>
        <p class="mt-1 text-lg font-semibold">{{ \App\Support\Format::money($collected) }}</p>
    </div>

    <form method="GET" class="mb-4 flex flex-wrap items-end gap-2">
        <input type="text" name="q" value="{{ request('q') }}" placeholder="Reçu, facture, client, référence"
               class="w-64 rounded-lg border border-line px-3 py-2 text-sm">

        <select name="method" class="rounded-lg border border-line px-3 py-2 text-sm">
            <option value="">Tous les modes</option>
            @foreach ($methods as $method)
                <option value="{{ $method->value }}" @selected(request('method') === $method->value)>{{ $method->label() }}</option>
            @endforeach
        </select>

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
                    <th class="px-4 py-3">Reçu</th>
                    <th class="px-4 py-3">Date</th>
                    <th class="px-4 py-3">Facture</th>
                    <th class="px-4 py-3">Client</th>
                    <th class="px-4 py-3">Mode</th>
                    <th class="px-4 py-3">Référence</th>
                    <th class="px-4 py-3">Statut</th>
                    <th class="px-4 py-3 text-right">Montant</th>
                    <th class="px-4 py-3 text-right"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-soft">
                @forelse ($payments as $payment)
                    <tr>
                        <td class="px-4 py-3 font-mono text-xs">{{ $payment->payment_number }}</td>
                        <td class="px-4 py-3">{{ $payment->payment_date->format('d/m/Y') }}</td>
                        <td class="px-4 py-3 font-mono text-xs">
                            @can('invoices.view')
                                <a href="{{ route('invoices.show', $payment->invoice) }}" class="text-link hover:underline">{{ $payment->invoice->invoice_number }}</a>
                            @else
                                {{ $payment->invoice->invoice_number }}
                            @endcan
                        </td>
                        <td class="px-4 py-3">{{ $payment->invoice->customer_snapshot['name'] ?? $payment->invoice->customer->name }}</td>
                        <td class="px-4 py-3">{{ $payment->method->label() }}</td>
                        <td class="px-4 py-3">{{ $payment->reference ?? '-' }}</td>
                        <td class="px-4 py-3">
                            <span class="rounded-full px-2 py-1 text-xs {{ $payment->status->badgeClass() }}">{{ $payment->status->label() }}</span>
                        </td>
                        <td class="px-4 py-3 text-right">{{ \App\Support\Format::money($payment->amount) }}</td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('payments.receipt', $payment) }}" target="_blank" class="text-link hover:underline">Reçu</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="px-4 py-6 text-center text-muted">Aucun paiement.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $payments->links() }}</div>
@endsection
