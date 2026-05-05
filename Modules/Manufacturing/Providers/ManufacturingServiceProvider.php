<?php

namespace Modules\Manufacturing\Providers;

use Illuminate\Support\ServiceProvider;

class ManufacturingServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->mergeConfigFrom(__DIR__ . '/../Config/config.php', 'manufacturing');

        $this->publishes([
            __DIR__ . '/../Config/config.php' => config_path('manufacturing.php'),
        ], 'config');

        $langPath = resource_path('lang/modules/manufacturing');
        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, 'manufacturing');
        } else {
            $this->loadTranslationsFrom(__DIR__ . '/../Resources/lang', 'manufacturing');
        }

        $viewPath = __DIR__ . '/../Resources/views';
        if (! is_dir($viewPath)) {
            mkdir($viewPath, 0755, true);
        }
        $this->loadViewsFrom($viewPath, 'manufacturing');

        $migrationPath = __DIR__ . '/../Database/Migrations';
        if (is_dir($migrationPath)) {
            $this->loadMigrationsFrom($migrationPath);
        }

        $routes = __DIR__ . '/../Routes/web.php';
        if (file_exists($routes)) {
            $this->loadRoutesFrom($routes);
        }
    }

    public function register() {}
}
