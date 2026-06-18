<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\TransactionPayment;
use App\AccountTransaction;
use App\AdminSetting;
use App\Transaction;
use App\Utils\ModuleUtil;

class BackfillDefaultAccountTransactions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'accounting:backfill-default-accounts {--dry-run : Only show what would be created, do not write to DB}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create missing account transactions for existing payments using default account mappings by transaction type.';

    /** @var ModuleUtil */
    protected $moduleUtil;

    public function __construct(ModuleUtil $moduleUtil)
    {
        parent::__construct();
        $this->moduleUtil = $moduleUtil;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $dryRun = (bool) $this->option('dry-run');

        $this->info('Scanning for payments with an account_id but no ledger entry (AccountTransaction)...');

        $query = TransactionPayment::query()
            ->whereNotNull('account_id')
            ->where('method', '!=', 'advance');

        $query->whereDoesntHave('account_transactions');

        $count = $query->count();
        if ($count === 0) {
            $this->info('No payments found that need backfilling.');
            if (! $dryRun) {
                $this->markLastRun();
            }
            return 0;
        }

        $this->info("Found {$count} payments missing account transactions.");

        $created = 0;

        $query->chunkById(200, function ($payments) use (&$created, $dryRun) {
            foreach ($payments as $payment) {
                if (! $this->moduleUtil->isModuleEnabled('account', $payment->business_id)) {
                    continue;
                }

                $transaction = $payment->transaction ?: Transaction::find($payment->transaction_id);
                if (! $transaction) {
                    continue;
                }

                $transaction_type = $transaction->type ?? null;
                if (empty($transaction_type)) {
                    continue;
                }

                $type = ! empty($payment->payment_type)
                    ? $payment->payment_type
                    : AccountTransaction::getAccountTransactionType($transaction_type);

                if ($transaction_type === 'sell' && $payment->is_return == 1) {
                    $type = 'debit';
                }
                if ($transaction_type === 'hms_booking' && $payment->is_return == 1) {
                    $type = 'debit';
                }

                $data = [
                    'amount' => $payment->amount,
                    'account_id' => $payment->account_id,
                    'type' => $type,
                    'operation_date' => $payment->paid_on,
                    'created_by' => $payment->created_by,
                    'transaction_id' => $payment->transaction_id,
                    'transaction_payment_id' => $payment->id,
                ];

                if ($dryRun) {
                    $this->line("[DRY-RUN] Would create AccountTransaction for payment ID {$payment->id} (transaction {$transaction->id}, type {$transaction_type}, account {$payment->account_id}, amount {$payment->amount})");
                } else {
                    AccountTransaction::createAccountTransaction($data);
                    $created++;
                }
            }
        });

        if ($dryRun) {
            $this->info('Dry run complete. No changes were written.');
        } else {
            $this->info("Backfill complete. Created {$created} account transactions.");
            $this->markLastRun();
        }

        return 0;
    }

    /**
     * Track the most recent execution time for dashboard status display.
     */
    protected function markLastRun(): void
    {
        try {
            $settings = AdminSetting::firstOrCreate([]);
            $settings->accounting_backfill_last_run_at = now();
            $settings->save();
        } catch (\Throwable $e) {
            // Swallow settings write failures to avoid failing the command itself.
        }
    }
}
