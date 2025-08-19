<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

final class DebugCsrfMiddleware
{
    /**
     * Local-only middleware to log CSRF/session-related request details
     * for key endpoints to diagnose 419 issues.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (app()->environment('local')) {
            $uri = $request->getRequestUri();
            $method = $request->getMethod();

            // Only log for selected endpoints / methods
            $shouldLog = $method !== 'GET' && (
                str_starts_with($uri, '/login') ||
                str_starts_with($uri, '/logout') ||
                str_starts_with($uri, '/onboarding')
            );

            if ($shouldLog) {
                $headers = [
                    'host' => $request->getHost(),
                    'x-csrf-token' => $request->headers->get('X-CSRF-TOKEN'),
                    'x-xsrf-token' => $request->headers->get('X-XSRF-TOKEN'),
                    'cookie' => substr((string) $request->headers->get('Cookie'), 0, 512),
                    'referer' => $request->headers->get('Referer'),
                    'origin' => $request->headers->get('Origin'),
                    'user-agent' => $request->userAgent(),
                ];
                Log::debug('[DebugCsrf] Incoming request', [
                    'method' => $method,
                    'uri' => $uri,
                    'headers' => $headers,
                    'time' => now()->toIso8601String(),
                ]);
            }
        }

        return $next($request);
    }
}

