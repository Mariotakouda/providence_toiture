@extends('layouts.app')

@section('title', 'Mouvements de stock')

@section('content')
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <form method="GET" class="flex flex-wrap items-end gap-2">
            @if (request('product_id'))
                <input type="hidden" name="product_id" value="{{ request('product_id') }}">
            @endif

            <input type="text" name="q" value="{{ request('q') }}" placeholder="Produit ou référence"
                   class="rounded-lg border border-line px-3 py-2 text-sm">

            <select name="type" class="rounded-lg border border-line px-3 py-2 text-sm">
                <option value="">Tous les types</option>
                @foreach ($types as $type)
                    <option value="{{ $type->value }}" @selected(request('type') === $type->value)>{{ $type->label() }}</option>
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

            @if (request('product_id'))
                <a href="{{ route('stock-movements.index') }}" class="text-sm text-link hover:underline">Retirer le filtre produit</a>
            @endif
        </form>

        @canany(['stock.move', 'stock.adjust'])
            <a href="{{ route('stock-movements.create') }}"
               class="rounded-lg bg-brand px-4 py-2 text-sm font-medium text-on-brand hover:bg-brand-hover">
                Nouveau mouvement
            </a>
        @endcanany
    </div>

    <div class="overflow-x-auto rounded-xl bg-white shadow">
        <table class="min-w-full text-left text-sm">
            <thead class="bg-page text-xs uppercase text-muted">
                <tr>
                    <th class="px-4 py-3">Date</th>
                    <th class="px-4 py-3">Produit</th>
                    <th class="px-4 py-3">Type</th>
                    <th class="px-4 py-3 text-right">Quantité</th>
                    <th class="px-4 py-3">Motif / document</th>
                    <th class="px-4 py-3">Utilisateur</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-soft">
                @forelse ($movements as $movement)
                    <tr>
                        <td class="px-4 py-3 whitespace-nowrap">{{ $movement->occurred_at->format('d/m/Y H:i') }}</td>
                        <td class="px-4 py-3">
                            <a href="{{ route('products.show', $movement->product) }}" class="font-medium hover:underline">
                                {{ $movement->product->name }}
                            </a>
                            <span class="block font-mono text-xs text-muted">{{ $movement->product->sku }}</span>
                        </td>
                        <td class="px-4 py-3">{{ $movement->movement_type->label() }}</td>
                        <td class="px-4 py-3 text-right font-medium {{ $movement->movement_type->isIncoming() ? 'text-green-700' : 'text-red-700' }}">
                            {{ $movement->signedQuantity() }}
                        </td>
                        <td class="px-4 py-3">
                            {{ $movement->reason ?? '-' }}
                            @if ($movement->reference instanceof \App\Models\Supplier)
                                <span class="block text-xs text-muted">Fournisseur : {{ $movement->reference->name }}</span>
                            @elseif ($movement->reference instanceof \App\Models\Delivery)
                                <span class="block text-xs text-muted">Livraison {{ $movement->reference->delivery_number }}</span>
                            @elseif ($movement->reference_type)
                                <span class="block text-xs text-muted">
                                    {{ class_basename($movement->reference_type) }} #{{ $movement->reference_id }}
                                </span>
                            @endif
                        </td>
                        <td class="px-4 py-3">{{ $movement->creator->name }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-6 text-center text-muted">Aucun mouvement.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $movements->links() }}</div>
@endsection
