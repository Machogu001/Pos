<?php

namespace Modules\ProductCatalogue\Http\Controllers;

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
            System::addProperty('productcatalogue_version', '1.0.0');
            $output = ['success' => true, 'msg' => __('lang_v1.success')];
        } catch (\Exception $e) {
            Log::error('ProductCatalogue module install failed: ' . $e->getMessage());
            $output = ['success' => false, 'msg' => 'ProductCatalogue installation failed. Check server logs.'];
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
            System::removeProperty('productcatalogue_version');
            $output = ['success' => true, 'msg' => __('lang_v1.success')];
        } catch (\Exception $e) {
            Log::error('ProductCatalogue module uninstall failed: ' . $e->getMessage());
            $output = ['success' => false, 'msg' => 'ProductCatalogue uninstall failed. Check server logs.'];
        }

        return redirect()
            ->action([\App\Http\Controllers\Install\ModulesController::class, 'index'])
            ->with('status', $output);
    }
}
