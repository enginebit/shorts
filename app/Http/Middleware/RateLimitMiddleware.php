<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

final class RateLimitMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $limiter = 'api'): Response
    {
        $executed = RateLimiter::attempt(
            $limiter,
            $this->resolveMaxAttempts($request, $limiter),
            fn () => $next($request)
        );

        if (! $executed) {
            return $this->buildResponse($request, $limiter);
        }

        return $executed;
    }

    /**
     * Resolve the number of attempts if the user is authenticated or not.
     */
    protected function resolveMaxAttempts(Request $request, string $limiter): int
    {
        return match ($limiter) {
            'auth' => 5,
            'links' => $request->user() ? 100 : 10,
            'analytics' => $request->user() ? 200 : 30,
            'workspaces' => $request->user() ? 50 : 10,
            'emails' => 5,
            'password-reset' => 3,
            'redirects' => 1000,
            default => $request->user() ? 1000 : 60,
        };
    }

    /**
     * Create a 'too many attempts' response.
     */
    protected function buildResponse(Request $request, string $limiter): Response
    {
        $retryAfter = RateLimiter::availableIn($limiter);

        $message = match ($limiter) {
            'auth' => "Too many authentication attempts. Please try again in {$retryAfter} seconds.",
            'links' => "Too many link creation attempts. Please try again in {$retryAfter} seconds.",
            'analytics' => "Too many analytics requests. Please try again in {$retryAfter} seconds.",
            'workspaces' => "Too many workspace operations. Please try again in {$retryAfter} seconds.",
            'emails' => "Too many email requests. Please try again in {$retryAfter} seconds.",
            'password-reset' => "Too many password reset attempts. Please try again in {$retryAfter} seconds.",
            'redirects' => "Too many redirect requests. Please try again in {$retryAfter} seconds.",
            default => "Too many requests. Please try again in {$retryAfter} seconds.",
        };

        if ($request->expectsJson()) {
            return response()->json([
                'error' => 'Rate limit exceeded',
                'message' => $message,
                'retry_after' => $retryAfter,
            ], 429);
        }

        return response()->view('errors.429', [
            'message' => $message,
            'retryAfter' => $retryAfter,
        ], 429);
    }
}
