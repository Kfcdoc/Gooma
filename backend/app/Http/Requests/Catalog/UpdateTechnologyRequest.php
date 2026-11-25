<?php

namespace App\Http\Requests\Catalog;

use App\Models\Technology;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTechnologyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        /** @var Technology|null $technology */
        $technology = $this->route('technology');

        return [
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:80',
                Rule::unique('technologies', 'name')->ignore($technology?->getKey()),
            ],
            'slug' => [
                'sometimes',
                'nullable',
                'string',
                'max:80',
                'regex:/^[a-z0-9-]+$/',
                Rule::unique('technologies', 'slug')->ignore($technology?->getKey()),
            ],
            'category' => ['sometimes', 'nullable', 'string', 'max:80'],
            'active' => ['sometimes', 'boolean'],
        ];
    }
}
