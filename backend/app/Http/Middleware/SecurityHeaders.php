<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class SecurityHeaders
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Security headers following dub-main patterns
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        // HSTS for production
        if (app()->environment('production')) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        // Content Security Policy - Allow external fonts and Vite dev server
        $csp = [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' http://127.0.0.1:5173 http://localhost:5173 http://127.0.0.1:5174 http://localhost:5174",
            "style-src 'self' 'unsafe-inline' https://fonts.bunny.net http://127.0.0.1:5173 http://localhost:5173 http://127.0.0.1:5174 http://localhost:5174",
            "img-src 'self' data: https:",
            "font-src 'self' data: https://fonts.bunny.net",
            "connect-src 'self' http://127.0.0.1:5173 http://localhost:5173 http://127.0.0.1:5174 http://localhost:5174 ws://127.0.0.1:5173 ws://localhost:5173 ws://127.0.0.1:5174 ws://localhost:5174",
            "frame-ancestors 'none'",
        ];

        $response->headers->set('Content-Security-Policy', implode('; ', $csp));

        return $response;
    }
}
