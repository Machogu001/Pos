<?php

namespace Modules\Hrm\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Routing\Router;

class HrmServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        $this->app->make(Router::class)->aliasMiddleware(
            'hrm.enabled',
            \Modules\Hrm\Http\Middleware\EnsureHrmModuleEnabled::class
        );

        // Load routes for the module (if present)
        $routes = __DIR__ . '/../Routes/web.php';
        if (file_exists($routes)) {
            $this->loadRoutesFrom($routes);
        }
        // Load translations
        $lang = __DIR__ . '/../Resources/lang';
        if (is_dir($lang)) {
            $this->loadTranslationsFrom($lang, 'hrm');
        }

        // Load views
        $views = __DIR__ . '/../Resources/views';
        if (!is_dir($views)) { mkdir($views, 0755, true); }
        $this->loadViewsFrom($views, 'hrm');

        // Load migrations
        $migrations = __DIR__ . '/../Database/Migrations';
        if (is_dir($migrations)) {
            $this->loadMigrationsFrom($migrations);
        }
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        // Register bindings or other module services here if needed
    }
}
