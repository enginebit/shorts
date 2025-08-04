<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class AuthController extends Controller
{
    /**
     * Register a new user.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $validated = $request->validated();

        // Check rate limiting
        $key = 'register:' . $request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages([
                'email' => ['Too many registration attempts. Please try again later.'],
            ]);
        }

        RateLimiter::hit($key, 300); // 5 minutes

        $user = User::create([
            'id' => 'user_' . Str::random(24), // Following dub-main CUID pattern
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password_hash' => Hash::make($validated['password']),
            'email_verified_at' => app()->environment('testing') ? now() : null,
            'subscribed' => true,
            'source' => $request->header('Referer') ? 'web' : 'api',
        ]);

        $token = $user->createToken('auth-token', ['*'], now()->addDays(7));

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'image' => $user->image,
                'email_verified' => $user->email_verified_at !== null,
            ],
            'token' => $token->plainTextToken,
            'expires_at' => $token->accessToken->expires_at,
        ], 201);
    }

    /**
     * Login user with email and password.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $validated = $request->validated();

        // Rate limiting following dub-main pattern (5 attempts per minute)
        $key = 'login-attempts:' . $validated['email'];
        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages([
                'email' => ['Too many login attempts. Please try again later.'],
            ]);
        }

        $user = User::where('email', $validated['email'])->first();

        if (!$user || !$user->password_hash) {
            RateLimiter::hit($key, 60);
            throw ValidationException::withMessages([
                'email' => ['Invalid credentials.'],
            ]);
        }

        // Check if account is locked (following dub-main pattern)
        if ($user->invalid_login_attempts >= 5) {
            throw ValidationException::withMessages([
                'email' => ['Account locked due to too many failed attempts.'],
            ]);
        }

        if (!Hash::check($validated['password'], $user->password_hash)) {
            // Increment invalid login attempts
            $user->increment('invalid_login_attempts');

            if ($user->invalid_login_attempts >= 5) {
                $user->update(['locked_at' => now()]);
            }

            RateLimiter::hit($key, 60);
            throw ValidationException::withMessages([
                'email' => ['Invalid credentials.'],
            ]);
        }

        if (!$user->email_verified_at) {
            throw ValidationException::withMessages([
                'email' => ['Email not verified.'],
            ]);
        }

        // Reset invalid login attempts on successful login
        $user->update(['invalid_login_attempts' => 0, 'locked_at' => null]);

        // Clear rate limiting on successful login
        RateLimiter::clear($key);

        $token = $user->createToken('auth-token', ['*'], now()->addDays(7));

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'image' => $user->image,
                'email_verified' => true,
                'default_workspace' => $user->default_workspace,
            ],
            'token' => $token->plainTextToken,
            'expires_at' => $token->accessToken->expires_at,
        ]);
    }

    /**
     * Logout user and revoke token.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully']);
    }

    /**
     * Get authenticated user.
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'image' => $user->image,
                'email_verified' => $user->email_verified_at !== null,
                'default_workspace' => $user->default_workspace,
                'default_partner_id' => $user->default_partner_id,
                'is_machine' => $user->is_machine,
                'subscribed' => $user->subscribed,
            ],
        ]);
    }

    /**
     * Refresh user token.
     */
    public function refresh(Request $request): JsonResponse
    {
        $user = $request->user();

        // Revoke current token
        $request->user()->currentAccessToken()->delete();

        // Create new token
        $token = $user->createToken('auth-token', ['*'], now()->addDays(7));

        return response()->json([
            'token' => $token->plainTextToken,
            'expires_at' => $token->accessToken->expires_at,
        ]);
    }
}
