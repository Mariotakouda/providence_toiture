@extends('layouts.app')

@section('title', $supplier->name)

@section('content')
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <a href="{{ route('suppliers.index') }}" class="text-sm text-link hover:underline">← Retour aux fournisseurs</a>

        @can('update', $supplier)
            <div class="flex gap-3">
                <a href="{{ route('suppliers.edit', $supplier) }}"
                   class="rounded-lg border border-line bg-white px-4 py-2 text-sm hover:bg-page">Modifier</a>
                <form method="POST" action="{{ route('suppliers.toggle-status', $supplier) }}"
                      data-confirm="{{ $supplier->is_active ? 'Désactiver' : 'Activer' }} ce fournisseur ?">
                    @csrf
                    @method('PATCH')
                    <button class="rounded-lg px-4 py-2 text-sm text-on-brand {{ $supplier->is_active ? 'bg-red-600 hover:bg-red-700' : 'bg-brand hover:bg-brand-hover' }}">
                        {{ $supplier->is_active ? 'Désactiver' : 'Activer' }}
                    </button>
                </form>
            </div>
        @endcan
    </div>

    <div class="rounded-xl bg-white p-6 shadow">
        <div class="mb-4 flex flex-wrap items-center gap-3">
            <h2 class="text-xl font-semibold">{{ $supplier->name }}</h2>
            @if ($supplier->is_active)
                <span class="rounded-full bg-green-100 px-2 py-1 text-xs text-green-700">Actif</span>
            @else
                <span class="rounded-full bg-soft px-2 py-1 text-xs text-muted">Inactif</span>
            @endif
        </div>

        <dl class="grid gap-4 text-sm md:grid-cols-3">
            <div><dt class="text-muted">Téléphone</dt><dd>{{ $supplier->phone ?? '-' }}</dd></div>
            <div><dt class="text-muted">E-mail</dt><dd>{{ $supplier->email ?? '-' }}</dd></div>
            <div><dt class="text-muted">Ville</dt><dd>{{ $supplier->city ?? '-' }}</dd></div>
            <div class="md:col-span-3"><dt class="text-muted">Adresse</dt><dd>{{ $supplier->address ?? '-' }}</dd></div>
        </dl>

        @if ($supplier->notes)
            <p class="mt-4 whitespace-pre-line text-sm text-ink">{{ $supplier->notes }}</p>
        @endif
    </div>

    @can('stock.view')
        <div class="mt-6 rounded-xl bg-white p-6 shadow">
            <h3 class="mb-3 text-lg font-semibold">Derniers achats enregistrés</h3>
            <div class="overflow-x-auto"><table class="min-w-full text-left text-sm">
                <thead class="bg-page text-xs uppercase text-muted">
                    <tr>
                        <th class="px-4 py-3">Date</th>
                        <th class="px-4 py-3">Produit</th>
                        <th class="px-4 py-3 text-right">Quantité</th>
                        <th class="px-4 py-3">Motif / document</th>
                        <th class="px-4 py-3">Utilisateur</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-soft">
                    @forelse ($movements as $movement)
                        <tr>
                            <td class="px-4 py-3">{{ $movement->occurred_at->format('d/m/Y H:i') }}</td>
                            <td class="px-4 py-3">{{ $movement->product->name }}</td>
                            <td class="px-4 py-3 text-right font-medium text-green-700">{{ $movement->signedQuantity() }}</td>
                            <td class="px-4 py-3">{{ $movement->reason ?? '-' }}</td>
                            <td class="px-4 py-3">{{ $movement->creator->name }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-4 text-center text-muted">Aucun achat enregistré.</td></tr>
                    @endforelse
                </tbody>
            </table></div>
        </div>
    @endcan
@endsection
