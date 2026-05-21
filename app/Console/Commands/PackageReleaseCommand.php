<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use ZipArchive;

/**
 * pos:package-release
 *
 * Packages the current codebase into a distributable zip archive stored at
 * storage/app/releases/v{version}.zip and writes a manifest.json alongside it.
 *
 * Run this on the CENTRAL server after deploying new code.
 * The resulting package is served by UpdateController::downloadPackage() to client servers.
 */
class PackageReleaseCommand extends Command
{
    protected $signature   = 'pos:package-release {--pkg-version= : Override the version string} {--force : Overwrite existing package without prompting}';
    protected $description = 'Package the current codebase into a distributable release zip.';

    private const EXCLUDE = [
        '.env',
        '.env.backup',
        '.env.example',
        '.env.production',
        '.git/',
        '.gitignore',
        '.gitattributes',
        'vendor/',
        'node_modules/',
        'storage/app/',
        'storage/logs/',
        'storage/framework/sessions/',
        'storage/framework/cache/',
        'bootstrap/cache/',
        'public/uploads/',
        'public/hot',
        '.DS_Store',
        'Thumbs.db',
    ];

    public function handle(): int
    {
        $version = $this->option('pkg-version') ?: config('author.app_version', '0');
        $outDir  = storage_path('app/releases');
        $zipFile = "{$outDir}/v{$version}.zip";

        @mkdir($outDir, 0775, true);

        if (file_exists($zipFile)) {
            if (! $this->option('force') && ! $this->confirm("v{$version}.zip already exists — overwrite?", false)) {
                $this->info('Aborted.');
                return self::SUCCESS;
            }
            unlink($zipFile);
        }

        $this->line("==> Packaging v{$version} → {$zipFile}");

        $zip = new ZipArchive;
        if ($zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            $this->error("Cannot create {$zipFile}");
            return self::FAILURE;
        }

        $base  = rtrim(base_path(), '/');
        $count = 0;

        $it = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(
                $base,
                \RecursiveDirectoryIterator::SKIP_DOTS
            ),
            \RecursiveIteratorIterator::SELF_FIRST,
            \RecursiveIteratorIterator::CATCH_GET_CHILD
        );

        foreach ($it as $item) {
            try {
                $path = $item->getPathname();
            } catch (\Throwable $e) {
                continue;
            }

            $rel = ltrim(substr($path, strlen($base)), '/');

            if ($this->isExcluded($rel)) {
                continue;
            }

            if ($item->isDir()) {
                $zip->addEmptyDir($rel);
                continue;
            }

            if (! is_readable($path)) {
                $this->warn("Skipping unreadable file: {$rel}");
                continue;
            }

            if ($zip->addFile($path, $rel)) {
                $count++;
            }
        }

        $zip->close();

        $sha256 = hash_file('sha256', $zipFile);
        $sizeKb = round(filesize($zipFile) / 1024);

        // Write manifest so the release-info endpoint can serve it.
        $manifest = [
            'version'     => $version,
            'filename'    => "v{$version}.zip",
            'sha256'      => $sha256,
            'size_kb'     => $sizeKb,
            'packaged_at' => now()->toIso8601String(),
        ];
        file_put_contents("{$outDir}/manifest.json", json_encode($manifest, JSON_PRETTY_PRINT));

        $this->info("==> Done. {$count} files · {$sizeKb} KB");
        $this->line("    SHA256 : {$sha256}");
        $this->line("    Manifest written to storage/app/releases/manifest.json");

        return self::SUCCESS;
    }

    private function isExcluded(string $rel): bool
    {
        foreach (self::EXCLUDE as $pattern) {
            if (str_ends_with($pattern, '/')) {
                if (str_starts_with($rel, $pattern) || $rel === rtrim($pattern, '/')) {
                    return true;
                }
            } elseif (str_contains($pattern, '*')) {
                if (fnmatch($pattern, basename($rel))) {
                    return true;
                }
            } else {
                if ($rel === $pattern || str_starts_with($rel, $pattern . '/')) {
                    return true;
                }
            }
        }
        return false;
    }
}
