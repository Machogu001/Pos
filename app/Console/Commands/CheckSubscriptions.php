<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Subscription;
use App\User;
use Carbon\Carbon;

class CheckSubscriptions extends Command
{
    protected $signature = 'subscriptions:check';
    protected $description = 'Check and update subscription statuses';

    public function handle()
    {
        // Expire subscriptions that have reached end date
        $expiredSubscriptions = Subscription::where('status', 'active')
            ->where('end_date', '<=', Carbon::now())
            ->get();
            
        $expiredCount = 0;
            
        foreach ($expiredSubscriptions as $subscription) {
            $subscription->update(['status' => 'expired']);
            $expiredCount++;
            
            // Update user status
            $user = User::find($subscription->user_id);
            if ($user) {
                $user->update([
                    'has_active_subscription' => false,
                ]);
                
                // Update subscription_status if column exists
                if (Schema::hasColumn('users', 'subscription_status')) {
                    $user->update(['subscription_status' => 'expired']);
                }
            }
        }
        
        $this->info("Subscription check completed. Expired: {$expiredCount} subscriptions");
        
        return 0;
    }
}