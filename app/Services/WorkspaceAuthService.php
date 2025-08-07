<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Support\Facades\Session;

/**
 * WorkspaceAuthService
 *
 * Based on dub-main workspace authentication patterns
 *
 * Handles workspace-aware authentication including:
 * - Default workspace detection and redirection
 * - Workspace session management
 * - First-time user workspace setup
 * - Workspace context preservation
 */
final class WorkspaceAuthService
{
    /**
     * Get the appropriate redirect URL after login based on user's workspace context
     */
    public function getPostLoginRedirectUrl(User $user, ?string $intended = null): string
    {
        // If there's an intended URL and it's workspace-specific, use it
        if ($intended && $this->isWorkspaceUrl($intended)) {
            $workspaceSlug = $this->extractWorkspaceSlugFromUrl($intended);
            if ($workspaceSlug && $this->userCanAccessWorkspace($user, $workspaceSlug)) {
                return $intended;
            }
        }

        // Get user's default workspace
        $defaultWorkspace = $user->defaultWorkspace();

        if ($defaultWorkspace) {
            // Store workspace context in session
            $this->setWorkspaceContext($defaultWorkspace);

            return "/{$defaultWorkspace->slug}";
        }

        // If user has workspaces but no default, use the first owned workspace
        $firstOwnedWorkspace = $user->ownedWorkspaces()->first();
        if ($firstOwnedWorkspace) {
            // Set as default workspace
            $user->update(['default_workspace' => $firstOwnedWorkspace->slug]);
            $this->setWorkspaceContext($firstOwnedWorkspace);

            return "/{$firstOwnedWorkspace->slug}";
        }

        // If user has member workspaces, use the first one
        $firstWorkspace = $user->workspaces()->first();
        if ($firstWorkspace) {
            $user->update(['default_workspace' => $firstWorkspace->slug]);
            $this->setWorkspaceContext($firstWorkspace);

            return "/{$firstWorkspace->slug}";
        }

        // New user with no workspaces - redirect to onboarding
        return '/onboarding';
    }

    /**
     * Set workspace context in session
     */
    public function setWorkspaceContext(Workspace $workspace): void
    {
        Session::put('current_workspace_id', $workspace->id);
        Session::put('current_workspace_slug', $workspace->slug);
    }

    /**
     * Clear workspace context from session
     */
    public function clearWorkspaceContext(): void
    {
        Session::forget(['current_workspace_id', 'current_workspace_slug']);
    }

    /**
     * Get current workspace from session
     */
    public function getCurrentWorkspaceFromSession(): ?Workspace
    {
        $workspaceId = Session::get('current_workspace_id');

        if (! $workspaceId) {
            return null;
        }

        return Workspace::find($workspaceId);
    }

    /**
     * Check if URL is workspace-specific
     */
    private function isWorkspaceUrl(string $url): bool
    {
        // Remove domain and query parameters
        $path = parse_url($url, PHP_URL_PATH) ?? '';
        $segments = array_filter(explode('/', $path));

        if (empty($segments)) {
            return false;
        }

        $firstSegment = $segments[0];

        // Check if first segment is not a reserved route
        $reservedRoutes = [
            'login', 'register', 'forgot-password', 'reset-password',
            'dashboard', 'api', 'admin', 'onboarding', 'settings',
            'profile', 'account', 'billing', 'support', 'docs',
        ];

        return ! in_array($firstSegment, $reservedRoutes);
    }

    /**
     * Extract workspace slug from URL
     */
    private function extractWorkspaceSlugFromUrl(string $url): ?string
    {
        $path = parse_url($url, PHP_URL_PATH) ?? '';
        $segments = array_filter(explode('/', $path));

        if (empty($segments)) {
            return null;
        }

        $firstSegment = $segments[0];

        // Validate that it looks like a workspace slug
        if (preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $firstSegment)) {
            return $firstSegment;
        }

        return null;
    }

    /**
     * Check if user can access workspace
     */
    private function userCanAccessWorkspace(User $user, string $workspaceSlug): bool
    {
        return $user->workspaces()
            ->where('slug', $workspaceSlug)
            ->exists();
    }

    /**
     * Handle first-time user setup
     */
    public function handleFirstTimeUser(User $user): string
    {
        // Check if user was invited to any workspaces
        $pendingInvites = \App\Models\WorkspaceInvite::where('email', $user->email)
            ->valid()
            ->with('workspace')
            ->get();

        if ($pendingInvites->isNotEmpty()) {
            // Accept the first invite automatically
            $invite = $pendingInvites->first();
            $workspace = $invite->workspace;

            // Add user to workspace
            $workspace->users()->attach($user->id, [
                'role' => $invite->role,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Set as default workspace
            $user->update(['default_workspace' => $workspace->slug]);

            // Delete the invite
            $invite->delete();

            // Set workspace context
            $this->setWorkspaceContext($workspace);

            return "/{$workspace->slug}";
        }

        // No pending invites - redirect to workspace creation
        return '/onboarding';
    }

    /**
     * Prepare workspace data for Inertia sharing
     *
     * Note: Following dub-main schema where domains belong to projects (workspaces)
     * via project_id, not workspace_id
     */
    public function getWorkspaceDataForSharing(User $user): array
    {
        $workspaces = $user->workspaces()
            ->with(['domains' => function ($query) {
                $query->select('id', 'slug', 'primary', 'verified', 'project_id')
                    ->take(100);
            }])
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function ($workspace) use ($user) {
                return [
                    'id' => $workspace->id,
                    'name' => $workspace->name,
                    'slug' => $workspace->slug,
                    'logo' => $workspace->logo,
                    'plan' => $workspace->plan,
                    'usage' => $workspace->usage,
                    'usageLimit' => $workspace->usage_limit,
                    'linksUsage' => $workspace->links_usage,
                    'linksLimit' => $workspace->links_limit,
                    'domainsLimit' => $workspace->domains_limit,
                    'usersLimit' => $workspace->users_limit,
                    'conversionEnabled' => $workspace->conversion_enabled,
                    'partnersEnabled' => $workspace->partners_enabled,
                    'createdAt' => $workspace->created_at,
                    'users' => [
                        [
                            'role' => $workspace->getUserRole($user),
                            'defaultFolderId' => $workspace->pivot->default_folder_id ?? null,
                        ],
                    ],
                    'domains' => $workspace->domains->map(function ($domain) {
                        return [
                            'slug' => $domain->slug,
                            'primary' => $domain->primary,
                            'verified' => $domain->verified,
                        ];
                    }),
                ];
            });

        $currentWorkspace = $this->getCurrentWorkspaceFromSession();

        return [
            'workspaces' => $workspaces,
            'currentWorkspace' => $currentWorkspace ? $workspaces->firstWhere('id', $currentWorkspace->id) : null,
        ];
    }
}
