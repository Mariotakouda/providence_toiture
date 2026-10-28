<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreInventoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('stock.adjust') ?? false;
    }

    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'max:255'],
            'counts' => ['required', 'array'],
            'counts.*' => ['nullable', 'numeric', 'min:0', 'decimal:0,2', 'max:9999999999'],
            'expected' => ['required', 'array'],
            'expected.*' => ['required', 'numeric'],
        ];
    }

    public function attributes(): array
    {
        return [
            'reason' => 'motif de l’inventaire',
            'counts.*' => 'quantité comptée',
        ];
    }
}
