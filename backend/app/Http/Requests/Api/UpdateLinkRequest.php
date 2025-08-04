<?php

declare(strict_types=1);

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateLinkRequest extends FormRequest
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
            'url' => ['sometimes', 'url', 'max:2048'],
            'key' => ['sometimes', 'string', 'max:255', 'regex:/^[a-zA-Z0-9\-_]+$/'],
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'string', 'max:500'],
            'image' => ['sometimes', 'url', 'max:2048'],
            'public_stats' => ['sometimes', 'boolean'],
            'password' => ['sometimes', 'nullable', 'string', 'max:255'],
            'expires_at' => ['sometimes', 'nullable', 'date', 'after:now'],
            'ios' => ['sometimes', 'nullable', 'url', 'max:2048'],
            'android' => ['sometimes', 'nullable', 'url', 'max:2048'],
            'geo' => ['sometimes', 'nullable', 'json'],
            'utm_source' => ['sometimes', 'nullable', 'string', 'max:255'],
            'utm_medium' => ['sometimes', 'nullable', 'string', 'max:255'],
            'utm_campaign' => ['sometimes', 'nullable', 'string', 'max:255'],
            'utm_term' => ['sometimes', 'nullable', 'string', 'max:255'],
            'utm_content' => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }

    /**
     * Get custom error messages.
     */
    public function messages(): array
    {
        return [
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
        ];
    }
}
