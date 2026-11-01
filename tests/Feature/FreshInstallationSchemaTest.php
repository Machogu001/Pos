<?php

namespace Tests\Feature;

use App\Services\PosInstaller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class FreshInstallationSchemaTest extends TestCase
{
    public function test_fresh_migrations_create_the_schema_required_by_both_installers()
    {
        $this->assertIsolatedTestDatabase();

        $this->artisan('migrate', [
            '--database' => 'sqlite',
            '--force' => true,
        ])->assertExitCode(0);

        app(PosInstaller::class)->assertRequiredSchema();

        $this->assertTrue(Schema::hasColumns('transaction_payments', [
            'transaction_id',
            'business_id',
            'amount',
            'method',
            'is_return',
            'paid_on',
            'created_by',
        ]));
    }

    public function test_command_line_fresh_reinstall_recreates_the_required_schema()
    {
        $this->assertIsolatedTestDatabase();

        $options = [
            '--database' => 'sqlite',
            '--force' => true,
        ];

        $this->artisan('migrate', $options)->assertExitCode(0);
        $this->artisan('migrate:fresh', $options)->assertExitCode(0);

        app(PosInstaller::class)->assertRequiredSchema();
    }

    public function test_installer_rejects_an_incomplete_core_schema()
    {
        $this->assertIsolatedTestDatabase();

        $this->artisan('migrate', [
            '--database' => 'sqlite',
            '--force' => true,
        ])->assertExitCode(0);

        Schema::drop('transaction_payments');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('transaction_payments table');

        app(PosInstaller::class)->assertRequiredSchema();
    }

    private function assertIsolatedTestDatabase(): void
    {
        $this->assertSame('sqlite', DB::connection()->getDriverName());
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());
        $this->assertSame('sqlite', config('database.default'));
        $this->assertSame('sqlite', config('database.connections.sqlite.driver'));
    }
}
