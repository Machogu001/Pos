<?php

namespace Modules\Connector\Http\Controllers;

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
            System::addProperty('connector_version', '1.0.0');
            $output = ['success' => true, 'msg' => __('lang_v1.success')];
        } catch (\Exception $e) {
            Log::error('Connector module install failed: ' . $e->getMessage());
            $output = ['success' => false, 'msg' => 'Connector installation failed. Check server logs.'];
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
            System::removeProperty('connector_version');
            $output = ['success' => true, 'msg' => __('lang_v1.success')];
        } catch (\Exception $e) {
            Log::error('Connector module uninstall failed: ' . $e->getMessage());
            $output = ['success' => false, 'msg' => 'Connector uninstall failed. Check server logs.'];
        }

        return redirect()
            ->action([\App\Http\Controllers\Install\ModulesController::class, 'index'])
            ->with('status', $output);
    }
}
