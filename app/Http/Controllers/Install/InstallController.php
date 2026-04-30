<?php

namespace App\Http\Controllers\Install;

use App\Http\Controllers\Controller;
use App\Utils\InstallUtil;
use Composer\Semver\Comparator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\Console\Output\BufferedOutput;
use Illuminate\Support\Facades\Abort;

//use Illuminate\Support\Facades\Storage;

class InstallController extends Controller
{
    /**
     * All Utils instance.
     */
    protected $outputLog;

    protected $appVersion;

    protected $macActivationKeyChecker;

    /**
     * Constructor
     *
     * @return void
     */
    public function __construct()
    {
        $this->appVersion = config('author.app_version');
        $this->env = config('app.env');

        // Prevent accidental access to installer routes after installation.
        // Allow install.success only once right after setup completes.
        $this->middleware(function ($request, $next) {
            if ($request->routeIs('install.success')) {
                if (session('install.just_completed') === true) {
                    return $next($request);
                }

                return redirect()->route('login');
            }

            // If .env already exists, treat installer as completed and redirect.
            // This avoids exposing install screens on live systems.
            if (file_exists(base_path('.env')) || $this->isInstalledCompletely()) {
                return redirect()->route('login');
            }

            return $next($request);
        })->except(['updateConfirmation', 'update']);

        //Check if mac based activation key is required or not.
        $this->macActivationKeyChecker = false;
        if (file_exists(__DIR__.'/MacActivationKeyChecker.php')) {
            include_once __DIR__.'/MacActivationKeyChecker.php';
            $this->macActivationKeyChecker = $mac_is_enabled;
        }

        $this->installSettings();
    }

    /**
     * Initialize all install functions
     */
    private function installSettings()
    {
        config(['app.debug' => true]);
        Artisan::call('config:clear');
        Artisan::call('cache:clear');
    }

    /**
     * Check if project is already installed then show 404 error
     */
    private function isInstalled()
    {
        $envPath = base_path('.env');
        if (file_exists($envPath)) {
            abort(404);
        }
    }

