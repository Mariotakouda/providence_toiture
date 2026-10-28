<?php

namespace App\Http\Requests;

use App\Enums\Unit;
use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Product::class) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['sku' => strtoupper(trim((string) $this->input('sku')))]);
    }

    public function rules(): array
    {
        return [
            'category_id' => ['required', Rule::exists('categories', 'id')->where('is_active', true)],
            'sku' => ['required', 'string', 'max:50', 'regex:/^[A-Z0-9._-]+$/', 'unique:products,sku'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'unit' => ['required', Rule::enum(Unit::class)],
            'purchase_price' => ['nullable', 'numeric', 'min:0', 'max:9999999999999'],
            'sale_price' => ['required', 'numeric', 'min:0', 'max:9999999999999'],
            'min_stock' => ['nullable', 'numeric', 'min:0', 'max:9999999999'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'sku.regex' => 'La référence ne peut contenir que des lettres, chiffres, points, tirets et underscores.',
        ];
    }

    public function attributes(): array
    {
        return [
            'category_id' => 'catégorie',
            'sku' => 'référence',
            'name' => 'nom',
            'unit' => 'unité',
            'purchase_price' => 'coût d’achat',
            'sale_price' => 'prix de vente',
            'min_stock' => 'seuil d’alerte',
        ];
    }
}
