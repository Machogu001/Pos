<?php

namespace Tests\Feature;

use App\Http\Controllers\Install\InstallController;
use App\Services\PosInstaller;
use App\Services\RuntimePermissions;
use Dotenv\Repository\Adapter\ArrayAdapter;
use Dotenv\Repository\RepositoryBuilder;
use Illuminate\Database\Connectors\MySqlConnector;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Env;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Tests\TestCase;

class InstallationEntryPointsTest extends TestCase
{
    private string $fixtureRoot;
    private string $originalRoot;

    protected function setUp(): void
    {
        parent::setUp();
        $this->originalRoot = base_path();
        $this->fixtureRoot = sys_get_temp_dir().'/pos-install-'.bin2hex(random_bytes(8));
        mkdir($this->fixtureRoot);
        $this->app->setBasePath($this->fixtureRoot);
        session()->flush();
    }

    protected function tearDown(): void
    {
        $this->app->setBasePath($this->originalRoot);
        if (is_file($this->fixtureRoot.'/.env')) {
            unlink($this->fixtureRoot.'/.env');
        }
        if (is_file($this->fixtureRoot.'/.env.example')) {
            unlink($this->fixtureRoot.'/.env.example');
        }
        if (is_file($this->fixtureRoot.'/config/database.php')) {
            unlink($this->fixtureRoot.'/config/database.php');
            rmdir($this->fixtureRoot.'/config');
        }
        rmdir($this->fixtureRoot);
        parent::tearDown();
    }

    public function test_new_wizard_requests_are_allowed_without_running_setup_in_the_constructor(): void
    {
        $this->assertSame('allowed', $this->runInstallerGate('install.index'));
    }

    public function test_existing_environment_cannot_reopen_the_installer(): void
    {
        file_put_contents($this->fixtureRoot.'/.env', 'APP_NAME=Existing');

        $response = $this->runInstallerGate('install.index');

        $this->assertSame(route('login'), $response->getTargetUrl());
    }

    public function test_manual_environment_can_only_resume_the_matching_wizard_session(): void
    {
        $content = 'APP_NAME=New';
        file_put_contents($this->fixtureRoot.'/.env', $content);
        session(['install.pending_env_hash' => hash('sha256', $content)]);
        Schema::shouldReceive('hasTable')->once()->with('users')->andReturn(false);

        $this->assertSame('allowed', $this->runInstallerGate('install.installAlternate'));
    }

    public function test_mismatched_environment_is_not_allowed_to_resume(): void
    {
        file_put_contents($this->fixtureRoot.'/.env', 'APP_NAME=Existing');
        session(['install.pending_env_hash' => hash('sha256', 'APP_NAME=New')]);

        $response = $this->runInstallerGate('install.installAlternate');

        $this->assertSame(route('login'), $response->getTargetUrl());
    }

    public function test_completed_installation_cannot_be_resumed_with_a_pending_session(): void
    {
        $content = 'APP_NAME=New';
        file_put_contents($this->fixtureRoot.'/.env', $content);
        session(['install.pending_env_hash' => hash('sha256', $content)]);
        Schema::shouldReceive('hasTable')->with('users')->once()->andReturn(true);
        Schema::shouldReceive('hasTable')->with('business')->once()->andReturn(true);
        Schema::shouldReceive('hasTable')->with('admin_settings')->once()->andReturn(true);

        $response = $this->runInstallerGate('install.installAlternate');

        $this->assertSame(route('login'), $response->getTargetUrl());
    }

