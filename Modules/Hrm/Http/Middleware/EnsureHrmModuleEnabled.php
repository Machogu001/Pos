<?php

namespace Modules\Hrm\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureHrmModuleEnabled
{
    public function handle(Request $request, Closure $next)
    {
        $enabled = session('business.enabled_modules', []);

        if (is_string($enabled)) {
            $decoded = json_decode($enabled, true);
            $enabled = is_array($decoded) ? $decoded : [];
        }

        if (!is_array($enabled)) {
            $enabled = [];
        }

        $isEnabled = in_array('hrm', $enabled, true);

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
