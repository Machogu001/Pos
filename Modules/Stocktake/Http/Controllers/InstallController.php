<?php

namespace Modules\Stocktake\Http\Controllers;

use App\Http\Controllers\Controller;
use App\System;
use Illuminate\Support\Facades\Log;

class InstallController extends Controller
{
    /**
     * Run Stocktake module installation: mark as installed.
     * (No migrations — this module is a lightweight placeholder.)
     */
    public function index()
    {
        if (! auth()->user()->can('manage_modules')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            System::addProperty('stocktake_version', '1.0.0');

            $output = [
                'success' => true,
                'msg'     => __('lang_v1.success'),
            ];
        } catch (\Exception $e) {
            Log::error('Stocktake module install failed: ' . $e->getMessage());
            $output = [
                'success' => false,
                'msg'     => 'Stocktake installation failed. Check server logs for details.',
            ];
        }

        return redirect()
            ->action([\App\Http\Controllers\Install\ModulesController::class, 'index'])
            ->with('status', $output);
    }

    /**
     * Uninstall: remove the version marker.
     */
    public function uninstall()
    {
        if (! auth()->user()->can('manage_modules')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            System::removeProperty('stocktake_version');

            $output = [
                'success' => true,
                'msg'     => __('lang_v1.success'),
            ];
        } catch (\Exception $e) {
            Log::error('Stocktake module uninstall failed: ' . $e->getMessage());
            $output = [
                'success' => false,
                'msg'     => 'Stocktake uninstall failed. Check server logs for details.',
            ];
        }

        return redirect()
            ->action([\App\Http\Controllers\Install\ModulesController::class, 'index'])
            ->with('status', $output);
    }
}
