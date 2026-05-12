<?php

/**
 * Convert plain-string __() calls to stable ui.* keys and generate ui.php files
 * for all configured locales. Keeps existing namespaced keys unchanged.
 */

$base = '/var/www/pos';
$locales = ['ar', 'ce', 'de', 'en', 'es', 'fr', 'hi', 'id', 'lo', 'nl', 'ps', 'pt', 'ro', 'sq', 'tr', 'vi'];

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

function isLikelyNamespacedKey($value)
{
    return preg_match('/^[a-z][a-z0-9_]*::[a-z][a-z0-9_]*\.[a-z0-9_]+(?:\.[a-z0-9_]+)*$/', $value)
        || preg_match('/^[a-z][a-z0-9_]*\.[a-z0-9_]+(?:\.[a-z0-9_]+)*$/', $value);
}

function slugifyValue($value)
{
    $slug = strtolower($value);
    $slug = preg_replace('/:[a-z_]+/i', '', $slug);
    $slug = preg_replace('/[^a-z0-9]+/', '_', $slug);
    $slug = trim($slug, '_');

    if ($slug === '' || preg_match('/^[0-9]+$/', $slug)) {
        $slug = 'label_' . substr(sha1($value), 0, 10);
    }

    return $slug;
}

function collectTargetFiles($paths)
{
    $files = [];
    foreach ($paths as $path) {
        if (is_file($path)) {
            $files[] = $path;
            continue;
        }
        if (!is_dir($path)) {
            continue;
        }
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS)
        );
        foreach ($iterator as $file) {
            if ($file->isFile() && preg_match('/\.blade\.php$|\.php$/', $file->getFilename())) {
                $files[] = $file->getPathname();
            }
        }
    }

    sort($files);
    return array_values(array_unique($files));
}

// Add HRM module translation loading.
$providerPath = "$base/Modules/Hrm/Providers/HrmServiceProvider.php";
if (file_exists($providerPath)) {
    $provider = file_get_contents($providerPath);
    if (strpos($provider, 'loadTranslationsFrom') === false) {
        $needle = "        // Load views\n";
        $insert = "        // Load translations\n        \$lang = __DIR__ . '/../Resources/lang';\n        if (is_dir(\$lang)) {\n            \$this->loadTranslationsFrom(\$lang, 'hrm');\n        }\n\n";
        $provider = str_replace($needle, $insert . $needle, $provider);
        file_put_contents($providerPath, $provider);
    }
}

$files = collectTargetFiles($targetPaths);
$valueToKey = [];
$usedKeys = [];

// First pass: collect all plain-string keys.
foreach ($files as $file) {
    $content = file_get_contents($file);
    if ($content === false || strpos($content, "__('") === false) {
        continue;
    }

    if (preg_match_all("/__\('((?:\\\\'|[^'])*)'\)/", $content, $matches)) {
        foreach ($matches[1] as $raw) {
            $value = str_replace("\\'", "'", $raw);
            if (isLikelyNamespacedKey($value)) {
                continue;
            }
            if (!array_key_exists($value, $valueToKey)) {
                $baseKey = slugifyValue($value);
                $candidate = $baseKey;
                $i = 2;
                while (isset($usedKeys[$candidate])) {
                    $candidate = $baseKey . '_' . $i;
                    $i++;
                }
                $valueToKey[$value] = $candidate;
                $usedKeys[$candidate] = true;
            }
        }
    }
}

// Second pass: apply replacements.
foreach ($files as $file) {
    $content = file_get_contents($file);
    if ($content === false || strpos($content, "__('") === false) {
        continue;
    }

    $new = preg_replace_callback("/__\('((?:\\\\'|[^'])*)'\)/", function ($m) use ($valueToKey) {
        $value = str_replace("\\'", "'", $m[1]);
        if (isLikelyNamespacedKey($value)) {
            return $m[0];
        }
        if (!array_key_exists($value, $valueToKey)) {
            return $m[0];
        }
        return "__('ui." . $valueToKey[$value] . "')";
    }, $content);

    if ($new !== null && $new !== $content) {
        file_put_contents($file, $new);
    }
}

// Build ui.php payload from discovered values.
$uiPayload = [];
foreach ($valueToKey as $value => $key) {
    $uiPayload[$key] = $value;
}
ksort($uiPayload);

foreach ($locales as $locale) {
    $dir = "$base/lang/$locale";
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    $file = "$dir/ui.php";
    $content = "<?php\n\nreturn [\n";
    foreach ($uiPayload as $k => $v) {
        $kEsc = str_replace("'", "\\'", $k);
        $vEsc = str_replace("'", "\\'", $v);
        $content .= "    '$kEsc' => '$vEsc',\n";
    }
    $content .= "];\n";
    file_put_contents($file, $content);
}

echo "i18n namespace migration completed\n";
echo "Converted plain strings: " . count($valueToKey) . "\n";
echo "Updated files: " . count($files) . "\n";
