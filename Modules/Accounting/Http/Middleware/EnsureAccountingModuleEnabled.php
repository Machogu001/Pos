<?php

namespace Modules\Accounting\Http\Middleware;

use App\Support\AccountingModuleGate;
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

        $isEnabled = AccountingModuleGate::isEnabledForBusiness(
            (int) $request->session()->get('user.business_id'),
            $request->session()->get('business.enabled_modules', [])
        );

        if (! $isEnabled) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => __('accounting::general.accounting_module_not_enabled_for_business'),
                ], 403);
            }

            return redirect()->to('/home')->with('status', [
                'success' => 0,
                'msg' => __('accounting::general.accounting_module_not_enabled_for_business'),
            ]);
        }

        return $next($request);
    }
}
