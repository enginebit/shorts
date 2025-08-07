<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\EmailService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

final class OAuthController extends Controller
{
    public function __construct(
        private readonly EmailService $emailService
    ) {}

    /**
     * Redirect to OAuth provider.
     */
    public function redirect(string $provider): RedirectResponse
    {
        $this->validateProvider($provider);

        return Socialite::driver($provider)->redirect();
    }

    /**
     * Handle OAuth callback.
     */
    public function callback(string $provider): JsonResponse
    {
        $this->validateProvider($provider);

        try {
            $socialUser = Socialite::driver($provider)->user();
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'OAuth authentication failed',
                'message' => 'Unable to authenticate with '.$provider,
            ], 400);
        }

        if (! $socialUser->getEmail()) {
            return response()->json([
                'error' => 'Email required',
                'message' => 'Email address is required for authentication',
            ], 400);
        }

        // Find or create user
        $user = User::where('email', $socialUser->getEmail())->first();

        if (! $user) {
            // Create new user following dub-main patterns
            $user = User::create([
                'id' => 'user_'.Str::random(24),
                'name' => $socialUser->getName() ?? $socialUser->getNickname(),
                'email' => $socialUser->getEmail(),
                'image' => $this->storeAvatar($socialUser->getAvatar(), 'user_'.Str::random(24)),
                'email_verified_at' => now(), // OAuth emails are considered verified
                'subscribed' => true,
                'source' => $provider,
            ]);

            // Send welcome email for new OAuth users (queued for better performance)
            try {
                $this->emailService->sendWelcomeEmail($user);
            } catch (\Exception $e) {
                // Log error but don't fail OAuth registration
                \Illuminate\Support\Facades\Log::warning('Failed to send welcome email during OAuth registration', [
                    'user_id' => $user->id,
                    'provider' => $provider,
                    'error' => $e->getMessage(),
                ]);
            }
        } else {
            // Update existing user with OAuth data if needed
            $updates = [];

            if (! $user->name && $socialUser->getName()) {
                $updates['name'] = $socialUser->getName();
            }

            if (! $user->image && $socialUser->getAvatar()) {
                $updates['image'] = $this->storeAvatar($socialUser->getAvatar(), $user->id);
            }

            if (! $user->email_verified_at) {
                $updates['email_verified_at'] = now();
            }

            if (! empty($updates)) {
                $user->update($updates);
            }
        }

        // Create token
        $token = $user->createToken('oauth-token', ['*'], now()->addDays(7));

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
     * Validate OAuth provider.
     */
    private function validateProvider(string $provider): void
    {
        $allowedProviders = ['google', 'github'];

        if (! in_array($provider, $allowedProviders)) {
            abort(400, 'Invalid OAuth provider');
        }
    }

    /**
     * Store user avatar from OAuth provider.
     */
    private function storeAvatar(?string $avatarUrl, string $userId): ?string
    {
        if (! $avatarUrl) {
            return null;
        }

        try {
            $contents = file_get_contents($avatarUrl);
            if ($contents === false) {
                return null;
            }

            $filename = "avatars/{$userId}/".Str::random(40).'.jpg';
            Storage::disk('public')->put($filename, $contents);

            // Generate proper URL for both local and production environments
            return config('app.url').'/storage/'.$filename;
        } catch (\Exception $e) {
            // Log error but don't fail authentication
            logger()->error('Failed to store OAuth avatar', [
                'user_id' => $userId,
                'avatar_url' => $avatarUrl,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
