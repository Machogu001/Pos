<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Tests\Support\TestSQLiteConnection;

trait CreatesApplication
{
    /**
     * Creates the application.
     *
     * @return \Illuminate\Foundation\Application
     */
    public function createApplication()
    {
        $testingConfigCache = __DIR__.'/../bootstrap/cache/config-testing.php';
        putenv('APP_CONFIG_CACHE='.$testingConfigCache);
        $_ENV['APP_CONFIG_CACHE'] = $testingConfigCache;
        $_SERVER['APP_CONFIG_CACHE'] = $testingConfigCache;

        $app = require __DIR__.'/../bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        // Keep database isolation explicit even if a custom test bootstrap
        // introduces cached configuration later.
        $app['config']->set('app.env', 'testing');
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite.database', ':memory:');
        DB::purge();

        // Register SQLite-compatible connection that silently skips MySQL-specific
        // ALTER TABLE clauses (MODIFY, CHANGE) unsupported by SQLite.
        DB::extend('sqlite', function (array $config, string $name) {
            $connector = new \Illuminate\Database\Connectors\SQLiteConnector();
            $pdo = $connector->connect($config);
            $conn = new TestSQLiteConnection($pdo, $config['database'], $config['prefix'] ?? '', $config);
            $conn->setReadPdo($pdo);
            return $conn;
        });

        return $app;
    }
}
