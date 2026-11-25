<?php

namespace App\Http\Requests\Projects;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'code' => ['sometimes', 'nullable', 'string', 'max:40', Rule::unique('projects', 'code')],
            'client_id' => ['required', 'string', Rule::exists('clients', '_id')],
            'manager_id' => ['required', 'string', Rule::exists('users', '_id')],
            'description' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'base_currency' => ['sometimes', 'string', Rule::in(['MXN', 'USD', 'EUR'])],
            'start_date' => ['required', 'date_format:Y-m-d'],
            'end_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'status' => ['sometimes', 'string', Rule::in(['active', 'closed'])],
            'show_rates_to_client' => ['sometimes', 'boolean'],
        ];
    }

    /** RN-20: un gestor siempre es el responsable de los proyectos que crea. */
    protected function prepareForValidation(): void
    {
        $user = $this->user();

        if ($user?->isGestor()) {
            $this->merge(['manager_id' => $user->getKey()]);
        }
    }
}
