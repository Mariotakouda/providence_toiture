@extends('layouts.app')

@section('title', 'Rapport financier')

@section('content')
    <form method="GET" class="mb-4 flex flex-wrap items-end gap-2">
        @include('partials.period-filter')
        <button class="rounded-lg bg-soft px-4 py-2 text-sm text-ink hover:bg-soft-hover">Filtrer</button>
        <a href="{{ route('reports.financial.export', request()->query()) }}"
           class="rounded-lg border border-line bg-white px-4 py-2 text-sm hover:bg-page">Exporter les paiements en CSV</a>
    </form>

    <p class="mb-4 text-sm text-muted">Du {{ $from->format('d/m/Y') }} au {{ $to->format('d/m/Y') }}.</p>

    <div class="mb-6 grid gap-4 md:grid-cols-3">
        <div class="rounded-xl bg-white p-5 shadow">
            <p class="text-xs uppercase text-muted">Chiffre d’affaires facturé</p>
            <p class="mt-1 text-2xl font-semibold">{{ \App\Support\Format::money($invoiced) }}</p>
            <p class="mt-1 text-xs text-muted">Factures émises sur la période, taxes et frais compris.</p>
        </div>
        <div class="rounded-xl bg-white p-5 shadow">
            <p class="text-xs uppercase text-muted">Encaissements</p>
            <p class="mt-1 text-2xl font-semibold">{{ \App\Support\Format::money($collected) }}</p>
            <p class="mt-1 text-xs text-muted">Paiements validés reçus sur la période.</p>
        </div>
        @if ($margin)
            <div class="rounded-xl bg-white p-5 shadow">
                <p class="text-xs uppercase text-muted">Marge estimée</p>
                <p class="mt-1 text-2xl font-semibold">{{ \App\Support\Format::money($margin['margin']) }}</p>
                <p class="mt-1 text-xs text-muted">
                    Ventes HT {{ \App\Support\Format::money($margin['revenue']) }} moins coût {{ \App\Support\Format::money($margin['cost']) }},
                    d’après le coût d’achat actuel du catalogue.
                    @if ($margin['missing'] > 0) {{ $margin['missing'] }} ligne(s) sans coût exclue(s). @endif
                </p>
            </div>
        @endif
    </div>

    <div class="grid gap-6 md:grid-cols-2">
        <div class="rounded-xl bg-white p-5 shadow">
            <h3 class="mb-2 font-semibold">Encaissements par mode</h3>
            <div class="overflow-x-auto"><table class="min-w-full text-sm">
                @forelse ($byMethod as $row)
                    <tr>
                        <td class="py-1">{{ \App\Enums\PaymentMethod::tryFrom($row->method)?->label() ?? $row->method }} ({{ $row->n }})</td>
                        <td class="py-1 text-right">{{ \App\Support\Format::money($row->total) }}</td>
                    </tr>
                @empty
                    <tr><td class="py-2 text-muted">Aucun encaissement.</td></tr>
                @endforelse
            </table></div>
            @if ($cancelled['count'] > 0)
                <p class="mt-3 text-xs text-muted">
                    Paiements annulés sur la période (exclus des encaissements) :
                    {{ $cancelled['count'] }} pour {{ \App\Support\Format::money($cancelled['total']) }}.
                </p>
            @endif
        </div>

        <div class="rounded-xl bg-white p-5 shadow">
            <h3 class="mb-2 font-semibold">Créances (à ce jour)</h3>
            <p class="text-sm">
                Reste à encaisser : <span class="font-semibold">{{ \App\Support\Format::money($outstanding['due']) }}</span>
                ({{ $outstanding['count'] }} facture(s)).
            </p>
            <p class="text-sm {{ $outstanding['overdue_count'] > 0 ? 'text-red-700' : 'text-muted' }}">
                En retard : {{ \App\Support\Format::money($outstanding['overdue_due']) }} ({{ $outstanding['overdue_count'] }}).
            </p>
        </div>

        <div class="rounded-xl bg-white p-5 shadow md:col-span-2">
            <h3 class="mb-2 font-semibold">Soldes clients les plus élevés</h3>
            <div class="overflow-x-auto"><table class="min-w-full text-left text-sm">
                <thead class="text-xs uppercase text-muted">
                    <tr><th class="py-1">Client</th><th class="py-1 text-right">Factures ouvertes</th><th class="py-1 text-right">Solde dû</th></tr>
                </thead>
                <tbody class="divide-y divide-soft">
                    @forelse ($balances as $balance)
                        <tr>
                            <td class="py-1">
                                @can('customers.view')
                                    <a href="{{ route('customers.show', $balance->id) }}" class="text-link hover:underline">{{ $balance->name }}</a>
                                @else
                                    {{ $balance->name }}
                                @endcan
                            </td>
                            <td class="py-1 text-right">{{ $balance->invoices_count }}</td>
                            <td class="py-1 text-right">{{ \App\Support\Format::money($balance->due) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="py-2 text-muted">Aucun solde dû.</td></tr>
                    @endforelse
                </tbody>
            </table></div>
        </div>
    </div>
@endsection
