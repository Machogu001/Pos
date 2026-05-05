<?php

namespace App\Http\Controllers\Install;

use App\Http\Controllers\Controller;
use App\Utils\ModuleUtil;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Module;
use ZipArchive;
use Illuminate\Support\Facades\Artisan;

class ModulesController extends Controller
{
    protected $moduleUtil;

    /**
     * Constructor
     *
     * @param  ModuleUtil  $moduleUtil
     * @return void
     */
    public function __construct(ModuleUtil $moduleUtil)
    {
        $this->moduleUtil = $moduleUtil;
        $this->middleware(['auth', 'superadmin']);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $this->ensureManageModulesAccess();

        $notAllowed = $this->moduleUtil->notAllowedInDemo();
        if (! empty($notAllowed)) {
            return $notAllowed;
        }

        //Get list of all modules.
        $modules = Module::toCollection()->toArray();
        //print_r($modules);exit;

        foreach ($modules as $module => $details) {
            $modules[$module]['is_installed'] = $this->moduleUtil->isModuleInstalled($details['name']) ? true : false;

            //Get version information.
            if ($modules[$module]['is_installed']) {
                $modules[$module]['version'] = $this->moduleUtil->getModuleVersionInfo($details['name']);
            } else {
                unset($modules[$module]['version']);
            }

            // Always go through wrapper routes for safer, centralized install/update/uninstall handling.
            $modules[$module]['install_link'] = route('manage-modules.install', ['module_name' => $details['name']]);
            $modules[$module]['update_link'] = route('manage-modules.update-by-name', ['module_name' => $details['name']]);
            $modules[$module]['uninstall_link'] = route('manage-modules.uninstall', ['module_name' => $details['name']]);
        }

        $is_demo = (config('app.env') == 'demo');
        $mods = $this->__available_modules();
        
        return view('install.modules.index')
            ->with(compact('modules', 'is_demo', 'mods'));

        //Option to uninstall

        //Option to activate/deactivate

        //Upload module.
    }

    public function regenerate()
    {
        $this->ensureManageModulesAccess();

        $notAllowed = $this->moduleUtil->notAllowedInDemo();
        if (! empty($notAllowed)) {
            return $notAllowed;
        }

        try {
            Artisan::call('module:publish');
            Artisan::call('passport:install --force');
            // Artisan::call('scribe:generate');

            $output = ['success' => 1,
                'msg' => __('lang_v1.success'),
            ];
        } catch (\Exception $e) {
            Log::error('Module regenerate failed: '.$e->getMessage());
            $output = ['success' => 0,
                'msg' => __('messages.something_went_wrong'),
            ];
        }

        return redirect()->back()->with('status', $output);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Activate/Deaactivate the specified module.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $module_name)
    {
        $this->ensureManageModulesAccess();

        $notAllowed = $this->moduleUtil->notAllowedInDemo();
        if (! empty($notAllowed)) {
            return $notAllowed;
        }

        try {
            $module = Module::find($module_name);

            //php artisan module:disable Blog
            if ($request->action_type == 'activate') {
                $module->enable();
            } elseif ($request->action_type == 'deactivate') {
                $module->disable();
            }

            $output = ['success' => true,
                'msg' => __('lang_v1.success'),
            ];
        } catch (\Exception $e) {
            $output = ['success' => false,
                'msg' => $e->getMessage(),
            ];
        }

        if ($request->ajax()) {
            return response()->json($output);
        }

        return redirect()->back()->with(['status' => $output]);
    }