    /**
     * True only when install artifacts and core tables are present.
     */
    private function isInstalledCompletely(): bool
    {
        try {
            return file_exists(base_path('.env'))
                && Schema::hasTable('users')
                && Schema::hasTable('business')
                && Schema::hasTable('admin_settings');
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * This function deletes .env file.
     */
    private function deleteEnv()
    {
        $envPath = base_path('.env');
        if ($envPath && file_exists($envPath)) {
            unlink($envPath);
        }

        return true;
    }

    /**
     * Installation
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //Check for .env file
        $this->isInstalled();
        $this->installSettings();

        return view('install.index');
    }

    public function checkServer()
    {
        //Check for .env file
        $this->isInstalled();
        $this->installSettings();

        $output = [];

        //Check for php version
        $output['php'] = (PHP_MAJOR_VERSION >= 7 && PHP_MINOR_VERSION >= 1) ? true : false;
        $output['php_version'] = PHP_VERSION;

        //Check for php extensions
        $output['openssl'] = extension_loaded('openssl') ? true : false;
        $output['pdo'] = extension_loaded('pdo') ? true : false;
        $output['mbstring'] = extension_loaded('mbstring') ? true : false;
        $output['tokenizer'] = extension_loaded('tokenizer') ? true : false;
        $output['xml'] = extension_loaded('xml') ? true : false;
        $output['curl'] = extension_loaded('curl') ? true : false;
        $output['zip'] = extension_loaded('zip') ? true : false;
        $output['gd'] = extension_loaded('gd') ? true : false;

        //Check for writable permission. storage and the bootstrap/cache directories should be writable by your web server
        $output['storage_writable'] = is_writable(storage_path());
        $output['cache_writable'] = is_writable(base_path('bootstrap/cache'));

        $output['next'] = $output['php'] && $output['openssl'] && $output['pdo'] && $output['mbstring'] && $output['tokenizer'] && $output['xml'] && $output['curl'] && $output['zip'] && $output['gd'] && $output['storage_writable'] && $output['cache_writable'];

        return view('install.check-server')
            ->with(compact('output'));
    }

    public function details()
    {
        //Check for .env file
        $this->isInstalled();
        $this->installSettings();

        //Check if .env.example is present or not.
        $env_example = base_path('.env.example');
        if (! file_exists($env_example)) {
            exit("<b>.env.example file not found in <code>$env_example</code></b> <br/><br/> - In the downloaded codebase you will find .env.example file, please upload it and refresh this page.");
        }

        return view('install.details')
            ->with('activation_key', $this->macActivationKeyChecker);
    }

    public function postDetails(Request $request)
    {
        //Check for .env file
        $this->isInstalled();
        $this->installSettings();

        try {
            ini_set('max_execution_time', 0);
            ini_set('memory_limit', '512M');

            $validatedData = $request->validate(
                [
                    'APP_NAME' => 'required',
                    'ENVATO_PURCHASE_CODE' => 'required',
                    'DB_DATABASE' => 'required',
                    'DB_USERNAME' => 'required',
                    'DB_PASSWORD' => 'required',
                    'DB_HOST' => 'required',
                    'DB_PORT' => 'required',
                ],
                [
                    'APP_NAME.required' => 'App Name is required',
                    'ENVATO_PURCHASE_CODE.required' => 'Envaot Purchase code is required',
                    'DB_DATABASE.required' => 'Database Name is required',
                    'DB_USERNAME.required' => 'Database Username is required',
                    'DB_PASSWORD.required' => 'Database Password is required',
                    'DB_HOST.required' => 'Database Host is required',
                    'DB_PORT.required' => 'Database port is required',
                ]
            );

            $this->outputLog = new BufferedOutput;

            $input = $request->only(['APP_NAME', 'APP_TITLE', 'DB_HOST', 'DB_PORT', 'DB_DATABASE', 'DB_USERNAME', 'DB_PASSWORD', 'ENVATO_PURCHASE_CODE',
                'MAIL_MAILER',
                'MAIL_FROM_ADDRESS', 'MAIL_FROM_NAME', 'MAIL_HOST', 'MAIL_PORT', 'MAIL_ENCRYPTION',
                'MAIL_USERNAME', 'MAIL_PASSWORD', ]);

            $input['APP_DEBUG'] = 'false';
            $input['APP_URL'] = url('/');
            $input['APP_ENV'] = 'live';

            //Check for database details
            $mysql_link = @mysqli_connect($input['DB_HOST'], $input['DB_USERNAME'], $input['DB_PASSWORD'], $input['DB_DATABASE'], $input['DB_PORT']);
            if (mysqli_connect_errno()) {
                $msg = '<b>ERROR</b>: Failed to connect to MySQL: '.mysqli_connect_error();
                $msg .= "<br/>Provide correct details for 'Database Host', 'Database Port', 'Database Name', 'Database Username', 'Database Password'.";

                return redirect()
                    ->back()
                    ->with('error', $msg);
            }

            //Check for activation key
            if ($this->macActivationKeyChecker) {
                $licence_code = $request->get('MAC_LICENCE_CODE');
                $licence_valid = mac_verify_licence_code($licence_code);
                if (! $licence_valid) {
                    return redirect()->back()
                        ->with('error', 'Invalid Activation Licence Code!!')
                        ->withInput();
                    exit('Invalid Purchase Code');
                }

                $input['MAC_LICENCE_CODE'] = $licence_code;
            }

            //Get .env file details and write the contents in it.
            $envPathExample = base_path('.env.example');
            $envPath = base_path('.env');

            $env_lines = file($envPathExample);
            foreach ($input as $index => $value) {
                foreach ($env_lines as $key => $line) {
                    //Check if present then replace it.
                    if (strpos($line, $index) !== false) {
                        $env_lines[$key] = $index.'="'.$value.'"'.PHP_EOL;
                    }
                }
            }

            // Automatically create .env and run installation.
            // Fall back to manual copy instructions if write permission is blocked.
            $envContent = implode('', $env_lines);
            $write_success = @file_put_contents($envPath, $envContent) !== false;

            if ($write_success) {
                // Artisan commands
                $this->runArtisanCommands();

                session(['install.just_completed' => true]);

                return redirect()->route('install.success');
            }

            $this->deleteEnv();

            return view('install.envText')
                ->with(compact('envContent', 'envPath'));
        } catch (\Exception $e) {
            $this->deleteEnv();

            return redirect()->back()
                ->with('error', 'Something went wrong, please try again!!');
        }
    }

    //Generate key, migrate and seed
    private function runArtisanCommands()
    {
        ini_set('max_execution_time', 0);
        ini_set('memory_limit', '512M');

        $this->installSettings();

        // Run all directory/symlink/key setup tasks first
        Artisan::call('pos:setup', ['--force' => true]);

        DB::statement('SET default_storage_engine=INNODB;');
        Artisan::call('migrate:fresh', ['--force' => true]);
        Artisan::call('db:seed', ['--force' => true]);
        Artisan::call('optimize');
    }

    public function installAlternate(Request $request)
    {
        try {
            $this->installSettings();

            //Check if no .env file than redirect back.
            $envPath = base_path('.env');
            if (! file_exists($envPath)) {
                return redirect()->route('install.details')
                    ->with('error', 'Looks like you haven\'t created the .env file '.$envPath);
            }

            $this->runArtisanCommands();

            session(['install.just_completed' => true]);

            return redirect()->route('install.success');
        } catch (\Exception $e) {
            $this->deleteEnv();

            return redirect()->back()
                ->with('error', 'Something went wrong, please try again!!');
        }
    }

    public function success()
    {
        // One-time access: after rendering this page, lock installer routes again.
        session()->forget('install.just_completed');

        $checks = [
            'env_file' => file_exists(base_path('.env')),
            'app_key' => ! empty(config('app.key')),
            'storage_writable' => is_writable(storage_path()),
            'cache_writable' => is_writable(base_path('bootstrap/cache')),
            'users_table' => $this->hasTableSafe('users'),
            'business_table' => $this->hasTableSafe('business'),
            'admin_settings_table' => $this->hasTableSafe('admin_settings'),
            'db_connection' => $this->dbConnectionHealthy(),
        ];

        $all_passed = ! in_array(false, $checks, true);
        $redirect_to = route('login');

        return view('install.success', compact('checks', 'all_passed', 'redirect_to'));
    }

    private function hasTableSafe(string $table): bool
    {
        try {
            return Schema::hasTable($table);
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function dbConnectionHealthy(): bool
    {
        try {
            DB::connection()->getPdo();
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function updateConfirmation()
    {
        $installUtil = new installUtil();
        $db_version = $installUtil->getSystemInfo('db_version');

        if (Comparator::greaterThan($this->appVersion, $db_version)) {
            return view('install.update_confirmation');
        } else {
            // abort(404);
            exit("<b> Update already done to Version <code>".$db_version."</code></b>");
        }
    }

    //Updating
    public function update(Request $request)
    {
        //Check if db_version is same as app_verison then 404
        //If app_version > db_version - run update script.
        //Else there is some problem.

        $version = null;

        try {
            DB::beginTransaction();

            ini_set('max_execution_time', 0);
            ini_set('memory_limit', '512M');

            //Static version value is passed for 1.2 version.
            if ($version == 1.2) {
                exit('Update not supported. Kindly install again.');
            } elseif (is_null($version)) {
                $installUtil = new installUtil();
                $db_version = $installUtil->getSystemInfo('db_version');

                //if($db_version < $this->appVersion){
                if (Comparator::greaterThan($this->appVersion, $db_version)) {
                    ini_set('max_execution_time', 0);
                    ini_set('memory_limit', '512M');
                    $this->installSettings();
                    DB::statement('SET default_storage_engine=INNODB;');
                    Artisan::call('migrate', ['--force' => true]);
                    Artisan::call('module:publish');
                    Artisan::call('passport:install', ['--force' => true]);
                    Artisan::call('optimize');

                    $installUtil->setSystemInfo('db_version', $this->appVersion);
                } else {
                    abort(404);
                }
            } else {
                abort(404);
            }

            @DB::commit();

            $output = ['success' => 1,
                'msg' => 'Updated Succesfully to version '.$this->appVersion.' !!',
            ];

            return redirect('login')->with('status', $output);
        } catch (\Exception $e) {
            DB::rollBack();
            exit($e->getMessage());
        }
    }
}
