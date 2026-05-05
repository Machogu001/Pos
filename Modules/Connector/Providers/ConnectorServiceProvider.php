<?php

namespace Modules\Connector\Providers;

use Illuminate\Support\ServiceProvider;

class ConnectorServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->mergeConfigFrom(__DIR__ . '/../Config/config.php', 'connector');

        $this->publishes([
            __DIR__ . '/../Config/config.php' => config_path('connector.php'),
        ], 'config');

        $langPath = resource_path('lang/modules/connector');
        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, 'connector');
        } else {
            $this->loadTranslationsFrom(__DIR__ . '/../Resources/lang', 'connector');
        }

        $viewPath = __DIR__ . '/../Resources/views';
        if (! is_dir($viewPath)) {
            mkdir($viewPath, 0755, true);
        }
        $this->loadViewsFrom($viewPath, 'connector');

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
