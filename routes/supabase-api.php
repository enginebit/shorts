<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Supabase Authentication API Routes
|--------------------------------------------------------------------------
|
| These routes provide Supabase JWT authentication endpoints for testing
| and demonstrating the authentication system integration.
|
*/

// Public health check - no authentication required
Route::get('/supabase/health', function () {
    return response()->json([
        'status' => 'ok',
        'service' => 'Supabase Auth API',
        'timestamp' => now()->toISOString(),
        'version' => '1.0.0',
        'environment' => app()->environment(),
    ]);
});

// Protected routes - require Supabase JWT authentication
Route::middleware(['supabase.auth'])->group(function () {

    // Get authenticated user information
    Route::get('/supabase/user', function (Request $request) {
        $user = $request->user();
        $supabaseUser = $request->get('supabase_user');
        $workspaceData = $request->get('workspace_data', []);

        return response()->json([
            'success' => true,
            'data' => [
                'laravel_user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'supabase_id' => $user->supabase_id,
                    'default_workspace' => $user->default_workspace,
                ],
                'supabase_user' => $supabaseUser,
                'authentication' => [
                    'method' => 'Supabase JWT',
                    'role' => $supabaseUser['role'] ?? 'unknown',
                    'aal' => $supabaseUser['aal'] ?? 'aal1',
                    'mfa_enabled' => $user->hasMfaEnabled(),
                ],
                'workspace_context' => [
                    'total_workspaces' => is_array($workspaceData) && isset($workspaceData['workspaces'])
                        ? $workspaceData['workspaces']->count()
                        : 0,
                    'current_workspace' => $workspaceData['currentWorkspace'] ?? null,
                ],
            ],
        ]);
    });

    // Get JWT token information
    Route::get('/supabase/token/info', function (Request $request) {
        $jwtPayload = $request->get('jwt_payload');
        $supabaseToken = $request->get('supabase_token');

        return response()->json([
            'success' => true,
            'data' => [
                'token_info' => [
                    'algorithm' => 'ES256',
                    'issuer' => $jwtPayload['iss'] ?? 'unknown',
                    'audience' => $jwtPayload['aud'] ?? 'unknown',
                    'subject' => $jwtPayload['sub'] ?? 'unknown',
                    'role' => $jwtPayload['role'] ?? 'unknown',
                    'issued_at' => isset($jwtPayload['iat']) ? date('Y-m-d H:i:s', $jwtPayload['iat']) : 'unknown',
                    'expires_at' => isset($jwtPayload['exp']) ? date('Y-m-d H:i:s', $jwtPayload['exp']) : 'unknown',
                    'time_to_expiry' => isset($jwtPayload['exp']) ? ($jwtPayload['exp'] - time()).' seconds' : 'unknown',
                ],
                'token_preview' => $supabaseToken ? substr($supabaseToken, 0, 50).'...' : 'no token',
                'validation_status' => 'Valid JWT token',
            ],
        ]);
    });
});
