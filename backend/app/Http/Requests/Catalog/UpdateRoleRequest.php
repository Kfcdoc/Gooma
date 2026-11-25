<?php

namespace App\Http\Requests\Catalog;

use App\Models\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        /** @var Role|null $role */
        $role = $this->route('role');

        return [
            'code' => [
                'sometimes',
                'required',
                'string',
                'max:40',
                'regex:/^[a-z0-9_]+$/',
                Rule::unique('roles', 'code')->ignore($role?->getKey()),
            ],
            'name' => ['sometimes', 'required', 'string', 'max:120'],
            'description' => ['sometimes', 'nullable', 'string', 'max:500'],
            'active' => ['sometimes', 'boolean'],
        ];
    }
}
