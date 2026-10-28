<?php

namespace App\Http\Requests;

use App\Enums\PaymentMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('recordPayment', $this->route('invoice')) ?? false;
    }

    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'gt:0', 'decimal:0,2', 'max:9999999999999'],
            'payment_date' => ['required', 'date', 'before_or_equal:today'],
            'method' => ['required', Rule::enum(PaymentMethod::class)],
            'reference' => [
                Rule::requiredIf(fn () => PaymentMethod::tryFrom((string) $this->input('method'))?->requiresReference() ?? false),
                'nullable', 'string', 'max:100',
            ],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return ['reference.required' => 'La référence est obligatoire pour ce mode de paiement.'];
    }

    public function attributes(): array
    {
        return [
            'amount' => 'montant',
            'payment_date' => 'date du paiement',
            'method' => 'mode de paiement',
            'reference' => 'référence',
        ];
    }
}
