<?php

$base = '/var/www/pos';
$targetPaths = [
    "$base/app/Http/Middleware/AdminSidebarMenu.php",
    "$base/Modules/Hrm/Resources/views",
    "$base/Modules/Essentials/Resources/views/todos",
    "$base/Modules/Crm/Resources/views/marketplace",
    "$base/Modules/Accounting/Resources/views/transfers",
    "$base/Modules/Superadmin/Resources/views",
    "$base/resources/views/subscription",
    "$base/resources/views/brand",
    "$base/resources/views/auth",
    "$base/resources/views/install",
    "$base/resources/views/sale_pos/receipts",
    "$base/resources/views/report",
    "$base/resources/views/sale_pos/create_old.blade.php",
    "$base/resources/views/sale_pos/edit_old.blade.php",
    "$base/resources/views/transaction_payment/index.blade.php",
];

function isNamespacedKey(string $value): bool
{
    return (bool) preg_match('/^[a-z][a-z0-9_]*::[a-z][a-z0-9_]*\.[a-z0-9_]+(?:\.[a-z0-9_]+)*$/', $value)
        || (bool) preg_match('/^[a-z][a-z0-9_]*\.[a-z0-9_]+(?:\.[a-z0-9_]+)*$/', $value);
}

function collectFiles(array $paths): array
{
    $files = [];
    foreach ($paths as $path) {
        if (is_file($path)) {
            $files[] = $path;
            continue;
        }
        if (! is_dir($path)) {
            continue;
        }

        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS)
        );

        foreach ($it as $f) {
            if (! $f->isFile()) {
                continue;
            }
            $name = $f->getFilename();
            if (preg_match('/\.blade\.php$|\.php$/', $name)) {
                $files[] = $f->getPathname();
            }
        }
    }

    sort($files);
    return array_values(array_unique($files));
}

$files = collectFiles($targetPaths);
$matches = [];

foreach ($files as $file) {
    $content = @file_get_contents($file);
    if ($content === false || strpos($content, "__('") === false) {
        continue;
    }

    if (! preg_match_all("/__\('((?:\\\\'|[^'])*)'\)/", $content, $all, PREG_OFFSET_CAPTURE)) {
        continue;
    }

    foreach ($all[1] as $entry) {
        $rawValue = $entry[0];
        $value = str_replace("\\'", "'", $rawValue);

        if (isNamespacedKey($value)) {
            continue;
        }

        $offset = $entry[1];
        $line = substr_count(substr($content, 0, $offset), "\n") + 1;
        $matches[] = [
            'file' => str_replace($base . '/', '', $file),
            'line' => $line,
            'value' => $value,
        ];
    }
}

echo 'FILE_COUNT=' . count($files) . PHP_EOL;
echo 'PLAIN_CALL_COUNT=' . count($matches) . PHP_EOL;

$unique = [];
foreach ($matches as $m) {
    $unique[$m['value']] = true;
}

echo 'UNIQUE_PLAIN_KEYS=' . count($unique) . PHP_EOL;

$limit = 100;
$shown = 0;
foreach ($matches as $m) {
    echo $m['file'] . ':' . $m['line'] . ' => ' . $m['value'] . PHP_EOL;
    $shown++;
    if ($shown >= $limit) {
        break;
    }
}
