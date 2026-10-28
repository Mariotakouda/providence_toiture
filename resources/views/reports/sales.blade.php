@extends('layouts.app')

@section('title', 'Rapport de ventes')

@section('content')
    <form method="GET" class="mb-4 flex flex-wrap items-end gap-2">
        @include('partials.period-filter')

        <div>
            <label class="block text-xs text-muted">Catégorie</label>
            <select name="category_id" class="rounded-lg border border-line px-3 py-2 text-sm">
                <option value="">Toutes</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}" @selected((string) request('category_id') === (string) $category->id)>{{ $category->name }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-xs text-muted">Commercial</label>
            <select name="commercial_id" class="rounded-lg border border-line px-3 py-2 text-sm">
                <option value="">Tous</option>
                @foreach ($commercials as $commercial)
                    <option value="{{ $commercial->id }}" @selected((string) request('commercial_id') === (string) $commercial->id)>{{ $commercial->name }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-xs text-muted">Statut de facture</label>
            <select name="status" class="rounded-lg border border-line px-3 py-2 text-sm">
                <option value="">Émises, partiellement payées et payées</option>
                @foreach ($statuses as $status)
                    <option value="{{ $status }}" @selected(request('status') === $status)>{{ \App\Enums\InvoiceStatus::from($status)->label() }}</option>
                @endforeach
            </select>
        </div>

        <button class="rounded-lg bg-soft px-4 py-2 text-sm text-ink hover:bg-soft-hover">Filtrer</button>
        <a href="{{ route('reports.sales.export', request()->query()) }}"
           class="rounded-lg border border-line bg-white px-4 py-2 text-sm hover:bg-page">Exporter en CSV</a>
    </form>

    <p class="mb-4 text-sm text-muted">Du {{ $from->format('d/m/Y') }} au {{ $to->format('d/m/Y') }}.</p>

    <div class="mb-6 grid gap-4 md:grid-cols-3">
        <div class="rounded-xl bg-white p-5 shadow">
            <p class="text-xs uppercase text-muted">Factures</p>
            <p class="mt-1 text-2xl font-semibold">{{ $report['invoices_count'] }}</p>
        </div>
        <div class="rounded-xl bg-white p-5 shadow">
            <p class="text-xs uppercase text-muted">Ventes (lignes, hors taxe et frais)</p>
            <p class="mt-1 text-2xl font-semibold">{{ \App\Support\Format::money($report['lines_total']) }}</p>
        </div>
        <div class="rounded-xl bg-white p-5 shadow">
            <p class="text-xs uppercase text-muted">Total facturé (taxes et frais compris)</p>
            <p class="mt-1 text-2xl font-semibold">{{ $report['invoiced'] !== null ? \App\Support\Format::money($report['invoiced']) : '-' }}</p>
            @if ($report['invoiced'] === null)
                <p class="mt-1 text-xs text-muted">Non calculé avec un filtre par catégorie.</p>
            @endif
        </div>
    </div>

    <div class="grid gap-6 md:grid-cols-2">
        <div class="rounded-xl bg-white p-5 shadow">
            <h3 class="mb-2 font-semibold">Par catégorie</h3>
            <div class="overflow-x-auto"><table class="min-w-full text-sm">
                @forelse ($report['by_category'] as $row)
                    <tr><td class="py-1">{{ $row->label }}</td><td class="py-1 text-right">{{ \App\Support\Format::money($row->total) }}</td></tr>
                @empty
                    <tr><td class="py-2 text-muted">Aucune vente.</td></tr>
                @endforelse
            </table></div>
        </div>

        <div class="rounded-xl bg-white p-5 shadow">
            <h3 class="mb-2 font-semibold">Par commercial</h3>
            <div class="overflow-x-auto"><table class="min-w-full text-sm">
                @forelse ($report['by_commercial'] as $row)
                    <tr><td class="py-1">{{ $row->label }}</td><td class="py-1 text-right">{{ \App\Support\Format::money($row->total) }}</td></tr>
                @empty
                    <tr><td class="py-2 text-muted">Aucune vente.</td></tr>
                @endforelse
            </table></div>
        </div>

        <div class="rounded-xl bg-white p-5 shadow md:col-span-2">
            <h3 class="mb-2 font-semibold">Produits les plus vendus</h3>
            <div class="overflow-x-auto"><table class="min-w-full text-left text-sm">
                <thead class="text-xs uppercase text-muted">
                    <tr><th class="py-1">Produit</th><th class="py-1 text-right">Quantité</th><th class="py-1 text-right">Ventes HT</th></tr>
                </thead>
                <tbody class="divide-y divide-soft">
                    @forelse ($report['top_products'] as $row)
                        <tr>
                            <td class="py-1">{{ $row->name }} <span class="font-mono text-xs text-muted">{{ $row->sku }}</span></td>
                            <td class="py-1 text-right">{{ \App\Support\Format::quantity($row->quantity) }} {{ $row->unit }}</td>
                            <td class="py-1 text-right">{{ \App\Support\Format::money($row->total) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="py-2 text-muted">Aucune vente.</td></tr>
                    @endforelse
                </tbody>
            </table></div>
        </div>
    </div>

    <p class="mt-4 text-xs text-muted">
        Le commercial est celui qui a converti le devis en commande. Les quantités de produits à unités différentes sont additionnées par désignation et unité.
    </p>
@endsection
