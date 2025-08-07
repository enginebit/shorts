<?php

declare(strict_types=1);

namespace App\Http\Requests\Workspace;

use App\Services\WorkspaceService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * CreateWorkspaceRequest
 *
 * Based on dub-main createWorkspaceSchema
 *
 * Validates workspace creation data including name, slug, and logo.
 */
final class CreateWorkspaceRequest extends FormRequest
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
        return [
            'name' => [
                'required',
                'string',
                'min:1',
                'max:32',
            ],
            'slug' => [
                'required',
                'string',
                'min:3',
                'max:48',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', // kebab-case format
                Rule::unique('workspaces', 'slug'),
                function ($attribute, $value, $fail) {
                    // Check against reserved slugs (similar to dub-main)
                    $reservedSlugs = [
                        'api', 'app', 'www', 'mail', 'ftp', 'admin', 'root',
                        'dashboard', 'settings', 'profile', 'account', 'billing',
                        'support', 'help', 'docs', 'blog', 'news', 'about',
                        'contact', 'privacy', 'terms', 'legal', 'security',
                        'status', 'health', 'ping', 'test', 'demo', 'staging',
                        'dev', 'development', 'prod', 'production', 'beta',
                        'alpha', 'preview', 'cdn', 'assets', 'static', 'media',
                        'uploads', 'files', 'images', 'js', 'css', 'fonts',
                    ];

                    if (in_array($value, $reservedSlugs)) {
                        $fail('The slug is reserved and cannot be used.');
                    }
                },
            ],
            'logo' => [
                'nullable',
                'string',
                'max:2048', // Base64 encoded image
            ],
            'conversion_enabled' => [
                'nullable',
                'boolean',
            ],
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Auto-generate slug from name if not provided
        if (! $this->has('slug') && $this->has('name')) {
            $workspaceService = app(WorkspaceService::class);
            $this->merge([
                'slug' => $workspaceService->generateUniqueSlug($this->input('name')),
            ]);
        } else {
            // Ensure slug is properly formatted
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
            'name.required' => 'Workspace name is required.',
            'name.max' => 'Workspace name must be less than 32 characters.',
            'slug.required' => 'Workspace slug is required.',
            'slug.min' => 'Slug must be at least 3 characters.',
            'slug.max' => 'Slug must be less than 48 characters.',
            'slug.regex' => 'Slug must contain only lowercase letters, numbers, and hyphens.',
            'slug.unique' => 'This slug is already taken.',
        ];
    }
}
