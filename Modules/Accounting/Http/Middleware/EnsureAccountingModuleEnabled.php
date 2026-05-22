<?php

namespace Modules\Accounting\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureAccountingModuleEnabled
{
    public function handle(Request $request, Closure $next)
    {
        // Keep install/update routes reachable for recovery and setup.
        if ($request->is('accounting/install') || $request->is('accounting/install/*')) {
            return $next($request);
        }

        $enabled = session('business.enabled_modules', []);

        if (is_string($enabled)) {
            $decoded = json_decode($enabled, true);
            $enabled = is_array($decoded) ? $decoded : [];
        }

        if (! is_array($enabled)) {
            $enabled = [];
        }

        $isEnabled = in_array('accounting_module', $enabled, true) || in_array('accounting', $enabled, true);

        if (! $isEnabled) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Accounting module not enabled for this business.',
                ], 403);
            }

            return redirect()->to('/home')->with('status', [
                'success' => 0,
                'msg' => 'Accounting module not enabled for this business.',
            ]);
        }

        return $next($request);
    }
}
