<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Arr;

class AuditTranslations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'translations:audit
                            {--base=en : Base locale used as source of truth}
                            {--export= : Optional export path (csv or json)}
                            {--include-vendor : Include lang/vendor translations in the audit}
                            {--files= : Optional comma-separated relative files to audit (e.g. ui.php,account.php)}
                            {--worklist-dir= : Optional directory to export per-locale CSV worklists}
                            {--top-per-locale=0 : Limit rows per locale in worklists (0 = no limit)}
                            {--baseline= : Optional baseline file (audit json or baseline json) for new-issue detection}
                            {--new-only : When baseline is provided, output/export only newly introduced issues}
                            {--export-baseline= : Optional path to export compact baseline fingerprints json}
                            {--fail-on-issues : Exit with non-zero code when any issue is found}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Audit translation coverage and native-localization quality across locales';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $langPath = base_path('lang');
        $baseLocale = (string) $this->option('base');
        $includeVendor = (bool) $this->option('include-vendor');

        $basePath = $langPath . DIRECTORY_SEPARATOR . $baseLocale;
        if (! is_dir($basePath)) {
            $this->error("Base locale folder not found: {$basePath}");

            return self::FAILURE;
        }

        $locales = $this->getLocales($langPath, $baseLocale);
        if (empty($locales)) {
            $this->warn('No target locales found to audit.');

            return self::SUCCESS;
        }

        $baseFiles = $this->collectPhpFiles($basePath, $includeVendor);
        $fileFilter = $this->parseFileFilter((string) ($this->option('files') ?? ''));
        if (! empty($fileFilter)) {
            $baseFiles = array_values(array_filter($baseFiles, function (string $relativeFile) use ($fileFilter): bool {
                return in_array($relativeFile, $fileFilter, true);
            }));
        }

        if (empty($baseFiles)) {
            $this->warn('No base locale files matched the requested file filter.');

            return self::SUCCESS;
        }

        $issues = [];
        $summary = [];

        foreach ($locales as $locale) {
            $localePath = $langPath . DIRECTORY_SEPARATOR . $locale;
            $missingFiles = 0;
            $missingKeys = 0;
            $untranslatedKeys = 0;

            foreach ($baseFiles as $relativeFile) {
                $baseFilePath = $basePath . DIRECTORY_SEPARATOR . $relativeFile;
                $targetFilePath = $localePath . DIRECTORY_SEPARATOR . $relativeFile;

                if (! file_exists($targetFilePath)) {
                    $missingFiles++;
                    $issues[] = [
                        'locale' => $locale,
                        'file' => $relativeFile,
                        'type' => 'missing_file',
                        'key' => '',
                        'base' => '',
                        'target' => '',
                    ];
                    continue;
                }

                $baseTranslations = $this->flattenTranslations($baseFilePath);
                $targetTranslations = $this->flattenTranslations($targetFilePath);

                foreach ($baseTranslations as $key => $baseValue) {
                    if (! array_key_exists($key, $targetTranslations)) {
                        $missingKeys++;
                        $issues[] = [
                            'locale' => $locale,
                            'file' => $relativeFile,
                            'type' => 'missing_key',
                            'key' => $key,
                            'base' => $baseValue,
                            'target' => '',
                        ];
                        continue;
                    }

                    $targetValue = $targetTranslations[$key];
                    if ($this->looksUntranslated($baseValue, $targetValue, $locale)) {
                        $untranslatedKeys++;
                        $issues[] = [
                            'locale' => $locale,
                            'file' => $relativeFile,
                            'type' => 'untranslated_value',
                            'key' => $key,
                            'base' => $baseValue,
                            'target' => $targetValue,
                        ];
                    }
                }
            }

            $summary[$locale] = [
                'missing_files' => $missingFiles,
                'missing_keys' => $missingKeys,
                'untranslated_values' => $untranslatedKeys,
                'total_issues' => $missingFiles + $missingKeys + $untranslatedKeys,
            ];
        }

        $this->renderSummary($summary);

        $baselinePath = (string) ($this->option('baseline') ?? '');
        $baselineFingerprints = $this->loadBaselineFingerprints($baselinePath);
        $newIssues = $this->filterNewIssues($issues, $baselineFingerprints);

        if ($baselinePath !== '') {
            $this->info('Baseline loaded: ' . $baselinePath);
            $this->info('New issues vs baseline: ' . count($newIssues));
        }

        $useNewOnly = (bool) $this->option('new-only') && $baselinePath !== '';
        $issuesForOutput = $useNewOnly ? $newIssues : $issues;

        $this->renderTopIssues($issuesForOutput);

        $exportPath = (string) ($this->option('export') ?? '');
        if ($exportPath !== '') {
            $this->exportIssues($issuesForOutput, $summary, $exportPath, $baselinePath, $useNewOnly);
            $this->info("Audit exported to: {$exportPath}");
        }

        $worklistDir = (string) ($this->option('worklist-dir') ?? '');
        if ($worklistDir !== '') {
            $topPerLocale = max(0, (int) ($this->option('top-per-locale') ?? 0));
            $this->exportLocaleWorklists($issuesForOutput, $worklistDir, $topPerLocale);
            $this->info("Per-locale worklists exported to: {$worklistDir}");
        }

        $exportBaselinePath = (string) ($this->option('export-baseline') ?? '');
        if ($exportBaselinePath !== '') {
            $this->exportBaselineFingerprints($issues, $exportBaselinePath, $baseLocale);
            $this->info('Baseline fingerprints exported to: ' . $exportBaselinePath);
        }

        $issuesForFail = $baselinePath !== '' ? $newIssues : $issues;
        if (! empty($issuesForFail) && (bool) $this->option('fail-on-issues')) {
            if ($baselinePath !== '') {
                $this->error('New translation issues found vs baseline and --fail-on-issues was set.');
            } else {
                $this->error('Translation issues found and --fail-on-issues was set.');
            }
            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /**
     * @return array<int, string>
     */
    private function getLocales(string $langPath, string $baseLocale): array
    {
        $entries = @scandir($langPath) ?: [];

        $locales = [];
        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..' || $entry === 'vendor' || $entry === $baseLocale) {
                continue;
            }

            $path = $langPath . DIRECTORY_SEPARATOR . $entry;
            if (is_dir($path)) {
                $locales[] = $entry;
            }
        }

        sort($locales);

        return $locales;
    }

    /**
     * @return array<int, string>
     */
    private function collectPhpFiles(string $basePath, bool $includeVendor): array
    {
        $files = [];

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($basePath, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $fileInfo) {
            /** @var \SplFileInfo $fileInfo */
            if (! $fileInfo->isFile() || $fileInfo->getExtension() !== 'php') {
                continue;
            }

            $relative = ltrim(str_replace($basePath, '', $fileInfo->getPathname()), DIRECTORY_SEPARATOR);

            if (! $includeVendor && str_starts_with($relative, 'vendor' . DIRECTORY_SEPARATOR)) {
                continue;
            }

            $files[] = $relative;
        }

        sort($files);

        return $files;
    }

    /**
     * @return array<string, string>
     */
    private function flattenTranslations(string $filePath): array
    {
        $data = include $filePath;

        if (! is_array($data)) {
            return [];
        }

        $flattened = Arr::dot($data);

        $result = [];
        foreach ($flattened as $key => $value) {
            if (is_scalar($value) || $value === null) {
                $result[(string) $key] = (string) $value;
            }
        }

        ksort($result);

        return $result;
    }

    private function looksUntranslated(string $baseValue, string $targetValue, string $locale): bool
    {
        if ($locale === 'en') {
            return false;
        }

        $normalizedBase = trim($baseValue);
        $normalizedTarget = trim($targetValue);

        if ($normalizedBase === '' || $normalizedTarget === '') {
            return false;
        }

        return $normalizedBase === $normalizedTarget;
    }

    /**
     * @return array<string, bool>
     */
    private function loadBaselineFingerprints(string $baselinePath): array
    {
        if ($baselinePath === '' || ! is_file($baselinePath)) {
            return [];
        }

        $content = file_get_contents($baselinePath);
        if ($content === false || trim($content) === '') {
            return [];
        }

        $payload = json_decode($content, true);
        if (! is_array($payload)) {
            return [];
        }

        $fingerprints = [];

        if (isset($payload['issue_fingerprints']) && is_array($payload['issue_fingerprints'])) {
            foreach ($payload['issue_fingerprints'] as $fp) {
                if (is_string($fp) && $fp !== '') {
                    $fingerprints[$fp] = true;
                }
            }

            return $fingerprints;
        }

        if (isset($payload['issues']) && is_array($payload['issues'])) {
            foreach ($payload['issues'] as $issue) {
                if (! is_array($issue)) {
                    continue;
                }

                $fingerprints[$this->issueFingerprint($issue)] = true;
            }
        }

        return $fingerprints;
    }

    /**
     * @param array<int, array<string, string>> $issues
     * @param array<string, bool> $baselineFingerprints
     * @return array<int, array<string, string>>
     */
    private function filterNewIssues(array $issues, array $baselineFingerprints): array
    {
        if (empty($baselineFingerprints)) {
            return $issues;
        }

        $newIssues = [];
        foreach ($issues as $issue) {
            $fp = $this->issueFingerprint($issue);
            if (! isset($baselineFingerprints[$fp])) {
                $newIssues[] = $issue;
            }
        }

        return $newIssues;
    }

    /**
     * @param array<string, string> $issue
     */
    private function issueFingerprint(array $issue): string
    {
        $locale = (string) ($issue['locale'] ?? '');
        $file = (string) ($issue['file'] ?? '');
        $type = (string) ($issue['type'] ?? '');
        $key = (string) ($issue['key'] ?? '');

        return sha1($locale . '|' . $file . '|' . $type . '|' . $key);
    }

    /**
     * @return array<int, string>
     */
    private function parseFileFilter(string $raw): array
    {
        if (trim($raw) === '') {
            return [];
        }

        $parts = array_map('trim', explode(',', $raw));
        $parts = array_values(array_filter($parts, static function (string $value): bool {
            return $value !== '';
        }));

        return array_values(array_unique($parts));
    }

    /**
     * @param array<string, array<string, int>> $summary
     */
    private function renderSummary(array $summary): void
    {
        $rows = [];
        foreach ($summary as $locale => $stats) {
            $rows[] = [
                $locale,
                (string) $stats['missing_files'],
                (string) $stats['missing_keys'],
                (string) $stats['untranslated_values'],
                (string) $stats['total_issues'],
            ];
        }

        $this->table(['Locale', 'Missing files', 'Missing keys', 'Untranslated values', 'Total issues'], $rows);
    }

    /**
     * @param array<int, array<string, string>> $issues
     */
    private function renderTopIssues(array $issues): void
    {
        if (empty($issues)) {
            $this->info('No translation issues found.');
            return;
        }

        $this->warn('Showing first 25 issues (use --export for full report):');

        $rows = [];
        foreach (array_slice($issues, 0, 25) as $issue) {
            $rows[] = [
                $issue['locale'],
                $issue['file'],
                $issue['type'],
                $issue['key'],
            ];
        }

        $this->table(['Locale', 'File', 'Type', 'Key'], $rows);
    }

    /**
     * @param array<int, array<string, string>> $issues
     * @param array<string, array<string, int>> $summary
     */
    private function exportIssues(array $issues, array $summary, string $exportPath, string $baselinePath = '', bool $newOnly = false): void
    {
        $ext = strtolower(pathinfo($exportPath, PATHINFO_EXTENSION));
        $dir = dirname($exportPath);
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        if ($ext === 'json') {
            $payload = [
                'generated_at' => now()->toIso8601String(),
                'summary' => $summary,
                'issues' => $issues,
                'baseline' => $baselinePath,
                'new_only' => $newOnly,
            ];
            file_put_contents($exportPath, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            return;
        }

        $handle = fopen($exportPath, 'w');
        fputcsv($handle, ['locale', 'file', 'type', 'key', 'base', 'target']);

        foreach ($issues as $issue) {
            fputcsv($handle, [
                $issue['locale'],
                $issue['file'],
                $issue['type'],
                $issue['key'],
                $issue['base'],
                $issue['target'],
            ]);
        }

        fclose($handle);
    }

    /**
     * @param array<int, array<string, string>> $issues
     */
    private function exportLocaleWorklists(array $issues, string $worklistDir, int $topPerLocale = 0): void
    {
        if (! is_dir($worklistDir)) {
            mkdir($worklistDir, 0755, true);
        }

        $byLocale = [];
        foreach ($issues as $issue) {
            $locale = $issue['locale'] ?? 'unknown';
            $byLocale[$locale][] = $issue;
        }

        ksort($byLocale);

        foreach ($byLocale as $locale => $localeIssues) {
            usort($localeIssues, function (array $a, array $b): int {
                $priority = [
                    'missing_file' => 0,
                    'missing_key' => 1,
                    'untranslated_value' => 2,
                ];

                $aType = (string) ($a['type'] ?? 'untranslated_value');
                $bType = (string) ($b['type'] ?? 'untranslated_value');

                $aP = $priority[$aType] ?? 99;
                $bP = $priority[$bType] ?? 99;

                if ($aP !== $bP) {
                    return $aP <=> $bP;
                }

                $aFile = (string) ($a['file'] ?? '');
                $bFile = (string) ($b['file'] ?? '');
                if ($aFile !== $bFile) {
                    return $aFile <=> $bFile;
                }

                return ((string) ($a['key'] ?? '')) <=> ((string) ($b['key'] ?? ''));
            });

            if ($topPerLocale > 0) {
                $localeIssues = array_slice($localeIssues, 0, $topPerLocale);
            }

            $path = rtrim($worklistDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $locale . '_translation_worklist.csv';
            $handle = fopen($path, 'w');
            fputcsv($handle, ['locale', 'file', 'type', 'key', 'base', 'target']);

            foreach ($localeIssues as $issue) {
                fputcsv($handle, [
                    $issue['locale'],
                    $issue['file'],
                    $issue['type'],
                    $issue['key'],
                    $issue['base'],
                    $issue['target'],
                ]);
            }

            fclose($handle);
        }
    }

    /**
     * @param array<int, array<string, string>> $issues
     */
    private function exportBaselineFingerprints(array $issues, string $exportPath, string $baseLocale): void
    {
        $dir = dirname($exportPath);
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $fingerprints = [];
        foreach ($issues as $issue) {
            $fingerprints[] = $this->issueFingerprint($issue);
        }

        $fingerprints = array_values(array_unique($fingerprints));
        sort($fingerprints);

        $payload = [
            'generated_at' => now()->toIso8601String(),
            'base_locale' => $baseLocale,
            'issue_count' => count($issues),
            'issue_fingerprints' => $fingerprints,
        ];

        file_put_contents($exportPath, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
}
