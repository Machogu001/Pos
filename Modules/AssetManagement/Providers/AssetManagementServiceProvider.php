<?php

namespace Modules\AssetManagement\Providers;

use Illuminate\Support\ServiceProvider;

class AssetManagementServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $routes = __DIR__ . '/../Routes/web.php';
        if (file_exists($routes)) {
            $this->loadRoutesFrom($routes);
        }
        $views = __DIR__ . '/../Resources/views';
        if (!is_dir($views)) { mkdir($views, 0755, true); }
        $this->loadViewsFrom($views, 'assetmanagement');
        $migrations = __DIR__ . '/../Database/Migrations';
        if (is_dir($migrations)) {
            $this->loadMigrationsFrom($migrations);
        }
    }

    public function register() {}
}
