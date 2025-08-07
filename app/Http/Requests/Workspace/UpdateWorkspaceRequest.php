<?php

declare(strict_types=1);

namespace App\Http\Requests\Workspace;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * UpdateWorkspaceRequest
 *
 * Based on dub-main updateWorkspaceSchema
 *
 * Validates workspace update data with partial updates allowed.
 */
final class UpdateWorkspaceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $workspaceId = $this->route('idOrSlug');

        return [
            'name' => [
                'sometimes',
                'string',
                'min:1',
                'max:32',
            ],
            'slug' => [
                'sometimes',
                'string',
                'min:3',
                'max:48',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('workspaces', 'slug')->ignore($workspaceId, 'slug'),
            ],
            'logo' => [
                'nullable',
                'string',
                'max:2048',
            ],
            'conversion_enabled' => [
                'sometimes',
                'boolean',
            ],
            'allowed_hostnames' => [
                'sometimes',
                'array',
            ],
            'allowed_hostnames.*' => [
                'string',
                'regex:/^[a-zA-Z0-9]([a-zA-Z0-9\-]{0,61}[a-zA-Z0-9])?(\.[a-zA-Z0-9]([a-zA-Z0-9\-]{0,61}[a-zA-Z0-9])?)*$/',
            ],
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('slug')) {
            $this->merge([
                'slug' => Str::slug($this->input('slug')),
            ]);
        }
    }

    /**
     * Get custom error messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.max' => 'Workspace name must be less than 32 characters.',
            'slug.min' => 'Slug must be at least 3 characters.',
            'slug.max' => 'Slug must be less than 48 characters.',
            'slug.regex' => 'Slug must contain only lowercase letters, numbers, and hyphens.',
            'slug.unique' => 'This slug is already taken.',
            'allowed_hostnames.*.regex' => 'Invalid hostname format.',
        ];
    }
}
