<?php

namespace Tests\Unit;

use App\Services\RuntimePermissions;
use PHPUnit\Framework\TestCase;

class RuntimePermissionsTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir().DIRECTORY_SEPARATOR.'pos-permissions-'.bin2hex(random_bytes(8));
        mkdir($this->root);
    }

    protected function tearDown(): void
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->root, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($iterator as $entry) {
            if ($entry->isDir() && ! $entry->isLink()) {
                rmdir($entry->getPathname());
            } else {
                unlink($entry->getPathname());
            }
        }
        rmdir($this->root);
    }

    public function testRepairsNestedCacheWithoutDeletingIdempotencyData(): void
    {
        $nested = $this->root.'/storage/framework/cache/data/60/2c';
        mkdir($nested, 0755, true);
        file_put_contents($nested.'/sale-lock', 'confirmed-payment');
        $service = new RuntimePermissions();
        $service->repair($this->root, $this->currentUser());
        $service->repair($this->root, $this->currentUser());
        $this->assertSame('confirmed-payment', file_get_contents($nested.'/sale-lock'));
        $this->assertDirectoryExists($this->root.'/bootstrap/cache');
        $this->assertDirectoryExists($this->root.'/public/uploads/temp');
        if (PHP_OS_FAMILY !== 'Windows') {
            clearstatcache();
            $this->assertSame(02775, fileperms($nested) & 07777);
            $this->assertSame(0664, fileperms($nested.'/sale-lock') & 07777);
        }
        $this->assertSame([], glob($this->root.'/storage/framework/.pos-write-probe-*'));
    }

    public function testDoesNotChangePrivateKeysOrApplicationCode(): void
    {
        mkdir($this->root.'/storage');
        foreach (['.env', 'storage/oauth-private.key', 'artisan'] as $file) {
            file_put_contents($this->root.'/'.$file, 'private');
            chmod($this->root.'/'.$file, 0600);
        }
        $before = fileperms($this->root.'/storage/oauth-private.key');
        (new RuntimePermissions())->repair($this->root, $this->currentUser());
        clearstatcache();
        $this->assertSame($before, fileperms($this->root.'/storage/oauth-private.key'));
        $this->assertSame('private', file_get_contents($this->root.'/storage/oauth-private.key'));
        if (PHP_OS_FAMILY !== 'Windows') {
            $this->assertSame(0600, fileperms($this->root.'/.env') & 07777);
            $this->assertSame(0600, fileperms($this->root.'/artisan') & 07777);
        }
    }

    public function testRejectsUnknownWorkerBeforeChangingFiles(): void
    {
        $this->expectException(\RuntimeException::class);
        (new RuntimePermissions())->repair($this->root, 'pos-nonexistent-'.bin2hex(random_bytes(8)));
    }

    public function testDoesNotFollowRuntimeSymlink(): void
    {
        if (PHP_OS_FAMILY === 'Windows') {
            $this->markTestSkipped('Creating symbolic links requires Windows administrator privileges.');
        }
        mkdir($this->root.'/outside');
        mkdir($this->root.'/storage');
        symlink($this->root.'/outside', $this->root.'/storage/framework');
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('refuses symbolic links');
        (new RuntimePermissions())->repair($this->root, $this->currentUser());
    }

    private function currentUser(): ?string
    {
        return function_exists('posix_geteuid') ? posix_getpwuid(posix_geteuid())['name'] : null;
    }
}
