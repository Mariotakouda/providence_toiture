@extends('layouts.app')

@section('title', 'Modifier la facture '.$invoice->invoice_number)

@section('content')
    <form method="POST" action="{{ route('invoices.update', $invoice) }}" class="w-full max-w-6xl rounded-xl bg-white p-4 shadow sm:p-6">
        @csrf
        @method('PUT')

        @error('status')
            <div class="mb-4 rounded-lg bg-red-50 p-3 text-sm text-red-700">{{ $message }}</div>
        @enderror

        <div class="grid gap-4 md:grid-cols-2">
            <div>
                <label for="due_date" class="block text-sm font-medium">Date d’échéance</label>
                <input id="due_date" name="due_date" type="date" required
                       value="{{ old('due_date', $invoice->due_date?->toDateString()) }}"
                       class="mt-1 w-full rounded-lg border border-line px-3 py-2">
                @error('due_date') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="md:col-span-2">
                <label for="terms" class="block text-sm font-medium">Mentions et conditions</label>
                <textarea id="terms" name="terms" rows="4" class="mt-1 w-full rounded-lg border border-line px-3 py-2">{{ old('terms', $invoice->terms) }}</textarea>
                @error('terms') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="md:col-span-2">
                <label for="notes" class="block text-sm font-medium">Notes internes</label>
                <textarea id="notes" name="notes" rows="2" class="mt-1 w-full rounded-lg border border-line px-3 py-2">{{ old('notes', $invoice->notes) }}</textarea>
                @error('notes') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="mt-6 flex gap-3">
            <button class="rounded-lg bg-brand px-4 py-2 text-sm font-medium text-on-brand hover:bg-brand-hover">Enregistrer</button>
            <a href="{{ route('invoices.show', $invoice) }}" class="rounded-lg border border-line px-4 py-2 text-sm hover:bg-page">Annuler</a>
        </div>
    </form>
@endsection
