<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * WorkspaceService
 *
 * Based on dub-main workspace management logic
 *
 * Handles workspace creation, updates, deletion, and business logic
 * following dub-main patterns and constraints.
 */
final class WorkspaceService
{
    /**
     * Create a new workspace for the user
     */
    public function createWorkspace(User $user, array $data): Workspace
    {
        return DB::transaction(function () use ($user, $data) {
            // Create the workspace
            $workspace = Workspace::create([
                'name' => $data['name'],
                'slug' => $data['slug'],
                'logo' => $data['logo'] ?? null,
                'plan' => 'free',
                'billing_cycle_start' => now()->day,
                'invite_code' => Str::random(24),
                'invoice_prefix' => Str::upper(Str::random(8)),
            ]);

            // Add the user as owner
            $workspace->users()->attach($user->id, [
                'role' => 'owner',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Set as default workspace if user doesn't have one
            if (! $user->default_workspace) {
                $user->update(['default_workspace' => $workspace->slug]);
            }

            return $workspace;
        });
    }

    /**
     * Update an existing workspace
     */
    public function updateWorkspace(Workspace $workspace, array $data): Workspace
    {
        return DB::transaction(function () use ($workspace, $data) {
            $oldSlug = $workspace->slug;

            $workspace->update(array_filter([
                'name' => $data['name'] ?? null,
                'slug' => $data['slug'] ?? null,
                'logo' => $data['logo'] ?? null,
                'conversion_enabled' => $data['conversion_enabled'] ?? null,
                'allowed_hostnames' => $data['allowed_hostnames'] ?? null,
            ]));

            // Update user default workspace if slug changed
            if (isset($data['slug']) && $data['slug'] !== $oldSlug) {
                User::where('default_workspace', $oldSlug)
                    ->update(['default_workspace' => $data['slug']]);
            }

            return $workspace->fresh();
        });
    }

    /**
     * Delete a workspace and all associated data
     */
    public function deleteWorkspace(Workspace $workspace): void
    {
        DB::transaction(function () use ($workspace) {
            // Remove workspace as default for users
            User::where('default_workspace', $workspace->slug)
                ->update(['default_workspace' => null]);

            // Delete the workspace (cascade will handle related data)
            $workspace->delete();
        });
    }

    /**
     * Find workspace by ID or slug
     */
    public function findWorkspaceByIdOrSlug(string $idOrSlug): ?Workspace
    {
        // Try to find by ID first (numeric)
        if (is_numeric($idOrSlug)) {
            return Workspace::find($idOrSlug);
        }

        // Otherwise find by slug
        return Workspace::where('slug', $idOrSlug)->first();
    }

    /**
     * Check if slug is available
     */
    public function isSlugAvailable(string $slug): bool
    {
        return ! Workspace::where('slug', $slug)->exists();
    }

    /**
     * Generate a unique slug from name
     */
    public function generateUniqueSlug(string $name): string
    {
        $baseSlug = Str::slug($name);
        $slug = $baseSlug;
        $counter = 1;

        while (! $this->isSlugAvailable($slug)) {
            $slug = $baseSlug.'-'.$counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * Get workspace usage statistics
     */
    public function getWorkspaceUsage(Workspace $workspace): array
    {
        return [
            'links' => [
                'usage' => $workspace->links_usage,
                'limit' => $workspace->links_limit,
                'percentage' => $workspace->links_limit > 0
                    ? round(($workspace->links_usage / $workspace->links_limit) * 100, 2)
                    : 0,
            ],
            'users' => [
                'usage' => $workspace->users()->count(),
                'limit' => $workspace->users_limit,
                'percentage' => $workspace->users_limit > 0
                    ? round(($workspace->users()->count() / $workspace->users_limit) * 100, 2)
                    : 0,
            ],
            'domains' => [
                'usage' => $workspace->domains()->count(),
                'limit' => $workspace->domains_limit,
                'percentage' => $workspace->domains_limit > 0
                    ? round(($workspace->domains()->count() / $workspace->domains_limit) * 100, 2)
                    : 0,
            ],
        ];
    }

    /**
     * Check if workspace can perform action based on plan limits
     */
    public function canPerformAction(Workspace $workspace, string $action): bool
    {
        return match ($action) {
            'create_link' => ! $workspace->hasReachedLinksLimit(),
            'invite_user' => ! $workspace->hasReachedUsersLimit(),
            'add_domain' => ! $workspace->hasReachedDomainsLimit(),
            default => true,
        };
    }
}
