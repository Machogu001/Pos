<?php

namespace App\Http\Middleware;

use App\Http\Middleware\SetSessionData;
use App\Http\Middleware\Timezone;
use App\Services\MobileLoginService;
use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

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

        config(['session.driver' => 'array']);
        $manager = app('session');
        if (method_exists($manager, 'forgetDrivers')) {
            $manager->forgetDrivers();
        }
        $session = $manager->driver('array');
        $session->start();
        $request->setLaravelSession($session);
        Session::swap($session);

        return app(SetSessionData::class)->handle($request, function ($request) use ($next) {
            return app(Timezone::class)->handle($request, $next);
        });
    }
}
