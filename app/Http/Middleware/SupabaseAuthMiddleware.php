<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\SupabaseAuthService;
use App\Services\WorkspaceAuthService;
use Closure;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

final class SupabaseAuthMiddleware
{
    private SupabaseAuthService $supabaseAuth;

    private WorkspaceAuthService $workspaceAuth;

    public function __construct(
        SupabaseAuthService $supabaseAuth,
        WorkspaceAuthService $workspaceAuth
    ) {
        $this->supabaseAuth = $supabaseAuth;
        $this->workspaceAuth = $workspaceAuth;
    }

    /**
     * Handle an incoming request with Supabase JWT authentication
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Extract JWT token from Authorization header
        $token = $this->extractTokenFromRequest($request);

        if (! $token) {
            Log::info('Supabase Auth: No token provided', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'url' => $request->fullUrl(),
            ]);

            return $this->unauthorizedResponse('Missing authentication token');
        }

        // Validate the JWT token
        $payload = $this->supabaseAuth->validateToken($token);

        if (! $payload) {
            Log::warning('Supabase Auth: Invalid token provided', [
                'ip' => $request->ip(),
                'token_preview' => substr($token, 0, 50).'...',
            ]);

            return $this->unauthorizedResponse('Invalid authentication token');
        }

        // Extract user information from JWT payload
        $supabaseUser = $this->supabaseAuth->extractUserFromPayload($payload);

        // Find or create corresponding Laravel user
        $user = $this->findOrCreateUser($supabaseUser);

        if (! $user) {
            Log::error('Supabase Auth: Could not find or create user', [
                'supabase_id' => $supabaseUser['id'],
                'email' => $supabaseUser['email'],
            ]);

            return $this->unauthorizedResponse('User not found or could not be created');
        }

        // Set authenticated user in Laravel
        auth()->setUser($user);

        // Add Supabase user data to request for access in controllers
        $request->merge([
            'supabase_user' => $supabaseUser,
            'supabase_token' => $token,
            'jwt_payload' => $payload,
        ]);

        // Handle workspace-aware authentication if enabled
        if (config('supabase.auth.workspace_aware', true)) {
            $this->handleWorkspaceContext($request, $user);
        }

        Log::info('Supabase Auth: User authenticated successfully', [
            'user_id' => $user->id,
            'supabase_id' => $supabaseUser['id'],
            'email' => $user->email,
            'role' => $supabaseUser['role'],
        ]);

        return $next($request);
    }

    /**
     * Extract JWT token from request headers, query params, or cookies
     */
    private function extractTokenFromRequest(Request $request): ?string
    {
        // Check Authorization header (Bearer token) - Primary method
        $authHeader = $request->header('Authorization');
        if ($authHeader && str_starts_with($authHeader, 'Bearer ')) {
            return substr($authHeader, 7);
        }

        // Check for token in query parameters (for WebSocket connections or special cases)
        if ($request->has('token')) {
            return $request->get('token');
        }

        // Check for token in cookies (if using cookie-based auth)
        if ($request->hasCookie('supabase_token')) {
            return $request->cookie('supabase_token');
        }

        // Check custom header (some clients use this)
        if ($request->hasHeader('X-Supabase-Token')) {
            return $request->header('X-Supabase-Token');
        }

        return null;
    }

