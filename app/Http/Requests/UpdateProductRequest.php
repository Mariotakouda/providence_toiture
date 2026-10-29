<?php

namespace App\Http\Requests;

use App\Enums\Unit;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('product')) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['sku' => strtoupper(trim((string) $this->input('sku')))]);
    }

    public function rules(): array
    {
        $product = $this->route('product');

        return [
            // Une catégorie désactivée reste acceptée si c'est celle du produit.
            'category_id' => ['required', Rule::exists('categories', 'id')->where(
                fn ($q) => $q->where('is_active', true)->orWhere('id', $product->category_id)
            )],
            'sku' => ['required', 'string', 'max:50', 'regex:/^[A-Z0-9._-]+$/', Rule::unique('products', 'sku')->ignore($product->id)],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'unit' => ['required', Rule::enum(Unit::class)],
            'purchase_price' => ['nullable', 'numeric', 'min:0', 'max:9999999999999'],
            'sale_price' => ['required', 'numeric', 'min:0', 'max:9999999999999'],
            'min_stock' => ['nullable', 'numeric', 'min:0', 'max:9999999999'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
            'remove_image' => ['nullable', 'boolean'],
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
            'image' => 'photo',
        ];
    }
}
