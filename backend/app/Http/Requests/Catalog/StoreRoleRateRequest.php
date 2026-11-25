<?php

namespace App\Http\Requests\Catalog;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRoleRateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'currency' => ['sometimes', 'string', Rule::in(['MXN', 'USD', 'EUR'])],
            'rate' => ['required', 'numeric', 'min:0.01', 'max:100000'],
            'effective_from' => ['required', 'date_format:Y-m-d'],
            'effective_to' => ['nullable', 'date_format:Y-m-d', 'after:effective_from'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }
}
