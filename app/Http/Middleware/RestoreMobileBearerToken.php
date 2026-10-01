<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Some Apache/CGI/FPM hosts strip the Authorization header before it reaches PHP.
 * The mobile app also sends the bearer token as X-Authorization; restore it so
 * Passport can authenticate the request.
 */
class RestoreMobileBearerToken
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->is('api/mobile/*') && ! $request->headers->has('Authorization')) {
            $fallback = $request->headers->get('X-Authorization')
                ?: $request->server('REDIRECT_HTTP_AUTHORIZATION');

            if (! empty($fallback)) {
                $request->headers->set('Authorization', $fallback);
            }
        }

        return $next($request);
    }
}
