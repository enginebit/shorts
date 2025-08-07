<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\WorkspaceAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

/**
 * WorkspaceAuthenticatedSessionController
 *
 * Based on dub-main authentication patterns with workspace context
 *
 * Handles workspace-aware authentication including:
 * - Login with workspace redirection
 * - Logout with workspace context cleanup
 * - First-time user workspace setup
 * - Session management with workspace data
 *
 * Adaptations for Laravel + Inertia.js:
 * - Maintains existing Laravel authentication security
 * - Adds workspace context handling via WorkspaceAuthService
 * - Preserves Inertia.js response patterns
 * - Integrates with our workspace system architecture
 */
final class WorkspaceAuthenticatedSessionController extends Controller
{
    public function __construct(
        private readonly WorkspaceAuthService $workspaceAuthService
    ) {}

    /**
     * Display the login view.
     */
    public function create(): Response
    {
        return Inertia::render('auth/login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => session('status'),
        ]);
    }

    /**
     * Handle an incoming authentication request with workspace context.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        $user = $request->user();

        // Get intended URL before workspace processing
        $intended = $request->session()->get('url.intended');

        // Determine appropriate redirect URL based on workspace context
        $redirectUrl = $this->workspaceAuthService->getPostLoginRedirectUrl($user, $intended);

        // Handle first-time users or users without workspaces
        if ($redirectUrl === '/onboarding') {
            // Check for pending invites and handle first-time setup
            $redirectUrl = $this->workspaceAuthService->handleFirstTimeUser($user);
        }

        // Clear the intended URL since we've processed it
        $request->session()->forget('url.intended');

        return redirect($redirectUrl);
    }

    /**
     * Destroy an authenticated session with workspace context cleanup.
     */
    public function destroy(Request $request): RedirectResponse
    {
        // Clear workspace context before logout
        $this->workspaceAuthService->clearWorkspaceContext();

        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }

    /**
     * Switch to a different workspace (for authenticated users)
     */
    public function switchWorkspace(Request $request, string $workspaceSlug): RedirectResponse
    {
        $user = $request->user();

        // Validate that user can access this workspace
        $workspace = $user->workspaces()
            ->where('slug', $workspaceSlug)
            ->first();

        if (! $workspace) {
            return redirect()->back()->with('error', 'Workspace not found or access denied.');
        }

        // Update user's default workspace
        $user->update(['default_workspace' => $workspaceSlug]);

        // Set workspace context in session
        $this->workspaceAuthService->setWorkspaceContext($workspace);

        // Redirect to workspace dashboard
        return redirect("/{$workspaceSlug}");
    }

    /**
     * Handle workspace invitation acceptance
     */
    public function acceptInvite(Request $request, string $inviteId): RedirectResponse
    {
        $user = $request->user();

        // Find the invite
        $invite = \App\Models\WorkspaceInvite::where('id', $inviteId)
            ->where('email', $user->email)
            ->valid()
            ->with('workspace')
            ->first();

        if (! $invite) {
            return redirect('/dashboard')->with('error', 'Invitation not found or expired.');
        }

        $workspace = $invite->workspace;

        // Check if user is already a member
        if ($workspace->isMember($user)) {
            $invite->delete();

            return redirect("/{$workspace->slug}")->with('message', 'You are already a member of this workspace.');
        }

        // Add user to workspace
        $workspace->users()->attach($user->id, [
            'role' => $invite->role,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Set as default workspace if user doesn't have one
        if (! $user->default_workspace) {
            $user->update(['default_workspace' => $workspace->slug]);
        }

        // Delete the invite
        $invite->delete();

        // Set workspace context
        $this->workspaceAuthService->setWorkspaceContext($workspace);

        return redirect("/{$workspace->slug}")
            ->with('message', "Welcome to {$workspace->name}!");
    }

    /**
     * Get current user's workspace data for frontend
     */
    public function getWorkspaceData(Request $request): array
    {
        $user = $request->user();

        if (! $user) {
            return [
                'workspaces' => [],
                'currentWorkspace' => null,
            ];
        }

        return $this->workspaceAuthService->getWorkspaceDataForSharing($user);
    }
}
