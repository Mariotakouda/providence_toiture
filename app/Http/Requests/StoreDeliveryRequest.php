<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDeliveryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('createDelivery', $this->route('order')) ?? false;
    }

    public function rules(): array
    {
        return [
            'planned_at' => ['nullable', 'date'],
            'driver_name' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.order_item_id' => ['required', 'integer'],
            'lines.*.quantity' => ['nullable', 'numeric', 'min:0', 'decimal:0,2', 'max:9999999999'],
        ];
    }

    public function attributes(): array
    {
        return [
            'planned_at' => 'date prévue',
            'driver_name' => 'livreur',
            'lines.*.quantity' => 'quantité',
        ];
    }
}