    /**
     * Deletes the module.
     *
     * @param  string  $module_name
     * @return \Illuminate\Http\Response
     */
    public function destroy($module_name)
    {
        $this->ensureManageModulesAccess();

        $notAllowed = $this->moduleUtil->notAllowedInDemo();
        if (! empty($notAllowed)) {
            return $notAllowed;
        }

        try {
            $module = Module::find($module_name);
            // $module->delete();

            $path = $module->getPath();

            die("To delete the module delete this folder <br/>" . $path . '<br/> Go back after deleting');

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
     * Upload the module.
     */
    public function uploadModule(Request $request)
    {
        $this->ensureManageModulesAccess();

        $notAllowed = $this->moduleUtil->notAllowedInDemo();
        if (! empty($notAllowed)) {
            return $notAllowed;
        }

        $request->validate([
            'module' => 'required|file|max:102400',
        ]);

        $tempExtractPath = null;

        try {
            $uploadedModule = $request->file('module');
            $zip = new ZipArchive();

            $this->ensureModuleUploadFilesystemReady();

            if ($zip->open($uploadedModule->getRealPath()) !== true) {
                throw new \RuntimeException('Uploaded module is not a valid ZIP archive.');
            }

            $moduleDetails = $this->extractModuleDetailsFromZip($zip);
            $moduleName = $moduleDetails['name'];

            $this->ensureModuleUploadFilesystemReady($moduleName);

            $tempExtractPath = storage_path('app/module_uploads/'.Str::uuid()->toString());
            File::ensureDirectoryExists($tempExtractPath);

            if (! $zip->extractTo($tempExtractPath)) {
                throw new \RuntimeException('Unable to extract uploaded module archive.');
            }
            $zip->close();

            $extractedModulePath = $this->resolveExtractedModulePath($tempExtractPath, $moduleDetails);
            $this->validateExtractedModule($extractedModulePath, $moduleDetails);

            $modulesRoot = base_path('Modules');
            File::ensureDirectoryExists($modulesRoot);

            $targetModulePath = $modulesRoot.DIRECTORY_SEPARATOR.$moduleName;

            if (File::isDirectory($targetModulePath)) {
                $this->mergeModuleDirectory($extractedModulePath, $targetModulePath);
            } elseif (! File::moveDirectory($extractedModulePath, $targetModulePath)) {
                if (! File::copyDirectory($extractedModulePath, $targetModulePath)) {
                    throw new \RuntimeException('Unable to place module files in Modules directory.');
                }
            }

            $this->repairModulePermissions($moduleName);

            // module:publish accesses a console-only protected property ($components)
            // and will throw when called from a web request — publish assets manually instead.
            $this->publishModuleAssets($moduleName);
            $this->repairModulePermissions($moduleName);

            try {
                Artisan::call('optimize:clear');
            } catch (\Throwable $e) {
                Log::warning('optimize:clear failed during module upload (non-fatal): '.$e->getMessage());
            }

            // Ensure the module is enabled in modules_statuses.json
            $freshModule = Module::find($moduleName);
            if ($freshModule && ! $freshModule->isEnabled()) {
                $freshModule->enable();
            }

            try {
                Artisan::call('module:enable', ['module' => $moduleName]);
            } catch (\Throwable $e) {
                Log::warning('module:enable failed during module upload (non-fatal): '.$e->getMessage());
            }

            // Flush OPcache so the newly written PHP files are visible in this process
            if (function_exists('opcache_reset')) {
                opcache_reset();
            }

            Log::info("Module upload: \"{$moduleName}\" -> ".base_path('Modules'));

            // Always use the named fallback route — action() will fail for freshly
            // uploaded modules because their routes aren't registered yet in this request.
            return redirect()->route('manage-modules.install', ['module_name' => $moduleName]);
        } catch (\Throwable $e) {
            Log::error('Module upload failed: '.$e->getMessage());

            $output = [
                'success' => false,
                'msg' => __('messages.something_went_wrong'),
            ];
        } finally {
            if (! empty($tempExtractPath) && File::isDirectory($tempExtractPath)) {
                File::deleteDirectory($tempExtractPath);
            }
        }

        return redirect()->back()->with(['status' => $output]);
    }

    public function installByModuleName($module_name)
    {
        $this->ensureManageModulesAccess();

        return $this->runInstallControllerMethod($module_name, 'index');
    }

    public function uninstallByModuleName($module_name)
    {
        $this->ensureManageModulesAccess();

        return $this->runInstallControllerMethod($module_name, 'uninstall');
    }

    public function updateByModuleName($module_name)
    {
        $this->ensureManageModulesAccess();

        return $this->runInstallControllerMethod($module_name, 'update');
    }

    private function ensureManageModulesAccess(): void
    {
        $user = auth()->user();

        if (empty($user) || ! method_exists($user, 'isSuperAdmin') || ! $user->isSuperAdmin() || ! $user->can('manage_modules')) {
            abort(403, 'Unauthorized action.');
        }
    }

    private function extractModuleDetailsFromZip(ZipArchive $zip): array
    {
        $moduleJsonFiles = [];

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $entryName = str_replace('\\', '/', $zip->getNameIndex($i));
            if (preg_match('/(^|\/)module\.json$/i', $entryName)) {
                $moduleJsonFiles[] = $entryName;
            }
        }

        usort($moduleJsonFiles, function ($first, $second) {
            return substr_count($first, '/') <=> substr_count($second, '/');
        });

        foreach ($moduleJsonFiles as $moduleJsonEntry) {
            $moduleJsonContent = $zip->getFromName($moduleJsonEntry);
            if ($moduleJsonContent === false) {
                continue;
            }

            $moduleJson = json_decode($moduleJsonContent, true);
            $moduleName = trim((string) data_get($moduleJson, 'name'));

            if (empty($moduleName)) {
                continue;
            }

            if (! preg_match('/^[A-Za-z][A-Za-z0-9]*$/', $moduleName)) {
                throw new \RuntimeException('Uploaded module contains an invalid module name in module.json.');
            }

            return [
                'name' => $moduleName,
                'providers' => (array) data_get($moduleJson, 'providers', []),
                'module_json_entry' => $moduleJsonEntry,
            ];
        }

        throw new \RuntimeException('Uploaded ZIP does not contain a valid module.json file.');
    }

    private function resolveExtractedModulePath(string $tempExtractPath, array $moduleDetails): string
    {
        $moduleName = $moduleDetails['name'];
        $moduleJsonEntry = trim((string) $moduleDetails['module_json_entry']);

        $moduleJsonDirectory = trim(dirname(str_replace('\\', '/', $moduleJsonEntry)), '.');

        if (! empty($moduleJsonDirectory) && $moduleJsonDirectory !== DIRECTORY_SEPARATOR) {
            $candidatePath = $tempExtractPath.DIRECTORY_SEPARATOR.trim($moduleJsonDirectory, '/');
            if (File::isDirectory($candidatePath)) {
                return $candidatePath;
            }
        }

        $namedFolderPath = $tempExtractPath.DIRECTORY_SEPARATOR.$moduleName;
        if (File::isDirectory($namedFolderPath)) {
            return $namedFolderPath;
        }

        return $tempExtractPath;
    }

    private function validateExtractedModule(string $modulePath, array $moduleDetails): void
    {
        if (! File::exists($modulePath.DIRECTORY_SEPARATOR.'module.json')) {
            throw new \RuntimeException('Uploaded module is missing module.json in extracted root.');
        }

        $installControllerPath = $modulePath.DIRECTORY_SEPARATOR.'Http/Controllers/InstallController.php';
        if (! File::exists($installControllerPath)) {
            throw new \RuntimeException('Uploaded module is missing InstallController.php.');
        }

        foreach ((array) $moduleDetails['providers'] as $provider) {
            $providerRelativePath = str_replace('\\', DIRECTORY_SEPARATOR, $provider).'.php';
            $providerRelativePath = preg_replace('/^Modules'.preg_quote(DIRECTORY_SEPARATOR, '/').preg_quote($moduleDetails['name'], '/').preg_quote(DIRECTORY_SEPARATOR, '/').'/', '', $providerRelativePath);
            $providerAbsolutePath = $modulePath.DIRECTORY_SEPARATOR.ltrim($providerRelativePath, DIRECTORY_SEPARATOR);

            if (! File::exists($providerAbsolutePath)) {
                throw new \RuntimeException('Uploaded module is missing provider file: '.$providerRelativePath);
            }
        }
    }

    private function publishModuleAssets(string $moduleName): void
    {
        // Replicate what `module:publish {moduleName}` does: copy
        // Modules/{Name}/Resources/assets -> public/modules/{lowercase-name}/
        $sourcePath = base_path('Modules'.DIRECTORY_SEPARATOR.$moduleName.DIRECTORY_SEPARATOR.'Resources'.DIRECTORY_SEPARATOR.'assets');
        if (! File::isDirectory($sourcePath)) {
            return;
        }

        $targetPath = public_path('modules'.DIRECTORY_SEPARATOR.strtolower($moduleName));
        $this->ensureWritablePath($targetPath, true, false);

        if (! is_writable($targetPath)) {
            Log::warning('Module upload assets publish skipped (path not writable): '.$targetPath);

            return;
        }

        foreach (File::allFiles($sourcePath) as $file) {
            $relative = ltrim(str_replace($sourcePath, '', $file->getPathname()), DIRECTORY_SEPARATOR);
            $dest = $targetPath.DIRECTORY_SEPARATOR.$relative;
            File::ensureDirectoryExists(dirname($dest));
            try {
                File::copy($file->getPathname(), $dest);
            } catch (\Throwable $e) {
                Log::warning('Module upload assets publish file copy failed (non-fatal): '.$e->getMessage());
            }
        }
    }

    private function ensureModuleUploadFilesystemReady(?string $moduleName = null): void
    {
        $this->ensureWritablePath(storage_path('app/module_uploads'), true);
        $this->ensureWritablePath(base_path('Modules'), true);

        if (! empty($moduleName)) {
            $this->ensureWritablePath(base_path('Modules'.DIRECTORY_SEPARATOR.$moduleName), true);
        }
    }

    private function ensureWritablePath(string $path, bool $isDirectory = false, bool $strict = true): void
    {
        if (! File::exists($path) && $isDirectory) {
            File::ensureDirectoryExists($path, 0775, true);
        }

        if (! File::exists($path)) {
            if (! $strict) {
                return;
            }

            throw new \RuntimeException('Required filesystem path is missing: '.$path);
        }

        // Best effort permission repair for web uploads.
        if (! is_writable($path)) {
            @chmod($path, $isDirectory ? 0775 : 0664);
        }

        if ($isDirectory && ! is_writable($path)) {
            @chmod($path, 02775);
        }

        if (! is_writable($path) && $strict) {
            throw new \RuntimeException('Path is not writable for module upload: '.$path);
        }
    }

    private function mergeModuleDirectory(string $sourcePath, string $targetPath): void
    {
        if (! File::isDirectory($sourcePath)) {
            throw new \RuntimeException('Uploaded module extraction directory was not found.');
        }

        File::ensureDirectoryExists($targetPath);

        foreach (File::allFiles($sourcePath) as $sourceFile) {
            $sourceFilePath = $sourceFile->getPathname();
            $relativeFilePath = ltrim(str_replace($sourcePath, '', $sourceFilePath), DIRECTORY_SEPARATOR);
            $targetFilePath = $targetPath.DIRECTORY_SEPARATOR.$relativeFilePath;

            File::ensureDirectoryExists(dirname($targetFilePath));
            File::copy($sourceFilePath, $targetFilePath);
        }
    }

    private function runInstallControllerMethod(string $moduleName, string $method)
    {
        $moduleName = Str::studly($moduleName);
        $installClass = '\\Modules\\'.$moduleName.'\\Http\\Controllers\\InstallController';

        if (! class_exists($installClass)) {
            return redirect()->back()->with('status', [
                'success' => false,
                'msg' => $moduleName.' module installer was not found.',
            ]);
        }

        if (! method_exists($installClass, $method)) {
            return redirect()->back()->with('status', [
                'success' => false,
                'msg' => $moduleName.' module installer action is unavailable.',
            ]);
        }

        try {
            $this->repairModulePermissions($moduleName);

            return app()->call([app($installClass), $method]);
        } catch (\Throwable $e) {
            if ($this->isIgnorableExistingSchemaError($e)) {
                Log::warning('Dynamic module '.$method.' skipped existing schema for '.$moduleName.': '.$e->getMessage());

                return redirect()->back()->with('status', [
                    'success' => true,
                    'msg' => $moduleName.' module schema already exists. Skipped existing tables/columns.',
                ]);
            }

            Log::error('Dynamic module '.$method.' failed for '.$moduleName.': '.$e->getMessage());

            return redirect()->back()->with('status', [
                'success' => false,
                'msg' => __('messages.something_went_wrong'),
            ]);
        }
    }

    private function isIgnorableExistingSchemaError(\Throwable $e): bool
    {
        $message = $e->getMessage();

        return str_contains($message, 'SQLSTATE[42S01]')
            || str_contains($message, 'Base table or view already exists')
            || str_contains($message, 'SQLSTATE[42S21]')
            || str_contains($message, 'Duplicate column name')
            || str_contains($message, 'already exists');
    }

    private function repairModulePermissions(string $moduleName): void
    {
        $moduleName = Str::studly($moduleName);

        $paths = [
            storage_path(),
            base_path('bootstrap'.DIRECTORY_SEPARATOR.'cache'),
            base_path('Modules'),
            base_path('Modules'.DIRECTORY_SEPARATOR.$moduleName),
            base_path('modules_statuses.json'),
            public_path('modules'),
            public_path('modules'.DIRECTORY_SEPARATOR.strtolower($moduleName)),
        ];

        foreach ($paths as $path) {
            $exists = File::exists($path);
            $isDirectory = $exists ? File::isDirectory($path) : str_ends_with($path, $moduleName) || str_ends_with($path, 'modules') || str_ends_with($path, 'storage') || str_ends_with($path, 'cache');

            if (! $exists && $isDirectory) {
                File::ensureDirectoryExists($path, 0775, true);
            }

            if (! File::exists($path)) {
                continue;
            }

            $this->normalizePermissions($path);

            if (File::isDirectory($path)) {
                $this->normalizePermissionsRecursively($path);
            }
        }
    }

    private function normalizePermissionsRecursively(string $directoryPath): void
    {
        foreach (File::directories($directoryPath) as $directory) {
            $this->normalizePermissions($directory, true);
            $this->normalizePermissionsRecursively($directory);
        }

        foreach (File::files($directoryPath) as $file) {
            $this->normalizePermissions($file->getPathname());
        }
    }

    private function normalizePermissions(string $path, ?bool $isDirectory = null): void
    {
        $isDirectory = $isDirectory ?? File::isDirectory($path);

        @chmod($path, $isDirectory ? 02775 : 0664);

        if (! is_writable($path)) {
            @chmod($path, $isDirectory ? 0775 : 0664);
        }

        if (! is_writable($path)) {
            Log::warning('Automatic module permission repair could not make path writable: '.$path);
        }
    }

    private function __available_modules()
    {
        return 'a:14:{i:0;a:4:{s:1:"n";s:10:"Essentials";s:2:"dn";s:17:"Essentials Module";s:1:"u";s:53:"https://ultimatefosters.com/recommends/essential-app/";s:1:"d";s:49:"Essentials features for every growing businesses.";}i:1;a:4:{s:1:"n";s:10:"Superadmin";s:2:"dn";s:17:"Superadmin Module";s:1:"u";s:54:"https://ultimatefosters.com/recommends/superadmin-app/";s:1:"d";s:76:"Turn your POS to SaaS application and start earning by selling subscriptions";}i:2;a:4:{s:1:"n";s:11:"Woocommerce";s:2:"dn";s:18:"Woocommerce Module";s:1:"u";s:55:"https://ultimatefosters.com/recommends/woocommerce-app/";s:1:"d";s:36:"Sync your Woocommerce store with POS";}i:3;a:4:{s:1:"n";s:13:"Manufacturing";s:2:"dn";s:20:"Manufacturing Module";s:1:"u";s:57:"https://ultimatefosters.com/recommends/manufacturing-app/";s:1:"d";s:70:"Manufacture products from raw materials, organise recipe & ingredients";}i:4;a:4:{s:1:"n";s:7:"Project";s:2:"dn";s:14:"Project Module";s:1:"u";s:51:"https://ultimatefosters.com/recommends/project-app/";s:1:"d";s:66:"Manage Projects, tasks, tasks time logs, activities and much more.";}i:5;a:4:{s:1:"n";s:6:"Repair";s:2:"dn";s:13:"Repair Module";s:1:"u";s:50:"https://ultimatefosters.com/recommends/repair-app/";s:1:"d";s:248:"Repair module helps with complete repair service management of electronic goods like Cellphone, Computers, Desktops, Tablets, Television, Watch, Wireless devices, Printers, Electronic instruments and many more similar devices which you can imagine!";}i:6;a:4:{s:1:"n";s:3:"Crm";s:2:"dn";s:10:"CRM Module";s:1:"u";s:63:"https://ultimatefosters.com/product/crm-module-for-ultimatepos/";s:1:"d";s:39:"Customer relationship management module";}i:7;a:4:{s:1:"n";s:16:"ProductCatalogue";s:2:"dn";s:16:"ProductCatalogue";s:1:"u";s:90:"https://codecanyon.net/item/digital-product-catalogue-menu-module-for-ultimatepos/28825346";s:1:"d";s:32:"Digital Product catalogue Module";}i:8;a:4:{s:1:"n";s:10:"Accounting";s:2:"dn";s:17:"Accounting Module";s:1:"u";s:82:"https://ultimatefosters.com/product/accounting-bookkeeping-module-for-ultimatepos/";s:1:"d";s:48:"Accounting & Book keeping module for UltimatePOS";}i:9;a:4:{s:1:"n";s:12:"AiAssistance";s:2:"dn";s:19:"AiAssistance Module";s:1:"u";s:73:"https://ultimatefosters.com/product/ai-assistance-module-for-ultimatepos/";s:1:"d";s:104:"AI Assistant module for UltimatePOS. This module used openAI API to help with in copywriting & reporting";}i:10;a:4:{s:1:"n";s:15:"AssetManagement";s:2:"dn";s:22:"AssetManagement Module";s:1:"u";s:76:"https://ultimatefosters.com/product/asset-management-module-for-ultimatepos/";s:1:"d";s:40:"Useful for managing all kinds of assets.";}i:11;a:4:{s:1:"n";s:3:"Cms";s:2:"dn";s:10:"Cms Module";s:1:"u";s:59:"https://ultimatefosters.com/product/ultimatepos-cms-module/";s:1:"d";s:153:"Mini CMS (content management system) Module for UltimatePOS to help manage all frontend contents like Landing page, Blogs, Contact us & many other pages.";}i:12;a:4:{s:1:"n";s:9:"Connector";s:2:"dn";s:20:"Connector/API Module";s:1:"u";s:68:"https://ultimatefosters.com/product/rest-api-module-for-ultimatepos/";s:1:"d";s:24:"Provide the API for POS.";}i:13;a:4:{s:1:"n";s:11:"Spreadsheet";s:2:"dn";s:18:"Spreadsheet Module";s:1:"u";s:71:"https://ultimatefosters.com/product/spreadsheet-module-for-ultimatepos/";s:1:"d";s:72:"Allows you to create spreadsheet and share with employees, roles & todos";}}';
    }
}
