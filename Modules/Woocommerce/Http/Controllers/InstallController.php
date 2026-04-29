<?php

namespace Modules\Woocommerce\Http\Controllers;

use App\Http\Controllers\Controller;
use App\System;
use Illuminate\Support\Facades\Log;

class InstallController extends Controller
{
    public function index()
    {
        if (! auth()->user()->can('manage_modules')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            System::addProperty('woocommerce_version', '1.0.0');
            $output = ['success' => true, 'msg' => __('lang_v1.success')];
        } catch (\Exception $e) {
            Log::error('Woocommerce module install failed: ' . $e->getMessage());
            $output = ['success' => false, 'msg' => 'Woocommerce installation failed. Check server logs.'];
        }

        return redirect()
            ->action([\App\Http\Controllers\Install\ModulesController::class, 'index'])
            ->with('status', $output);
    }

    public function uninstall()
    {
        if (! auth()->user()->can('manage_modules')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            System::removeProperty('woocommerce_version');
            $output = ['success' => true, 'msg' => __('lang_v1.success')];
        } catch (\Exception $e) {
            Log::error('Woocommerce module uninstall failed: ' . $e->getMessage());
            $output = ['success' => false, 'msg' => 'Woocommerce uninstall failed. Check server logs.'];
        }

        return redirect()
            ->action([\App\Http\Controllers\Install\ModulesController::class, 'index'])
            ->with('status', $output);
    }
}
