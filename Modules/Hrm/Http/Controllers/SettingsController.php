<?php

namespace Modules\Hrm\Http\Controllers;

use App\Business;
use App\AdminSetting;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class SettingsController extends Controller
{
    protected function getAuthUser($request)
    {
        return $request->user('api') ?? $request->user() ?? auth()->user();
    }

    public function editDefaultLeave(Request $request)
    {
        $this->authorizeForUser($this->getAuthUser($request), 'update', AdminSetting::class);

        $admin = null;
        if (Schema::hasTable('admin_settings')) {
            $admin = AdminSetting::first();
        }

        // If API requested JSON, return current value
        if ($request->wantsJson() || $request->expectsJson()) {
            return response()->json(['default_annual_leave' => $admin->default_annual_leave ?? config('hrm.default_annual_leave', 21)]);
        }

        return view('hrm::settings.leave', ['default' => $admin->default_annual_leave ?? config('hrm.default_annual_leave', 21)]);
    }

    public function updateDefaultLeave(Request $request)
    {
        $this->authorizeForUser($this->getAuthUser($request), 'update', AdminSetting::class);

        $this->validate($request, [
            'default_annual_leave' => 'required|integer|min:0',
        ]);

        if (! Schema::hasTable('admin_settings')) {
            return redirect()->back()->withErrors(['error' => 'Admin settings table missing']);
        }

        $admin = AdminSetting::first();
        if (! $admin) {
            $admin = new AdminSetting();
        }

        $admin->default_annual_leave = intval($request->input('default_annual_leave'));
        $admin->save();

        return redirect()->back()->with('success', 'Updated successfully');
    }

    /**
     * Show form to enable/disable core modules via HRM.
     */
    public function editModules(Request $request)
    {
        $user = $this->getAuthUser($request);
        $this->authorizeForUser($user, 'business_settings.access');

        $business_id = $user->business_id;
        $business = Business::where('id', $business_id)->firstOrFail();

        $enabled = $business->enabled_modules ?? [];

        // Supported toggle keys mapped to friendly labels
        $moduleOptions = [
            'purchases' => 'Purchases',
            'add_sale' => 'Add Sale',
            'pos_sale' => 'POS',
            'stock_transfers' => 'Stock Transfers',
            'stock_adjustment' => 'Stock Adjustment',
            'expenses' => 'Expenses',
            'account' => 'Account',
            'tables' => 'Tables',
            'modifiers' => 'Modifiers',
            'service_staff' => 'Service staff',
            'booking' => 'Enable Bookings',
            'kitchen' => 'Kitchen (For restaurants)',
            'subscription' => 'Enable Subscription',
            'types_of_service' => 'Types of service',
        ];

        if ($request->wantsJson() || $request->expectsJson()) {
            return response()->json([
                'enabled_modules' => $enabled,
                'available' => $moduleOptions,
            ]);
        }

        return view('hrm::settings.modules', [
            'enabled' => $enabled,
            'moduleOptions' => $moduleOptions,
        ]);
    }

    /**
     * Update enabled/disabled modules for the current business.
     */
    public function updateModules(Request $request)
    {
        $user = $this->getAuthUser($request);
        $this->authorizeForUser($user, 'business_settings.access');

        $business = Business::where('id', $user->business_id)->firstOrFail();

        // The request will contain checkboxes named modules[] with values of keys
        $selected = (array) $request->input('modules', []);

        // Whitelist keys we support toggling here to avoid accidental writes
        $allowedKeys = [
            'purchases', 'add_sale', 'pos_sale', 'stock_transfers', 'stock_adjustment', 'expenses',
            'account', 'tables', 'modifiers', 'service_staff', 'booking', 'kitchen', 'subscription', 'types_of_service',
        ];
        $selected = array_values(array_intersect($selected, $allowedKeys));

        $business->enabled_modules = $selected;
        $business->save();

        // refresh session business to reflect new module set
        $request->session()->put('business', $business);

        if ($request->wantsJson() || $request->expectsJson()) {
            return response()->json(['success' => true, 'enabled_modules' => $selected]);
        }

        return redirect()->route('hrm.settings.modules.edit')->with('success', 'Updated successfully');
    }
}
