<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Subscription;
use App\Models\MpesaPayment;
use Carbon\Carbon;

class FixSubscriptionPayments extends Command
{
    protected $signature = 'subscriptions:fix-payments';
    protected $description = 'Fix subscriptions linked to Mpesa payments';

    public function handle()
    {
        $count = $this->fixByCheckoutRequestId();
        $this->info("Fixed {$count} subscriptions.");
    }

    private function fixByCheckoutRequestId()
    {
        $fixedCount = 0;

        $subscriptions = Subscription::whereNotNull('checkout_request_id')
            ->whereDoesntHave('payments', function($query) {
                $query->where('checkout_request_id', '!=', null);
            })
            ->get();

        foreach ($subscriptions as $subscription) {
            $payment = MpesaPayment::where('checkout_request_id', $subscription->checkout_request_id)->first();

            if ($payment && !$payment->subscription_id) {
                $payment->subscription_id = $subscription->id;
                $payment->save();

                if (in_array($payment->transaction_status, ['paid', 'success']) && $subscription->status !== 'active') {
                    $baseDate = ($subscription->end_date && Carbon::parse($subscription->end_date)->isFuture())
                        ? Carbon::parse($subscription->end_date)
                        : Carbon::now();

                    $subscription->update([
                        'status' => 'active',
                        'mpesa_receipt' => $payment->mpesa_receipt_number,
                        'start_date' => Carbon::now(),
                        'end_date' => $this->calculateEndDate($subscription->billing_cycle, $baseDate),
                    ]);

                    $this->info("Activated subscription {$subscription->id} for payment {$payment->id}");
                }

                $fixedCount++;
                $this->info("Linked subscription {$subscription->id} to payment {$payment->id}");
            }
        }

        return $fixedCount;
    }

    private function calculateEndDate($billingCycle, $startDate)
    {
        // Example implementation
        return Carbon::parse($startDate)->addMonth();
    }
}
