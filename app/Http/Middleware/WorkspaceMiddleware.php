<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\WorkspaceAuthService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

/**
 * WorkspaceMiddleware
 *
 * Based on dub-main workspace context handling patterns
 *
 * Handles:
 * - Automatic workspace context loading from URL
 * - Workspace access permission validation
 * - Inertia.js data sharing for workspace context
 * - Workspace session management
 *
 * Adaptations for Laravel + Inertia.js:
 * - Uses Laravel middleware pattern
 * - Integrates with Inertia.js data sharing
 * - Maintains workspace context across requests
 * - Handles workspace-specific route protection
 */
final class WorkspaceMiddleware
{
    public function __construct(
        private readonly WorkspaceAuthService $workspaceAuthService
    ) {}

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        // Only process for authenticated users
        if (! $user) {
            return $next($request);
        }

        // Extract workspace slug from URL if present
        $workspaceSlug = $this->extractWorkspaceSlugFromRequest($request);

        if ($workspaceSlug) {
            // Validate workspace access
            $workspace = $user->workspaces()
                ->where('slug', $workspaceSlug)
                ->first();

            if (! $workspace) {
                // User doesn't have access to this workspace
                return redirect('/dashboard')
                    ->with('error', 'Workspace not found or access denied.');
            }

            // Set workspace context in session
            $this->workspaceAuthService->setWorkspaceContext($workspace);

            // Update user's default workspace if different
            if ($user->default_workspace !== $workspaceSlug) {
                $user->update(['default_workspace' => $workspaceSlug]);
            }
        }

        // Share workspace data with Inertia
        $this->shareWorkspaceDataWithInertia($user);

        return $next($request);
    }

    /**
     * Extract workspace slug from request URL
     */
    private function extractWorkspaceSlugFromRequest(Request $request): ?string
    {
        $path = $request->path();
        $segments = array_filter(explode('/', $path));

        if (empty($segments)) {
            return null;
        }

        $firstSegment = $segments[0];

        // Check if first segment is not a reserved route
        $reservedRoutes = [
            'api', 'admin', 'dashboard', 'onboarding', 'settings',
            'profile', 'account', 'billing', 'support', 'docs',
            'login', 'register', 'forgot-password', 'reset-password',
            'email', 'password', 'two-factor-challenge', 'confirm-password',
        ];

        if (in_array($firstSegment, $reservedRoutes)) {
            return null;
        }

        // Validate workspace slug format
        if (preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $firstSegment)) {
            return $firstSegment;
        }

        return null;
    }

    /**
     * Share workspace data with Inertia.js
     */
    private function shareWorkspaceDataWithInertia($user): void
    {
        $workspaceData = $this->workspaceAuthService->getWorkspaceDataForSharing($user);

        Inertia::share([
            'workspaces' => $workspaceData['workspaces'],
            'currentWorkspace' => $workspaceData['currentWorkspace'],
        ]);
    }
}
