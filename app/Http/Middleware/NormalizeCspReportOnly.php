<?php

namespace App\Http\Middleware;

use Closure;

class NormalizeCspReportOnly
{
    /**
     * Replace broken report-only CSP policies that block same-origin XHR/fetch.
     */
    public function handle($request, Closure $next)
    {
        $response = $next($request);

        if (!method_exists($response, 'headers')) {
            return $response;
        }

        $existing = (string) $response->headers->get('Content-Security-Policy-Report-Only', '');
        $existingLower = strtolower($existing);

        $shouldReplace = empty($existing)
            || str_contains($existingLower, "connect-src 'none'")
            || str_contains($existingLower, "default-src 'none'");

        if ($shouldReplace) {
            $response->headers->set(
                'Content-Security-Policy-Report-Only',
                implode(' ', [
                    "default-src 'self' https: data: blob:;",
                    "script-src 'self' 'unsafe-inline' 'unsafe-eval' https: blob:;",
                    "style-src 'self' 'unsafe-inline' https:;",
                    "img-src 'self' data: blob: https:;",
                    "font-src 'self' data: https:;",
                    "connect-src 'self' https: ws: wss:;",
                    "frame-src 'self' https:;",
                    "object-src 'none';",
                    "base-uri 'self';",
                    "form-action 'self';",
                ])
            );
        }

        return $response;
    }
}
