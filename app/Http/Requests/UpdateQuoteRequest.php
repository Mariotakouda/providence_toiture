<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\QuoteRules;
use Illuminate\Foundation\Http\FormRequest;

class UpdateQuoteRequest extends FormRequest
{
    use QuoteRules;

    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('quote')) ?? false;
    }

    public function rules(): array
    {
        return $this->quoteRules($this->route('quote')->customer_id);
    }
}
