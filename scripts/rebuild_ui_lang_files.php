<?php

/**
 * Rebuild locale ui.php files from all ui.* keys used in views/PHP files.
 */

$base = '/var/www/pos';
$locales = ['ar', 'ce', 'de', 'en', 'es', 'fr', 'hi', 'id', 'lo', 'nl', 'ps', 'pt', 'ro', 'sq', 'tr', 'vi'];

$scanRoots = [
    "$base/app",
    "$base/resources/views",
    "$base/Modules",
];

$existingValues = [];
$enUiPath = "$base/lang/en/ui.php";
if (file_exists($enUiPath)) {
    $loaded = include $enUiPath;
    if (is_array($loaded)) {
        $existingValues = $loaded;
    }
}

$keys = [];

$patterns = [
    "/__\('ui\.([a-z0-9_]+)'\)/",
    "/@lang\('ui\.([a-z0-9_]+)'\)/",
    "/trans\('ui\.([a-z0-9_]+)'\)/",
];

foreach ($scanRoots as $root) {
    if (!is_dir($root)) {
        continue;
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)
    );

    foreach ($iterator as $file) {
        if (!$file->isFile()) {
            continue;
        }

        $name = $file->getFilename();
        if (!preg_match('/\.php$|\.blade\.php$/', $name)) {
            continue;
        }

        $content = file_get_contents($file->getPathname());
        if ($content === false || strpos($content, 'ui.') === false) {
            continue;
        }

        foreach ($patterns as $pattern) {
            if (preg_match_all($pattern, $content, $matches)) {
                foreach ($matches[1] as $k) {
                    $keys[$k] = true;
                }
            }
        }
    }
}

ksort($keys);

function humanize($key)
{
    $value = str_replace('_', ' ', $key);
    $value = trim($value);
    if ($value === '') {
        return $key;
    }

    // Basic title casing while preserving all-caps short tokens.
    $parts = preg_split('/\s+/', $value);
    $parts = array_map(function ($part) {
        if (strlen($part) <= 4 && strtoupper($part) === $part) {
            return $part;
        }
        if (preg_match('/^[a-z]$/', $part)) {
            return strtoupper($part);
        }
        return ucfirst($part);
    }, $parts);

    return implode(' ', $parts);
}

$payload = [];
foreach (array_keys($keys) as $key) {
    if (array_key_exists($key, $existingValues) && is_string($existingValues[$key]) && $existingValues[$key] !== '') {
        $payload[$key] = $existingValues[$key];
    } else {
        $payload[$key] = humanize($key);
    }
}

foreach ($locales as $locale) {
    $dir = "$base/lang/$locale";
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    $path = "$dir/ui.php";
    $content = "<?php\n\nreturn [\n";
    foreach ($payload as $key => $value) {
        $kEsc = str_replace("'", "\\'", $key);
        $vEsc = str_replace("'", "\\'", $value);
        $content .= "    '$kEsc' => '$vEsc',\n";
    }
    $content .= "];\n";

    file_put_contents($path, $content);
}

echo 'Rebuilt ui.php for locales with ' . count($payload) . " keys\n";
