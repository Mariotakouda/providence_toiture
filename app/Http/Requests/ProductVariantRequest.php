<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ProductVariantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('product')) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['variant_sku' => strtoupper(trim((string) $this->input('variant_sku')))]);
    }

    public function rules(): array
    {
        return [
            'variant_sku' => [
                'required', 'string', 'max:50', 'regex:/^[A-Z0-9._-]+$/',
                Rule::unique('product_variants', 'variant_sku')->ignore($this->route('variant')?->id),
            ],
            'color' => ['nullable', 'string', 'max:50'],
            'thickness' => ['nullable', 'numeric', 'min:0', 'max:999999'],
            'length' => ['nullable', 'numeric', 'min:0', 'max:999999'],
            'width' => ['nullable', 'numeric', 'min:0', 'max:999999'],
            'sale_price_override' => ['nullable', 'numeric', 'min:0', 'max:9999999999999'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                $hasAttribute = collect(['color', 'thickness', 'length', 'width'])
                    ->contains(fn ($field) => filled($this->input($field)));

                if (! $hasAttribute) {
                    $validator->errors()->add('color', 'Renseignez au moins une caractéristique : couleur, épaisseur, longueur ou largeur.');
                }
            },
        ];
    }

    public function messages(): array
    {
        return [
            'variant_sku.regex' => 'La référence ne peut contenir que des lettres, chiffres, points, tirets et underscores.',
        ];
    }

    public function attributes(): array
    {
        return [
            'variant_sku' => 'référence de la variante',
            'color' => 'couleur',
            'thickness' => 'épaisseur',
            'length' => 'longueur',
            'width' => 'largeur utile',
            'sale_price_override' => 'prix de vente spécifique',
        ];
    }
}
