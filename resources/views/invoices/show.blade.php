@extends('layouts.app')

@section('title', 'Facture '.$invoice->invoice_number)

@section('content')
    @php
        $snap = $invoice->customer_snapshot ?? [];
        $input = 'rounded-lg border border-line px-3 py-2 text-sm';
    @endphp

    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <a href="{{ route('invoices.index') }}" class="text-sm text-link hover:underline">← Retour aux factures</a>

        <div class="flex flex-wrap items-start gap-3">
            <a href="{{ route('invoices.pdf', $invoice) }}" target="_blank"
               class="rounded-lg border border-line bg-white px-4 py-2 text-sm hover:bg-page">Exporter en PDF</a>

            @can('update', $invoice)
                <a href="{{ route('invoices.edit', $invoice) }}"
                   class="rounded-lg border border-line bg-white px-4 py-2 text-sm hover:bg-page">Modifier</a>
            @endcan

            @can('transition', [$invoice, \App\Enums\InvoiceStatus::Issued])
                <form method="POST" action="{{ route('invoices.transition', $invoice) }}"
                      data-confirm="Émettre cette facture ? Elle ne sera plus modifiable.">
                    @csrf
                    <input type="hidden" name="status" value="issued">
                    <button class="rounded-lg bg-brand px-4 py-2 text-sm text-on-brand hover:bg-brand-hover">Émettre la facture</button>
                </form>
            @endcan

            @can('transition', [$invoice, \App\Enums\InvoiceStatus::Cancelled])
                <form method="POST" action="{{ route('invoices.transition', $invoice) }}"
                      data-confirm="Annuler cette facture ?" class="flex items-start gap-2">
                    @csrf
                    <input type="hidden" name="status" value="cancelled">
                    <input type="text" name="reason" placeholder="Motif d’annulation" required minlength="3" maxlength="255" class="{{ $input }}">
                    <button class="rounded-lg bg-red-600 px-4 py-2 text-sm text-white hover:bg-red-700">Annuler</button>
                </form>
            @endcan
        </div>
    </div>

    @foreach (['status', 'reason', 'payment'] as $field)
        @error($field)
            <div class="mb-4 rounded-lg bg-red-50 p-3 text-sm text-red-700">{{ $message }}</div>
        @enderror
    @endforeach

    <div class="rounded-xl bg-white p-6 shadow">
        <div class="mb-4 flex flex-wrap items-center gap-3">
            <h2 class="font-mono text-xl font-semibold">{{ $invoice->invoice_number }}</h2>
            <span class="rounded-full px-2 py-1 text-xs {{ $invoice->status->badgeClass() }}">{{ $invoice->status->label() }}</span>
            @if ($invoice->isOverdue())
                <span class="rounded-full bg-red-100 px-2 py-1 text-xs text-red-700">En retard</span>
            @endif
        </div>

        <dl class="grid gap-4 text-sm md:grid-cols-4">
            <div>
                <dt class="text-muted">Client</dt>
                <dd>
                    <span class="font-medium">{{ $snap['name'] ?? '' }}</span><br>
                    {{ $snap['phone'] ?? '' }}<br>
                    {{ $snap['address'] ?? '' }} {{ $snap['city'] ?? '' }}
                </dd>
            </div>
            <div><dt class="text-muted">Émise le</dt><dd>{{ $invoice->issue_date->format('d/m/Y') }}</dd></div>
            <div><dt class="text-muted">Échéance</dt><dd>{{ $invoice->due_date?->format('d/m/Y') ?? '-' }}</dd></div>
            <div>
                <dt class="text-muted">Commande</dt>
                <dd>
                    @if ($invoice->order)
                        @can('orders.view')
                            <a href="{{ route('orders.show', $invoice->order) }}" class="font-mono text-link hover:underline">{{ $invoice->order->order_number }}</a>
                        @else
                            <span class="font-mono">{{ $invoice->order->order_number }}</span>
                        @endcan
                    @else
                        -
                    @endif
                </dd>
            </div>
            @if ($invoice->cancelled_at)
                <div class="md:col-span-4">
                    <dt class="text-muted">Annulée le {{ $invoice->cancelled_at->format('d/m/Y H:i') }}</dt>
                    <dd>{{ $invoice->cancel_reason }}</dd>
                </div>
            @endif
        </dl>
    </div>

    <div class="mt-6 overflow-x-auto rounded-xl bg-white p-6 shadow">
        <table class="min-w-full text-left text-sm">
            <thead class="bg-page text-xs uppercase text-muted">
                <tr>
                    <th class="px-4 py-3">Désignation</th>
                    <th class="px-4 py-3 text-right">Quantité</th>
                    <th class="px-4 py-3 text-right">Prix unitaire</th>
                    <th class="px-4 py-3 text-right">Remise</th>
                    <th class="px-4 py-3 text-right">Total</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-soft">
                @foreach ($invoice->items as $item)
                    <tr>
                        <td class="px-4 py-3">
                            {{ $item->product_name_snapshot }}
                            <span class="block font-mono text-xs text-muted">{{ $item->sku_snapshot }}</span>
                        </td>
                        <td class="px-4 py-3 text-right">{{ \App\Support\Format::quantity($item->quantity) }} {{ $item->unit_snapshot }}</td>
                        <td class="px-4 py-3 text-right">{{ \App\Support\Format::money($item->unit_price) }}</td>
                        <td class="px-4 py-3 text-right">{{ (float) $item->discount_percent > 0 ? \App\Support\Format::quantity($item->discount_percent).' %' : '-' }}</td>
                        <td class="px-4 py-3 text-right">{{ \App\Support\Format::money($item->line_total) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <dl class="ml-auto mt-4 w-full max-w-sm space-y-1 text-sm">
            <div class="flex justify-between"><dt>Sous-total</dt><dd>{{ \App\Support\Format::money($invoice->subtotal) }}</dd></div>
            @if ((float) $invoice->discount_total > 0)
                <div class="flex justify-between"><dt>Remises</dt><dd>- {{ \App\Support\Format::money($invoice->discount_total) }}</dd></div>
            @endif
            @if ((float) $invoice->tax_total > 0)
                <div class="flex justify-between"><dt>Taxe</dt><dd>{{ \App\Support\Format::money($invoice->tax_total) }}</dd></div>
            @endif
            @if ((float) $invoice->delivery_fee > 0)
                <div class="flex justify-between"><dt>Frais annexes</dt><dd>{{ \App\Support\Format::money($invoice->delivery_fee) }}</dd></div>
            @endif
            <div class="flex justify-between border-t pt-2 text-base font-semibold"><dt>Total</dt><dd>{{ \App\Support\Format::money($invoice->total) }}</dd></div>
            <div class="flex justify-between"><dt>Déjà payé</dt><dd>{{ \App\Support\Format::money($invoice->amount_paid) }}</dd></div>
            <div class="flex justify-between font-semibold {{ (float) $invoice->balance_due > 0 && $invoice->status !== \App\Enums\InvoiceStatus::Cancelled ? 'text-red-700' : '' }}">
                <dt>Reste à payer</dt><dd>{{ \App\Support\Format::money($invoice->balance_due) }}</dd>
            </div>
        </dl>
    </div>

    @if ($invoice->terms)
        <div class="mt-6 rounded-xl bg-white p-6 shadow">
            <h3 class="mb-2 font-semibold">Mentions et conditions</h3>
            <p class="whitespace-pre-line text-sm text-ink">{{ $invoice->terms }}</p>
        </div>
    @endif

    @can('payments.view')
        <div class="mt-6 overflow-x-auto rounded-xl bg-white p-6 shadow">
            <h3 class="mb-3 text-lg font-semibold">Paiements</h3>
            <table class="min-w-full text-left text-sm">
                <thead class="bg-page text-xs uppercase text-muted">
                    <tr>
                        <th class="px-4 py-3">Numéro</th>
                        <th class="px-4 py-3">Date</th>
                        <th class="px-4 py-3">Mode</th>
                        <th class="px-4 py-3">Référence</th>
                        <th class="px-4 py-3">Reçu par</th>
                        <th class="px-4 py-3">Statut</th>
                        <th class="px-4 py-3 text-right">Montant</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-soft">
                    @forelse ($invoice->payments as $payment)
                        <tr class="{{ $payment->status === \App\Enums\PaymentStatus::Cancelled ? 'text-muted line-through' : '' }}">
                            <td class="px-4 py-3 font-mono text-xs">{{ $payment->payment_number }}</td>
                            <td class="px-4 py-3">{{ $payment->payment_date->format('d/m/Y') }}</td>
                            <td class="px-4 py-3">{{ $payment->method->label() }}</td>
                            <td class="px-4 py-3">{{ $payment->reference ?? '-' }}</td>
                            <td class="px-4 py-3">{{ $payment->receiver->name }}</td>
                            <td class="px-4 py-3 no-underline">
                                <span class="rounded-full px-2 py-1 text-xs {{ $payment->status->badgeClass() }}">{{ $payment->status->label() }}</span>
                                @if ($payment->cancel_reason)
                                    <span class="block text-xs text-muted">{{ $payment->cancel_reason }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">{{ \App\Support\Format::money($payment->amount) }}</td>
                            <td class="px-4 py-3">
                                <div class="flex items-start justify-end gap-3">
                                    <a href="{{ route('payments.receipt', $payment) }}" target="_blank" class="text-link hover:underline">Reçu</a>
                                    @can('cancel', $payment)
                                        <form method="POST" action="{{ route('payments.cancel', $payment) }}"
                                              data-confirm="Annuler ce paiement ?" class="flex gap-1">
                                            @csrf
                                            <input type="text" name="reason" placeholder="Motif" required minlength="3" maxlength="255" class="w-32 rounded border border-line px-2 py-1 text-xs">
                                            <button class="text-red-600 hover:underline">Annuler</button>
                                        </form>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="px-4 py-4 text-center text-muted">Aucun paiement.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @endcan

    @can('recordPayment', $invoice)
        <form method="POST" action="{{ route('payments.store', $invoice) }}" class="mt-6 rounded-xl bg-white p-6 shadow">
            @csrf
            <h3 class="mb-3 text-lg font-semibold">Enregistrer un paiement</h3>

            <div class="grid gap-4 md:grid-cols-4">
                <div>
                    <label for="amount" class="block text-sm font-medium">Montant</label>
                    <input id="amount" name="amount" type="number" step="0.01" min="0.01" max="{{ $invoice->balance_due }}" required
                           value="{{ old('amount', $invoice->balance_due) }}" class="mt-1 w-full {{ $input }}">
                    @error('amount') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="payment_date" class="block text-sm font-medium">Date</label>
                    <input id="payment_date" name="payment_date" type="date" max="{{ now()->toDateString() }}" required
                           value="{{ old('payment_date', now()->toDateString()) }}" class="mt-1 w-full {{ $input }}">
                    @error('payment_date') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="method" class="block text-sm font-medium">Mode</label>
                    <select id="method" name="method" required class="mt-1 w-full {{ $input }}">
                        @foreach ($methods as $method)
                            <option value="{{ $method->value }}" @selected(old('method', 'cash') === $method->value)>{{ $method->label() }}</option>
                        @endforeach
                    </select>
                    @error('method') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="reference" class="block text-sm font-medium">Référence</label>
                    <input id="reference" name="reference" type="text" maxlength="100" value="{{ old('reference') }}"
                           placeholder="N° de transaction, de chèque…" class="mt-1 w-full {{ $input }}">
                    @error('reference') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div class="md:col-span-4">
                    <label for="notes" class="block text-sm font-medium">Notes</label>
                    <input id="notes" name="notes" type="text" maxlength="1000" value="{{ old('notes') }}" class="mt-1 w-full {{ $input }}">
                </div>
            </div>

            <button class="mt-4 rounded-lg bg-brand px-4 py-2 text-sm font-medium text-on-brand hover:bg-brand-hover">Enregistrer le paiement</button>
        </form>
    @endcan
@endsection
