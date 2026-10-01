<?php

namespace Modules\Hrm\Http\Middleware;

use App\Business;
use Closure;
use Illuminate\Http\Request;

class EnsureHrmModuleEnabled
{
    protected function normalizeModules($modules): array
    {
        if (is_string($modules)) {
            $decoded = json_decode($modules, true);
            $modules = is_array($decoded) ? $decoded : [];
        }

        return is_array($modules) ? $modules : [];
    }

    public function handle(Request $request, Closure $next)
    {
        $sessionModules = $this->normalizeModules(session('business.enabled_modules', []));
        $businessModules = $this->normalizeModules(optional(optional($request->user())->business)->enabled_modules ?? []);

        $enabled = array_values(array_unique(array_merge($sessionModules, $businessModules)));

        if (empty($enabled) && ! empty(session('business.id'))) {
            $dbModules = Business::where('id', session('business.id'))->value('enabled_modules');
            $enabled = $this->normalizeModules($dbModules);
        }

        $isEnabled = in_array('hrm', $enabled, true) || in_array('Hrm', $enabled, true);

        if (!$isEnabled) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'HRM module not enabled for this business.',
                ], 403);
            }

            return redirect()->to('/home')
                ->with('status', [
                    'success' => 0,
                    'msg' => 'HRM module not enabled for this business.',
                ]);
        }

        return $next($request);
    }
}
