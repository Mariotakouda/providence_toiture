@extends('layouts.app')

@section('title', 'Nouveau bon de livraison : '.$order->order_number)

@section('content')
    <form method="POST" action="{{ route('deliveries.store', $order) }}" class="w-full max-w-6xl rounded-xl bg-white p-4 shadow sm:p-6">
        @csrf

        <p class="mb-4 text-sm text-muted">
            Commande <span class="font-mono">{{ $order->order_number }}</span> : {{ $order->customer->name }}.
            Les quantités proposées sont le reste à livrer ; vous pouvez livrer en plusieurs fois.
        </p>

        @foreach ($errors->getMessages() as $key => $messages)
            @if (str_starts_with($key, 'lines'))
                <p class="mb-1 text-sm text-red-600">{{ $messages[0] }}</p>
            @endif
        @endforeach

        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="bg-page text-xs uppercase text-muted">
                    <tr>
                        <th class="px-3 py-2">Produit</th>
                        <th class="px-3 py-2 text-right">Commandée</th>
                        <th class="px-3 py-2 text-right">Livrée</th>
                        <th class="px-3 py-2 text-right">Reste à livrer</th>
                        <th class="px-3 py-2 w-36 text-right">À livrer dans ce bon</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-soft">
                    @foreach ($order->items as $i => $item)
                        @php $left = $remaining[$item->id]; @endphp
                        <tr>
                            <td class="px-3 py-2">
                                {{ $item->product_name_snapshot }}
                                <span class="block font-mono text-xs text-muted">{{ $item->sku_snapshot }}</span>
                            </td>
                            <td class="px-3 py-2 text-right">{{ \App\Support\Format::quantity($item->quantity_ordered) }} {{ $item->unit_snapshot }}</td>
                            <td class="px-3 py-2 text-right">{{ \App\Support\Format::quantity($item->quantity_delivered) }}</td>
                            <td class="px-3 py-2 text-right font-medium">{{ \App\Support\Format::quantity($left) }}</td>
                            <td class="px-3 py-2 text-right">
                                @if (bccomp($left, '0', 2) > 0)
                                    <input type="hidden" name="lines[{{ $i }}][order_item_id]" value="{{ $item->id }}">
                                    <input type="number" step="0.01" min="0" max="{{ $left }}" name="lines[{{ $i }}][quantity]"
                                           value="{{ old('lines.'.$i.'.quantity', $left) }}"
                                           class="w-32 rounded-lg border border-line px-2 py-1 text-right">
                                @else
                                    <span class="text-muted">Complet</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-6 grid gap-4 md:grid-cols-2">
            <div>
                <label for="planned_at" class="block text-sm font-medium">Date et heure prévues</label>
                <input id="planned_at" name="planned_at" type="datetime-local" value="{{ old('planned_at') }}"
                       class="mt-1 w-full rounded-lg border border-line px-3 py-2">
                @error('planned_at') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="driver_name" class="block text-sm font-medium">Livreur</label>
                <input id="driver_name" name="driver_name" type="text" list="drivers" value="{{ old('driver_name') }}"
                       class="mt-1 w-full rounded-lg border border-line px-3 py-2">
                <datalist id="drivers">
                    @foreach ($drivers as $driver)
                        <option value="{{ $driver }}">
                    @endforeach
                </datalist>
                @error('driver_name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="md:col-span-2">
                <label for="notes" class="block text-sm font-medium">Observations</label>
                <textarea id="notes" name="notes" rows="3" class="mt-1 w-full rounded-lg border border-line px-3 py-2">{{ old('notes') }}</textarea>
                @error('notes') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="mt-6 flex gap-3">
            <button type="submit" class="rounded-lg bg-brand px-4 py-2 text-sm font-medium text-on-brand hover:bg-brand-hover">Créer le bon de livraison</button>
            <a href="{{ route('orders.show', $order) }}" class="rounded-lg border border-line px-4 py-2 text-sm hover:bg-page">Annuler</a>
        </div>
    </form>
@endsection
