<?php

namespace App\Console\Commands;

use App\Services\SellPostingAuditService;
use Illuminate\Console\Command;

class AuditSellPostings extends Command
{
    protected $signature = 'accounting:audit-sell-postings
                            {--business-id= : Restrict audit to one business}
                            {--fix : Auto-backfill missing item-sell postings}
                            {--dry-run : Show what would be fixed without writing changes}';

    protected $description = 'Audit item-sell COGS/inventory postings and optionally backfill missing rows.';

    protected SellPostingAuditService $sellPostingAuditService;

    public function __construct(SellPostingAuditService $sellPostingAuditService)
    {
        parent::__construct();
        $this->sellPostingAuditService = $sellPostingAuditService;
    }

    public function handle(): int
    {
        $businessId = $this->option('business-id');
        $businessId = $businessId !== null ? (int) $businessId : null;
        $fix = (bool) $this->option('fix');
        $dryRun = (bool) $this->option('dry-run');

        $result = $this->sellPostingAuditService->alertAndOptionallyBackfill($businessId, $fix, $dryRun);
        $summary = $result['initial_summary'];

        $this->info('Item-sell posting audit summary:');
        $this->line('Scope business id: '.($summary['scope_business_id'] ?? 'all'));
        $this->line('Final item sells: '.$summary['final_non_subscription_sell_count']);
        $this->line('Missing COGS postings: '.$summary['missing_cogs_count']);
        $this->line('Missing inventory postings: '.$summary['missing_inventory_count']);

        if (! empty($summary['affected_businesses'])) {
            $this->line('Affected businesses:');
            foreach ($summary['affected_businesses'] as $business) {
                $this->line(sprintf(
                    '- %s (ID %d): missing COGS %d, missing inventory %d',
                    $business['business_name'],
                    $business['business_id'],
                    $business['missing_cogs_count'],
                    $business['missing_inventory_count']
                ));
            }
        }

        if (! empty($result['result'])) {
            $fixResult = $result['result'];
            $this->info($dryRun ? 'Dry-run backfill summary:' : 'Backfill summary:');
            $this->line('Initial missing transactions: '.$fixResult['initial_missing_count']);
            $this->line('Processed transactions: '.$fixResult['processed_count']);
            $this->line('Error count: '.$fixResult['error_count']);
            $this->line('Remaining missing COGS postings: '.$fixResult['summary']['missing_cogs_count']);
            $this->line('Remaining missing inventory postings: '.$fixResult['summary']['missing_inventory_count']);
        }

        return 0;
    }
}
