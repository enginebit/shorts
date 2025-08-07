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
 * AuthenticatedSessionController
 *
 * Enhanced with workspace-aware authentication patterns from dub-main
 *
 * Maintains backward compatibility while adding workspace context handling
 * for seamless user experience with workspace redirection after login.
 */
final class AuthenticatedSessionController extends Controller
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
}
