<?php

namespace App\Http\Requests\Hours;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTimeEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'team_member_id' => ['required', 'string', Rule::exists('team_members', '_id')],
            'work_date' => ['required', 'date_format:Y-m-d'],
            'hours' => ['required', 'numeric', 'min:0.25', 'max:24'],
            'description' => ['nullable', 'string', 'max:255'],
        ];
    }
}
