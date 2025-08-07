<?php

declare(strict_types=1);

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

final class CreateLinkRequest extends FormRequest
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
            'url' => ['required', 'url', 'max:2048'],
            'domain' => ['sometimes', 'string', 'max:255'],
            'key' => ['sometimes', 'string', 'max:255', 'regex:/^[a-zA-Z0-9\-_]+$/'],
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'string', 'max:500'],
            'image' => ['sometimes', 'url', 'max:2048'],
            'project_id' => ['sometimes', 'string', 'exists:projects,id'],
            'public_stats' => ['sometimes', 'boolean'],
            'password' => ['sometimes', 'string', 'max:255'],
            'expires_at' => ['sometimes', 'date', 'after:now'],
            'ios' => ['sometimes', 'url', 'max:2048'],
            'android' => ['sometimes', 'url', 'max:2048'],
            'geo' => ['sometimes', 'json'],
            'utm_source' => ['sometimes', 'string', 'max:255'],
            'utm_medium' => ['sometimes', 'string', 'max:255'],
            'utm_campaign' => ['sometimes', 'string', 'max:255'],
            'utm_term' => ['sometimes', 'string', 'max:255'],
            'utm_content' => ['sometimes', 'string', 'max:255'],
        ];
    }

    /**
     * Get custom error messages.
     */
    public function messages(): array
    {
        return [
            'url.required' => 'The destination URL is required.',
            'url.url' => 'Please provide a valid URL.',
            'key.regex' => 'The short link key can only contain letters, numbers, hyphens, and underscores.',
            'expires_at.after' => 'The expiration date must be in the future.',
        ];
    }

    /**
     * Get custom attribute names.
     */
    public function attributes(): array
    {
        return [
            'url' => 'destination URL',
            'key' => 'short link key',
            'project_id' => 'workspace',
        ];
    }
}
