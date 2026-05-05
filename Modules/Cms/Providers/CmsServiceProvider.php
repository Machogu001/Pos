<?php

namespace Modules\Cms\Providers;

use Illuminate\Support\ServiceProvider;

class CmsServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->mergeConfigFrom(__DIR__ . '/../Config/config.php', 'cms');

        $this->publishes([
            __DIR__ . '/../Config/config.php' => config_path('cms.php'),
        ], 'config');

        $langPath = resource_path('lang/modules/cms');
        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, 'cms');
        } else {
            $this->loadTranslationsFrom(__DIR__ . '/../Resources/lang', 'cms');
        }

        $viewPath = __DIR__ . '/../Resources/views';
        if (! is_dir($viewPath)) {
            mkdir($viewPath, 0755, true);
        }
        $this->loadViewsFrom($viewPath, 'cms');

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
