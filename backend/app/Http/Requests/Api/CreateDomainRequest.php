<?php

declare(strict_types=1);

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

final class CreateDomainRequest extends FormRequest
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
            'slug' => ['required', 'string', 'max:255', 'regex:/^[a-zA-Z0-9.-]+$/'],
            'project_id' => ['sometimes', 'string', 'exists:projects,id'],
            'primary' => ['sometimes', 'boolean'],
            'placeholder' => ['sometimes', 'string', 'max:255'],
            'expired_url' => ['sometimes', 'nullable', 'url', 'max:2048'],
            'not_found_url' => ['sometimes', 'nullable', 'url', 'max:2048'],
        ];
    }

    /**
     * Get custom error messages.
     */
    public function messages(): array
    {
        return [
            'slug.required' => 'The domain name is required.',
            'slug.regex' => 'The domain name can only contain letters, numbers, dots, and hyphens.',
            'expired_url.url' => 'Please provide a valid URL for the expired link redirect.',
            'not_found_url.url' => 'Please provide a valid URL for the 404 redirect.',
        ];
    }

    /**
     * Get custom attribute names.
     */
    public function attributes(): array
    {
        return [
            'slug' => 'domain name',
            'project_id' => 'workspace',
            'expired_url' => 'expired link URL',
            'not_found_url' => '404 redirect URL',
        ];
    }
}
