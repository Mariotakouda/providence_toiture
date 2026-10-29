@extends('layouts.app')

@section('title', 'Devis '.$quote->quote_number)

@section('content')
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <a href="{{ route('quotes.index') }}" class="text-sm text-link hover:underline">← Retour aux devis</a>

        <div class="flex flex-wrap gap-3">

            @if ($quote->order)
    @can('orders.view')
        <a href="{{ route('orders.show', $quote->order) }}"
           class="rounded-lg bg-brand px-4 py-2 text-sm text-on-brand hover:bg-brand-hover">
            Voir la commande {{ $quote->order->order_number }}
        </a>
    @endcan
@else
    @can('convert', $quote)
        <form method="POST" action="{{ route('quotes.convert', $quote) }}"
              onsubmit="return confirm('Convertir ce devis en commande ?')">
            @csrf
            <button class="rounded-lg bg-brand px-4 py-2 text-sm text-on-brand hover:bg-brand-hover">Convertir en commande</button>
        </form>
    @endcan
@endif

            @php
                $waQuote = \App\Support\WhatsApp::link(
                    $quote->customer?->phone,
                    "Bonjour {$quote->customer?->name}, voici votre devis {$quote->quote_number} de ".app(\App\Services\SettingService::class)->get('company_name', 'Providence Toiture').". Je vous l'envoie en PDF juste après ce message. N'hésitez pas si vous avez des questions."
                );
            @endphp
            @if ($waQuote)
                <a href="{{ $waQuote }}" target="_blank" rel="noopener"
                   class="rounded-lg bg-[#25D366] px-4 py-2 text-sm font-semibold text-[#06361b] hover:brightness-95">Envoyer par WhatsApp</a>
            @endif

            <a href="{{ route('quotes.pdf', $quote) }}" target="_blank"
               class="rounded-lg border border-line bg-white px-4 py-2 text-sm hover:bg-page">Exporter en PDF</a>

            @can('update', $quote)
                <a href="{{ route('quotes.edit', $quote) }}"
                   class="rounded-lg border border-line bg-white px-4 py-2 text-sm hover:bg-page">Modifier</a>
            @endcan

            @foreach ($quote->status->allowedTransitions() as $target)
                @can('transition', [$quote, $target])
                    <form method="POST" action="{{ route('quotes.transition', $quote) }}"
                          onsubmit="return confirm('{{ $target->actionLabel() }} ?')">
                        @csrf
                        <input type="hidden" name="status" value="{{ $target->value }}">
                        <button class="rounded-lg px-4 py-2 text-sm text-on-brand
                            {{ in_array($target, [\App\Enums\QuoteStatus::Cancelled, \App\Enums\QuoteStatus::Rejected]) ? 'bg-red-600 hover:bg-red-700' : 'bg-brand hover:bg-brand-hover' }}">
                            {{ $target->actionLabel() }}
                        </button>
                    </form>
                @endcan
            @endforeach
        </div>
    </div>

    @if ($errors->has('status'))
        <div class="mb-4 rounded-lg bg-red-50 p-3 text-sm text-red-700">{{ $errors->first('status') }}</div>
    @endif

    <div class="rounded-xl bg-white p-6 shadow">
        <div class="mb-4 flex flex-wrap items-center gap-3">
            <h2 class="font-mono text-xl font-semibold">{{ $quote->quote_number }}</h2>
            <span class="rounded-full px-2 py-1 text-xs {{ $quote->status->badgeClass() }}">{{ $quote->status->label() }}</span>
        </div>

        <dl class="grid gap-4 text-sm md:grid-cols-4">
            <div>
                <dt class="text-muted">Client</dt>
                <dd>
                    @can('customers.view')
                        <a href="{{ route('customers.show', $quote->customer) }}" class="text-link hover:underline">{{ $quote->customer->name }}</a>
                    @else
                        {{ $quote->customer->name }}
                    @endcan
                </dd>
            </div>
            <div><dt class="text-muted">Émis le</dt><dd>{{ $quote->issue_date->format('d/m/Y') }}</dd></div>
            <div><dt class="text-muted">Valide jusqu’au</dt><dd>{{ $quote->valid_until->format('d/m/Y') }}</dd></div>
            <div><dt class="text-muted">Créé par</dt><dd>{{ $quote->creator->name }}</dd></div>
            @if ($quote->sent_at)
                <div><dt class="text-muted">Envoyé le</dt><dd>{{ $quote->sent_at->format('d/m/Y H:i') }}</dd></div>
            @endif
            @if ($quote->responded_at)
                <div><dt class="text-muted">Réponse le</dt><dd>{{ $quote->responded_at->format('d/m/Y H:i') }}</dd></div>
            @endif
        </dl>
    </div>

    <div class="mt-6 overflow-x-auto rounded-xl bg-white p-6 shadow">
        <table class="min-w-full text-left text-sm">
            <thead class="bg-page text-xs uppercase text-muted">
                <tr>
                    <th class="px-4 py-3">Produit</th>
                    <th class="px-4 py-3 text-right">Quantité</th>
                    <th class="px-4 py-3 text-right">Prix unitaire</th>
                    <th class="px-4 py-3 text-right">Remise</th>
                    <th class="px-4 py-3 text-right">Total ligne</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-soft">
                @foreach ($quote->items as $item)
                    <tr>
                        <td class="px-4 py-3">
                            {{ $item->product_name_snapshot }}
                            <span class="block font-mono text-xs text-muted">{{ $item->sku_snapshot }}</span>
                        </td>
                        <td class="px-4 py-3 text-right">{{ \App\Support\Format::quantity($item->quantity) }} {{ $item->unit_snapshot }}</td>
                        <td class="px-4 py-3 text-right">{{ \App\Support\Format::money($item->unit_price) }}</td>
                        <td class="px-4 py-3 text-right">
                            {{ (float) $item->discount_percent > 0 ? \App\Support\Format::quantity($item->discount_percent).' %' : '-' }}
                        </td>
                        <td class="px-4 py-3 text-right">{{ \App\Support\Format::money($item->line_total) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <dl class="ml-auto mt-4 w-full max-w-sm space-y-1 text-sm">
            <div class="flex justify-between"><dt>Sous-total</dt><dd>{{ \App\Support\Format::money($quote->subtotal) }}</dd></div>
            @if ((float) $quote->discount_total > 0)
                <div class="flex justify-between"><dt>Remises</dt><dd>- {{ \App\Support\Format::money($quote->discount_total) }}</dd></div>
            @endif
            @if ((float) $quote->tax_total > 0)
                <div class="flex justify-between"><dt>Taxe</dt><dd>{{ \App\Support\Format::money($quote->tax_total) }}</dd></div>
            @endif
            @if ((float) $quote->delivery_fee > 0)
                <div class="flex justify-between"><dt>Frais annexes</dt><dd>{{ \App\Support\Format::money($quote->delivery_fee) }}</dd></div>
            @endif
            <div class="flex justify-between border-t pt-2 text-base font-semibold"><dt>Total</dt><dd>{{ \App\Support\Format::money($quote->total) }}</dd></div>
        </dl>
    </div>

    @if ($quote->terms)
        <div class="mt-6 rounded-xl bg-white p-6 shadow">
            <h3 class="mb-2 font-semibold">Conditions commerciales</h3>
            <p class="whitespace-pre-line text-sm text-ink">{{ $quote->terms }}</p>
        </div>
    @endif

    @if ($quote->notes)
        <div class="mt-6 rounded-xl bg-white p-6 shadow">
            <h3 class="mb-2 font-semibold">Notes internes</h3>
            <p class="whitespace-pre-line text-sm text-ink">{{ $quote->notes }}</p>
        </div>
    @endif
@endsection
