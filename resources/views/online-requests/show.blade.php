@extends('layouts.app')

@section('title', 'Demande '.$onlineRequest->reference)

@section('content')
    <div class="mb-4">
        <a href="{{ route('online-requests.index') }}" class="text-sm text-link hover:underline">← Retour aux demandes</a>
    </div>

    @if ($errors->has('status'))
        <div class="mb-4 rounded-lg bg-red-50 p-3 text-sm text-red-700">{{ $errors->first('status') }}</div>
    @endif

    <div class="rounded-xl bg-white p-6 shadow">
        <div class="mb-4 flex flex-wrap items-center gap-3">
            <h2 class="font-mono text-xl font-semibold">{{ $onlineRequest->reference }}</h2>
            <span class="rounded-full px-2 py-1 text-xs {{ $onlineRequest->status->badgeClass() }}">{{ $onlineRequest->status->label() }}</span>
            <span class="rounded-full bg-soft px-2 py-1 text-xs text-muted">{{ $onlineRequest->type->label() }}</span>
        </div>

        <dl class="grid gap-4 text-sm md:grid-cols-4">
            <div><dt class="text-muted">Nom</dt><dd class="font-medium">{{ $onlineRequest->name }}</dd></div>
            <div><dt class="text-muted">Téléphone</dt><dd>{{ $onlineRequest->phone }}</dd></div>
            <div><dt class="text-muted">E-mail</dt><dd>{{ $onlineRequest->email ?? '-' }}</dd></div>
            <div><dt class="text-muted">Reçue le</dt><dd>{{ $onlineRequest->created_at->format('d/m/Y H:i') }}</dd></div>
            <div><dt class="text-muted">Ville</dt><dd>{{ $onlineRequest->city ?? '-' }}</dd></div>
            <div class="md:col-span-2"><dt class="text-muted">Adresse</dt><dd>{{ $onlineRequest->address ?? '-' }}</dd></div>
            <div><dt class="text-muted">Traitée par</dt><dd>{{ $onlineRequest->assignee?->name ?? '-' }}</dd></div>
        </dl>

        @php
            $company = app(\App\Services\SettingService::class)->get('company_name', 'Providence Toiture');
            $waText = $onlineRequest->quote
                ? "Bonjour {$onlineRequest->name}, c'est {$company}. Votre devis {$onlineRequest->quote->quote_number} (demande {$onlineRequest->reference}) est prêt. Souhaitez-vous que nous vous l'envoyions ici en PDF ?"
                : "Bonjour {$onlineRequest->name}, c'est {$company}. Nous avons bien reçu votre demande {$onlineRequest->reference}. Nous revenons vers vous très rapidement. Merci de votre confiance.";
            $waLink = \App\Support\WhatsApp::link($onlineRequest->phone, $waText);
        @endphp
        @if ($waLink)
            <a href="{{ $waLink }}" target="_blank" rel="noopener"
               class="mt-4 inline-flex items-center gap-2 rounded-lg bg-[#25D366] px-4 py-2 text-sm font-semibold text-[#06361b] hover:brightness-95">
                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3a9 9 0 0 0-7.8 13.5L3 21l4.6-1.2A9 9 0 1 0 12 3z"/><path d="M9 8.5c0 3 2.5 5.5 5.5 5.5l1-1.5-2-1-1 .8a3.5 3.5 0 0 1-1.6-1.6l.8-1-1-2z"/></svg>
                Répondre sur WhatsApp
            </a>
        @endif

        @if ($onlineRequest->message)
            <div class="mt-4 rounded-lg bg-soft p-4 text-sm">
                <p class="mb-1 text-xs uppercase text-muted">Message du client</p>
                <p class="whitespace-pre-line">{{ $onlineRequest->message }}</p>
            </div>
        @endif

        @if ($onlineRequest->closed_reason)
            <p class="mt-4 text-sm text-muted">Clôturée le {{ $onlineRequest->closed_at?->format('d/m/Y H:i') }} : {{ $onlineRequest->closed_reason }}</p>
        @endif

        @if ($onlineRequest->quote)
            <p class="mt-4 text-sm">
                Devis créé :
                @can('quotes.view')
                    <a href="{{ route('quotes.show', $onlineRequest->quote) }}" class="font-mono text-link hover:underline">{{ $onlineRequest->quote->quote_number }}</a>
                @else
                    <span class="font-mono">{{ $onlineRequest->quote->quote_number }}</span>
                @endcan
                @if ($onlineRequest->customer)
                    pour {{ $onlineRequest->customer->name }}
                @endif
            </p>
        @endif
    </div>

    <div class="mt-6 overflow-x-auto rounded-xl bg-white p-6 shadow">
        <h3 class="mb-3 text-lg font-semibold">Produits demandés</h3>
        <table class="min-w-full text-left text-sm">
            <thead class="bg-soft text-xs uppercase text-muted">
                <tr>
                    <th class="px-4 py-3">Produit</th>
                    <th class="px-4 py-3 text-right">Quantité</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-line">
                @forelse ($onlineRequest->items as $item)
                    <tr>
                        <td class="px-4 py-3">
                            {{ $item->product_name_snapshot }}
                            <span class="block font-mono text-xs text-muted">{{ $item->sku_snapshot }}</span>
                        </td>
                        <td class="px-4 py-3 text-right">{{ \App\Support\Format::quantity($item->quantity) }} {{ $item->unit_snapshot }}</td>
                    </tr>
                @empty
                    <tr><td colspan="2" class="px-4 py-4 text-center text-muted">Aucun produit sélectionné : voir le message du client.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($onlineRequest->status->isOpen())
        @can('requests.manage')
            <div class="mt-6 grid gap-6 md:grid-cols-2">

                <div class="rounded-xl bg-white p-6 shadow">
                    <h3 class="mb-3 text-lg font-semibold">Transformer en devis</h3>

                    @can('quotes.create')
                        <form method="POST" action="{{ route('online-requests.convert', $onlineRequest) }}"
                              onsubmit="return confirm('Créer un devis brouillon depuis cette demande ?')" class="space-y-3 text-sm">
                            @csrf

                            @if ($candidates->isNotEmpty())
                                <p class="text-muted">Des clients existants ont le même téléphone ou le même e-mail. Choisissez le bon client :</p>
                                @foreach ($candidates as $candidate)
                                    <label class="flex items-start gap-2">
                                        <input type="radio" name="customer_choice" value="{{ $candidate->id }}" required class="mt-1">
                                        <span>
                                            Utiliser <strong>{{ $candidate->name }}</strong>
                                            <span class="block text-xs text-muted">{{ $candidate->phone }} · {{ $candidate->email ?? 'pas d’e-mail' }}{{ $candidate->is_active ? '' : ' · inactif' }}</span>
                                        </span>
                                    </label>
                                @endforeach
                                @can('customers.create')
                                    <label class="flex items-start gap-2">
                                        <input type="radio" name="customer_choice" value="new" required class="mt-1">
                                        <span>Créer un <strong>nouveau client</strong> ({{ $onlineRequest->name }})</span>
                                    </label>
                                @endcan
                            @else
                                <input type="hidden" name="customer_choice" value="new">
                                <p class="text-muted">Aucun client existant ne correspond : une fiche client sera créée.</p>
                            @endif

                            @error('customer_choice') <p class="text-sm text-red-600">{{ $message }}</p> @enderror

                            <button class="rounded-lg bg-brand px-4 py-2 font-semibold text-on-brand hover:bg-brand-hover">Créer le devis brouillon</button>
                            <p class="text-xs text-muted">Les prix viennent du catalogue. Vous pourrez compléter le devis avant de l’envoyer.</p>
                        </form>
                    @else
                        <p class="text-sm text-muted">Vous n’avez pas le droit de créer des devis.</p>
                    @endcan
                </div>

                <div class="rounded-xl bg-white p-6 shadow">
                    <h3 class="mb-3 text-lg font-semibold">Traiter la demande</h3>

                    @if ($onlineRequest->status === \App\Enums\OnlineRequestStatus::New)
                        <form method="POST" action="{{ route('online-requests.take', $onlineRequest) }}" class="mb-5">
                            @csrf
                            <button class="rounded-lg bg-soft px-4 py-2 text-sm font-semibold hover:brightness-95">Prendre en charge</button>
                            <p class="mt-1 text-xs text-muted">Indique que vous êtes en train de traiter cette demande.</p>
                        </form>
                    @endif

                    <form method="POST" action="{{ route('online-requests.close', $onlineRequest) }}"
                          onsubmit="return confirm('Clôturer cette demande ?')" class="space-y-3 text-sm">
                        @csrf
                        <label for="reason" class="block font-medium">Clôturer sans devis (motif obligatoire)</label>
                        <textarea id="reason" name="reason" rows="3" required minlength="3" maxlength="255"
                                  class="w-full rounded-lg border border-line px-3 py-2">{{ old('reason') }}</textarea>
                        @error('reason') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
                        <button class="rounded-lg border border-red-300 px-4 py-2 font-semibold text-red-700 hover:bg-red-50">Clôturer la demande</button>
                    </form>
                </div>
            </div>
        @endcan
    @endif
@endsection
