<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\MpesaPayment;
use App\Http\Controllers\MpesaController;
use App\Http\Controllers\SubscriptionController;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        \App\Console\Commands\CheckSubscriptions::class,
        \App\Console\Commands\SimulateMpesaFlow::class,
        \App\Console\Commands\ListUnconsumedMpesaPayments::class,
        \App\Console\Commands\SetDefaultLeave::class,
        \App\Console\Commands\SendSubscriptionReminders::class,
        \App\Console\Commands\BackfillDefaultAccountTransactions::class,
        \App\Console\Commands\RealignPaymentAccountMappings::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $env = config('app.env');
        $email = config('mail.username');

        if ($env === 'live') {
            //Scheduling backup, specify the time when the backup will get cleaned & time when it will run.
            $schedule->command('backup:clean')->daily()->at('01:00');
            $schedule->command('backup:run')->daily()->at('01:30');

            //Schedule to create recurring invoices
            $schedule->command('pos:generateSubscriptionInvoices')->dailyAt('23:30');
            $schedule->command('pos:updateRewardPoints')->dailyAt('23:45');

            $schedule->command('pos:autoSendPaymentReminder')->dailyAt('8:00');
        }

        if ($env === 'demo') {
            //IMPORTANT NOTE: This command will delete all business details and create dummy business, run only in demo server.
            $schedule->command('pos:dummyBusiness')
                ->cron('0 */3 * * *')
                ->emailOutputTo($email);
        }

        // Check for expired subscriptions - runs daily in all environments
        $schedule->command('subscriptions:check')->dailyAt('00:00');

        // Generate subscription pre-expiry invoice notices and reminders (14d & 7d)
        $schedule->command('subscriptions:send_reminders')->dailyAt('09:00');

        // Prune Telescope entries older than 48 hours to keep the DB lean
        $schedule->command('telescope:prune --hours=48')->dailyAt('03:00');

        // Check pending Mpesa payments every 5 minutes
        $schedule->call(function () {
            $pendingPayments = MpesaPayment::where('transaction_status', 'pending')
                ->where('created_at', '>', now()->subHours(24))
                ->get();

            foreach ($pendingPayments as $payment) {
                $mpesaController = new MpesaController();
                $status = $mpesaController->checkPaymentStatusDirect($payment->checkout_request_id);

                if ($status['transaction_status'] === 'success' && $payment->subscription_id) {
                    $subscriptionController = new SubscriptionController();
                    $subscriptionController->activateSubscription($payment);
                }
            }
        })->everyFiveMinutes();
    }

    /**
     * Register the Closure based commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');
        require base_path('routes/console.php');
    }
    
}