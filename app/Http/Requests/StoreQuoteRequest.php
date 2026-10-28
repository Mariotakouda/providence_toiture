<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\QuoteRules;
use App\Models\Quote;
use Illuminate\Foundation\Http\FormRequest;

class StoreQuoteRequest extends FormRequest
{
    use QuoteRules;

    public function authorize(): bool
    {
        return $this->user()?->can('create', Quote::class) ?? false;
    }

    public function rules(): array
    {
        return $this->quoteRules(null);
    }
}
