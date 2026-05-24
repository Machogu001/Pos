<?php

namespace App\Http\Controllers;

use App\Support\AccountingModuleGate;
use Illuminate\Http\Request;

class AccountingDashboardStubController extends Controller
{
    public function __invoke(Request $request)
    {
        $isEnabled = AccountingModuleGate::isEnabledForBusiness(
            (int) session('user.business_id'),
            session('business.enabled_modules', [])
        );

        if (! $isEnabled) {
            return redirect('/home')->with('status', [
                'success' => 0,
                'msg' => __('accounting::general.accounting_module_not_enabled_for_business'),
            ]);
        }

        return app(\Modules\Accounting\Http\Controllers\DashboardController::class)->index();
    }
}
