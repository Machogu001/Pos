<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class BreakTimeTranslationTest extends TestCase
{
    public function test_break_time_is_translated_in_all_supported_locales(): void
    {
        $root = dirname(__DIR__, 2);
        $english = require $root . '/lang/en/ui.php';

        foreach (glob($root . '/lang/*/ui.php') as $path) {
            $locale = basename(dirname($path));
            if ($locale === 'en') {
                continue;
            }

            $translations = require $path;

            $this->assertArrayHasKey('break_time', $translations, $locale);
            $this->assertNotSame('', trim($translations['break_time']), $locale);
            $this->assertNotSame(
                strtolower($english['break_time']),
                strtolower(trim($translations['break_time'])),
                "The break_time label must be translated for {$locale}."
            );
        }
    }
}
