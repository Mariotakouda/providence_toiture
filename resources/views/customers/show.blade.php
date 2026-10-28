@extends('layouts.app')

@section('title', $customer->name)

@section('content')
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <a href="{{ route('customers.index') }}" class="text-sm text-link hover:underline">← Retour aux clients</a>

        @can('update', $customer)
            <div class="flex gap-3">
                <a href="{{ route('customers.edit', $customer) }}"
                   class="rounded-lg border border-line bg-white px-4 py-2 text-sm hover:bg-page">Modifier</a>
                <form method="POST" action="{{ route('customers.toggle-status', $customer) }}"
                      onsubmit="return confirm('{{ $customer->is_active ? 'Désactiver' : 'Activer' }} ce client ?')">
                    @csrf
                    @method('PATCH')
                    <button class="rounded-lg px-4 py-2 text-sm text-on-brand {{ $customer->is_active ? 'bg-red-600 hover:bg-red-700' : 'bg-brand hover:bg-brand-hover' }}">
                        {{ $customer->is_active ? 'Désactiver' : 'Activer' }}
                    </button>
                </form>
            </div>
        @endcan
    </div>

    <div class="rounded-xl bg-white p-6 shadow">
        <div class="mb-4 flex flex-wrap items-center gap-3">
            <h2 class="text-xl font-semibold">{{ $customer->name }}</h2>
            <span class="rounded-full bg-page px-2 py-1 text-xs text-slate-700">{{ $customer->customer_type->label() }}</span>
            @if ($customer->is_active)
                <span class="rounded-full bg-green-100 px-2 py-1 text-xs text-green-700">Actif</span>
            @else
                <span class="rounded-full bg-soft px-2 py-1 text-xs text-muted">Inactif</span>
            @endif
        </div>

        <dl class="grid gap-4 text-sm md:grid-cols-3">
            <div><dt class="text-muted">Téléphone</dt><dd>{{ $customer->phone }}</dd></div>
            <div><dt class="text-muted">E-mail</dt><dd>{{ $customer->email ?? '-' }}</dd></div>
            <div><dt class="text-muted">Ville</dt><dd>{{ $customer->city ?? '-' }}</dd></div>
            <div class="md:col-span-3"><dt class="text-muted">Adresse</dt><dd>{{ $customer->address ?? '-' }}</dd></div>
        </dl>

        @if ($customer->notes)
            <p class="mt-4 whitespace-pre-line text-sm text-ink">{{ $customer->notes }}</p>
        @endif
    </div>

    @if ($balance)
        <div class="mt-6 grid gap-4 md:grid-cols-3">
            <div class="rounded-xl bg-white p-4 shadow">
                <p class="text-xs uppercase text-muted">Total facturé</p>
                <p class="mt-1 text-lg font-semibold">{{ \App\Support\Format::money($balance->invoiced) }}</p>
            </div>
            <div class="rounded-xl bg-white p-4 shadow">
                <p class="text-xs uppercase text-muted">Total payé</p>
                <p class="mt-1 text-lg font-semibold">{{ \App\Support\Format::money($balance->paid) }}</p>
            </div>
            <div class="rounded-xl bg-white p-4 shadow">
                <p class="text-xs uppercase text-muted">Solde à payer</p>
                <p class="mt-1 text-lg font-semibold {{ (float) $balance->due > 0 ? 'text-red-700' : '' }}">
                    {{ \App\Support\Format::money($balance->due) }}
                </p>
            </div>
        </div>
    @endif

    @if ($quotes !== null)
        <div class="mt-6 rounded-xl bg-white p-6 shadow">
            <h3 class="mb-3 text-lg font-semibold">Devis</h3>
            <div class="overflow-x-auto"><table class="min-w-full text-left text-sm">
                <thead class="bg-page text-xs uppercase text-muted">
                    <tr>
                        <th class="px-4 py-3">Numéro</th>
                        <th class="px-4 py-3">Date</th>
                        <th class="px-4 py-3">Statut</th>
                        <th class="px-4 py-3 text-right">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-soft">
                    @forelse ($quotes as $quote)
                        <tr>
                            <td class="px-4 py-3 font-mono text-xs">{{ $quote->quote_number }}</td>
                            <td class="px-4 py-3">{{ $quote->issue_date->format('d/m/Y') }}</td>
                            <td class="px-4 py-3">{{ $quote->status->label() }}</td>
                            <td class="px-4 py-3 text-right">{{ \App\Support\Format::money($quote->total) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-4 py-4 text-center text-muted">Aucun devis.</td></tr>
                    @endforelse
                </tbody>
            </table></div>
        </div>
    @endif

    @if ($orders !== null)
        <div class="mt-6 rounded-xl bg-white p-6 shadow">
            <h3 class="mb-3 text-lg font-semibold">Commandes</h3>
            <div class="overflow-x-auto"><table class="min-w-full text-left text-sm">
                <thead class="bg-page text-xs uppercase text-muted">
                    <tr>
                        <th class="px-4 py-3">Numéro</th>
                        <th class="px-4 py-3">Date</th>
                        <th class="px-4 py-3">Statut</th>
                        <th class="px-4 py-3 text-right">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-soft">
                    @forelse ($orders as $order)
                        <tr>
                            <td class="px-4 py-3 font-mono text-xs">{{ $order->order_number }}</td>
                            <td class="px-4 py-3">{{ $order->order_date->format('d/m/Y') }}</td>
                            <td class="px-4 py-3">{{ $order->status->label() }}</td>
                            <td class="px-4 py-3 text-right">{{ \App\Support\Format::money($order->total) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-4 py-4 text-center text-muted">Aucune commande.</td></tr>
                    @endforelse
                </tbody>
            </table></div>
        </div>
    @endif

    @if ($invoices !== null)
        <div class="mt-6 rounded-xl bg-white p-6 shadow">
            <h3 class="mb-3 text-lg font-semibold">Factures</h3>
            <div class="overflow-x-auto"><table class="min-w-full text-left text-sm">
                <thead class="bg-page text-xs uppercase text-muted">
                    <tr>
                        <th class="px-4 py-3">Numéro</th>
                        <th class="px-4 py-3">Date</th>
                        <th class="px-4 py-3">Statut</th>
                        <th class="px-4 py-3 text-right">Total</th>
                        <th class="px-4 py-3 text-right">Reste à payer</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-soft">
                    @forelse ($invoices as $invoice)
                        <tr>
                            <td class="px-4 py-3 font-mono text-xs">{{ $invoice->invoice_number }}</td>
                            <td class="px-4 py-3">{{ $invoice->issue_date->format('d/m/Y') }}</td>
                            <td class="px-4 py-3">{{ $invoice->status->label() }}</td>
                            <td class="px-4 py-3 text-right">{{ \App\Support\Format::money($invoice->total) }}</td>
                            <td class="px-4 py-3 text-right">{{ \App\Support\Format::money($invoice->balance_due) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-4 text-center text-muted">Aucune facture.</td></tr>
                    @endforelse
                </tbody>
            </table></div>
        </div>
    @endif

    @if ($payments !== null)
        <div class="mt-6 rounded-xl bg-white p-6 shadow">
            <h3 class="mb-3 text-lg font-semibold">Paiements</h3>
            <div class="overflow-x-auto"><table class="min-w-full text-left text-sm">
                <thead class="bg-page text-xs uppercase text-muted">
                    <tr>
                        <th class="px-4 py-3">Numéro</th>
                        <th class="px-4 py-3">Date</th>
                        <th class="px-4 py-3">Mode</th>
                        <th class="px-4 py-3">Statut</th>
                        <th class="px-4 py-3 text-right">Montant</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-soft">
                    @forelse ($payments as $payment)
                        <tr>
                            <td class="px-4 py-3 font-mono text-xs">{{ $payment->payment_number }}</td>
                            <td class="px-4 py-3">{{ $payment->payment_date->format('d/m/Y') }}</td>
                            <td class="px-4 py-3">{{ $payment->method->label() }}</td>
                            <td class="px-4 py-3">{{ $payment->status->label() }}</td>
                            <td class="px-4 py-3 text-right">{{ \App\Support\Format::money($payment->amount) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-4 text-center text-muted">Aucun paiement.</td></tr>
                    @endforelse
                </tbody>
            </table></div>
        </div>
    @endif
@endsection