    /**
     * @dataProvider wizardPaths
     */
    public function test_web_installer_loads_new_database_settings_and_uses_the_shared_workflow(bool $automatic): void
    {
        mkdir($this->fixtureRoot.'/config');
        copy($this->originalRoot.'/config/database.php', $this->fixtureRoot.'/config/database.php');
        $environment = implode("\n", [
            'APP_NAME="Wizard POS"',
            'APP_TITLE="APP_NAME"',
            'APP_ENV=live',
            'APP_DEBUG=false',
            'DB_CONNECTION=mysql',
            'DB_HOST=installer-db',
            'DB_DATABASE=wizard_database',
            'DB_USERNAME=wizard_user',
            'DB_PASSWORD="test-only-password"',
        ])."\n";
        $sessionCookie = config('session.cookie');
        $password = $automatic ? 'test-"quoted"$dollar\\path#end' : 'test-only-password';
        if ($automatic) {
            file_put_contents($this->fixtureRoot.'/.env.example', $environment);
            $connector = Mockery::mock(MySqlConnector::class);
            $connector->shouldReceive('connect')->once()->withArgs(function (array $config) {
                return $config['host'] === 'installer-db'
                    && $config['database'] === 'wizard_database'
                    && $config['username'] === 'wizard_user';
            });
            $this->app->instance(MySqlConnector::class, $connector);
        } else {
            file_put_contents($this->fixtureRoot.'/.env', $environment);
        }

        $permissions = Mockery::mock(RuntimePermissions::class);
        $permissions->shouldReceive('repair')->once()->with($this->fixtureRoot, null);
        $this->app->instance(RuntimePermissions::class, $permissions);
        Artisan::shouldReceive('call')->once()->with('config:clear')->andReturn(0);
        Artisan::shouldReceive('call')->once()->with('cache:clear')->andReturn(0);
        DB::shouldReceive('purge')->once();

        $installer = Mockery::mock(PosInstaller::class);
        $installer->shouldReceive('run')->once()->withNoArgs()->andReturnUsing(function () use ($password) {
            $this->assertSame('mysql', config('database.default'));
            $this->assertSame('installer-db', config('database.connections.mysql.host'));
            $this->assertSame('wizard_database', config('database.connections.mysql.database'));
            $this->assertSame('wizard_user', config('database.connections.mysql.username'));
            $this->assertSame($password, config('database.connections.mysql.password'));
            $this->assertSame('Wizard POS', config('app.name'));
            $this->assertFalse(config('app.debug'));
        });
        $this->app->instance(PosInstaller::class, $installer);

        $repositoryProperty = new \ReflectionProperty(Env::class, 'repository');
        $repositoryProperty->setAccessible(true);
        $originalRepository = Env::getRepository();
        $repositoryProperty->setValue(null, RepositoryBuilder::createWithNoAdapters()
            ->addAdapter(ArrayAdapter::class)->immutable()->make());

        try {
            $controller = new InstallController;
            if ($automatic) {
                $response = $controller->postDetails(Request::create('/install/post-details', 'POST', [
                    'APP_NAME' => 'Wizard POS',
                    'DB_HOST' => 'installer-db',
                    'DB_PORT' => '3306',
                    'DB_DATABASE' => 'wizard_database',
                    'DB_USERNAME' => 'wizard_user',
                    'DB_PASSWORD' => $password,
                    'ENVATO_PURCHASE_CODE' => 'test-only-code',
                ]));
                $values = \Dotenv\Dotenv::parse(file_get_contents($this->fixtureRoot.'/.env'));
                $this->assertSame($sessionCookie, $values['SESSION_COOKIE']);
                $this->assertSame('APP_NAME', $values['APP_TITLE']);
            } else {
                $response = $controller->installAlternate(Request::create('/install/install-alternate', 'POST'));
            }

            $this->assertSame(route('install.success'), $response->getTargetUrl());
            $this->assertTrue(session('install.just_completed'));
        } finally {
            $repositoryProperty->setValue(null, $originalRepository);
        }
    }

    public function wizardPaths(): array
    {
        return [
            'automatic environment creation' => [true],
            'manual environment continuation' => [false],
        ];
    }

    public function test_success_page_is_available_once_after_installation(): void
    {
        file_put_contents($this->fixtureRoot.'/.env', 'APP_NAME=New');
        session(['install.just_completed' => true]);
        $this->assertSame('allowed', $this->runInstallerGate('install.success'));
        session()->forget('install.just_completed');

        $response = $this->runInstallerGate('install.success');
        $this->assertSame(route('login'), $response->getTargetUrl());
    }

    /**
     * @dataProvider cliPaths
     */
    public function test_cli_installer_uses_the_shared_workflow(bool $fresh): void
    {
        file_put_contents($this->fixtureRoot.'/.env', 'APP_NAME=New');
        $installer = Mockery::mock(PosInstaller::class);
        $installer->shouldReceive('validateEnvironmentConfig')->once()->andReturn([]);
        $installer->shouldReceive('run')->once()->with($fresh, Mockery::type('callable'));
        $this->app->instance(PosInstaller::class, $installer);

        $this->artisan('pos:install', ['--fresh' => $fresh, '--force' => true])->assertExitCode(0);
    }

    public function cliPaths(): array
    {
        return [
            'new installation' => [false],
            'explicit fresh reinstall' => [true],
        ];
    }

    public function test_cli_installer_reports_shared_workflow_failure(): void
    {
        file_put_contents($this->fixtureRoot.'/.env', 'APP_NAME=New');
        $installer = Mockery::mock(PosInstaller::class);
        $installer->shouldReceive('validateEnvironmentConfig')->once()->andReturn([]);
        $installer->shouldReceive('run')->once()->andThrow(new \RuntimeException('Runtime path is not writable.'));
        $this->app->instance(PosInstaller::class, $installer);

        $this->artisan('pos:install', ['--force' => true])
            ->expectsOutput('Installation failed: Runtime path is not writable.')
            ->assertExitCode(1);
    }

    private function runInstallerGate(string $routeName)
    {
        $controller = new InstallController;
        $request = Request::create('/install');
        $route = (new Route('GET', '/install', []))->name($routeName);
        $request->setRouteResolver(fn () => $route);
        $request->setLaravelSession(session()->driver());
        $middleware = $controller->getMiddleware()[0]['middleware'];

        return $middleware($request, fn () => 'allowed');
    }
}
