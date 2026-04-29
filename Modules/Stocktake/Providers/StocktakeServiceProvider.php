<?php

namespace Modules\Stocktake\Providers;

use Illuminate\Support\ServiceProvider;

class StocktakeServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        // If module-specific routes exist, load them. We intentionally don't
        // move the existing app routes; this provider is lightweight and
        // will load module resources if/when they are added under Modules/Stocktake.
        $routes = __DIR__ . '/../Routes/web.php';
        if (file_exists($routes)) {
            $this->loadRoutesFrom($routes);
        }

        $views = __DIR__ . '/../../Resources/views';
        if (is_dir($views)) {
            $this->loadViewsFrom($views, 'stocktake');
        }

        $migrations = __DIR__ . '/../../Database/Migrations';
        if (is_dir($migrations)) {
            $this->loadMigrationsFrom($migrations);
        }
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        // Register any bindings here if required in future
    }
}
