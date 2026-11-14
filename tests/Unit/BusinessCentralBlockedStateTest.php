<?php

namespace Tests\Unit;

use App\Services\BusinessCentralSyncService;
use Tests\TestCase;

class BusinessCentralBlockedStateTest extends TestCase
{
    /**
     * @dataProvider blockedStates
     */
    public function test_it_normalizes_business_central_blocked_states($value, bool $expected): void
    {
        $service = new TestableBusinessCentralBlockedStateService();

        $this->assertSame($expected, $service->blocked($value));
    }

    /**
     * @dataProvider payTermFormulas
     */
    public function test_it_parses_bc_due_date_formulas(string $formula, ?array $expected): void
    {
        $service = new TestableBusinessCentralBlockedStateService();

        $this->assertSame($expected, $service->payTerm($formula));
    }

    public static function payTermFormulas(): array
    {
        return [
            'thirty days' => ['30D', ['number' => 30, 'type' => 'days']],
            'one month' => ['1M', ['number' => 1, 'type' => 'months']],
            'month with discount days' => ['1M(8D)', ['number' => 1, 'type' => 'months']],
            'current month' => ['CM', null],
            'empty' => ['', null],
        ];
    }

    public static function blockedStates(): array
    {
        return [
            'boolean false' => [false, false],
            'boolean true' => [true, true],
            'BC encoded blank' => ['_x0020_', false],
            'blank string' => [' ', false],
            'false string' => ['false', false],
            'all blocked' => ['All', true],
            'invoice blocked' => ['Invoice', true],
        ];
    }
}

class TestableBusinessCentralBlockedStateService extends BusinessCentralSyncService
{
    public function __construct()
    {
    }

    public function blocked($value): bool
    {
        return $this->isBlocked($value);
    }

    public function payTerm(string $formula): ?array
    {
        return $this->parseDueDateCalculation($formula);
    }
}
