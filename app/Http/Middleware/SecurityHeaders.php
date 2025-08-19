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
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');
        }

        // Content Security Policy - Allow external fonts and Vite dev server; allow all first-party subdomains
        $base = (string) (config('domains.base') ?? 'branchy.io'); // e.g., app.brachy.io
        $wild = '*.' . $base; // e.g., *.app.brachy.io

        $viteHosts = [
            'localhost:5174', '127.0.0.1:5174', $wild . ':5174',
        ];

        $csp = [
            // Allow our first-party subdomains over http/https in dev
            "default-src 'self' http://{$wild} https://{$wild}",
            // Allow Vite dev server scripts from localhost/127.0.0.1 and any subdomain on :5174
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' http://localhost:5174 http://127.0.0.1:5174 http://{$wild}:5174",
            // Allow inline styles and Vite style updates
            "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://fonts.bunny.net http://localhost:5174 http://127.0.0.1:5174 http://{$wild}:5174",
            "img-src 'self' data: https:",
            "font-src 'self' data: https://fonts.googleapis.com https://fonts.gstatic.com https://fonts.bunny.net",
            // Allow HMR websocket + XHR to localhost/127.0.0.1 and any subdomain on :5174
            "connect-src 'self' http://localhost:5174 http://127.0.0.1:5174 http://{$wild}:5174 ws://localhost:5174 ws://127.0.0.1:5174 ws://{$wild}:5174",
            "frame-ancestors 'none'",
        ];

        // Join and collapse spaces
        $response->headers->set('Content-Security-Policy', preg_replace('/\s+/', ' ', implode('; ', $csp)));

        // Prevent caching of pages with CSRF tokens to avoid stale token issues
        if ($request->isMethod('GET') && $request->route() &&
            in_array($request->route()->getName(), ['login', 'register', 'onboarding.index', 'onboarding.workspace'])) {
            $response->headers->set('Cache-Control', 'no-cache, no-store, must-revalidate');
            $response->headers->set('Pragma', 'no-cache');
            $response->headers->set('Expires', '0');
        }

        return $response;
    }
}
