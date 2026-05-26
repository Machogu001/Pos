<?php

/**
 * Populate lang/<locale>/{ui,hrm,hrm_common,hrm_theme}.php values using existing
 * project translations by matching English source phrases.
 */

$base = '/var/www/pos';
$locales = ['ar', 'ce', 'de', 'es', 'fr', 'hi', 'id', 'lo', 'nl', 'ps', 'pt', 'ro', 'sq', 'tr', 'vi'];
$targetFiles = ['ui.php', 'hrm.php', 'hrm_common.php', 'hrm_theme.php'];

function flattenArray(array $arr, string $prefix = ''): array
{
    $flat = [];
    foreach ($arr as $key => $value) {
        $path = $prefix === '' ? (string) $key : $prefix . '.' . $key;
        if (is_array($value)) {
            $flat += flattenArray($value, $path);
        } else {
            $flat[$path] = $value;
        }
    }

    return $flat;
}

function collectPairedFiles(string $base, string $locale): array
{
    $pairs = [];

    // Core lang files
    $enRoot = $base . '/lang/en';
    $locRoot = $base . '/lang/' . $locale;
    if (is_dir($enRoot) && is_dir($locRoot)) {
        $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($enRoot, FilesystemIterator::SKIP_DOTS));
        foreach ($it as $file) {
            if (! $file->isFile() || substr($file->getFilename(), -4) !== '.php') {
                continue;
            }
            $rel = substr($file->getPathname(), strlen($enRoot) + 1);
            $locPath = $locRoot . '/' . $rel;
            if (is_file($locPath)) {
                $pairs[] = [$file->getPathname(), $locPath];
            }
        }
    }

    // Module lang files
    $modulesRoot = $base . '/Modules';
    if (is_dir($modulesRoot)) {
        $mods = scandir($modulesRoot);
        foreach ($mods as $mod) {
            if ($mod === '.' || $mod === '..') {
                continue;
            }
            $modPath = $modulesRoot . '/' . $mod;
            if (! is_dir($modPath)) {
                continue;
            }
            $enModRoot = $modPath . '/Resources/lang/en';
            $locModRoot = $modPath . '/Resources/lang/' . $locale;
            if (! is_dir($enModRoot) || ! is_dir($locModRoot)) {
                continue;
            }
            $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($enModRoot, FilesystemIterator::SKIP_DOTS));
            foreach ($it as $file) {
                if (! $file->isFile() || substr($file->getFilename(), -4) !== '.php') {
                    continue;
                }
                $rel = substr($file->getPathname(), strlen($enModRoot) + 1);
                $locPath = $locModRoot . '/' . $rel;
                if (is_file($locPath)) {
                    $pairs[] = [$file->getPathname(), $locPath];
                }
            }
        }
    }

    return $pairs;
}

function buildPhraseDictionary(string $base, string $locale): array
{
    $dict = [];
    $pairs = collectPairedFiles($base, $locale);

    foreach ($pairs as [$enFile, $locFile]) {
        try {
            $enData = include $enFile;
            $locData = include $locFile;
        } catch (Throwable $e) {
            continue;
        }

        if (! is_array($enData) || ! is_array($locData)) {
            continue;
        }

        $enFlat = flattenArray($enData);
        $locFlat = flattenArray($locData);

        foreach ($enFlat as $key => $enVal) {
            if (! is_string($enVal) || $enVal === '') {
                continue;
            }
            if (! array_key_exists($key, $locFlat) || ! is_string($locFlat[$key]) || $locFlat[$key] === '') {
                continue;
            }

            $locVal = $locFlat[$key];
            if ($locVal === $enVal) {
                continue;
            }

            // First hit wins to keep consistent phrase selection.
            if (! isset($dict[$enVal])) {
                $dict[$enVal] = $locVal;
            }
        }
    }

    return $dict;
}

function exportPhpArray(array $arr): string
{
    $content = "<?php\n\nreturn [\n";
    foreach ($arr as $k => $v) {
        $kEsc = str_replace("'", "\\'", (string) $k);
        $vEsc = str_replace("'", "\\'", (string) $v);
        $content .= "    '$kEsc' => '$vEsc',\n";
    }
    $content .= "];\n";

    return $content;
}

$summary = [];

foreach ($locales as $locale) {
    $dict = buildPhraseDictionary($base, $locale);
    $replacedTotal = 0;

    foreach ($targetFiles as $fileName) {
        $path = $base . '/lang/' . $locale . '/' . $fileName;
        if (! is_file($path)) {
            continue;
        }

        $data = include $path;
        if (! is_array($data)) {
            continue;
        }

        $replaced = 0;
        foreach ($data as $key => $value) {
            if (! is_string($value) || $value === '') {
                continue;
            }
            if (isset($dict[$value])) {
                $data[$key] = $dict[$value];
                $replaced++;
            }
        }

        if ($replaced > 0) {
            file_put_contents($path, exportPhpArray($data));
            chmod($path, 0644);
        }

        $replacedTotal += $replaced;
    }

    $summary[$locale] = [
        'dict' => count($dict),
        'replaced' => $replacedTotal,
    ];
}

foreach ($summary as $locale => $stats) {
    echo $locale . ': dict=' . $stats['dict'] . ', replaced=' . $stats['replaced'] . PHP_EOL;
}
