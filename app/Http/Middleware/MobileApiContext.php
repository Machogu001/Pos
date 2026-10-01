<?php

namespace App\Http\Middleware;

use App\Http\Middleware\SetSessionData;
use App\Http\Middleware\Timezone;
use App\Services\MobileLoginService;
use Closure;
use Illuminate\Support\Facades\Auth;

class MobileApiContext
{
    public function __construct(protected MobileLoginService $loginService)
    {
    }

    public function handle($request, Closure $next)
    {
        Auth::shouldUse('api');
        $user = $request->user('api');

        if (! $user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.', 'code' => 'unauthenticated'], 401);
        }

        if ($message = $this->loginService->restrictionMessage($user)) {
            return response()->json(['success' => false, 'message' => $message, 'code' => 'unauthenticated'], 401);
        }

        $this->loginService->ensureRequestSession($request);

        return app(SetSessionData::class)->handle($request, function ($request) use ($next) {
            return app(Timezone::class)->handle($request, $next);
        });
    }
}
