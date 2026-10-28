@extends('layouts.app')

@section('title', 'Paramètres')

@section('content')
    @php
        $input = 'mt-1 w-full rounded-lg border border-line px-3 py-2';
        $v = fn ($key) => old($key, $values[$key] ?? '');
    @endphp

    <form method="POST" action="{{ route('settings.update') }}" enctype="multipart/form-data" class="w-full max-w-6xl space-y-6">
        @csrf
        @method('PUT')

        <section class="rounded-xl bg-white p-6 shadow">
            <h2 class="mb-4 text-lg font-semibold">Entreprise</h2>
            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label class="block text-sm font-medium" for="company_name">Nom</label>
                    <input id="company_name" name="company_name" class="{{ $input }}" value="{{ $v('company_name') }}" required>
                    @error('company_name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium" for="company_tax_id">Identifiant fiscal / RCCM</label>
                    <input id="company_tax_id" name="company_tax_id" class="{{ $input }}" value="{{ $v('company_tax_id') }}">
                    @error('company_tax_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium" for="company_address">Adresse</label>
                    <input id="company_address" name="company_address" class="{{ $input }}" value="{{ $v('company_address') }}">
                    @error('company_address') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium" for="company_city">Ville</label>
                    <input id="company_city" name="company_city" class="{{ $input }}" value="{{ $v('company_city') }}">
                    @error('company_city') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium" for="company_phone">Téléphone</label>
                    <input id="company_phone" name="company_phone" class="{{ $input }}" value="{{ $v('company_phone') }}">
                    @error('company_phone') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium" for="company_email">E-mail</label>
                    <input id="company_email" name="company_email" type="email" class="{{ $input }}" value="{{ $v('company_email') }}">
                    @error('company_email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium" for="company_logo">Logo (PNG ou JPG, 1 Mo maximum)</label>
                    @if ($logo)
                        <img src="{{ $logo }}" alt="Logo" class="my-2 h-16">
                        <label class="inline-flex items-center gap-2 text-sm">
                            <input type="hidden" name="remove_logo" value="0">
                            <input type="checkbox" name="remove_logo" value="1"> Supprimer le logo actuel
                        </label>
                    @endif
                    <input id="company_logo" name="company_logo" type="file" accept=".png,.jpg,.jpeg" class="mt-1 block text-sm">
                    @error('company_logo') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>
        </section>

        <section class="rounded-xl bg-white p-6 shadow">
            <h2 class="mb-4 text-lg font-semibold">Devise et taxes</h2>
            <div class="grid gap-4 md:grid-cols-3">
                <div>
                    <label class="block text-sm font-medium" for="currency_code">Devise (affichée)</label>
                    <input id="currency_code" name="currency_code" class="{{ $input }}" value="{{ $v('currency_code') }}" required>
                    @error('currency_code') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium">Taxe sur les devis et factures</label>
                    <input type="hidden" name="tax_enabled" value="0">
                    <label class="mt-2 inline-flex items-center gap-2 text-sm">
                        <input type="checkbox" name="tax_enabled" value="1" @checked($v('tax_enabled') === '1' || $v('tax_enabled') === 1)>
                        Appliquer une taxe
                    </label>
                </div>
                <div>
                    <label class="block text-sm font-medium" for="tax_rate">Taux (%)</label>
                    <input id="tax_rate" name="tax_rate" type="number" step="0.01" min="0" max="100" class="{{ $input }}" value="{{ $v('tax_rate') }}">
                    @error('tax_rate') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>
            <p class="mt-2 text-xs text-muted">À confirmer avec la direction et le comptable avant la mise en production.</p>
        </section>

        <section class="rounded-xl bg-white p-6 shadow">
            <h2 class="mb-4 text-lg font-semibold">Documents</h2>
            <div class="grid gap-4">
                <div class="md:w-1/3">
                    <label class="block text-sm font-medium" for="quote_validity_days">Validité d’un devis (jours)</label>
                    <input id="quote_validity_days" name="quote_validity_days" type="number" min="1" max="365" class="{{ $input }}" value="{{ $v('quote_validity_days') }}" required>
                    @error('quote_validity_days') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium" for="quote_terms">Conditions commerciales des devis</label>
                    <textarea id="quote_terms" name="quote_terms" rows="4" class="{{ $input }}">{{ $v('quote_terms') }}</textarea>
                    @error('quote_terms') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    <p class="mt-1 text-xs text-muted">Texte validé par l’entreprise (acompte, délais, garantie…). Copié dans chaque nouveau devis.</p>
                </div>
                <div>
                    <label class="block text-sm font-medium" for="invoice_terms">Mentions et conditions des factures</label>
                    <textarea id="invoice_terms" name="invoice_terms" rows="4" class="{{ $input }}">{{ $v('invoice_terms') }}</textarea>
                    @error('invoice_terms') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>
        </section>

        <section class="rounded-xl bg-white p-6 shadow">
            <h2 class="mb-4 text-lg font-semibold">Numérotation</h2>
            <div class="grid gap-4 md:grid-cols-3">
                <div>
                    <label class="block text-sm font-medium">Remise à zéro</label>
                    <input type="hidden" name="numbering_reset_yearly" value="0">
                    <label class="mt-2 inline-flex items-center gap-2 text-sm">
                        <input type="checkbox" name="numbering_reset_yearly" value="1" @checked($v('numbering_reset_yearly') === '1' || $v('numbering_reset_yearly') === 1)>
                        Chaque année
                    </label>
                </div>
                <div>
                    <label class="block text-sm font-medium" for="numbering_padding">Nombre de chiffres</label>
                    <input id="numbering_padding" name="numbering_padding" type="number" min="3" max="8" class="{{ $input }}" value="{{ $v('numbering_padding') }}" required>
                    @error('numbering_padding') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="mt-4 grid gap-4 md:grid-cols-5">
                @foreach (['quote' => 'Devis', 'order' => 'Commandes', 'invoice' => 'Factures', 'payment' => 'Reçus', 'delivery' => 'Livraisons'] as $type => $label)
                    <div>
                        <label class="block text-sm font-medium" for="prefix_{{ $type }}">{{ $label }}</label>
                        <input id="prefix_{{ $type }}" name="prefix_{{ $type }}" class="{{ $input }} uppercase" value="{{ $v('prefix_'.$type) }}" required>
                        @error('prefix_'.$type) <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                @endforeach
            </div>
            <p class="mt-2 text-xs text-muted">Format : PRÉFIXE-ANNÉE-NUMÉRO, par exemple DEV-2026-0001. Les numéros déjà attribués ne changent pas.</p>
        </section>

        <button class="rounded-lg bg-brand px-5 py-2 text-sm font-medium text-on-brand hover:bg-brand-hover">Enregistrer les paramètres</button>
    </form>
@endsection
