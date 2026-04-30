<?php

namespace Modules\Repair\Providers;

use Illuminate\Support\ServiceProvider;

class RepairServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $routes = __DIR__ . '/../Routes/web.php';
        if (file_exists($routes)) {
            $this->loadRoutesFrom($routes);
        }
        $views = __DIR__ . '/../Resources/views';
        if (!is_dir($views)) { mkdir($views, 0755, true); }
        $this->loadViewsFrom($views, 'repair');
        $migrations = __DIR__ . '/../Database/Migrations';
        if (is_dir($migrations)) {
            $this->loadMigrationsFrom($migrations);
        }
        $lang = __DIR__ . '/../Resources/lang';
        if (!is_dir($lang)) { mkdir($lang, 0755, true); }
        $this->loadTranslationsFrom($lang, 'repair');
    }

    public function register() {}
}
