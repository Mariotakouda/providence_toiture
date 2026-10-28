<?php

namespace App\Http\Requests;

use App\Enums\MovementType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStockMovementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && MovementType::allowedFor($this->user()) !== [];
    }

    public function rules(): array
    {
        $allowed = array_map(fn (MovementType $type) => $type->value, MovementType::allowedFor($this->user()));

        return [
            'product_id' => ['required', Rule::exists('products', 'id')->where('is_active', true)],
            'movement_type' => ['required', Rule::in($allowed)],
            'quantity' => ['required', 'numeric', 'gt:0', 'decimal:0,2', 'max:9999999999'],
            'unit_cost' => ['nullable', 'numeric', 'min:0', 'max:9999999999999'],
            'reason' => [
                Rule::requiredIf(fn () => MovementType::tryFrom((string) $this->input('movement_type'))?->requiresReason() ?? false),
                'nullable', 'string', 'max:255',
            ],
            'occurred_at' => ['nullable', 'date', 'before_or_equal:now'],
            'supplier_id' => ['nullable', Rule::exists('suppliers', 'id')->where('is_active', true)],
        ];
    }

    public function messages(): array
    {
        return [
            'movement_type.in' => 'Ce type de mouvement n’est pas autorisé pour votre profil.',
            'reason.required' => 'Un motif est obligatoire pour ce type de mouvement.',
        ];
    }

    public function attributes(): array
    {
        return [
            'product_id' => 'produit',
            'movement_type' => 'type de mouvement',
            'quantity' => 'quantité',
            'unit_cost' => 'coût unitaire',
            'reason' => 'motif',
            'occurred_at' => 'date',
        ];
    }
}
