<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

final class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * Only exclude external webhooks and API endpoints that don't use session auth.
     * Do NOT exclude authentication routes as they need CSRF protection.
     */
    protected $except = [
        // Stripe webhooks
        'stripe/*',

        // Supabase webhooks (if any)
        'webhooks/supabase/*',

        // API routes (these should use Sanctum token auth instead)
        'api/*',
    ];

    /**
     * Determine if the HTTP request uses a 'read' verb.
     *
     * Override to ensure proper CSRF validation for all mutating requests.
     */
    protected function isReading($request): bool
    {
        return in_array($request->method(), ['HEAD', 'GET', 'OPTIONS']);
    }

    /**
     * Determine if the request has a URI that should pass through CSRF verification.
     *
     * Override to provide more explicit control over CSRF exceptions.
     */
    protected function inExceptArray($request): bool
    {
        foreach ($this->except as $except) {
            if ($except !== '/') {
                $except = trim($except, '/');
            }

            if ($request->fullUrlIs($except) || $request->is($except)) {
                return true;
            }
        }

        return false;
    }
}
