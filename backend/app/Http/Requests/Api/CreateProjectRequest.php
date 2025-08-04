<?php

declare(strict_types=1);

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

final class CreateProjectRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'regex:/^[a-zA-Z0-9\-_]+$/', 'unique:projects,slug'],
            'logo' => ['sometimes', 'nullable', 'url', 'max:2048'],
        ];
    }

    /**
     * Get custom error messages.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'The workspace name is required.',
            'slug.required' => 'The workspace slug is required.',
            'slug.unique' => 'This workspace slug is already taken.',
            'slug.regex' => 'The workspace slug can only contain letters, numbers, hyphens, and underscores.',
            'logo.url' => 'Please provide a valid URL for the logo.',
        ];
    }

    /**
     * Get custom attribute names.
     */
    public function attributes(): array
    {
        return [
            'name' => 'workspace name',
            'slug' => 'workspace slug',
            'logo' => 'workspace logo',
        ];
    }
}
