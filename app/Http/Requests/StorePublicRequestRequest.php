<?php

namespace App\Http\Requests;

use App\Enums\OnlineRequestType;
use App\Support\Phone;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StorePublicRequestRequest extends FormRequest
{
    /** En cas d'erreur, on revient sur la section du formulaire de la page d'accueil. */
    protected $redirect = '/#demande';

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        // Les lignes entièrement vides (ajoutées puis laissées en blanc) sont ignorées.
        $items = collect($this->input('items', []))
            ->filter(fn ($item) => is_array($item) && (filled($item['product_id'] ?? null) || filled($item['quantity'] ?? null)))
            ->values()
            ->all();

        $this->merge([
            'items' => $items,
            'email' => filled($this->input('email')) ? mb_strtolower(trim((string) $this->input('email'))) : null,
        ]);
    }

    public function rules(): array
    {
        return [
            'type' => ['required', Rule::enum(OnlineRequestType::class)],
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:30', Phone::rule()],
            'email' => ['nullable', 'email', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string', 'max:255'],
            'message' => ['nullable', 'string', 'max:3000'],
            'items' => ['nullable', 'array', 'max:10'],
            'items.*.product_id' => ['required', Rule::exists('products', 'id')->where('is_active', true)],
            'items.*.quantity' => ['required', 'numeric', 'gt:0', 'decimal:0,2', 'max:999999'],
            'consent' => ['accepted'],
            'website' => ['nullable', 'max:0'], // champ piège : un humain ne le remplit jamais
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                if (empty($this->input('items')) && ! filled($this->input('message'))) {
                    $validator->errors()->add('message', 'Indiquez les produits souhaités ou décrivez votre besoin.');
                }
            },
        ];
    }

    public function messages(): array
    {
        return [
            'consent.accepted' => 'Vous devez accepter le traitement de vos informations pour envoyer la demande.',
            'website.max' => 'Requête invalide.',
            'items.*.product_id.required' => 'Choisissez un produit pour chaque ligne.',
            'items.*.product_id.exists' => 'Un produit choisi n’est plus disponible.',
            'items.*.quantity.required' => 'Indiquez une quantité pour chaque produit.',
        ];
    }

    public function attributes(): array
    {
        return [
            'type' => 'type de demande',
            'name' => 'nom',
            'phone' => 'téléphone',
            'email' => 'e-mail',
            'city' => 'ville',
            'address' => 'adresse',
            'items.*.quantity' => 'quantité',
        ];
    }
}
