<?php

namespace App\Http\Requests;

use App\Support\Phone;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('settings.manage') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $upper = [];

        foreach (['quote', 'order', 'invoice', 'payment', 'delivery'] as $type) {
            $key = "prefix_{$type}";
            if ($this->has($key)) {
                $upper[$key] = strtoupper(trim((string) $this->input($key)));
            }
        }

        $this->merge($upper);
    }

    public function rules(): array
    {
        $prefix = ['required', 'string', 'regex:/^[A-Z0-9]{2,6}$/'];

        return [
            'company_name' => ['required', 'string', 'max:255'],
            'company_address' => ['nullable', 'string', 'max:255'],
            'company_city' => ['nullable', 'string', 'max:100'],
            'company_phone' => ['nullable', 'string', 'max:30', Phone::rule()],
            'company_email' => ['nullable', 'email', 'max:255'],
            'company_tax_id' => ['nullable', 'string', 'max:100'],
            'company_logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg', 'max:1024'],
            'remove_logo' => ['nullable', 'boolean'],
            'currency_code' => ['required', 'string', 'max:10'],
            'tax_enabled' => ['required', 'boolean'],
            'tax_rate' => ['required_if:tax_enabled,1', 'nullable', 'numeric', 'between:0,100', 'decimal:0,2'],
            'quote_validity_days' => ['required', 'integer', 'between:1,365'],
            'quote_terms' => ['nullable', 'string', 'max:5000'],
            'invoice_terms' => ['nullable', 'string', 'max:5000'],
            'numbering_reset_yearly' => ['required', 'boolean'],
            'numbering_padding' => ['required', 'integer', 'between:3,8'],
            'prefix_quote' => $prefix,
            'prefix_order' => $prefix,
            'prefix_invoice' => $prefix,
            'prefix_payment' => $prefix,
            'prefix_delivery' => $prefix,
        ];
    }

    public function messages(): array
    {
        return [
            'prefix_quote.regex' => 'Le préfixe doit contenir 2 à 6 lettres ou chiffres.',
            'prefix_order.regex' => 'Le préfixe doit contenir 2 à 6 lettres ou chiffres.',
            'prefix_invoice.regex' => 'Le préfixe doit contenir 2 à 6 lettres ou chiffres.',
            'prefix_payment.regex' => 'Le préfixe doit contenir 2 à 6 lettres ou chiffres.',
            'prefix_delivery.regex' => 'Le préfixe doit contenir 2 à 6 lettres ou chiffres.',
        ];
    }

    public function attributes(): array
    {
        return [
            'company_name' => 'nom de l’entreprise',
            'company_phone' => 'téléphone',
            'company_email' => 'e-mail',
            'company_logo' => 'logo',
            'currency_code' => 'devise',
            'tax_rate' => 'taux de taxe',
            'quote_validity_days' => 'durée de validité',
            'numbering_padding' => 'nombre de chiffres',
        ];
    }
}
