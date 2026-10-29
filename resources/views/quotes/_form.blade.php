@php
    $isEdit = $quote !== null;

    $initialLines = old('lines')
        ? array_values(old('lines'))
        : ($isEdit
            ? $quote->items->map(fn ($i) => [
                'id' => $i->id,
                'product_id' => $i->product_id,
                'product_variant_id' => $i->product_variant_id,
                'quantity' => (string) $i->quantity,
                'unit_price' => (string) $i->unit_price,
                'discount_percent' => (string) $i->discount_percent,
            ])->all()
            : [['id' => '', 'product_id' => '', 'product_variant_id' => '', 'quantity' => '1', 'unit_price' => '', 'discount_percent' => '0']]);

    $input = 'mt-1 w-full rounded-lg border border-line px-3 py-2';
@endphp

<div class="grid gap-4 md:grid-cols-4">
    <div class="md:col-span-2">
        <label for="customer_id" class="block text-sm font-medium">Client</label>
        <select id="customer_id" name="customer_id" required class="{{ $input }}">
            <option value="">Choisir un client</option>
            @foreach ($customers as $customer)
                <option value="{{ $customer->id }}" @selected((string) old('customer_id', $quote?->customer_id) === (string) $customer->id)>
                    {{ $customer->name }} ({{ $customer->phone }})
                </option>
            @endforeach
        </select>
        @error('customer_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="issue_date" class="block text-sm font-medium">Date d’émission</label>
        <input id="issue_date" name="issue_date" type="date" required class="{{ $input }}"
               value="{{ old('issue_date', $quote?->issue_date?->toDateString() ?? now()->toDateString()) }}">
        @error('issue_date') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="valid_until" class="block text-sm font-medium">Valide jusqu’au</label>
        <input id="valid_until" name="valid_until" type="date" required class="{{ $input }}"
               value="{{ old('valid_until', $quote?->valid_until?->toDateString() ?? $defaultValidUntil) }}">
        @error('valid_until') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
</div>

<h3 class="mt-6 mb-2 text-lg font-semibold">Lignes</h3>

@if (! $canOverride)
    <p class="mb-2 text-xs text-muted">
        Les prix sont ceux du catalogue. Une remise ou un prix différent nécessite une autorisation.
    </p>
@endif

@foreach ($errors->getMessages() as $key => $messages)
    @if (str_starts_with($key, 'lines'))
        <p class="mb-1 text-sm text-red-600">{{ $messages[0] }}</p>
    @endif
@endforeach

<div class="overflow-x-auto">
    <table class="min-w-full text-sm">
        <thead class="bg-page text-left text-xs uppercase text-muted">
            <tr>
                <th class="px-2 py-2">Produit</th>
                <th class="px-2 py-2">Variante</th>
                <th class="px-2 py-2 w-28">Quantité</th>
                <th class="px-2 py-2 w-36">Prix unitaire</th>
                <th class="px-2 py-2 w-24">Remise %</th>
                <th class="px-2 py-2 w-36 text-right">Total ligne</th>
                <th class="px-2 py-2 w-10"></th>
            </tr>
        </thead>
        <tbody id="lines-body"></tbody>
    </table>
</div>

<button type="button" id="add-line" class="mt-2 text-sm text-link hover:underline">+ Ajouter une ligne</button>

<div class="mt-6 grid gap-6 md:grid-cols-2">
    <div class="space-y-4">
        <div>
            <label for="delivery_fee" class="block text-sm font-medium">Frais annexes (transport, etc.)</label>
            <input id="delivery_fee" name="delivery_fee" type="number" step="0.01" min="0" class="{{ $input }}"
                   value="{{ old('delivery_fee', $quote?->delivery_fee ?? 0) }}">
            @error('delivery_fee') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="notes" class="block text-sm font-medium">Notes internes</label>
            <textarea id="notes" name="notes" rows="2" class="{{ $input }}">{{ old('notes', $quote?->notes) }}</textarea>
            @error('notes') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="terms" class="block text-sm font-medium">Conditions commerciales</label>
            <textarea id="terms" name="terms" rows="4" class="{{ $input }}">{{ old('terms', $isEdit ? $quote->terms : $defaultTerms) }}</textarea>
            @error('terms') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
    </div>

    <div class="self-start rounded-lg bg-page p-4 text-sm">
        <p class="mb-2 text-xs uppercase text-muted">Aperçu des totaux (le calcul définitif est fait à l’enregistrement)</p>
        <dl class="space-y-1">
            <div class="flex justify-between"><dt>Sous-total</dt><dd id="t-subtotal">0</dd></div>
            <div class="flex justify-between"><dt>Remises</dt><dd id="t-discount">0</dd></div>
            @if ((float) $taxRate > 0)
                <div class="flex justify-between"><dt>Taxe ({{ rtrim(rtrim(number_format((float) $taxRate, 2, ',', ''), '0'), ',') }} %)</dt><dd id="t-tax">0</dd></div>
            @endif
            <div class="flex justify-between"><dt>Frais annexes</dt><dd id="t-fee">0</dd></div>
            <div class="flex justify-between border-t pt-2 text-base font-semibold"><dt>Total</dt><dd id="t-total">0</dd></div>
        </dl>
    </div>
</div>

<div class="mt-6 flex gap-3">
    <button type="submit" class="rounded-lg bg-brand px-4 py-2 text-sm font-medium text-on-brand hover:bg-brand-hover">
        {{ $isEdit ? 'Enregistrer' : 'Créer le devis' }}
    </button>
    <a href="{{ $isEdit ? route('quotes.show', $quote) : route('quotes.index') }}"
       class="rounded-lg border border-line px-4 py-2 text-sm hover:bg-page">Annuler</a>
</div>

<script nonce="{{ Vite::cspNonce() }}">
(function () {
    const catalog = {{ Js::from($catalog) }};
    const taxRate = {{ (float) $taxRate }};
    const canOverride = {{ Js::from($canOverride) }};
    const initial = {{ Js::from($initialLines) }};

    const body = document.getElementById('lines-body');
    const feeInput = document.getElementById('delivery_fee');
    let counter = 0;

    const fmt = (n) => new Intl.NumberFormat('fr-FR', { maximumFractionDigits: 2 }).format(n);
    const cls = 'w-full rounded-lg border border-line px-2 py-1';

    function option(value, text, selected) {
        const o = document.createElement('option');
        o.value = value;
        o.textContent = text;
        if (selected) o.selected = true;
        return o;
    }

    function input(name, type, value, attrs) {
        const i = document.createElement('input');
        i.type = type;
        i.name = name;
        i.value = value === null || value === undefined ? '' : value;
        i.className = cls;
        Object.entries(attrs || {}).forEach(([k, v]) => {
            if (v === true) i.setAttribute(k, '');
            else if (v !== false) i.setAttribute(k, v);
        });
        return i;
    }

    function cell(node, extraClass) {
        const td = document.createElement('td');
        td.className = 'px-2 py-2 ' + (extraClass || '');
        if (node) td.appendChild(node);
        return td;
    }

    function fillVariants(select, product, selectedId) {
        select.innerHTML = '';
        select.appendChild(option('', product && product.variants.length ? 'Aucune' : '-', false));
        if (!product) return;
        product.variants.forEach((v) => select.appendChild(option(v.id, v.label, String(v.id) === String(selectedId))));
    }

    function addRow(data) {
        const i = counter++;
        const tr = document.createElement('tr');
        tr.className = 'align-top';

        const idInput = input('lines[' + i + '][id]', 'hidden', data.id);

        const productSel = document.createElement('select');
        productSel.name = 'lines[' + i + '][product_id]';
        productSel.className = cls;
        productSel.appendChild(option('', 'Choisir un produit', false));
        catalog.forEach((p) => productSel.appendChild(
            option(p.id, p.sku + ' : ' + p.name + ' (' + p.unit + ')', String(p.id) === String(data.product_id))
        ));

        const variantSel = document.createElement('select');
        variantSel.name = 'lines[' + i + '][product_variant_id]';
        variantSel.className = cls;

        const qty = input('lines[' + i + '][quantity]', 'number', data.quantity || '1', { step: '0.01', min: '0.01', required: true });
        const price = input('lines[' + i + '][unit_price]', 'number', data.unit_price, { step: '0.01', min: '0', readonly: !canOverride });
        const disc = input('lines[' + i + '][discount_percent]', 'number', data.discount_percent || '0', { step: '0.01', min: '0', max: '100', readonly: !canOverride });

        const total = document.createElement('span');
        total.className = 'line-total';
        total.textContent = '0';

        const remove = document.createElement('button');
        remove.type = 'button';
        remove.textContent = '✕';
        remove.className = 'text-red-600 hover:text-red-800';
        remove.title = 'Supprimer la ligne';

        const productOf = () => catalog.find((p) => String(p.id) === String(productSel.value));
        const variantOf = (p) => (p ? p.variants.find((v) => String(v.id) === String(variantSel.value)) : null);

        function applyCatalogPrice() {
            const p = productOf();
            const v = variantOf(p);
            if (!p) { price.value = ''; return; }
            price.value = (v && v.price !== null) ? v.price : p.price;
        }

        productSel.addEventListener('change', () => {
            fillVariants(variantSel, productOf(), '');
            applyCatalogPrice();
            recalc();
        });
        variantSel.addEventListener('change', () => { applyCatalogPrice(); recalc(); });
        [qty, price, disc].forEach((el) => el.addEventListener('input', recalc));
        remove.addEventListener('click', () => { tr.remove(); recalc(); });

        const p0 = catalog.find((p) => String(p.id) === String(data.product_id));
        fillVariants(variantSel, p0, data.product_variant_id);
        if (p0 && (data.unit_price === '' || data.unit_price === null || data.unit_price === undefined)) {
            const v0 = variantOf(p0);
            price.value = (v0 && v0.price !== null) ? v0.price : p0.price;
        }

        tr.appendChild(cell((() => { const w = document.createElement('div'); w.append(idInput, productSel); return w; })()));
        tr.appendChild(cell(variantSel));
        tr.appendChild(cell(qty));
        tr.appendChild(cell(price));
        tr.appendChild(cell(disc));
        tr.appendChild(cell(total, 'text-right'));
        tr.appendChild(cell(remove, 'text-center'));
        body.appendChild(tr);
    }

    function recalc() {
        let subtotal = 0, discount = 0, tax = 0;

        body.querySelectorAll('tr').forEach((tr) => {
            const q = parseFloat(tr.querySelector('[name$="[quantity]"]').value) || 0;
            const p = parseFloat(tr.querySelector('[name$="[unit_price]"]').value) || 0;
            const d = parseFloat(tr.querySelector('[name$="[discount_percent]"]').value) || 0;

            const gross = q * p;
            const disc = gross * d / 100;
            const net = gross - disc;

            subtotal += gross;
            discount += disc;
            tax += net * taxRate / 100;
            tr.querySelector('.line-total').textContent = fmt(net);
        });

        const fee = parseFloat(feeInput.value) || 0;

        document.getElementById('t-subtotal').textContent = fmt(subtotal);
        document.getElementById('t-discount').textContent = fmt(discount);
        const taxEl = document.getElementById('t-tax');
        if (taxEl) taxEl.textContent = fmt(tax);
        document.getElementById('t-fee').textContent = fmt(fee);
        document.getElementById('t-total').textContent = fmt(subtotal - discount + tax + fee);
    }

    document.getElementById('add-line').addEventListener('click', () => {
        addRow({ id: '', product_id: '', product_variant_id: '', quantity: '1', unit_price: '', discount_percent: '0' });
        recalc();
    });
    feeInput.addEventListener('input', recalc);

    initial.forEach((line) => addRow(line));
    recalc();
})();
</script>
