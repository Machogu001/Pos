<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\MpesaPayment;
use Carbon\Carbon;

class SimulateMpesaFlow extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'simulate:mpesa_flow {--checkout=} {--phone=} {--amount=} {--receipt=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Simulate a M-Pesa STK callback by creating/updating a mpesa_payments record and marking it paid.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $checkout = $this->option('checkout') ?: $this->ask('CheckoutRequestID (e.g. ws_CO_123456)');
        $phone = $this->option('phone') ?: $this->ask('Phone (2547XXXXXXXX)');
        $amount = $this->option('amount') ?: $this->ask('Amount (e.g. 100)');
        $receipt = $this->option('receipt') ?: 'SIM' . strtoupper(str_random(6));

        // Normalize phone
        $phone = preg_replace('/[^0-9]/', '', $phone);

        $this->info("Simulating callback for checkout_request_id={$checkout}");

        $payment = MpesaPayment::firstOrNew(['checkout_request_id' => $checkout]);
        $payment->phone_number = $phone;
        $payment->amount = $amount;
        $payment->merchant_request_id = 'SIMMREQ' . time();
        $payment->mpesa_receipt_number = $receipt;
        $payment->result_code = 0;
        $payment->result_desc = 'The service request is processed successfully.';
        $payment->transaction_status = 'success';
        $payment->paid_at = Carbon::now();
        $payment->save();

        $this->info('MpesaPayment updated:');
        $this->line(json_encode($payment->toArray(), JSON_PRETTY_PRINT));

        // Show that an authoritative lookup (DB) would find this record
        $found = MpesaPayment::where('checkout_request_id', $checkout)->first();
        if ($found) {
            $this->info('Authoritative lookup found the payment and its status is: ' . $found->transaction_status);
        } else {
            $this->error('Authoritative lookup did not find the payment.');
        }

        return 0;
    }
}
