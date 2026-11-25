<?php

namespace App\Http\Requests\Projects;

use App\Models\Project;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        /** @var Project|null $project */
        $project = $this->route('project');

        return [
            'name' => ['sometimes', 'required', 'string', 'max:150'],
            'code' => [
                'sometimes',
                'nullable',
                'string',
                'max:40',
                Rule::unique('projects', 'code')->ignore($project?->getKey()),
            ],
            'client_id' => ['sometimes', 'string', Rule::exists('clients', '_id')],
            'manager_id' => ['sometimes', 'string', Rule::exists('users', '_id')],
            'description' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'base_currency' => ['sometimes', 'string', Rule::in(['MXN', 'USD', 'EUR'])],
            'start_date' => ['sometimes', 'date_format:Y-m-d'],
            'end_date' => ['sometimes', 'date_format:Y-m-d'],
            'status' => ['sometimes', 'string', Rule::in(['active', 'closed'])],
            'show_rates_to_client' => ['sometimes', 'boolean'],
        ];
    }
}
