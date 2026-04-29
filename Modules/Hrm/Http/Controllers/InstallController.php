<?php

namespace Modules\Hrm\Http\Controllers;

use App\Http\Controllers\Controller;
use App\System;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InstallController extends Controller
{
    /**
     * Run HRM module installation: migrate tables, seed data, mark as installed.
     */
    public function index()
    {
        if (! auth()->user()->can('manage_modules')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            // Run module migrations
            Artisan::call('migrate', [
                '--path'  => 'Modules/Hrm/Database/Migrations',
                '--force' => true,
            ]);

            // Seed base data and permissions
            Artisan::call('db:seed', [
                '--class' => 'Modules\\Hrm\\Database\\Seeders\\HrmDatabaseSeeder',
                '--force' => true,
            ]);

            // Mark module as installed
            System::addProperty('hrm_version', '1.0.0');

            $output = [
                'success' => true,
                'msg'     => __('lang_v1.success'),
            ];
        } catch (\Exception $e) {
            Log::error('HRM module install failed: ' . $e->getMessage());
            $output = [
                'success' => false,
                'msg'     => 'HRM installation failed. Check server logs for details.',
            ];
        }

        return redirect()
            ->action([\App\Http\Controllers\Install\ModulesController::class, 'index'])
            ->with('status', $output);
    }

    /**
     * Uninstall: remove the version marker (data is preserved).
     */
    public function uninstall()
    {
        if (! auth()->user()->can('manage_modules')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            System::removeProperty('hrm_version');

            $output = [
                'success' => true,
                'msg'     => __('lang_v1.success'),
            ];
        } catch (\Exception $e) {
            Log::error('HRM module uninstall failed: ' . $e->getMessage());
            $output = [
                'success' => false,
                'msg'     => 'HRM uninstall failed. Check server logs for details.',
            ];
        }

        return redirect()
            ->action([\App\Http\Controllers\Install\ModulesController::class, 'index'])
            ->with('status', $output);
    }
}
