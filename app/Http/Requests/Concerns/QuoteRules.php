<?php

namespace App\Http\Requests\Concerns;

use Illuminate\Validation\Rule;

trait QuoteRules
{
    protected function quoteRules(?int $currentCustomerId): array
    {
        return [
            'customer_id' => ['required', Rule::exists('customers', 'id')->where(
                fn ($q) => $q->where('is_active', true)->when($currentCustomerId, fn ($q2) => $q2->orWhere('id', $currentCustomerId))
            )],
            'issue_date' => ['required', 'date'],
            'valid_until' => ['required', 'date', 'after_or_equal:issue_date'],
            'delivery_fee' => ['nullable', 'numeric', 'min:0', 'max:9999999999999'],
            'terms' => ['nullable', 'string', 'max:5000'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'lines' => ['required', 'array', 'min:1', 'max:100'],
            'lines.*.id' => ['nullable', 'integer'],
            'lines.*.product_id' => ['required', Rule::exists('products', 'id')->where('is_active', true)],
            'lines.*.product_variant_id' => ['nullable', 'integer', 'exists:product_variants,id'],
            'lines.*.quantity' => ['required', 'numeric', 'gt:0', 'decimal:0,2', 'max:9999999999'],
            'lines.*.unit_price' => ['nullable', 'numeric', 'min:0', 'max:9999999999999'],
            'lines.*.discount_percent' => ['nullable', 'numeric', 'between:0,100', 'decimal:0,2'],
        ];
    }

    public function messages(): array
    {
        return [
            'lines.required' => 'Ajoutez au moins une ligne au devis.',
            'lines.min' => 'Ajoutez au moins une ligne au devis.',
            'valid_until.after_or_equal' => 'La date de validité doit être postérieure ou égale à la date d’émission.',
            'lines.*.product_id.required' => 'Choisissez un produit pour chaque ligne.',
            'lines.*.product_id.exists' => 'Un produit de la liste est introuvable ou archivé.',
        ];
    }

    public function attributes(): array
    {
        return [
            'customer_id' => 'client',
            'issue_date' => 'date d’émission',
            'valid_until' => 'date de validité',
            'delivery_fee' => 'frais annexes',
            'lines.*.quantity' => 'quantité',
            'lines.*.unit_price' => 'prix unitaire',
            'lines.*.discount_percent' => 'remise',
        ];
    }
}
