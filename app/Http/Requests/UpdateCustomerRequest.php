<?php

namespace App\Http\Requests;

use App\Enums\CustomerType;
use App\Support\Phone;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('customer')) ?? false;
    }

    protected function prepareForValidation(): void
    {
        if (filled($this->input('email'))) {
            $this->merge(['email' => mb_strtolower(trim($this->input('email')))]);
        }
    }

    public function rules(): array
    {
        return [
            'customer_type' => ['required', Rule::enum(CustomerType::class)],
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:30', Phone::rule()],
            'email' => ['nullable', 'string', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['required', 'boolean'],
            'confirm_duplicate' => ['nullable', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'customer_type' => 'type de client',
            'name' => 'nom ou raison sociale',
            'phone' => 'téléphone',
            'email' => 'e-mail',
            'address' => 'adresse',
            'city' => 'ville',
        ];
    }
}
