<?php

namespace App\Http\Controllers\Install;

use App\Http\Controllers\Controller;
use App\Services\PosInstaller;
use App\Utils\InstallUtil;
use Composer\Semver\Comparator;
use Dotenv\Dotenv;
use Illuminate\Database\Connectors\MySqlConnector;
use Illuminate\Http\Request;
use Illuminate\Support\Env;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;
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

    protected $posInstaller;

    /**
     * Constructor
     *
     * @return void
     */
    public function __construct()
    {
        $this->appVersion = config('author.app_version');
        $this->env = config('app.env');
        $this->posInstaller = app(PosInstaller::class);

        // Prevent accidental access to installer routes after installation.
        // Allow install.success only once right after setup completes.
        $this->middleware(function ($request, $next) {
            if ($request->routeIs('install.success')) {
                if (session('install.just_completed') === true) {
                    return $next($request);
                }

                return redirect()->route('login');
            }

            $envPath = base_path('.env');
            $pendingHash = session('install.pending_env_hash');
            $resumingWizard = $request->routeIs('install.installAlternate')
                && is_string($pendingHash)
                && is_file($envPath)
                && is_readable($envPath)
                && hash_equals($pendingHash, hash_file('sha256', $envPath))
                && ! $this->isInstalledCompletely();

            if (file_exists($envPath) && ! $resumingWizard) {
                return redirect()->route('login');
            }

            return $next($request);
        });

        //Check if mac based activation key is required or not.
        $this->macActivationKeyChecker = false;
        if (file_exists(__DIR__.'/MacActivationKeyChecker.php')) {
            include_once __DIR__.'/MacActivationKeyChecker.php';
            $this->macActivationKeyChecker = $mac_is_enabled;
        }
    }

    /**
     * Initialize all install functions
     */
    private function installSettings()
    {
        try {
            app(\App\Services\RuntimePermissions::class)->repair(base_path(), env('POS_WEB_USER'));
        } catch (\RuntimeException $exception) {
            abort(503, $exception->getMessage());
        }
        config(['app.debug' => true]);

        // Wipe stale bootstrap/cache package manifests on every installer page
        // load. This prevents "Class SentinelServiceProvider not found" from
        // a partial or failed previous install attempt.
        foreach (['packages.php', 'services.php'] as $cacheFile) {
            $path = base_path('bootstrap/cache/' . $cacheFile);
            if (file_exists($path)) {
                @unlink($path);
            }
        }

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
        $output['php'] = version_compare(PHP_VERSION, '8.0.0', '>=');
        $output['php_version'] = PHP_VERSION;

        //Check for php extensions
        $output['openssl'] = extension_loaded('openssl') ? true : false;
        $output['pdo'] = extension_loaded('pdo') && extension_loaded('pdo_mysql');
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
                    'DB_PASSWORD' => 'nullable',
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
            $input['SESSION_COOKIE'] = config('session.cookie');

            //Check for database details
            app(MySqlConnector::class)->connect(array_replace(
                config('database.connections.mysql'),
                [
                    'host' => $input['DB_HOST'],
                    'port' => $input['DB_PORT'],
                    'database' => $input['DB_DATABASE'],
                    'username' => $input['DB_USERNAME'],
                    'password' => $input['DB_PASSWORD'] ?? '',
                ]
            ));

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
                $replacement = $index.'="'.str_replace(
                    ['\\', '"', '$', "\r", "\n"],
                    ['\\\\', '\\"', '\\$', '', '\\n'],
                    (string) $value
                ).'"'.PHP_EOL;
                $found = false;
                foreach ($env_lines as $key => $line) {
                    if (preg_match('/^\s*'.preg_quote($index, '/').'\s*=/', $line)) {
                        $env_lines[$key] = $replacement;
                        $found = true;
                    }
                }
                if (! $found) {
                    $env_lines[] = $replacement;
                }
            }

            // Automatically create .env and run installation.
            // Fall back to manual copy instructions if write permission is blocked.
            $envContent = implode('', $env_lines);
            $write_success = @file_put_contents($envPath, $envContent) !== false;

            if ($write_success) {
                $this->loadWizardEnvironment();
                // Artisan commands
                $this->runArtisanCommands();

                session()->forget('install.pending_env_hash');
                session(['install.just_completed' => true]);

                return redirect()->route('install.success');
            }

            $this->deleteEnv();
            session(['install.pending_env_hash' => hash('sha256', $envContent)]);

            return view('install.envText')
                ->with(compact('envContent', 'envPath'));
        } catch (\Exception $e) {
            \Log::error('InstallController::postDetails failed', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->deleteEnv();

            return redirect()->back()
                ->with('error', 'Something went wrong, please try again!!');
        }
    }

    //Generate key, migrate and seed
    private function runArtisanCommands()
    {
        $this->posInstaller->run();
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

            $this->loadWizardEnvironment();
            $this->runArtisanCommands();

            session()->forget('install.pending_env_hash');
            session(['install.just_completed' => true]);

            return redirect()->route('install.success');
        } catch (\Exception $e) {
            \Log::error('InstallController::installAlternate failed', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->deleteEnv();

            return redirect()->back()
                ->with('error', 'Something went wrong, please try again!!');
        }
    }

    private function loadWizardEnvironment(): void
    {
        Dotenv::create(Env::getRepository(), base_path())->load();
        config([
            'database' => require config_path('database.php'),
            'app.name' => env('APP_NAME'),
            'app.url' => env('APP_URL'),
            'app.env' => env('APP_ENV'),
            'app.debug' => filter_var(env('APP_DEBUG', false), FILTER_VALIDATE_BOOLEAN),
            'app.key' => env('APP_KEY'),
        ]);
        DB::purge();
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
                    try {
                        DB::statement('SET default_storage_engine=INNODB;');
                    } catch (\Throwable $e) {
                        // Non-fatal outside MySQL/MariaDB.
                        \Log::warning('InstallController: unable to set default_storage_engine during update', [
                            'message' => $e->getMessage(),
                        ]);
                    }

                    // Run core migrations + any module migrations not yet applied
                    $this->runArtisanStep('migrate', ['--force' => true]);
                    $this->runArtisanStep('module:migrate', ['--force' => true]);

                    // Publish updated module assets
                    $this->runArtisanStep('module:publish', [], false);

                    // Ensure OAuth clients exist
                    $this->runArtisanStep('passport:install', ['--force' => true]);

                    // Re-seed permissions so new permissions from code are available
                    $this->runArtisanStep('db:seed', ['--class' => 'PermissionsTableSeeder', '--force' => true]);

                    // Reset Spatie permission cache so new permissions are immediately active
                    $this->runArtisanStep('permission:cache-reset', [], false);

                    // Rebuild package manifest before optimize to prevent stale provider errors
                    $this->runArtisanStep('package:discover', ['--ansi' => false]);

                    $this->runArtisanStep('optimize', [], false);

                    $installUtil->setSystemInfo('db_version', $this->appVersion);
                } else {
                    abort(404);
                }
            } else {
                abort(404);
            }

            $output = ['success' => 1,
                'msg' => 'Updated Succesfully to version '.$this->appVersion.' !!',
            ];

            return redirect('login')->with('status', $output);
        } catch (\Exception $e) {
            \Log::error('InstallController::update failed', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            exit($e->getMessage());
        }
    }

}
