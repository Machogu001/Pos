<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\Connection;
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

        // Register SQLite compatibility globally so nested Artisan migration
        // commands cannot replace it with a connection using cached settings.
        Connection::resolverFor('sqlite', function ($connection, $database, $prefix, $config) {
            return new TestSQLiteConnection($connection, $database, $prefix, $config);
        });

        $connection = DB::connection();
        if ($connection->getDriverName() !== 'sqlite' || $connection->getDatabaseName() !== ':memory:') {
            throw new \RuntimeException(
                'Tests must use the isolated in-memory SQLite database. Refusing to run against a configured application database.'
            );
        }

        return $app;
    }
}
