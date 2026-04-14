<?php

namespace App\Console\Commands;

use App\AccountTransaction;
use App\Transaction;
use App\TransactionPayment;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RealignPaymentAccountMappings extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'accounting:realign-payment-accounts
                            {--transaction-type= : Limit to a specific transaction type such as purchase or sell}
                            {--from-account= : Only move payments currently assigned to this account id}
                            {--to-account= : Override the target account id instead of resolving the current default mapping}
                            {--payment-id=* : Limit to specific payment ids}
                            {--dry-run : Show the rows that would be updated without writing changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Realign stored payment account ids and linked account transactions to the current intended account mapping.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $transactionType = $this->option('transaction-type');
        $fromAccountId = $this->option('from-account');
        $overrideToAccountId = $this->option('to-account');
        $paymentIds = array_filter((array) $this->option('payment-id'));
        $dryRun = (bool) $this->option('dry-run');

        $query = TransactionPayment::query()
            ->with(['transaction:id,type,location_id,business_id'])
            ->where('method', '!=', 'advance');

        if (! empty($paymentIds)) {
            $query->whereIn('id', $paymentIds);
        }

        if (! empty($fromAccountId)) {
            $query->where('account_id', $fromAccountId);
        }

        if (! empty($transactionType)) {
            $query->whereHas('transaction', function ($transactionQuery) use ($transactionType) {
                $transactionQuery->where('type', $transactionType);
            });
        }

        $payments = $query->orderBy('id')->get();

        if ($payments->isEmpty()) {
            $this->info('No matching payments found.');

            return 0;
        }

        $updatedPayments = 0;
        $updatedAccountTransactions = 0;

        foreach ($payments as $payment) {
            $transaction = $payment->transaction ?: Transaction::find($payment->transaction_id);
            if (empty($transaction)) {
                continue;
            }

            $targetAccountId = ! empty($overrideToAccountId)
                ? (int) $overrideToAccountId
                : TransactionPayment::resolveDefaultAccountId(
                    $payment->method,
                    $transaction->location_id,
                    $transaction->business_id,
                    $transaction->type
                );

            if (empty($targetAccountId) || (int) $payment->account_id === (int) $targetAccountId) {
                continue;
            }

            $matchingAccountTransactions = AccountTransaction::where('transaction_payment_id', $payment->id)->get();

            $this->line(sprintf(
                'Payment %d | transaction %d (%s) | %s | account %s -> %s | ledger rows: %d',
                $payment->id,
                $payment->transaction_id,
                $transaction->type,
                $payment->method,
                $payment->account_id,
                $targetAccountId,
                $matchingAccountTransactions->count()
            ));

            if ($dryRun) {
                continue;
            }

            DB::transaction(function () use ($payment, $targetAccountId, $matchingAccountTransactions, &$updatedPayments, &$updatedAccountTransactions) {
                $payment->account_id = $targetAccountId;
                $payment->save();
                $updatedPayments++;

                foreach ($matchingAccountTransactions as $accountTransaction) {
                    $accountTransaction->account_id = $targetAccountId;
                    $accountTransaction->save();
                    $updatedAccountTransactions++;
                }
            });
        }

        if ($dryRun) {
            $this->info('Dry run complete. No changes were written.');

            return 0;
        }

        $this->info("Updated {$updatedPayments} payment records and {$updatedAccountTransactions} linked ledger rows.");

        return 0;
    }
}