    /**
     * Find or create Laravel user based on Supabase user data
     */
    private function findOrCreateUser(array $supabaseUser): ?User
    {
        try {
            // First, try to find user by Supabase ID
            $user = User::where('supabase_id', $supabaseUser['id'])->first();

            // If not found by Supabase ID, try by email
            if (! $user && $supabaseUser['email']) {
                $user = User::where('email', $supabaseUser['email'])->first();

                // If found by email but no Supabase ID, update it
                if ($user && ! $user->supabase_id) {
                    $user->update(['supabase_id' => $supabaseUser['id']]);
                    Log::info('Updated existing user with Supabase ID', [
                        'user_id' => $user->id,
                        'supabase_id' => $supabaseUser['id'],
                    ]);
                }
            }

            // Create new user if not found and auto-creation is enabled
            if (! $user && config('supabase.auth.auto_create_users', true)) {
                $user = $this->createUserFromSupabase($supabaseUser);
            }

            // Update user metadata if sync is enabled
            if ($user && config('supabase.auth.sync_user_metadata', true)) {
                $user->updateFromSupabase($supabaseUser);
            }

            return $user;

        } catch (Exception $e) {
            Log::error('Failed to find or create user from Supabase data', [
                'supabase_user' => $supabaseUser,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return null;
        }
    }

    /**
     * Create a new Laravel user from Supabase user data
     */
    private function createUserFromSupabase(array $supabaseUser): ?User
    {
        if (! $supabaseUser['email']) {
            Log::warning('Cannot create user without email', [
                'supabase_id' => $supabaseUser['id'],
            ]);

            return null;
        }

        try {
            $userData = [
                'supabase_id' => $supabaseUser['id'],
                'email' => $supabaseUser['email'],
                'email_verified_at' => now(), // Supabase handles email verification
                'supabase_metadata' => [
                    'aal' => $supabaseUser['aal'],
                    'session_id' => $supabaseUser['session_id'],
                    'is_anonymous' => $supabaseUser['is_anonymous'],
                    'app_metadata' => $supabaseUser['app_metadata'],
                    'user_metadata' => $supabaseUser['user_metadata'],
                    'amr' => $supabaseUser['amr'],
                    'created_at' => now()->toISOString(),
                ],
            ];

            // Extract name from user metadata
            $userMetadata = $supabaseUser['user_metadata'];
            if (! empty($userMetadata['name'])) {
                $userData['name'] = $userMetadata['name'];
            } elseif (! empty($userMetadata['full_name'])) {
                $userData['name'] = $userMetadata['full_name'];
            } elseif (! empty($userMetadata['display_name'])) {
                $userData['name'] = $userMetadata['display_name'];
            } else {
                // Fallback to email username
                $userData['name'] = explode('@', $supabaseUser['email'])[0];
            }

            $user = User::create($userData);

            Log::info('Created new user from Supabase authentication', [
                'user_id' => $user->id,
                'supabase_id' => $supabaseUser['id'],
                'email' => $supabaseUser['email'],
                'name' => $userData['name'],
            ]);

            return $user;

        } catch (Exception $e) {
            Log::error('Failed to create user from Supabase data', [
                'supabase_user' => $supabaseUser,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Handle workspace context for authenticated user
     */
    private function handleWorkspaceContext(Request $request, User $user): void
    {
        try {
            // Use existing workspace authentication service
            $workspaceData = $this->workspaceAuth->getWorkspaceDataForSharing($user);

            // Add workspace data to request for controllers
            $request->merge([
                'workspace_data' => $workspaceData,
                'current_workspace' => $workspaceData['currentWorkspace'] ?? null,
            ]);

            // Set workspace context in session for web routes
            if ($workspaceData['currentWorkspace']) {
                session(['current_workspace' => $workspaceData['currentWorkspace']]);
            }

            Log::debug('Workspace context set for user', [
                'user_id' => $user->id,
                'workspace_count' => $workspaceData['workspaces']->count(),
                'current_workspace' => $workspaceData['currentWorkspace']['slug'] ?? null,
            ]);

        } catch (Exception $e) {
            Log::error('Failed to set workspace context', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            // Don't fail authentication if workspace context fails
            // Just log the error and continue
        }
    }

    /**
     * Return appropriate unauthorized response
     */
    private function unauthorizedResponse(string $message): Response
    {
        // Always return JSON for API routes or AJAX requests
        if (request()->expectsJson() || request()->is('api/*') || request()->ajax()) {
            return response()->json([
                'error' => 'Unauthorized',
                'message' => $message,
                'code' => 'SUPABASE_AUTH_FAILED',
            ], 401);
        }

        // For web requests, redirect to login with error message
        return redirect()->route('login')->with('error', $message);
    }
}
