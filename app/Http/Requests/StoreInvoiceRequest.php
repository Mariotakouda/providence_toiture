<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('createInvoice', $this->route('order')) ?? false;
    }

    public function rules(): array
    {
        return [
            'due_date' => ['required', 'date', 'after_or_equal:today'],
            'terms' => ['nullable', 'string', 'max:5000'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function attributes(): array
    {
        return ['due_date' => 'date d’échéance'];
    }
}
