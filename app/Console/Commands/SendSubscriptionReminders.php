<?php

namespace App\Console\Commands;

use App\Subscription;
use App\Utils\NotificationUtil;
use App\Utils\TransactionUtil;
use App\User;
use App\Utils\Util;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use App\Notifications\CustomerNotification;
use PDF;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Contact;
use App\Transaction;
use Spatie\Activitylog\Models\Activity;

class SendSubscriptionReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'subscriptions:send_reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate subscription invoice notices at 14 days and reminders at 7 days before expiry';

    protected $notificationUtil;

    public function __construct(NotificationUtil $notificationUtil)
    {
        parent::__construct();

        $this->notificationUtil = $notificationUtil;
    }

    public function handle()
    {
        try {
            $this->info('Running subscription reminders...');

            $today = Carbon::now()->startOfDay();

            // 14 day notices
            $target14 = $today->copy()->addDays(14)->format('Y-m-d');
            $subs14 = Subscription::whereDate('end_date', $target14)
                        ->where('status', 'active')
                        ->with('user')
                        ->get();

            foreach ($subs14 as $sub) {
                $this->process14DayInvoice($sub);
            }

            // 7 day reminders
            $target7 = $today->copy()->addDays(7)->format('Y-m-d');
            $subs7 = Subscription::whereDate('end_date', $target7)
                        ->where('status', 'active')
                        ->with('user')
                        ->get();

            foreach ($subs7 as $sub) {
                $this->process7DayReminder($sub);
            }

            $this->info('Subscription reminders processed.');
        } catch (\Exception $e) {
            Log::error('Subscription reminder error: '.$e->getMessage());
            $this->error('Error: '.$e->getMessage());

            if (app()->environment('testing')) {
                throw $e;
            }
        }
    }

    protected function sendInvoiceNotice(Subscription $subscription, int $days)
    {
        // Deprecated: kept for backward compatibility. New logic lives in process14DayInvoice and process7DayReminder.
        return;
    }

    protected function process14DayInvoice(Subscription $subscription)
    {
        $user = $subscription->user;
        if (empty($user) || empty($user->email)) {
            return;
        }

        $transactionUtil = app(TransactionUtil::class);

        $business = $user->business ?? null;
        $business_id = $business->id ?? null;

        // unique key for this subscription period invoice
        $uniqueKey = 'sub_invoice_'.$subscription->id.'_'.($subscription->end_date->format('Ymd'));

        // If a transaction already exists for this unique key, or subscription already marked, skip (ensures send-once)
        $existing = Transaction::where('subscription_no', $uniqueKey)->first();
        if ($existing || ! empty($subscription->invoice_sent_at)) {
            $this->info('Invoice already generated/sent for subscription '.$subscription->id.' ('.$uniqueKey.'). Skipping.');
            return;
        }

        // Create or find contact for user within business
        $contact = null;
        if ($business_id) {
            $contact = Contact::where('business_id', $business_id)
                        ->where(function ($q) use ($user) {
                            $q->where('email', $user->email)->orWhere('mobile', $user->phone ?? '');
                        })->first();

            if (! $contact) {
                $contact = Contact::create([
                    'business_id' => $business_id,
                    'type' => 'customer',
                    'name' => trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')),
                    'mobile' => $user->phone ?? null,
                    'email' => $user->email ?? null,
                    'contact_status' => 'active',
                    'created_by' => $user->id,
                ]);
            }
        }

    // Build transaction input

        $location_id = 1;
        try {
            if (method_exists($user, 'getDefaultLocation') && $user->getDefaultLocation()) {
                $location_id = $user->getDefaultLocation()->id;
            } elseif ($business && $business->locations()->count() > 0) {
                $location_id = $business->locations()->first()->id;
            }
        } catch (\Exception $e) {
        }

        // Try to build a subscription-specific invoice_no from admin settings (prefix + next)
        $invoice_no = null;
        try {
            DB::beginTransaction();
            $adminSettings = \App\AdminSetting::lockForUpdate()->first();
            if ($adminSettings) {
                $prefix = $adminSettings->subscription_invoice_prefix ?? '';
                $next = intval($adminSettings->subscription_invoice_next ?? 1);
                // pad numeric part for readability (6 digits)
                $numeric = str_pad($next, 6, '0', STR_PAD_LEFT);
                $invoice_no = $prefix . $numeric;
                $adminSettings->subscription_invoice_next = $next + 1;
                $adminSettings->save();
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::warning('Failed to generate subscription invoice sequence: '.$e->getMessage());
            $invoice_no = null;
        }

        $input = [
            'location_id' => $location_id,
            'status' => 'final',
            'contact_id' => $contact->id ?? null,
            'transaction_date' => now()->toDateTimeString(),
            'is_recurring' => 0,
            'subscription_no' => $uniqueKey,
            'sub_type' => 'subscription_invoice',
            'sale_note' => 'Subscription renewal invoice for '.$subscription->plan_name,
        ];

        if (!empty($invoice_no)) {
            $input['invoice_no'] = $invoice_no;
        }

        $amount = $subscription->amount ?? 0;
        // Determine VAT percent from admin settings (0 if not set)
        try {
            $adminSettings = \App\AdminSetting::first();
            $vatPercent = floatval($adminSettings->subscription_vat_percent ?? 0);
        } catch (\Exception $e) {
            $vatPercent = 0;
        }

        $vatAmount = 0;
        if ($vatPercent > 0 && $amount > 0) {
            $vatAmount = round(($amount * ($vatPercent / 100)), 2);
        }

        $finalAmount = round($amount + $vatAmount, 2);

        // Apply rounding precision from admin settings (default 0 = whole number)
        $roundPrecision = intval($adminSettings->subscription_round_precision ?? 0);
        if ($roundPrecision < 0) {
            $roundPrecision = 0;
        }
        $finalAmountRounded = round($finalAmount, $roundPrecision, PHP_ROUND_HALF_UP);

        $invoice_total = [
            'total_before_tax' => $amount,
            'tax' => $vatAmount,
        ];

        try {
            $tx = $transactionUtil->createSellTransaction($business_id, array_merge($input, ['final_total' => $finalAmountRounded]), $invoice_total, $user->id);
            // Ensure payment status due
            $tx->payment_status = 'due';
            $tx->save();

            try {
                $subscription->invoice_sent_at = Carbon::now();
                $subscription->save();
            } catch (\Throwable $e) {
                Log::warning('Failed to set invoice_sent_at for subscription '.$subscription->id.': '.$e->getMessage());
            }

            try {
                $renewalSub = \App\Subscription::where('previous_subscription_id', $subscription->id)
                                ->where('is_renewal', 1)
                                ->where('status', 'pending')
                                ->latest()
                                ->first();

                if (! $renewalSub) {
                    $renewalSub = \App\Subscription::create([
                        'user_id' => $subscription->user_id,
                        'plan_name' => $subscription->plan_name . ' (Renewal)',
                        'billing_cycle' => $subscription->billing_cycle,
                        'amount' => $subscription->amount,
                        'start_date' => $subscription->end_date,
                        'end_date' => $this->calculateRenewalEndDate($subscription->billing_cycle, $subscription->end_date),
                        'status' => 'pending',
                        'is_renewal' => 1,
                        'previous_subscription_id' => $subscription->id,
                    ]);
                }

                $mpesa = \App\MpesaPayment::where('subscription_id', $renewalSub->id)
                    ->where('transaction_status', 'pending')
                    ->latest()
                    ->first();

                if (! $mpesa) {
                    $accountRef = 'SUB' . $renewalSub->id . '-' . strtoupper(Str::random(6));
                    $attempts = 0;
                    while (\App\MpesaPayment::where('account_reference', $accountRef)->exists()) {
                        $attempts++;
                        if ($attempts > 5) {
                            $accountRef = 'SUB' . $renewalSub->id . '-' . strtoupper(Str::uuid()->toString());
                            break;
                        }
                        $accountRef = 'SUB' . $renewalSub->id . '-' . strtoupper(Str::random(6));
                    }

                    $mpesa = \App\MpesaPayment::create([
                        'user_id' => $subscription->user_id,
                        'business_id' => $subscription->user->business_id ?? null,
                        'subscription_id' => $renewalSub->id,
                        'phone_number' => $subscription->user->phone ?? null,
                        'amount' => $finalAmount,
                        'account_reference' => $accountRef,
                        'payment_type' => \App\MpesaPayment::TYPE_SUBSCRIPTION,
                        'transaction_status' => 'pending',
                        'first_name' => $subscription->user->first_name ?? null,
                        'last_name' => $subscription->user->last_name ?? null,
                        'payer_name' => ($subscription->user->first_name ?? '') . ' ' . ($subscription->user->last_name ?? ''),
                    ]);
                } else {
                    $mpesa->update([
                        'phone_number' => $subscription->user->phone ?? $mpesa->phone_number,
                        'amount' => $finalAmount,
                    ]);
                }

                $renewalSub->pending_invoice_transaction_id = $tx->id;
                $renewalSub->pending_mpesa_payment_id = $mpesa->id ?? null;
                $renewalSub->save();

                $subscription->pending_invoice_transaction_id = $tx->id;
                $subscription->pending_mpesa_payment_id = $mpesa->id ?? null;
                $subscription->save();
            } catch (\Throwable $e) {
                Log::warning('Failed to create renewal subscription or mpesa payment for subscription '.$subscription->id.': '.$e->getMessage());

                if (app()->environment('testing')) {
                    throw $e;
                }
            }

            try {
                $mpdf = $transactionUtil->getEmailAttachmentForGivenTransaction($business_id, $tx->id, true);

                $paymentLink = route('invoice_payment', ['token' => $tx->invoice_token ?? '']);
                $subject = __('Invoice for subscription renewal - :plan', ['plan' => $subscription->plan_name]);
                $body = "An invoice has been generated for your upcoming subscription renewal (ends on ". $subscription->end_date->toFormattedDateString() .").\n";
                if ($vatAmount > 0) {
                    $body .= "Amount (ex VAT): ".number_format($amount,2)."\n";
                    $body .= "VAT (".$vatPercent."%): ".number_format($vatAmount,2)."\n";
                    $body .= "Total: ".number_format($finalAmount,2)."\n";
                } else {
                    $body .= "Amount: ".number_format($amount,2)."\n";
                }
                $body .= "Pay now: ". $paymentLink;

                $data = [
                    'subject' => $subject,
                    'email_body' => nl2br(e($body)),
                    'pdf' => $mpdf,
                    'pdf_name' => 'INVOICE-'.$tx->invoice_no.'.pdf',
                ];

                Notification::route('mail', $user->email)->notify(new CustomerNotification($data));
            } catch (\Throwable $e) {
                Log::warning('Failed to send subscription invoice email for subscription '.$subscription->id.': '.$e->getMessage());
            }

            // Log activity
            try {
                $this->notificationUtil->activityLog($tx, 'subscription_invoice_14d_sent', null, ['email' => $user->email, 'subscription_id' => $subscription->id], false, $business_id);
            } catch (\Exception $e) {
            }

            $this->info('14-day invoice generated and emailed for subscription '.$subscription->id.' to '.$user->email);
        } catch (\Throwable $e) {
            Log::error('Error creating invoice for subscription '.$subscription->id.': '.$e->getMessage());

            if (app()->environment('testing')) {
                throw $e;
            }
        }
    }

    protected function process7DayReminder(Subscription $subscription)
    {
        $user = $subscription->user;
        if (empty($user) || empty($user->email)) {
            return;
        }

        // Find the invoice transaction for this subscription end date
        $uniqueKey = 'sub_invoice_'.$subscription->id.'_'.($subscription->end_date->format('Ymd'));
        $tx = Transaction::where('subscription_no', $uniqueKey)->first();
        if (! $tx) {
            // No invoice generated at 14 days? Create it now (fallback)
            $this->process14DayInvoice($subscription);
            $tx = Transaction::where('subscription_no', $uniqueKey)->first();
        }

        if (! $tx) {
            $this->info('No transaction invoice to remind for subscription '.$subscription->id);
            return;
        }

        // If already paid, skip reminder
        if ($tx->payment_status === 'paid') {
            $this->info('Transaction already paid for subscription '.$subscription->id.' - skipping 7-day reminder.');
            return;
        }

        // Check if a 7-day reminder already exists in activity log
        $existingReminder = Activity::where('subject_type', Transaction::class)
                                ->where('subject_id', $tx->id)
                                ->where('description', 'like', '%subscription_7d_reminder%')
                                ->first();
        if ($existingReminder) {
            $this->info('7-day reminder already sent for transaction '.$tx->id);
            return;
        }

        // Prepare and send reminder
        $transactionUtil = new TransactionUtil();
        try {
            $mpdf = $transactionUtil->getEmailAttachmentForGivenTransaction($tx->business_id, $tx->id, true);

            $paymentLink = route('invoice_payment', ['token' => $tx->invoice_token ?? '']);
            $subject = __('Reminder: Invoice for subscription renewal due in 7 days - :plan', ['plan' => $subscription->plan_name]);
            $body = "Reminder: Your subscription will expire on " . $subscription->end_date->toFormattedDateString() . ".\n";
            $body .= "Please pay the invoice: ". $paymentLink;

            $data = [
                'subject' => $subject,
                'email_body' => nl2br(e($body)),
                'pdf' => $mpdf,
                'pdf_name' => 'INVOICE-'.$tx->invoice_no.'.pdf',
            ];

            Notification::route('mail', $user->email)->notify(new CustomerNotification($data));

            // Set reminder_sent_at on subscription to mark that a reminder was sent
            try {
                $subscription->reminder_sent_at = \Carbon\Carbon::now();
                $subscription->save();
            } catch (\Exception $e) {
                Log::warning('Failed to set reminder_sent_at for subscription '.$subscription->id.': '.$e->getMessage());
            }

            $this->notificationUtil->activityLog($tx, 'subscription_7d_reminder', null, ['email' => $user->email, 'subscription_id' => $subscription->id], false, $tx->business_id);
            $this->info('7-day reminder sent for subscription '.$subscription->id.' to '.$user->email);
        } catch (\Exception $e) {
            Log::error('Error sending 7-day reminder for subscription '.$subscription->id.': '.$e->getMessage());
        }
    }

    protected function calculateRenewalEndDate($billingCycle, $startDate = null)
    {
        $start = $startDate ? Carbon::parse($startDate) : Carbon::now();

        return match ($billingCycle) {
            'monthly' => $start->copy()->addMonth(),
            'quarterly' => $start->copy()->addMonths(3),
            'yearly' => $start->copy()->addYear(),
            default => $start->copy()->addMonth(),
        };
    }
}
