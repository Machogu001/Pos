<?php

namespace App\Services;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;

class RuntimePermissions
{
    public function repair(string $root, ?string $webUser = null): void
    {
        $root = realpath($root);
        if ($root === false) {
            throw new RuntimeException('The application directory does not exist.');
        }

        $account = null;
        if ($webUser !== null && $webUser !== '') {
            if (! function_exists('posix_getpwnam') || ! ($account = posix_getpwnam($webUser))) {
                throw new RuntimeException('POS_WEB_USER must name an existing PHP worker account on this server.');
            }
        } elseif (function_exists('posix_geteuid') && posix_geteuid() === 0) {
            throw new RuntimeException('Root setup requires POS_WEB_USER or --web-user to identify the PHP worker account.');
        }

        // Only runtime paths are writable; application code, .env and OAuth keys are not changed.
        $paths = [
            'storage',
            'storage/framework',
            'storage/framework/cache',
            'storage/framework/cache/data',
            'storage/framework/sessions',
            'storage/framework/views',
            'storage/framework/testing',
            'storage/logs',
            'storage/app',
            'storage/app/public',
            'bootstrap/cache',
            'public/uploads',
            'public/uploads/temp',
        ];
        $trees = ['storage/framework', 'storage/logs', 'storage/app/public', 'bootstrap/cache', 'public/uploads'];
        foreach ($paths as $relative) {
            $path = $root.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $relative);
            $this->rejectLinkedParents($root, $path);
            if (! is_dir($path) && ! mkdir($path, 02775, true) && ! is_dir($path)) {
                $this->failed($path);
            }
            $this->permissions($path, true, $account);
        }
        foreach ($trees as $relative) {
            $path = $root.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $relative);
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
                RecursiveIteratorIterator::SELF_FIRST
            );
            foreach ($iterator as $entry) {
                if ($entry->isLink()) {
                    throw new RuntimeException('Runtime permission repair refuses symbolic links: '.$entry->getPathname());
                }
                if (! $entry->isDir() && ! $entry->isFile()) {
                    throw new RuntimeException('Unsupported runtime filesystem entry: '.$entry->getPathname());
                }
                $this->permissions($entry->getPathname(), $entry->isDir(), $account);
            }
        }
        foreach ($paths as $relative) {
            $path = $root.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $relative);
            $probe = $path.DIRECTORY_SEPARATOR.'.pos-write-probe-'.bin2hex(random_bytes(8));
            $file = fopen($probe, 'x+b');
            if ($file === false) {
                $this->failed($path);
            }
            try {
                if (! flock($file, LOCK_EX | LOCK_NB) || fwrite($file, 'POS') !== 3) {
                    $this->failed($path);
                }
            } finally {
                fclose($file);
                if (! unlink($probe)) {
                    $this->failed($probe);
                }
            }
        }
    }

    private function rejectLinkedParents(string $root, string $path): void
    {
        while ($path !== $root) {
            if (is_link($path)) {
                throw new RuntimeException('Runtime permission repair refuses symbolic links: '.$path);
            }
            $path = dirname($path);
        }
    }

    private function permissions(string $path, bool $directory, ?array $account): void
    {
        if (PHP_OS_FAMILY === 'Windows') {
            if (! is_writable($path)) {
                $this->failed($path);
            }
            return;
        }
        clearstatcache(true, $path);
        if ($account !== null) {
            if (function_exists('posix_geteuid') && posix_geteuid() === 0
                && fileowner($path) !== $account['uid'] && ! chown($path, $account['uid'])) {
                $this->failed($path);
            }
            if (filegroup($path) !== $account['gid'] && ! chgrp($path, $account['gid'])) {
                $this->failed($path);
            }
        }
        $mode = $directory ? 02775 : 0664;
        clearstatcache(true, $path);
        if ((fileperms($path) & 07777) !== $mode && ! chmod($path, $mode)) {
            $this->failed($path);
        }
        clearstatcache(true, $path);
        if (! is_writable($path)) {
            $this->failed($path);
        }
    }

    private function failed(string $path): void
    {
        throw new RuntimeException('Cannot repair/write runtime path: '.$path
            .'. Run scripts/repair_runtime_permissions.php as an administrator with the actual PHP worker username; do not use chmod 777.');
    }
}
