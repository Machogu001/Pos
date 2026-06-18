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
        \App\Console\Commands\AuditSellPostings::class,
        \App\Console\Commands\FixNegativeStockMismatches::class,
        \App\Console\Commands\BackfillStockCostingLayers::class,
        \App\Console\Commands\RealignPaymentAccountMappings::class,
        \App\Console\Commands\AutoCloseRegister::class,
        \App\Console\Commands\FetchRemoteVersion::class,
        \App\Console\Commands\PullUpdateCommand::class,
        \App\Console\Commands\PackageReleaseCommand::class,
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

        // Auto-close open cash registers at admin-configured time (respects admin toggle)
        try {
            $closeTime = \App\AdminSetting::first()?->auto_close_register_time ?? '23:59';
            // Validate HH:MM format; fall back to midnight on bad data
            if (! preg_match('/^\d{2}:\d{2}$/', $closeTime)) {
                $closeTime = '00:00';
            }
        } catch (\Throwable $e) {
            $closeTime = '00:00';
        }
        $schedule->command('pos:autoCloseRegister')->dailyAt($closeTime);

        // Fetch latest available version from the update server every 6 hours
        $schedule->command('pos:fetchRemoteVersion')->everySixHours();

        // Generate subscription pre-expiry invoice notices and reminders (14d & 7d)
        $schedule->command('subscriptions:send_reminders')->dailyAt('09:00');

        // Backfill missing account transactions based on superadmin-configured schedule.
        try {
            $adminSettings = \App\AdminSetting::first();
            if (! empty($adminSettings) && (bool) $adminSettings->accounting_backfill_enabled) {
                $frequency = (string) ($adminSettings->accounting_backfill_frequency ?? 'hourly');
                $time = (string) ($adminSettings->accounting_backfill_time ?? '02:00');
                if (! preg_match('/^\d{2}:\d{2}$/', $time)) {
                    $time = '02:00';
                }

                $backfillCommand = $schedule->command('accounting:backfill-default-accounts')->withoutOverlapping();

                switch ($frequency) {
                    case 'every_fifteen_minutes':
                        $backfillCommand->everyFifteenMinutes();
                        break;
                    case 'every_thirty_minutes':
                        $backfillCommand->everyThirtyMinutes();
                        break;
                    case 'daily':
                        $backfillCommand->dailyAt($time);
                        break;
                    case 'hourly':
                    default:
                        $backfillCommand->hourly();
                        break;
                }
            }
        } catch (\Throwable $e) {
            // Avoid failing the full scheduler because of setting lookup issues.
        }

        // Backfill missing stock costing layers (no qty_available change) on admin-configured schedule.
        try {
            $adminSettings = \App\AdminSetting::first();
            if (! empty($adminSettings) && (bool) $adminSettings->stock_costing_backfill_enabled) {
                $frequency = (string) ($adminSettings->stock_costing_backfill_frequency ?? 'daily');
                $time = (string) ($adminSettings->stock_costing_backfill_time ?? '01:30');
                if (! preg_match('/^\d{2}:\d{2}$/', $time)) {
                    $time = '01:30';
                }

                $businessId = (int) ($adminSettings->stock_costing_backfill_business_id ?? 0);
                $locationId = (int) ($adminSettings->stock_costing_backfill_location_id ?? 0);
                if ($businessId > 0 && $locationId > 0) {
                    $commandString = sprintf(
                        'stock:backfill-costing-layers --business-id=%d --location-id=%d',
                        $businessId,
                        $locationId
                    );

                    $stockBackfillCommand = $schedule->command($commandString)->withoutOverlapping();

                    switch ($frequency) {
                        case 'every_fifteen_minutes':
                            $stockBackfillCommand->everyFifteenMinutes();
                            break;
                        case 'every_thirty_minutes':
                            $stockBackfillCommand->everyThirtyMinutes();
                            break;
                        case 'daily':
                            $stockBackfillCommand->dailyAt($time);
                            break;
                        case 'hourly':
                        default:
                            $stockBackfillCommand->hourly();
                            break;
                    }
                }
            }
        } catch (\Throwable $e) {
            // Avoid failing the full scheduler because of setting lookup issues.
        }

        // Audit finalized item sells and auto-backfill any missing COGS/inventory postings.
        $schedule->command('accounting:audit-sell-postings --fix')->hourly()->withoutOverlapping();

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