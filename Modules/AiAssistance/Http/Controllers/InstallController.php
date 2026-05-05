<?php

namespace Modules\AiAssistance\Http\Controllers;

use App\System;
use Composer\Semver\Comparator;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class InstallController extends Controller
{
    public function __construct()
    {
        $this->module_name = 'aiassistance';
        $this->appVersion = config('aiassistance.module_version');
    }

    /**
     * Install
     *
     * @return Response
     */
    public function index()
    {
        if (! auth()->user()->can('superadmin')) {
            abort(403, 'Unauthorized action.');
        }

        ini_set('max_execution_time', 0);
        ini_set('memory_limit', '512M');

        $this->installSettings();

        //Check if aiassistance installed or not.
        $is_installed = System::getProperty($this->module_name.'_version');
        if (! empty($is_installed)) {
            abort(404);
        }
        $this->publishAssetsNoFail('AiAssistance');
        $action_url = action([\Modules\AiAssistance\Http\Controllers\InstallController::class, 'install']);
        $intruction_type = 'uf';

        return view('install.install-module')
            ->with(compact('action_url', 'intruction_type'));
    }

    /**
     * Initialize all install functions
     */
    private function installSettings()
    {
        config(['app.debug' => true]);
        Artisan::call('config:clear');
    }

    /**
     * Installing aiassistance Module
     */
    public function install()
    {
        try {
            request()->validate(
                ['license_code' => 'nullable',
                    'login_username' => 'nullable', ],
                ['license_code.required' => 'License code is required',
                    'login_username.required' => 'Username is required', ]
            );

            DB::beginTransaction();

            $license_code = request()->license_code;
            $email = request()->email;
            $login_username = request()->login_username;
            $pid = config('aiassistance.pid');

            $is_installed = System::getProperty($this->module_name.'_version');
            if (! empty($is_installed)) {
                abort(404);
            }

            DB::statement('SET default_storage_engine=INNODB;');
            Artisan::call('module:migrate', ['module' => 'AiAssistance', '--force' => true]);
            $this->publishAssetsNoFail('AiAssistance');
            System::addProperty($this->module_name.'_version', $this->appVersion);

            DB::commit();

            $output = ['success' => 1,
                'msg' => 'AiAssistance module installed succesfully',
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

            $output = [
                'success' => false,
                'msg' => $e->getMessage(),
            ];
        }

        return redirect()
            ->action([\App\Http\Controllers\Install\ModulesController::class, 'index'])
            ->with('status', $output);
    }

    /**
     * Uninstall
     *
     * @return Response
     */
    public function uninstall()
    {
        if (! auth()->user()->can('superadmin')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            System::removeProperty($this->module_name.'_version');

            $output = ['success' => true,
                'msg' => __('lang_v1.success'),
            ];
        } catch (\Exception $e) {
            $output = ['success' => false,
                'msg' => $e->getMessage(),
            ];
        }

        return redirect()->back()->with(['status' => $output]);
    }

    /**
     * update module
     *
     * @return Response
     */
    public function update()
    {
        //Check if aiassistance_version is same as appVersion then 404
        //If appVersion > aiassistance_version - run update script.
        //Else there is some problem.
        if (! auth()->user()->can('superadmin')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            DB::beginTransaction();
            ini_set('max_execution_time', 0);
            ini_set('memory_limit', '512M');

            $aiassistance_version = System::getProperty($this->module_name.'_version');

            if (Comparator::greaterThan($this->appVersion, $aiassistance_version)) {
                ini_set('max_execution_time', 0);
                ini_set('memory_limit', '512M');
                $this->installSettings();

                DB::statement('SET default_storage_engine=INNODB;');
                Artisan::call('module:migrate', ['module' => 'AiAssistance', '--force' => true]);
                $this->publishAssetsNoFail('AiAssistance');
                System::setProperty($this->module_name.'_version', $this->appVersion);
            } else {
                abort(404);
            }

            DB::commit();

            $output = ['success' => 1,
                'msg' => 'AiAssistance module updated Succesfully to version '.$this->appVersion.' !!',
            ];

            return redirect()->back()->with(['status' => $output]);
        } catch (Exception $e) {
            DB::rollBack();
            exit($e->getMessage());
        }
    }

    private function publishAssetsNoFail(string $moduleName): void
    {
        $sourcePath = base_path('Modules/'.$moduleName.'/Resources/assets');
        if (! File::isDirectory($sourcePath)) {
            return;
        }

        $targetPath = public_path('modules/'.strtolower($moduleName));
        if (! File::exists($targetPath)) {
            File::ensureDirectoryExists($targetPath);
        }

        if (! is_writable($targetPath)) {
            return;
        }

        foreach (File::allFiles($sourcePath) as $file) {
            $relative = ltrim(str_replace($sourcePath, '', $file->getPathname()), DIRECTORY_SEPARATOR);
            $destination = $targetPath.DIRECTORY_SEPARATOR.$relative;
            File::ensureDirectoryExists(dirname($destination));

            try {
                File::copy($file->getPathname(), $destination);
            } catch (\Throwable $e) {
                // Non-fatal for web installer flow.
            }
        }
    }
}
