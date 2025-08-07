<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

final class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to your application's "home" route.
     *
     * Typically, users are redirected here after authentication.
     */
    public const HOME = '/dashboard';

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     */
    public function boot(): void
    {
        $this->configureRateLimiting();

        $this->routes(function () {
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        });
    }

    /**
     * Configure the rate limiters for the application.
     */
    protected function configureRateLimiting(): void
    {
        // General API rate limiting - higher limits for authenticated users
        RateLimiter::for('api', function (Request $request) {
            if ($request->user()) {
                // Authenticated users: 1000 requests per minute
                return Limit::perMinute(1000)->by($request->user()->id);
            }

            // Unauthenticated users: 60 requests per minute per IP
            return Limit::perMinute(60)->by($request->ip());
        });

        // Authentication endpoints - stricter limits to prevent brute force
        RateLimiter::for('auth', function (Request $request) {
            // 5 attempts per minute per IP for login/register
            return [
                Limit::perMinute(5)->by($request->ip()),
                // Additional limit: 20 attempts per hour per IP
                Limit::perHour(20)->by($request->ip()),
            ];
        });

        // Link creation - prevent spam link creation
        RateLimiter::for('links', function (Request $request) {
            if ($request->user()) {
                // Authenticated users: 100 links per minute
                return Limit::perMinute(100)->by($request->user()->id);
            }

            // Unauthenticated users: 10 links per minute per IP
            return Limit::perMinute(10)->by($request->ip());
        });

        // Link access - high limits for link redirects
        RateLimiter::for('redirects', function (Request $request) {
            // 1000 redirects per minute per IP (for high-traffic links)
            return Limit::perMinute(1000)->by($request->ip());
        });

        // Analytics endpoints - moderate limits
        RateLimiter::for('analytics', function (Request $request) {
            if ($request->user()) {
                // Authenticated users: 200 requests per minute
                return Limit::perMinute(200)->by($request->user()->id);
            }

            // Unauthenticated users: 30 requests per minute per IP
            return Limit::perMinute(30)->by($request->ip());
        });

        // Workspace operations - moderate limits
        RateLimiter::for('workspaces', function (Request $request) {
            if ($request->user()) {
                // 50 workspace operations per minute per user
                return Limit::perMinute(50)->by($request->user()->id);
            }

            return Limit::perMinute(10)->by($request->ip());
        });

        // Email operations - strict limits to prevent spam
        RateLimiter::for('emails', function (Request $request) {
            return [
                // 5 emails per minute per IP
                Limit::perMinute(5)->by($request->ip()),
                // 50 emails per hour per IP
                Limit::perHour(50)->by($request->ip()),
            ];
        });

        // Password reset - very strict limits
        RateLimiter::for('password-reset', function (Request $request) {
            return [
                // 3 password reset attempts per minute per IP
                Limit::perMinute(3)->by($request->ip()),
                // 10 password reset attempts per hour per IP
                Limit::perHour(10)->by($request->ip()),
            ];
        });
    }
}
