<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\MpesaPayment;

class ListUnconsumedMpesaPayments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pos:list-unconsumed-mpesa {--days=30 : How many days back to search}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List M-Pesa payments that are paid but not consumed for manual reconciliation';

    public function handle()
    {
        $days = (int) $this->option('days');

        $rows = MpesaPayment::where('transaction_status', 'paid')
            ->whereNull('consumed_by_transaction_id')
            ->where('paid_at', '>=', now()->subDays($days))
            ->orderBy('paid_at', 'asc')
            ->get();

        if ($rows->isEmpty()) {
            $this->info('No unconsumed paid M-Pesa payments found.');
            return 0;
        }

        $table = [];
        foreach ($rows as $r) {
            $match = DB::table('transaction_payments')
                ->where('transaction_no', $r->mpesa_receipt_number)
                ->orWhere('transaction_no', $r->checkout_request_id)
                ->first();

            $table[] = [
                'id' => $r->id,
                'phone' => $r->phone_number,
                'amount' => $r->amount,
                'mpesa_receipt' => $r->mpesa_receipt_number,
                'checkout_request_id' => $r->checkout_request_id,
                'paid_at' => $r->paid_at,
                'payment_type' => $r->payment_type,
                'matched_transaction_id' => $match->transaction_id ?? null,
                'matched_payment_id' => $match->id ?? null,
                'matched_method' => $match->method ?? null,
            ];
        }

        $this->table([
            'id', 'phone', 'amount', 'mpesa_receipt', 'checkout_request_id', 'paid_at', 'payment_type', 'matched_transaction_id', 'matched_payment_id', 'matched_method'
        ], $table);

        $this->info('Found ' . count($table) . ' unconsumed paid M-Pesa payments (last ' . $days . ' days).');

        return 0;
    }
}
