<?php

namespace App\Providers;

use App\Business;
use App\Observers\TransactionSellLineObserver;
use App\Observers\TransactionObserver;
use App\Transaction;
use App\TransactionSellLine;
use App\System;
use App\Utils\ModuleUtil;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\Filesystem;
use Spatie\Dropbox\Client as DropboxClient;
use Spatie\FlysystemDropbox\DropboxAdapter;

use Laravel\Passport\Console\ClientCommand;
use Laravel\Passport\Console\InstallCommand;
use Laravel\Passport\Console\KeysCommand;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Transaction::observe(TransactionObserver::class);
        TransactionSellLine::observe(TransactionSellLineObserver::class);

        // Auto-repair storage/logs permissions so the web server can always
        // write log files even if a CLI command created them as a different user.
        // is_writable() is a single stat call — cheap enough to run every boot.
        $this->repairStoragePermissions();

        ini_set('memory_limit', '-1');
        set_time_limit(0);

        if (config('app.debug')) {
            error_reporting(E_ALL & ~E_USER_DEPRECATED);
        } else {
            error_reporting(0);
        }

        //force https
        $url = parse_url(config('app.url'));

        if ($url['scheme'] == 'https') {
            \URL::forceScheme('https');
        }

        if (request()->has('lang')) {
            \App::setLocale(request()->get('lang'));
        }

        //In Laravel 5.6, Blade will double encode special characters by default. If you would like to maintain the previous behavior of preventing double encoding, you may add Blade::withoutDoubleEncoding() to your AppServiceProvider boot method.
        Blade::withoutDoubleEncoding();

        //Laravel 5.6 uses Bootstrap 4 by default. Shift did not update your front-end resources or dependencies as this could impact your UI. If you are using Bootstrap and wish to continue using Bootstrap 3, you should add Paginator::useBootstrapThree() to your AppServiceProvider boot method.
        Paginator::useBootstrapThree();

        \Illuminate\Pagination\Paginator::useBootstrap();

        // Dropbox service provider
        Storage::extend('dropbox', function ($app, $config) {
            $adapter = new DropboxAdapter(new DropboxClient(
                $config['authorization_token']
            ));
 
            return new FilesystemAdapter(
                new Filesystem($adapter, $config),
                $adapter,
                $config
            );
        });


        $asset_v = config('constants.asset_version', 1);
        View::share('asset_v', $asset_v);

        // Share the list of modules enabled in sidebar
        View::composer(
            ['*'],
            function ($view) {
                $normalizeModules = function ($modules) {
                    if (is_string($modules)) {
                        $decoded = json_decode($modules, true);
                        $modules = is_array($decoded) ? $decoded : [];
                    }

                    return is_array($modules) ? array_values(array_unique($modules)) : [];
                };

                $sessionModules = $normalizeModules(session('business.enabled_modules'));
                $businessId = session('business.id') ?? optional(Auth::user())->business_id;
                $dbModules = [];

                if (! empty($businessId)) {
                    $dbModules = $normalizeModules(Business::where('id', $businessId)->value('enabled_modules'));
                }

                $enabled_modules = array_values(array_unique(array_merge($sessionModules, $dbModules)));

                $__is_pusher_enabled = isPusherEnabled();

                if (! Auth::check()) {
                    $__is_pusher_enabled = false;
                }

                $view->with('enabled_modules', $enabled_modules);
                $view->with('__is_pusher_enabled', $__is_pusher_enabled);
            }
        );

        View::composer(
            ['layouts.*'],
            function ($view) {
                if (isAppInstalled()) {
                    $keys = ['additional_js', 'additional_css'];
                    $__system_settings = System::getProperties($keys, true);

                    //Get js,css from modules
                    $moduleUtil = new ModuleUtil;
                    $module_additional_script = $moduleUtil->getModuleData('get_additional_script');
                    $additional_views = [];
                    $additional_html = '';
                    foreach ($module_additional_script as $key => $value) {
                        if (! empty($value['additional_js'])) {
                            if (isset($__system_settings['additional_js'])) {
                                $__system_settings['additional_js'] .= $value['additional_js'];
                            } else {
                                $__system_settings['additional_js'] = $value['additional_js'];
                            }
                        }
                        if (! empty($value['additional_css'])) {
                            if (isset($__system_settings['additional_css'])) {
                                $__system_settings['additional_css'] .= $value['additional_css'];
                            } else {
                                $__system_settings['additional_css'] = $value['additional_css'];
                            }
                        }
                        if (! empty($value['additional_html'])) {
                            $additional_html .= $value['additional_html'];
                        }
                        if (! empty($value['additional_views'])) {
                            $additional_views = array_merge($additional_views, $value['additional_views']);
                        }
                    }

                    $view->with('__additional_views', $additional_views);
                    $view->with('__additional_html', $additional_html);
                    $view->with('__system_settings', $__system_settings);
                }
            }
        );

        //This will fix "Specified key was too long; max key length is 767 bytes issue during migration"
        Schema::defaultStringLength(191);

        //Blade directive to format number into required format.
        Blade::directive('num_format', function ($expression) {
            return "number_format($expression, session('business.currency_precision', 2), session('currency')['decimal_separator'], session('currency')['thousand_separator'])";
        });

        //Blade directive to format quantity values into required format.
        Blade::directive('format_quantity', function ($expression) {
            return "number_format($expression, session('business.quantity_precision', 2), session('currency')['decimal_separator'], session('currency')['thousand_separator'])";
        });

        //Blade directive to return appropiate class according to transaction status
        Blade::directive('transaction_status', function ($status) {
            return "<?php if($status == 'ordered'){
                echo 'bg-aqua';
            }elseif($status == 'pending'){
                echo 'bg-red';
            }elseif ($status == 'received') {
                echo 'bg-light-green';
            }?>";
        });

        //Blade directive to return appropiate class according to transaction status
        Blade::directive('payment_status', function ($status) {
            return "<?php if($status == 'partial'){
                echo 'bg-aqua';
            }elseif($status == 'due'){
                echo 'bg-yellow';
            }elseif ($status == 'paid') {
                echo 'bg-light-green';
            }elseif ($status == 'overdue') {
                echo 'bg-red';
            }elseif ($status == 'partial-overdue') {
                echo 'bg-red';
            }?>";
        });

        //Blade directive to display help text.
        Blade::directive('show_tooltip', function ($message) {
            return "<?php
                if((int) session('business.enable_tooltip', 1) === 1){
                    echo '<i class=\"fa fa-info-circle text-info hover-q no-print \" aria-hidden=\"true\" 
                    data-container=\"body\" data-toggle=\"popover\" data-placement=\"auto bottom\" 
                    data-content=\"' . $message . '\" data-html=\"true\" data-trigger=\"hover\"></i>';
                }
                ?>";
        });

        //Blade directive to convert.
        Blade::directive('format_date', function ($date) {
            if (! empty($date)) {
                return "\Carbon::createFromTimestamp(strtotime($date))->format(session('business.date_format'))";
            } else {
                return null;
            }
        });

        //Blade directive to convert.
        Blade::directive('format_time', function ($date) {
            if (! empty($date)) {
                return "\\Carbon::createFromTimestamp(strtotime($date))->format('H:i:s')";
            } else {
                return null;
            }
        });

        Blade::directive('format_datetime', function ($date) {
            if (! empty($date)) {
                return "\\Carbon::createFromTimestamp(strtotime($date))->format(session('business.date_format') . ' H:i:s')";
            } else {
                return null;
            }
        });

        //Blade directive to format currency.
        Blade::directive('format_currency', function ($number) {
            return '<?php 
            $formated_number = "";
            if (session("business.currency_symbol_placement") == "before") {
                $formated_number .= session("currency")["symbol"] . " ";
            } 
            $formated_number .= number_format((float) '.$number.', session("business.currency_precision", 2) , session("currency")["decimal_separator"], session("currency")["thousand_separator"]);

            if (session("business.currency_symbol_placement") == "after") {
                $formated_number .= " " . session("currency")["symbol"];
            }
            echo $formated_number; ?>';
        });

        $this->registerCommands();
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    /**
     * Silently ensure storage/logs and its files are writable by the current
     * process. Handles the common case where artisan (run as a deploy user)
     * creates log files that the web server user cannot append to.
     */
    private function repairStoragePermissions(): void
    {
        try {
            $logsDir = storage_path('logs');
            $fallbackToStderr = false;

            // Fix directory if not writable
            if (is_dir($logsDir) && ! is_writable($logsDir)) {
                @chmod($logsDir, 02775); // setgid + rwxrwxr-x
            }

            if (! is_dir($logsDir) || ! is_writable($logsDir)) {
                $fallbackToStderr = true;
            }

            // Fix any existing log files that aren't writable
            if (is_dir($logsDir)) {
                foreach (glob($logsDir . '/*.log') ?: [] as $logFile) {
                    if (is_file($logFile) && ! is_writable($logFile)) {
                        @chmod($logFile, 0664);
                    }
                }

                $dailyLog = $logsDir . '/laravel-' . date('Y-m-d') . '.log';
                if (! file_exists($dailyLog)) {
                    @touch($dailyLog);
                    @chmod($dailyLog, 0664);
                }

                if (! $this->canAppendToFile($dailyLog)) {
                    $fallbackToStderr = true;
                }
            }

            if ($fallbackToStderr) {
                // Prevent fatal logging exceptions from blocking install/migrate.
                config([
                    'logging.default' => 'stderr',
                    'logging.channels.stack.channels' => ['stderr'],
                    'logging.channels.stack.ignore_exceptions' => true,
                ]);
            }
        } catch (\Throwable $e) {
            // Non-fatal — never let a permission check break the app
        }
    }

    /**
     * Verify append access without throwing.
     */
    private function canAppendToFile(string $path): bool
    {
        try {
            $handle = @fopen($path, 'ab');
            if (! is_resource($handle)) {
                return false;
            }
            fclose($handle);
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function register()
    {
        //
    }

    /**
     * Register commands.
     *
     * @return void
     */
    protected function registerCommands()
    {
        $this->commands([
            InstallCommand::class,
            ClientCommand::class,
            KeysCommand::class,
        ]);
    }
}
