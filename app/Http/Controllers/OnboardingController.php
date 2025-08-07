<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Workspace\CreateWorkspaceRequest;
use App\Services\WorkspaceAuthService;
use App\Services\WorkspaceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * OnboardingController
 *
 * Based on dub-main onboarding patterns
 *
 * Handles new user onboarding including:
 * - First workspace creation
 * - Workspace invitation acceptance
 * - User setup and preferences
 *
 * Adaptations for Laravel + Inertia.js:
 * - Uses Inertia.js for page rendering
 * - Integrates with our workspace system
 * - Maintains dub-main onboarding flow
 */
final class OnboardingController extends Controller
{
    public function __construct(
        private readonly WorkspaceService $workspaceService,
        private readonly WorkspaceAuthService $workspaceAuthService
    ) {}

    /**
     * Show the onboarding page for new users
     */
    public function index(Request $request): Response|RedirectResponse
    {
        $user = $request->user();

        // Check if user already has workspaces
        if ($user->workspaces()->exists()) {
            $defaultWorkspace = $user->defaultWorkspace();
            if ($defaultWorkspace) {
                return redirect("/{$defaultWorkspace->slug}");
            }

            return redirect('/dashboard');
        }

        // Check for pending invitations
        $pendingInvites = \App\Models\WorkspaceInvite::where('email', $user->email)
            ->valid()
            ->with('workspace')
            ->get()
            ->map(function ($invite) {
                return [
                    'id' => $invite->id,
                    'workspace' => [
                        'name' => $invite->workspace->name,
                        'slug' => $invite->workspace->slug,
                        'logo' => $invite->workspace->logo,
                    ],
                    'role' => $invite->role,
                    'expiresAt' => $invite->expires_at,
                ];
            });

        return Inertia::render('onboarding/index', [
            'pendingInvites' => $pendingInvites,
            'canCreateFreeWorkspace' => $user->canCreateFreeWorkspace(),
        ]);
    }

    /**
     * Create the user's first workspace
     */
    public function createWorkspace(CreateWorkspaceRequest $request): RedirectResponse
    {
        $user = $request->user();

        // Check if user can create more free workspaces
        if (! $user->canCreateFreeWorkspace()) {
            return redirect()->back()->with('error', 'You have reached the limit for free workspaces.');
        }

        $workspace = $this->workspaceService->createWorkspace(
            $user,
            $request->validated()
        );

        // Set workspace context
        $this->workspaceAuthService->setWorkspaceContext($workspace);

        return redirect("/{$workspace->slug}")
            ->with('message', "Welcome to {$workspace->name}! Your workspace has been created successfully.");
    }

    /**
     * Accept a workspace invitation
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
            return redirect()->back()->with('error', 'Invitation not found or expired.');
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

        // Set as default workspace
        $user->update(['default_workspace' => $workspace->slug]);

        // Delete the invite
        $invite->delete();

        // Set workspace context
        $this->workspaceAuthService->setWorkspaceContext($workspace);

        return redirect("/{$workspace->slug}")
            ->with('message', "Welcome to {$workspace->name}!");
    }

    /**
     * Decline a workspace invitation
     */
    public function declineInvite(Request $request, string $inviteId): RedirectResponse
    {
        $user = $request->user();

        // Find and delete the invite
        $invite = \App\Models\WorkspaceInvite::where('id', $inviteId)
            ->where('email', $user->email)
            ->first();

        if ($invite) {
            $workspaceName = $invite->workspace->name;
            $invite->delete();

            return redirect()->back()
                ->with('message', "Invitation to {$workspaceName} has been declined.");
        }

        return redirect()->back()->with('error', 'Invitation not found.');
    }

    /**
     * Skip onboarding and go to dashboard
     */
    public function skip(Request $request): RedirectResponse
    {
        // For users who want to skip workspace creation
        // They can create workspaces later from the dashboard
        return redirect('/dashboard');
    }
}
