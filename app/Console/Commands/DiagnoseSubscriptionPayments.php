<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\MpesaPayment;
use App\Subscription;

class DiagnoseSubscriptionPayments extends Command
{
    protected $signature = 'payments:diagnose';
    protected $description = 'Diagnose subscription payment issues';

    public function handle()
    {
        $this->info("=== Subscription Payment Diagnosis ===");
        
        // Check all payments
        $allPayments = MpesaPayment::count();
        $this->info("Total payments: {$allPayments}");
        
        // Check payments with NULL subscription_id
        $nullSubscriptionPayments = MpesaPayment::whereNull('subscription_id')->count();
        $this->info("Payments with NULL subscription_id: {$nullSubscriptionPayments}");
        
        // Check successful payments with NULL subscription_id
        $successfulNullSubscription = MpesaPayment::whereNull('subscription_id')
            ->whereIn('transaction_status', ['paid', 'success'])
            ->count();
        $this->info("Successful payments with NULL subscription_id: {$successfulNullSubscription}");
        
        // Check pending subscriptions
        $pendingSubscriptions = Subscription::where('status', 'pending')->count();
        $this->info("Pending subscriptions: {$pendingSubscriptions}");
        
        // List specific problematic payments
        $problemPayments = MpesaPayment::whereNull('subscription_id')
            ->whereIn('transaction_status', ['paid', 'success'])
            ->get();
            
        if ($problemPayments->count() > 0) {
            $this->info("\n=== Problematic Payments ===");
            foreach ($problemPayments as $payment) {
                $this->info("Payment ID: {$payment->id}, User: {$payment->user_id}, Amount: {$payment->amount}, Status: {$payment->transaction_status}");
                
                // Check if user has pending subscriptions
                $pendingSubs = Subscription::where('user_id', $payment->user_id)
                    ->where('status', 'pending')
                    ->count();
                $this->info("  User has {$pendingSubs} pending subscriptions");
            }
        }
        
        // List payments that should be linked to subscriptions
        $this->info("\n=== Payments that should be linked ===");
        $paymentsWithAccountRef = MpesaPayment::whereNotNull('account_reference')
            ->whereNull('subscription_id')
            ->get();
            
        foreach ($paymentsWithAccountRef as $payment) {
            $this->info("Payment ID: {$payment->id}, Account Reference: {$payment->account_reference}");
            
            // Try to extract subscription ID from account reference
            if (preg_match('/SUB(\d+)/', $payment->account_reference, $matches)) {
                $subscriptionId = $matches[1];
                $subscription = Subscription::find($subscriptionId);
                
                if ($subscription) {
                    $this->info("  Could link to subscription: {$subscriptionId} (Status: {$subscription->status})");
                } else {
                    $this->info("  Subscription {$subscriptionId} not found");
                }
            }
        }
        
        $this->info("\n=== Diagnosis Complete ===");
    }
}