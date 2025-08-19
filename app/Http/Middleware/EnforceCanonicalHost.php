<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnforceCanonicalHost
{
    /**
     * In local/dev, redirect any requests that use non-canonical hosts to the canonical dotted host.
     * - hyphenated: brachy-io -> brachy.io
     * - underscored: brachy_io -> brachy.io (and replace any '_' with '.')
     */
    public function handle(Request $request, Closure $next): Response
    {
        $host = $request->getHost(); // subdomains only, no port

        if (app()->environment('local')) {
            $newHost = $host;
            if (str_contains($newHost, 'brachy-io')) {
                $newHost = str_replace('brachy-io', 'brachy.io', $newHost);
            }
            if (str_contains($newHost, '_')) {
                $newHost = str_replace('_', '.', $newHost);
            }

            if ($newHost !== $host) {
                $scheme = $request->getScheme();
                $port = $request->getPort();
                $portPart = '';
                if (($scheme === 'http' && $port !== 80) || ($scheme === 'https' && $port !== 443)) {
                    $portPart = ':' . $port;
                }
                $target = $scheme . '://' . $newHost . $portPart . $request->getRequestUri();
                return redirect()->to($target, 302);
            }
        }

        return $next($request);
    }
}

