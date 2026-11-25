<?php

namespace App\Http\Requests\Hours;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTimeEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'work_date' => ['sometimes', 'date_format:Y-m-d'],
            'hours' => ['sometimes', 'numeric', 'min:0.25', 'max:24'],
            'description' => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }
}
