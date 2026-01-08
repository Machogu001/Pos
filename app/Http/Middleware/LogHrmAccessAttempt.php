<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Utils\ModuleUtil;

class LogHrmAccessAttempt
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();

        try {
            $roles = method_exists($user, 'getRoleNames') ? $user->getRoleNames()->toArray() : [];
            $permissions = method_exists($user, 'getAllPermissions') ? $user->getAllPermissions()->pluck('name')->toArray() : [];

            $business_id = session('user.business_id');
            $enabled_modules = (array) session('business.enabled_modules', []);
            $hrm_enabled = in_array('hrm', $enabled_modules);

            Log::info('HRM access attempt', [
                'path' => $request->path(),
                'user_id' => $user ? $user->id : null,
                'roles' => $roles,
                'permissions' => $permissions,
                'business_id' => $business_id,
                'enabled_modules' => $enabled_modules,
                'hrm_enabled' => $hrm_enabled,
            ]);
        } catch (\Throwable $e) {
            Log::warning('LogHrmAccessAttempt: logging failed', [
                'error' => $e->getMessage(),
            ]);
        }

        return $next($request);
    }
}
