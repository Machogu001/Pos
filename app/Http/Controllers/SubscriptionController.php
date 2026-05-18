<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Subscription;
use App\AdminSetting;
use App\MpesaPayment;
use App\User;
use App\Http\Controllers\MpesaController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

use Carbon\Carbon;
use Exception;

class SubscriptionController extends Controller
{
    // Billing cycle constants
    const BILLING_MONTHLY = 'monthly';
    const BILLING_QUARTERLY = 'quarterly';
    const BILLING_YEARLY = 'yearly';

    // Payment status constants
    const PAYMENT_PENDING = 'pending';
    const PAYMENT_SUCCESS = 'success';
    const PAYMENT_FAILED = 'failed';

    /**
     * Return a standardized safe JSON error response.
     */
    private function safeJsonError(string $userMessage, int $statusCode = 400)
    {
        return response()->json([
            'success' => false,
            'message' => $userMessage,
        ], $statusCode);
    }

    /**
     * Show available plans to the user.
     */
    public function showPlans()
    {
        try {
            $settings = AdminSetting::firstOrFail();
            $user = Auth::user();
            $isAdmin = $user->role === 'admin';
            
            // Get active subscription with eager loading
            // Admin sees their own, regular users see their own
            $activeSubscription = $user->subscriptions()
                ->where('status', 'active')
                ->where('end_date', '>', now())
                ->latest()
                ->first();

            $plans = $this->getSubscriptionPlans($settings);

            // Payment history filtering based on role
            if ($isAdmin) {
                // Admin sees ALL payment history
                $paymentHistory = MpesaPayment::where(function ($q) {
                        $q->whereNotNull('subscription_id')
                          ->orWhere('account_reference', 'like', 'SUB%');
                    })
                    ->orderBy('created_at', 'desc')
                    ->get();
            } else {
                // Regular users see only their own payments
                $paymentHistory = $user->mpesaPayments()
                    ->where(function ($q) {
                        $q->whereNotNull('subscription_id')
                          ->orWhere('account_reference', 'like', 'SUB%');
                    })
                    ->orderBy('created_at', 'desc')
                    ->get();
            }

            return view('subscription.plans', compact(
                'settings', 
                'activeSubscription', 
                'plans',
                'paymentHistory'
            ));

        } catch (Exception $e) {
            Log::error('Error showing plans: ' . $e->getMessage());
            return back()->with('error', 'Unable to load subscription plans. Please try again.');
        }
    }

    /**
     * Get subscription plans configuration
     */
    private function getSubscriptionPlans($settings)
    {
        return [
            self::BILLING_MONTHLY => [
                'name' => 'Monthly Plan',
                'price' => $settings->monthly_price,
                'duration' => '1 Month',
                'features' => [
                    'Full access to all content',
                    'Priority customer support',
                    'Monthly updates',
                    'Basic analytics'
                ],
                'savings' => null
            ],
            self::BILLING_QUARTERLY => [
                'name' => 'Quarterly Plan',
                'price' => $settings->quarterly_price,
                'duration' => '3 Months',
                'features' => [
                    'Everything in Monthly',
                    'Advanced analytics',
                    'Early access to new features',
                    'Discount compared to monthly'
                ],
                'savings' => $this->calculateSavings($settings->monthly_price * 3, $settings->quarterly_price)
            ],
            self::BILLING_YEARLY => [
                'name' => 'Yearly Plan',
                'price' => $settings->yearly_price,
                'duration' => '12 Months',
                'features' => [
                    'Everything in Quarterly',
                    'Premium support',
                    'Customization options',
                    'Maximum savings'
                ],
                'savings' => $this->calculateSavings($settings->monthly_price * 12, $settings->yearly_price)
            ]
        ];
    }

    /**
     * Calculate savings percentage
     */
    private function calculateSavings($original, $discounted)
    {
        if ($original <= $discounted) return null;
        
        $savings = (($original - $discounted) / $original) * 100;
        return round($savings, 0);
    }

    /**
     * First-time subscription purchase
     */
    public function processPayment(Request $request)
    {
        DB::beginTransaction();

        try {
            $user = Auth::user();
            
            $validator = Validator::make($request->all(), [
                'billing_cycle' => 'required|in:monthly,quarterly,yearly',
                'phone' => ['required', function ($attribute, $value, $fail) {
                    if (! MpesaPayment::normalizePhoneNumber($value)) {
                        $fail(__('payment.invalid_phone_format'));
                    }
                }],
                'checkout_request_id' => 'nullable|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid input data',
                    'errors' => $validator->errors()
                ], 422);
            }

            $settings = AdminSetting::firstOrFail();
            $billingCycle = $request->billing_cycle;
            $request->merge(['phone' => MpesaPayment::normalizePhoneNumber($request->phone)]);
            $amount = $settings->{$billingCycle . '_price'};

            // Apply subscription VAT if set in admin settings and rounding precision
            $vatPercent = floatval($settings->subscription_vat_percent ?? 0);
            $vatAmount = 0;
            if ($vatPercent > 0 && $amount > 0) {
                $vatAmount = round(($amount * ($vatPercent / 100)), 2);
            }
            $finalAmount = round($amount + $vatAmount, 2);
            $roundPrecision = intval($settings->subscription_round_precision ?? 0);
            // Round final amount to configured precision (default 0 -> whole number)
            $finalAmountRounded = round($finalAmount, $roundPrecision, PHP_ROUND_HALF_UP);
            $checkoutRequestId = $request->checkout_request_id;

            // Validate amount
            if ($amount <= 0) {
                throw new Exception('Invalid subscription price');
            }

            // Check for existing pending subscription
            $subscription = $this->getOrCreateSubscription($user, $billingCycle, $amount);

            // Check for existing payment (use VAT-inclusive rounded amount)
            $payment = $this->getOrCreatePayment($user, $subscription, $request->phone, $finalAmountRounded);

            // Ensure a subscription invoice Transaction exists and is linked
            try {
                $this->ensurePendingInvoiceTransaction(
                    $subscription,
                    $user,
                    $vatAmount,
                    $finalAmountRounded,
                    'Subscription invoice for ' . $subscription->plan_name
                );
            } catch (\Exception $e) {
                Log::warning('Error ensuring subscription invoice exists: ' . $e->getMessage());
            }
            // If we have a checkout_request_id, check payment status
            if ($checkoutRequestId) {
                $payment->update(['checkout_request_id' => $checkoutRequestId]);
                
                $paymentStatus = $this->resolvePaymentStatus($checkoutRequestId);
                
                if ($paymentStatus === self::PAYMENT_SUCCESS) {
                    $payment->update([
                        'transaction_status' => 'paid',
                        'paid_at' => Carbon::now(),
                        'result_code' => 0,
                        'result_desc' => 'Payment completed successfully',
                    ]);

                    $activated = $this->activateSubscription($payment);
                    DB::commit();

                    if (! $activated) {
                        return $this->safeJsonError('Payment confirmed but subscription activation failed. Please contact support.', 500);
                    }

                    return response()->json([
                        'success' => true,
                        'message' => 'Payment confirmed and subscription activated!',
                        'redirect_url' => route('subscription.success')
                    ]);
                }
                
                DB::commit();
                return response()->json([
                    'success' => false,
                    'message' => 'Payment is still pending. Please wait for confirmation.',
                    'status' => $paymentStatus
                ]);
            }

            // Initiate new payment
            $mpesaController = new MpesaController();
            
            // Use subscription-specific credentials if available
            $settings = AdminSetting::first();
            if ($settings && $settings->subscription_mpesa_consumer_key) {
                $mpesaController->setCustomCredentials(
                    $settings->subscription_mpesa_consumer_key,
                    $settings->subscription_mpesa_consumer_secret,
                    $settings->subscription_mpesa_shortcode,
                    $settings->subscription_mpesa_passkey,
                    $settings->subscription_mpesa_callback
                );
            }
            
            $response = $mpesaController->initiatePaymentDirect(
                $request->phone, 
                $finalAmountRounded,
                $payment->account_reference
            );

            if ($response['success']) {
                $payment->update([
                    'checkout_request_id' => $response['checkout_request_id'],
                    'merchant_request_id' => $response['merchant_request_id'],
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'payer_name' => $user->first_name . ' ' . $user->last_name
                ]);

                $subscription->update([
                    'checkout_request_id' => $response['checkout_request_id']
                ]);

                // Store in session
                session([
                    'payment_phone' => $request->phone,
                    'checkout_request_id' => $response['checkout_request_id'],
                    'subscription_id' => $subscription->id
                ]);
                session()->save();

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Payment initiated successfully. Enter your M-Pesa PIN.',
                    'checkout_request_id' => $response['checkout_request_id'],
                    'subscription_id' => $subscription->id
                ]);
            }

            throw new Exception($response['errorMessage'] ?? 'Failed to initiate payment');

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Payment processing error: ' . $e->getMessage());

            return $this->safeJsonError($e->getMessage() ?: 'Payment request failed. Please try again.', 400);
        }
    }

    /**
     * Get or create subscription
     */
    private function getOrCreateSubscription($user, $billingCycle, $amount)
    {
        $subscription = Subscription::where('user_id', $user->id)
            ->where('status', 'pending')
            ->where(function ($query) {
                $query->whereNull('is_renewal')
                    ->orWhere('is_renewal', false);
            })
            ->latest()
            ->first();

        if ($subscription) {
            $subscription->fill([
                'plan_name' => ucfirst($billingCycle) . ' Plan',
                'billing_cycle' => $billingCycle,
                'amount' => $amount,
                'start_date' => $subscription->start_date ?: now(),
                'end_date' => $this->calculateEndDate($billingCycle, $subscription->start_date ?: now()),
            ]);
            $subscription->save();

            return $subscription;
        }

        return Subscription::create([
            'user_id' => $user->id,
            'plan_name' => ucfirst($billingCycle) . ' Plan',
            'billing_cycle' => $billingCycle,
            'amount' => $amount,
            'start_date' => now(),
            'end_date' => $this->calculateEndDate($billingCycle),
            'status' => 'pending'
        ]);
    }

    private function getOrCreateRenewalSubscription($user, $latestSubscription, $billingCycle, $amount, $renewalStartDate)
    {
        $subscription = Subscription::where('user_id', $user->id)
            ->where('status', 'pending')
            ->where('is_renewal', true)
            ->where('previous_subscription_id', $latestSubscription->id)
            ->latest()
            ->first();

        if ($subscription) {
            $subscription->fill([
                'plan_name' => ucfirst($billingCycle) . ' Plan (Renewal)',
                'billing_cycle' => $billingCycle,
                'amount' => $amount,
                'start_date' => $renewalStartDate,
                'end_date' => $this->calculateEndDate($billingCycle, $renewalStartDate),
                'status' => 'pending',
            ]);
            $subscription->save();

            return $subscription;
        }

        return Subscription::create([
            'user_id' => $user->id,
            'plan_name' => ucfirst($billingCycle) . ' Plan (Renewal)',
            'billing_cycle' => $billingCycle,
            'amount' => $amount,
            'start_date' => $renewalStartDate,
            'end_date' => $this->calculateEndDate($billingCycle, $renewalStartDate),
            'status' => 'pending',
            'is_renewal' => true,
            'previous_subscription_id' => $latestSubscription->id
        ]);
    }

    /**
     * Get or create payment record
     */
    private function getOrCreatePayment($user, $subscription, $phone, $amount)
    {
        $normalizedPhone = MpesaPayment::normalizePhoneNumber($phone) ?? $phone;
        $payment = null;

        if (! empty($subscription->pending_mpesa_payment_id)) {
            $payment = MpesaPayment::where('id', $subscription->pending_mpesa_payment_id)
                ->where('payment_type', MpesaPayment::TYPE_SUBSCRIPTION)
                ->first();
        }

        if (! $payment) {
            $payment = MpesaPayment::where('subscription_id', $subscription->id)
                ->where('payment_type', MpesaPayment::TYPE_SUBSCRIPTION)
                ->whereIn('transaction_status', [self::PAYMENT_PENDING, self::PAYMENT_FAILED])
                ->latest()
                ->first();
        }

        if ($payment) {
            $payment->fill([
                'user_id' => $user->id,
                'business_id' => $user->business_id,
                'phone_number' => $normalizedPhone,
                'amount' => $amount,
                'payment_type' => MpesaPayment::TYPE_SUBSCRIPTION,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'payer_name' => trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')),
            ]);
            $payment->save();
        } else {
            $payment = MpesaPayment::create([
                'user_id' => $user->id,
                'business_id' => $user->business_id,
                'subscription_id' => $subscription->id,
                'phone_number' => $normalizedPhone,
                'amount' => $amount,
                'account_reference' => $this->generateSubscriptionAccountReference($subscription),
                'payment_type' => MpesaPayment::TYPE_SUBSCRIPTION,
                'transaction_status' => self::PAYMENT_PENDING,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'payer_name' => trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')),
            ]);
        }

        if ($subscription->pending_mpesa_payment_id !== $payment->id) {
            $subscription->pending_mpesa_payment_id = $payment->id;
            $subscription->save();
        }

        return $payment;
    }

    private function generateSubscriptionAccountReference(Subscription $subscription): string
    {
        $prefix = ! empty($subscription->is_renewal) ? 'RENEW' : 'SUB';
        $accountRef = $prefix . $subscription->id . '-' . strtoupper(Str::random(6));
        $attempts = 0;

        while (MpesaPayment::where('account_reference', $accountRef)->exists()) {
            $attempts++;
            if ($attempts > 5) {
                return $prefix . $subscription->id . '-' . strtoupper(Str::uuid()->toString());
            }

            $accountRef = $prefix . $subscription->id . '-' . strtoupper(Str::random(6));
        }

        return $accountRef;
    }

    private function ensurePendingInvoiceTransaction(Subscription $subscription, $user, float $vatAmount, float $finalAmount, string $saleNote): void
    {
        if (! empty($subscription->pending_invoice_transaction_id)) {
            $existingTx = \App\Transaction::find($subscription->pending_invoice_transaction_id);

            if ($existingTx && $existingTx->payment_status !== 'paid') {
                return;
            }
        }

        $transactionUtil = new \App\Utils\TransactionUtil();
        $location_id = 1;
        try {
            if (method_exists($user, 'getDefaultLocation') && $user->getDefaultLocation()) {
                $location_id = $user->getDefaultLocation()->id;
            }
        } catch (\Exception $e) {
        }

        $invoice_no = null;
        try {
            DB::beginTransaction();
            $adminSettings = \App\AdminSetting::lockForUpdate()->first();
            if ($adminSettings) {
                $prefix = $adminSettings->subscription_invoice_prefix ?? '';
                $next = intval($adminSettings->subscription_invoice_next ?? 1);
                $numeric = str_pad($next, 6, '0', STR_PAD_LEFT);
                $invoice_no = $prefix . $numeric;
                $adminSettings->subscription_invoice_next = $next + 1;
                $adminSettings->save();
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            $invoice_no = null;
        }

        $contact = null;
        try {
            $contactQuery = \App\Contact::where(function($q) use ($user) {
                $q->where('email', $user->email)->orWhere('mobile', $user->phone ?? '');
            });
            if (! empty($user->business_id)) {
                $contactQuery->where('business_id', $user->business_id);
            }
            $contact = $contactQuery->first();

            if (! $contact) {
                $contact = \App\Contact::create([
                    'business_id' => $user->business_id ?? null,
                    'type' => 'customer',
                    'name' => trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) ?: ($user->email ?? 'Subscriber'),
                    'mobile' => $user->phone ?? null,
                    'email' => $user->email ?? null,
                    'contact_status' => 'active',
                    'created_by' => $user->id,
                ]);
            }
        } catch (\Exception $e) {
            Log::warning('Failed to find/create contact for subscriber: ' . $e->getMessage());
            $contact = null;
        }

        $input = [
            'location_id' => $location_id,
            'status' => 'final',
            'contact_id' => $contact->id ?? null,
            'transaction_date' => now()->toDateTimeString(),
            'is_recurring' => 0,
            'subscription_no' => 'sub_invoice_' . $subscription->id . '_' . now()->format('Ymd'),
            'sub_type' => 'subscription_invoice',
            'sale_note' => $saleNote,
        ];

        if (! empty($invoice_no)) {
            $input['invoice_no'] = $invoice_no;
        }

        $invoice_total = [
            'total_before_tax' => $subscription->amount,
            'tax' => $vatAmount,
        ];

        try {
            $tx = $transactionUtil->createSellTransaction($user->business_id ?? null, array_merge($input, ['final_total' => $finalAmount]), $invoice_total, $user->id);
            $tx->payment_status = 'due';
            $tx->save();

            $subscription->pending_invoice_transaction_id = $tx->id;
            $subscription->save();
        } catch (\Exception $e) {
            Log::warning('Failed to create invoice transaction for subscription: ' . $e->getMessage());
        }
    }


    /**
     * M-Pesa STK Push initiation
     */
    public function stkPush(Request $request)
    {
        DB::beginTransaction();

        try {
            $user = Auth::user();
            
            $validator = Validator::make($request->all(), [
                'phone' => ['required', function ($attribute, $value, $fail) {
                    if (! MpesaPayment::normalizePhoneNumber($value)) {
                        $fail(__('payment.invalid_phone_format'));
                    }
                }],
                'plan_key' => 'required|in:monthly,quarterly,yearly'
            ]);

            $request->merge(['phone' => MpesaPayment::normalizePhoneNumber($request->phone)]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $settings = AdminSetting::firstOrFail();
            $billingCycle = $request->plan_key;
            $amount = $settings->{$billingCycle . '_price'};
            // Apply subscription VAT if configured and rounding precision
            $vatPercentTmp = floatval($settings->subscription_vat_percent ?? 0);
            $vatAmountTmp = 0;
            if ($vatPercentTmp > 0 && $amount > 0) {
                $vatAmountTmp = round(($amount * ($vatPercentTmp / 100)), 2);
            }
            $finalAmountTmp = round($amount + $vatAmountTmp, 2);
            $roundPrecisionTmp = intval($settings->subscription_round_precision ?? 0);
            $finalAmountTmpRounded = round($finalAmountTmp, $roundPrecisionTmp, PHP_ROUND_HALF_UP);

            // Create subscription
            $subscription = $this->getOrCreateSubscription($user, $billingCycle, $amount);

            // Ensure invoice transaction exists before creating payment
            try {
                $this->ensurePendingInvoiceTransaction(
                    $subscription,
                    $user,
                    $vatAmountTmp,
                    $finalAmountTmpRounded,
                    'Subscription invoice for ' . $subscription->plan_name
                );
            } catch (\Exception $e) {
                Log::warning('Error creating invoice for STK push: ' . $e->getMessage());
            }

            $payment = $this->getOrCreatePayment($user, $subscription, $request->phone, $finalAmountTmpRounded);

            // Initiate STK push
            $mpesaController = new MpesaController();
            
            // Use subscription-specific credentials if available
            $settings = AdminSetting::first();
            if ($settings && $settings->subscription_mpesa_consumer_key) {
                $mpesaController->setCustomCredentials(
                    $settings->subscription_mpesa_consumer_key,
                    $settings->subscription_mpesa_consumer_secret,
                    $settings->subscription_mpesa_shortcode,
                    $settings->subscription_mpesa_passkey,
                    $settings->subscription_mpesa_callback
                );
            }
            
            $response = $mpesaController->initiatePaymentDirect(
                $request->phone, 
                $finalAmountTmpRounded, 
                $payment->account_reference
            );

            if (isset($response['success']) && $response['success']) {
                $payment->update([
                    'checkout_request_id' => $response['checkout_request_id'],
                    'merchant_request_id' => $response['merchant_request_id'],
                    'result_code' => null,
                    'result_desc' => null,
                    'mpesa_receipt_number' => null,
                    'paid_at' => null,
                    'transaction_status' => self::PAYMENT_PENDING,
                ]);

                $subscription->update([
                    'checkout_request_id' => $response['checkout_request_id'],
                    'pending_mpesa_payment_id' => $payment->id,
                ]);

                session([
                    'payment_phone' => $request->phone,
                    'checkout_request_id' => $response['checkout_request_id'],
                    'subscription_id' => $subscription->id
                ]);
                session()->save();

                DB::commit();

                return response()->json([
                    'success' => true,
                    'checkout_request_id' => $response['checkout_request_id'],
                    'message' => 'STK push sent successfully. Please check your phone.',
                    'subscription_id' => $subscription->id
                ]);
            }

            throw new Exception($response['errorMessage'] ?? 'Failed to initiate STK push');

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('STK Push error: ' . $e->getMessage());

            return $this->safeJsonError($e->getMessage() ?: 'Failed to send STK push. Please try again.', 400);
        }
    }

    /**
     * Renew an active subscription
     */
    public function renew(Request $request)
    {
        DB::beginTransaction();

        try {
            $user = Auth::user();
            
            $validator = Validator::make($request->all(), [
                'billing_cycle' => 'required|in:monthly,quarterly,yearly',
                'phone' => ['required', function ($attribute, $value, $fail) {
                    if (! MpesaPayment::normalizePhoneNumber($value)) {
                        $fail(__('payment.invalid_phone_format'));
                    }
                }]
            ]);

            $request->merge(['phone' => MpesaPayment::normalizePhoneNumber($request->phone)]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid input data',
                    'errors' => $validator->errors()
                ], 422);
            }

            $settings = AdminSetting::firstOrFail();
            $billingCycle = $request->billing_cycle;
            $amount = $settings->{$billingCycle . '_price'};

            // Apply subscription VAT if configured and rounding precision for renewals
            $vatPercent = floatval($settings->subscription_vat_percent ?? 0);
            $vatAmount = 0;
            if ($vatPercent > 0 && $amount > 0) {
                $vatAmount = round(($amount * ($vatPercent / 100)), 2);
            }
            $finalAmount = round($amount + $vatAmount, 2);
            $roundPrecision = intval($settings->subscription_round_precision ?? 0);
            $finalAmountRounded = round($finalAmount, $roundPrecision, PHP_ROUND_HALF_UP);

            // Get latest subscription to determine renewal base date (active or expired)
            $latestSub = $user->subscriptions()
                ->whereIn('status', ['active', 'expired'])
                ->latest()
                ->first();

            if (!$latestSub) {
                return response()->json([
                    'success' => false,
                    'message' => 'No existing subscription found for renewal. Please create a new subscription instead.'
                ], 400);
            }

            // Determine start date for renewal
            $renewalStartDate = now();
            if ($latestSub->status === 'active' && $latestSub->end_date > now()) {
                // If current subscription is still active, start from its end date
                $renewalStartDate = $latestSub->end_date;
            }
            // If expired or inactive, start from now

            // Create or reuse renewal subscription
            $newSub = $this->getOrCreateRenewalSubscription($user, $latestSub, $billingCycle, $amount, $renewalStartDate);

            try {
                $this->ensurePendingInvoiceTransaction(
                    $newSub,
                    $user,
                    $vatAmount,
                    $finalAmountRounded,
                    'Subscription renewal invoice for ' . $newSub->plan_name
                );
            } catch (\Exception $e) {
                Log::warning('Failed to ensure invoice transaction for renewal: ' . $e->getMessage());
            }

            $payment = $this->getOrCreatePayment($user, $newSub, $request->phone, $finalAmountRounded);

            // Initiate payment
            $mpesaController = new MpesaController();
            
            // Use subscription-specific credentials if available
            $settings = AdminSetting::first();
            if ($settings && $settings->subscription_mpesa_consumer_key) {
                $mpesaController->setCustomCredentials(
                    $settings->subscription_mpesa_consumer_key,
                    $settings->subscription_mpesa_consumer_secret,
                    $settings->subscription_mpesa_shortcode,
                    $settings->subscription_mpesa_passkey,
                    $settings->subscription_mpesa_callback
                );
            }
            
            $response = $mpesaController->initiatePaymentDirect(
                $request->phone, 
                $finalAmountRounded, 
                $payment->account_reference
            );

            if ($response['success']) {
                $payment->update([
                    'checkout_request_id' => $response['checkout_request_id'],
                    'merchant_request_id' => $response['merchant_request_id'],
                    'result_code' => null,
                    'result_desc' => null,
                    'mpesa_receipt_number' => null,
                    'paid_at' => null,
                    'transaction_status' => self::PAYMENT_PENDING,
                ]);

                $newSub->update([
                    'checkout_request_id' => $response['checkout_request_id'],
                    'pending_mpesa_payment_id' => $payment->id,
                ]);

                session([
                    'payment_phone' => $request->phone,
                    'checkout_request_id' => $response['checkout_request_id'],
                    'subscription_id' => $newSub->id
                ]);
                session()->save();

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Renewal initiated. Enter your M-Pesa PIN.',
                    'checkout_request_id' => $response['checkout_request_id'],
                    'subscription_id' => $newSub->id
                ]);
            }

            throw new Exception($response['errorMessage'] ?? 'Failed to initiate renewal');

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Renewal error: ' . $e->getMessage());

            return $this->safeJsonError($e->getMessage() ?: 'Renewal request failed. Please try again.', 400);
        }
    }

    /**
     * Calculate subscription end date
     */
    private function calculateEndDate($billingCycle, $startDate = null)
    {
        $start = $startDate ? Carbon::parse($startDate) : now();
        
        Log::info("Calculating end date:", [
            'billing_cycle' => $billingCycle,
            'start_date' => $start->toDateTimeString(),
            'start_date_source' => $startDate ? 'provided' : 'now()'
        ]);
        
        $result = match($billingCycle) {
            self::BILLING_MONTHLY => $start->copy()->addMonth(),
            self::BILLING_QUARTERLY => $start->copy()->addMonths(3),
            self::BILLING_YEARLY => $start->copy()->addYear(),
            default => $start->copy()->addMonth(),
        };
        
        Log::info("Calculated end date:", ['end_date' => $result->toDateTimeString()]);
        
        return $result;
    }

    /**
     * Check payment status
     */
    private function resolvePaymentStatus($checkoutRequestId)
    {
        try {
            $mpesaController = new MpesaController();
            $result = $mpesaController->queryMpesaPaymentStatus($checkoutRequestId);
            
            return $result['success'] ? $result['transaction_status'] : self::PAYMENT_PENDING;
        } catch (Exception $e) {
            Log::error('Payment status check error: ' . $e->getMessage());
            return self::PAYMENT_PENDING;
        }
    }

    /**
     * Public payment status endpoint for subscription.check-status route.
     */
    public function checkPaymentStatus($checkoutRequestId)
    {
        try {
            $payment = MpesaPayment::where('checkout_request_id', $checkoutRequestId)->first();

            if (! $payment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment not found',
                ], 404);
            }

            $status = $this->resolvePaymentStatus($checkoutRequestId);

            if ($status === self::PAYMENT_SUCCESS) {
                $payment->update(['transaction_status' => 'paid']);
                $this->activateSubscription($payment);
            } elseif ($status === self::PAYMENT_FAILED) {
                $payment->update(['transaction_status' => 'failed']);
            }

            return response()->json([
                'success' => true,
                'transaction_status' => $status === self::PAYMENT_SUCCESS ? 'paid' : $status,
                'receipt_number' => $payment->mpesa_receipt_number,
            ]);
        } catch (Exception $e) {
            Log::error('Public status endpoint error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error checking payment status',
            ], 500);
        }
    }

    /**
     * Poll payment status with enhanced receipt handling
     */
    public function pollPaymentStatus($checkoutRequestId)
    {
        try {
            $mpesaController = new MpesaController();
            $result = $mpesaController->queryMpesaPaymentStatus($checkoutRequestId);

            if ($result['success'] && $result['transaction_status'] === 'success') {
                // Find payment record
                $payment = MpesaPayment::where('checkout_request_id', $checkoutRequestId)->first();

                if ($payment) {
                    // Update payment record with receipt number
                    $payment->update([
                        'transaction_status' => 'paid',
                        'mpesa_receipt_number' => $result['receipt_number'] ?? $payment->mpesa_receipt_number,
                        'paid_at' => now(),
                    ]);

                    // Activate subscription and user
                    $activationResult = $this->activateSubscription($payment);
                    
                    if ($activationResult) {
                        return response()->json([
                            'success' => true,
                            'status' => 'success',
                            'activated' => true,
                            'message' => 'Subscription activated successfully',
                            'receipt_number' => $payment->mpesa_receipt_number
                        ]);
                    }
                }
            }

            return response()->json([
                'success' => true,
                'status' => $result['transaction_status'] ?? 'pending',
                'activated' => false,
                'receipt_number' => $result['receipt_number'] ?? null
            ]);

        } catch (\Exception $e) {
            Log::error('Payment status check error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'status' => 'error',
                'message' => 'Error checking payment status'
            ]);
        }
    }

    /**
     * M-Pesa payment callback - ENHANCED VERSION
     */
    public function paymentCallback(Request $request)
    {
        DB::beginTransaction();

        try {
            Log::info('MPesa Callback Received: ', $request->all());
            
            $phone = $request->input('phone');
            $checkoutRequestId = $request->input('checkout_request_id');
            $mpesaReceipt = $request->input('mpesa_receipt_number');
            
            // Find payment
            $payment = $this->findPayment($checkoutRequestId, $phone);
            
            if (!$payment) {
                Log::warning("Payment record not found for checkout: {$checkoutRequestId}, phone: {$phone}");
                return response()->json([
                    'success' => false, 
                    'message' => 'Payment record not found',
                    'transaction_status' => 'not_found'
                ], 404);
            }
            
            // Update payment with receipt number if provided in callback
            if ($mpesaReceipt && empty($payment->mpesa_receipt_number)) {
                $payment->update(['mpesa_receipt_number' => $mpesaReceipt]);
                Log::info("Updated payment {$payment->id} with receipt: {$mpesaReceipt}");
            }
            
            // Extract receipt from callback data if still missing
            if (empty($payment->mpesa_receipt_number)) {
                $extractedReceipt = $this->extractReceiptFromCallback($request->all());
                if ($extractedReceipt) {
                    $payment->update(['mpesa_receipt_number' => $extractedReceipt]);
                    Log::info("Extracted receipt from callback: {$extractedReceipt}");
                }
            }
            
            // Check payment status
            $paymentStatus = $this->resolvePaymentStatus($checkoutRequestId);
            
            if ($paymentStatus === self::PAYMENT_SUCCESS) {
                // Update payment
                $payment->update([
                    'transaction_status' => 'paid',
                    'paid_at' => Carbon::now(),
                    'result_code' => 0,
                    'result_desc' => 'Payment completed successfully'
                ]);
                
                // Activate subscription
                $activationResult = $this->activateSubscription($payment);
                
                DB::commit();

                if ($activationResult) {
                    Log::info("✅ Payment confirmed and subscription activated for checkout: {$checkoutRequestId}");
                    return response()->json([
                        'success' => true,
                        'transaction_status' => 'success',
                        'message' => 'Payment confirmed and subscription activated!'
                    ]);
                }

                Log::error("Payment successful but subscription activation failed for checkout: {$checkoutRequestId}");
                return response()->json([
                    'success' => false,
                    'message' => 'Payment successful but subscription activation failed'
                ], 500);
            }
            
            // Update failed payment
            if ($paymentStatus === self::PAYMENT_FAILED) {
                $payment->update([
                    'transaction_status' => 'failed',
                    'result_desc' => 'Payment failed or was cancelled'
                ]);
                Log::info("Payment failed for checkout: {$checkoutRequestId}");
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'transaction_status' => $paymentStatus,
                'message' => 'Payment status: ' . $paymentStatus
            ]);

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Payment callback error: ' . $e->getMessage());
            Log::error($e->getTraceAsString());

            return response()->json([
                'success' => false,
                'message' => 'Callback processing failed'
            ], 500);
        }
    }

    /**
     * Extract M-Pesa receipt number from callback data
     */
    private function extractReceiptFromCallback($callbackData)
    {
        try {
            // Check various possible locations for the receipt number
            if (isset($callbackData['Body']['stkCallback']['CallbackMetadata']['Item'])) {
                foreach ($callbackData['Body']['stkCallback']['CallbackMetadata']['Item'] as $item) {
                    if (isset($item['Name']) && $item['Name'] === 'MpesaReceiptNumber' && isset($item['Value'])) {
                        return $item['Value'];
                    }
                }
            }
            
            // Check alternative formats
            if (isset($callbackData['mpesa_receipt_number'])) {
                return $callbackData['mpesa_receipt_number'];
            }
            
            if (isset($callbackData['rececept_number'])) {
                return $callbackData['rececept_number'];
            }
            
            if (isset($callbackData['TransactionReceipt'])) {
                return $callbackData['TransactionReceipt'];
            }
            
            return null;
        } catch (Exception $e) {
            Log::error('Error extracting receipt from callback: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Find payment by various identifiers
     */
    private function findPayment($checkoutRequestId = null, $phone = null)
    {
        if ($checkoutRequestId) {
            $payment = MpesaPayment::where('checkout_request_id', $checkoutRequestId)->first();
            if ($payment) return $payment;
        }
        
        if ($phone) {
            $payment = MpesaPayment::where('phone_number', $phone)
                ->orderBy('created_at', 'desc')
                ->first();
            if ($payment) return $payment;
        }
        
        return null;
    }

    /**
     * Activate subscription from payment - USING PAYMENT DATE AS START DATE
     */
    public function activateSubscription($payment)
    {
        DB::beginTransaction();

        try {
            // Safety: only activate when the payment_type explicitly indicates subscription
            if (($payment->payment_type ?? null) !== 'subscription') {
                Log::warning('activateSubscription called for non-subscription payment; aborting', [
                    'payment_id' => $payment->id,
                    'payment_type' => $payment->payment_type ?? 'null'
                ]);

                DB::rollBack();
                return false;
            }

            // Guard: do not activate if the payment is not marked as paid/success
            if (!in_array($payment->transaction_status, ['paid', 'success'], true)) {
                Log::warning('activateSubscription called for payment that is not paid; aborting', [
                    'payment_id' => $payment->id,
                    'transaction_status' => $payment->transaction_status ?? 'null'
                ]);

                DB::rollBack();
                return false;
            }

            Log::info("Starting subscription activation for payment ID: {$payment->id}");
            Log::info("Payment M-Pesa receipt: " . ($payment->mpesa_receipt_number ?? 'NULL'));
            Log::info("Payment created at: " . $payment->created_at->toDateTimeString());
            
            // Find the subscription
            $subscription = $this->findSubscriptionForPayment($payment);
            
            if (!$subscription) {
                Log::warning("No subscription found for payment: {$payment->id}");
                DB::rollBack();
                return false;
            }

            // If this payment is linked to a pending renewal that has an invoice transaction,
            // consume that transaction by creating a TransactionPayment entry so the
            // invoice is marked paid and mapping is durable. This ensures paying the
            // generated invoice (from the 14-day job) will mark the invoice paid and
            // tie the MpesaPayment -> Transaction mapping for future audits.
            try {
                if (!empty($subscription->pending_invoice_transaction_id) && $payment->transaction_status === 'paid') {
                    $tx = \App\Transaction::find($subscription->pending_invoice_transaction_id);
                        if ($tx && $tx->payment_status !== 'paid') {
                            // Ensure the transaction contact is the subscriber so receipts show subscriber name
                            try {
                                $subscriber = $subscription->user ?? null;
                                if ($subscriber) {
                                    // Find or create contact for subscriber
                                    $subscriberContact = null;
                                    try {
                                        $contactQuery = \App\Contact::where(function($q) use ($subscriber) {
                                            $q->where('email', $subscriber->email)->orWhere('mobile', $subscriber->phone ?? '');
                                        });
                                        if (! empty($subscriber->business_id)) {
                                            $contactQuery->where('business_id', $subscriber->business_id);
                                        }
                                        $subscriberContact = $contactQuery->first();

                                        if (! $subscriberContact) {
                                            $subscriberContact = \App\Contact::create([
                                                'business_id' => $subscriber->business_id ?? null,
                                                'type' => 'customer',
                                                'name' => trim(($subscriber->first_name ?? '') . ' ' . ($subscriber->last_name ?? '')) ?: ($subscriber->email ?? 'Subscriber'),
                                                'mobile' => $subscriber->phone ?? null,
                                                'email' => $subscriber->email ?? null,
                                                'contact_status' => 'active',
                                                'created_by' => $subscriber->id,
                                            ]);
                                        }
                                    } catch (\Exception $e) {
                                        Log::warning('Failed to find/create subscriber contact during activation: ' . $e->getMessage());
                                        $subscriberContact = null;
                                    }

                                    if ($subscriberContact) {
                                        $tx->contact_id = $subscriberContact->id;
                                        $tx->save();
                                    }
                                }
                            } catch (\Exception $e) {
                                Log::warning('Error ensuring transaction contact for subscription activation: ' . $e->getMessage());
                            }
                        $transactionUtil = new \App\Utils\TransactionUtil();

                        $ref_count = $transactionUtil->setAndGetReferenceCount('sell_payment', $tx->business_id);
                        $payment_ref_no = $transactionUtil->generateReferenceNumber('sell_payment', $ref_count, $tx->business_id);

                        $tpData = [
                            'paid_on' => Carbon::now()->toDateTimeString(),
                            'transaction_id' => $tx->id,
                            'amount' => $payment->amount ?? $tx->final_total,
                            'payment_for' => $tx->contact_id,
                            'method' => 'mpesa',
                            'note' => 'Auto-consumed subscription renewal via M-Pesa ' . ($payment->mpesa_receipt_number ?? ''),
                            'paid_through_link' => 0,
                            'gateway' => 'mpesa',
                            'business_id' => $tx->business_id,
                            'payment_ref_no' => $payment_ref_no,
                        ];

                        // Create a parent payment record and allocate it across due transactions
                        // using TransactionUtil->payAtOnce so partial payments are handled correctly.
                        $parentInputs = $tpData;
                        // mark as parent (is_advance like behavior) so allocation runs properly
                        $parentInputs['created_by'] = auth()->id() ?? $payment->user_id ?? 1;
                        $parentInputs['payment_for'] = $tpData['payment_for'] ?? $tx->contact_id;
                        $parentInputs['business_id'] = $tpData['business_id'] ?? $tx->business_id;

                        $parent_payment = \App\TransactionPayment::create($parentInputs);

                        // Distribute payment among unpaid transactions (this will create TransactionPayment rows)
                        $excess = $transactionUtil->payAtOnce($parent_payment, 'sell');

                        // Update mpesa payment to reference the consumed transaction (important for audit)
                        $payment->update(['consumed_by_transaction_id' => $tx->id, 'consumed_at' => Carbon::now()]);

                        // Ensure the specific transaction's payment_status is recalculated
                        $payment_status = $transactionUtil->updatePaymentStatus($tx->id, $tx->final_total);
                        $tx->payment_status = $payment_status;
                        $tx->save();

                        // Activity log
                        $transactionUtil->activityLog($tx, 'payment_added', null, ['mpesa_payment_id' => $payment->id]);
                    }
                }
            } catch (\Exception $e) {
                Log::warning('Failed to auto-consume invoice transaction for subscription activation: ' . $e->getMessage());
            }

            Log::info("Found subscription ID: {$subscription->id}, Current status: {$subscription->status}");

            // Determine start date for subscription activation.
            // Default to payment date, but if this payment is for a renewal that was
            // created to start after the current subscription (i.e., pre-paid renewal),
            // respect the subscription's configured start_date so the renewal begins
            // when the current subscription expires.
            $startDate = $payment->created_at;
            try {
                if (! empty($subscription->is_renewal) || ! empty($subscription->previous_subscription_id)) {
                    // If a planned start_date exists and is in the future relative to payment,
                    // use that as the real start date so prepaid renewals begin after current expiry.
                    if (! empty($subscription->start_date)) {
                        $plannedStart = \Carbon\Carbon::parse($subscription->start_date);
                        if ($plannedStart->gt($startDate)) {
                            $startDate = $plannedStart;
                        }
                    }
                }
            } catch (\Exception $e) {
                // fallback to payment created_at if any parsing fails
                $startDate = $payment->created_at;
            }
            
            // CRITICAL FIX: Ensure we always have the receipt number
            $mpesaReceipt = $payment->mpesa_receipt_number;
            
            // If receipt is missing, try to get it from payment query
            if (empty($mpesaReceipt)) {
                $mpesaController = new MpesaController();
                $result = $mpesaController->queryMpesaPaymentStatus($payment->checkout_request_id);
                
                if (isset($result['receipt_number']) && !empty($result['receipt_number'])) {
                    $mpesaReceipt = $result['receipt_number'];
                    // Update the payment record too
                    $payment->update(['mpesa_receipt_number' => $mpesaReceipt]);
                    Log::info("Retrieved receipt from payment query: {$mpesaReceipt}");
                }
            }

            $updateData = [
                'status' => 'active',
                'start_date' => $startDate, // Use payment date as start date
                'end_date' => $this->calculateEndDate($subscription->billing_cycle, $startDate),
                'mpesa_receipt' => $mpesaReceipt,
                'checkout_request_id' => $payment->checkout_request_id,
                'activated_at' => Carbon::now(),
            ];

            Log::info("Updating subscription with data: ", $updateData);
            
            // Update subscription
            $subscription->update($updateData);

            // Update user subscription status
            $user = User::find($payment->user_id);
            if ($user) {
                $userUpdateData = [
                    'has_active_subscription' => true,
                    'subscription_expires_at' => $updateData['end_date'],
                ];
                
                // Add subscription_status if column exists
                if (Schema::hasColumn('users', 'subscription_status')) {
                    $userUpdateData['subscription_status'] = 'active';
                }
                
                $user->update($userUpdateData);
                Log::info("Updated user {$user->id} subscription status to active");
            }

            // Commit transaction
            DB::commit();

            Log::info("✅ Subscription {$subscription->id} activated successfully with receipt: {$mpesaReceipt}");
            Log::info("Subscription starts: {$startDate}, ends: {$updateData['end_date']}");

            return true;

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Subscription activation error: ' . $e->getMessage());
            Log::error($e->getTraceAsString());
            return false;
        }
    }

    /**
     * Find subscription for payment
     */
    private function findSubscriptionForPayment($payment)
    {
        Log::info("Finding subscription for payment ID: {$payment->id}");
        Log::info("Payment subscription_id: " . ($payment->subscription_id ?? 'NULL'));
        Log::info("Payment checkout_request_id: " . ($payment->checkout_request_id ?? 'NULL'));
        Log::info("Payment user_id: " . ($payment->user_id ?? 'NULL'));

        // 1. Try by subscription_id (direct relationship)
        if ($payment->subscription_id) {
            $subscription = Subscription::find($payment->subscription_id);
            if ($subscription) {
                Log::info("Found subscription by subscription_id: {$subscription->id}");
                return $subscription;
            }
            Log::warning("Subscription not found by subscription_id: {$payment->subscription_id}");
        }

        // 2. Try by checkout_request_id
        if ($payment->checkout_request_id) {
            $subscription = Subscription::where('checkout_request_id', $payment->checkout_request_id)->first();
            if ($subscription) {
                Log::info("Found subscription by checkout_request_id: {$subscription->id}");
                // Link the payment to the subscription if not already linked
                if (!$payment->subscription_id) {
                    $payment->update(['subscription_id' => $subscription->id]);
                }
                return $subscription;
            }
            Log::warning("Subscription not found by checkout_request_id: {$payment->checkout_request_id}");
        }

        // 3. Try latest pending subscription for user
        if ($payment->user_id) {
            $subscription = Subscription::where('user_id', $payment->user_id)
                ->where('status', 'pending')
                ->latest()
                ->first();
                
            if ($subscription) {
                Log::info("Found pending subscription for user: {$subscription->id}");
                // Link the payment to the subscription
                $payment->update(['subscription_id' => $subscription->id]);
                return $subscription;
            }
            Log::warning("No pending subscription found for user: {$payment->user_id}");
        }

        // 4. Try any subscription for user (last resort)
        if ($payment->user_id) {
            $subscription = Subscription::where('user_id', $payment->user_id)
                ->latest()
                ->first();
                
            if ($subscription) {
                Log::info("Found latest subscription for user: {$subscription->id}");
                // Link the payment to the subscription
                $payment->update(['subscription_id' => $subscription->id]);
                return $subscription;
            }
        }

        Log::error("No subscription found for payment ID: {$payment->id}");
        return null;
    }

    /**
     * Manual sync of subscriptions with payments - USING PAYMENT DATES
     */
    public function syncSubscriptionsManually()
    {
        $payments = MpesaPayment::where('transaction_status', 'paid')
            ->whereNotNull('mpesa_receipt_number')
            ->where(function($query) {
                $query->whereDoesntHave('subscription')
                      ->orWhereHas('subscription', function($q) {
                          $q->where('status', '!=', 'active')
                            ->orWhereNull('mpesa_receipt');
                      });
            })
            ->get();

        $updatedCount = 0;

        foreach ($payments as $payment) {
            $subscription = $this->findSubscriptionForPayment($payment);
            
            if ($subscription && $subscription->status !== 'active') {
                // Use payment date as start date
                $startDate = $payment->created_at;
                
                $subscription->update([
                    'status' => 'active',
                    'mpesa_receipt' => $payment->mpesa_receipt_number,
                    'start_date' => $startDate, // Use payment date
                    'end_date' => $this->calculateEndDate($subscription->billing_cycle, $startDate),
                ]);
                
                $updatedCount++;
                Log::info("Manually synced subscription {$subscription->id} with payment date: {$startDate}");
            }
        }

        return "Updated {$updatedCount} subscriptions";
    }

    /**
     * Get base date for subscription calculation - SIMPLIFIED VERSION
     * Now always uses payment date as requested
     */
    private function getSubscriptionBaseDate($subscription)
    {
        // For new implementation, we'll use payment date instead
        // This method is kept for backward compatibility with other parts of the code
        
        if ($subscription->payment && $subscription->payment->created_at) {
            return $subscription->payment->created_at;
        }
        
        // Fallback to current time if payment date is not available
        return Carbon::now();
    }

    /**
     * Enhanced payment validation
     */
    public function validatePayment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'billing_cycle' => 'required|in:monthly,quarterly,yearly',
            'phone' => ['required', function ($attribute, $value, $fail) {
                if (! MpesaPayment::normalizePhoneNumber($value)) {
                    $fail(__('payment.invalid_phone_format'));
                }
            }],
        ]);

        $request->merge(['phone' => MpesaPayment::normalizePhoneNumber($request->phone)]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid input data',
                'errors' => $validator->errors()
                ], 422);
        }

        $user = Auth::user();
        $settings = AdminSetting::firstOrFail();
        $billingCycle = $request->billing_cycle;
        $amount = $settings->{$billingCycle . '_price'};

        // Check for duplicate pending payments for the same period
        $duplicatePayment = MpesaPayment::where('user_id', $user->id)
            ->where('amount', $amount)
            ->where('payment_type', MpesaPayment::TYPE_SUBSCRIPTION)
            ->where('transaction_status', self::PAYMENT_PENDING)
            ->where('created_at', '>', Carbon::now()->subMinutes(30))
            ->first();
            
        if ($duplicatePayment) {
            return response()->json([
                'success' => true,
                'message' => 'An existing pending subscription payment will be reused.',
                'checkout_request_id' => $duplicatePayment->checkout_request_id,
                'subscription_id' => $duplicatePayment->subscription_id,
                'reused_payment' => true,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Payment validation passed'
        ]);
    }

    /**
     * Send activation notification
     */
    private function sendActivationNotification($user, $subscription)
    {
        // Implement your notification logic here
        // Mail::to($user->email)->send(new SubscriptionActivated($subscription));
    }

    /**
     * Manual payment status check - ENHANCED VERSION
     */
    public function manualStatusCheck(Request $request)
    {
        // Allow checkout_request_id to be optional. If not provided, we'll
        // fall back to the user's latest payment (useful when sessionStorage
        // wasn't set or the page was rendered before the payment row existed).
        $validator = Validator::make($request->all(), [
            'checkout_request_id' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid request',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $checkoutRequestId = $request->checkout_request_id;
            $checkOnly = $request->input('check_only', false);

            // If no checkout_request_id provided, pick the latest payment for the
            // authenticated user (prefer pending or recently created payments).
            if (empty($checkoutRequestId)) {
                $payment = MpesaPayment::where('user_id', auth()->id())
                    ->orderBy('created_at', 'desc')
                    ->first();
            } else {
                $payment = MpesaPayment::where('checkout_request_id', $checkoutRequestId)->first();
            }

            if (!$payment) {
                // Try additional fallbacks to locate a likely payment record:
                // 1) Recent payments for authenticated user (pending or paid)
                if (auth()->check()) {
                    $payment = MpesaPayment::where('user_id', auth()->id())
                        ->whereIn('transaction_status', ['pending', 'paid'])
                        ->where('created_at', '>', now()->subDays(7))
                        ->orderBy('created_at', 'desc')
                        ->first();
                }
            }

            // If still not found, and caller supplied a phone or subscription_id, try those
            if (!$payment && $request->filled('phone')) {
                $phone = $request->input('phone');
                $payment = MpesaPayment::where('phone_number', $phone)
                    ->whereIn('transaction_status', ['pending', 'paid'])
                    ->orderBy('created_at', 'desc')
                    ->first();
            }

            if (!$payment && $request->filled('subscription_id')) {
                $payment = MpesaPayment::where('subscription_id', $request->input('subscription_id'))
                    ->orderBy('created_at', 'desc')
                    ->first();
            }

            if (!$payment) {
                Log::warning('manualStatusCheck: payment not found', [
                    'checkout_request_id' => $checkoutRequestId,
                    'user_id' => auth()->id(),
                    'phone' => $request->input('phone'),
                    'subscription_id' => $request->input('subscription_id')
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Payment not found'
                ], 404);
            }

            // If front-end only wants the DB status (fast path), return it immediately
            if ($checkOnly) {
                $dbStatus = in_array($payment->transaction_status, ['success', 'paid']) ? 'paid' : ($payment->transaction_status ?? 'pending');

                $subscription = null;
                if ($payment->subscription_id) {
                    $subscription = Subscription::find($payment->subscription_id);
                }

                return response()->json([
                    'success'            => true,
                    'transaction_status' => $dbStatus,
                    'receipt_number'     => $payment->mpesa_receipt_number,
                    'result_desc'        => $payment->result_desc,
                    'start_date'         => $subscription?->start_date,
                    'end_date'           => $subscription?->end_date,
                ]);
            }

            $checkoutRequestId = $payment->checkout_request_id ?: $request->checkout_request_id;
            $paymentStatus = $this->resolvePaymentStatus($checkoutRequestId);

            // Normalize DB status: map 'success' => 'paid' for consistency
            $dbStatus = $paymentStatus === self::PAYMENT_SUCCESS ? 'paid' : ($paymentStatus === self::PAYMENT_FAILED ? 'failed' : $paymentStatus);

            // If payment is successful, ensure we have the receipt number
            if ($paymentStatus === self::PAYMENT_SUCCESS && empty($payment->mpesa_receipt_number)) {
                // Try to get receipt from M-Pesa API
                $mpesaController = new MpesaController();
                $result = $mpesaController->queryMpesaPaymentStatus($checkoutRequestId);
                
                if (isset($result['receipt_number']) && !empty($result['receipt_number'])) {
                    $payment->mpesa_receipt_number = $result['receipt_number'];
                }
            }
            
            // Persist normalized status to DB
            $payment->update(['transaction_status' => $dbStatus]);

            if ($paymentStatus === self::PAYMENT_SUCCESS) {
                // Ensure DB shows 'paid' as the canonical successful state
                $payment->update(['transaction_status' => 'paid']);

                $this->activateSubscription($payment);
                
                return response()->json([
                    'success' => true,
                    'transaction_status' => 'paid',
                    'message' => 'Payment confirmed and subscription activated!',
                    'receipt_number' => $payment->mpesa_receipt_number,
                    'redirect_url' => route('subscription.success', ['subscription_id' => $payment->subscription_id])
                ]);
            }

            return response()->json([
                'success' => true,
                'transaction_status' => $paymentStatus,
                'message' => 'Payment status: ' . $paymentStatus,
                'receipt_number' => $payment->mpesa_receipt_number
            ]);

        } catch (Exception $e) {
            Log::error('Manual status check error: ' . $e->getMessage());

            return $this->safeJsonError('Status check failed. Please try again.', 500);
        }
    }

    /**
     * Manual activation for debugging
     */
    public function manualActivate($paymentId)
    {
        try {
            $payment = MpesaPayment::findOrFail($paymentId);
            
            Log::info("Manual activation for payment: {$paymentId}");
            Log::info("Payment status: {$payment->transaction_status}");
            Log::info("MPESA receipt: {$payment->mpesa_receipt_number}");
            
            $result = $this->activateSubscription($payment);
            
            if ($result) {
                return response()->json([
                    'success' => true,
                    'message' => 'Subscription activated manually'
                ]);
            }
            
            return response()->json([
                'success' => false,
                'message' => 'Manual activation failed'
            ]);
            
        } catch (Exception $e) {
            Log::error('Manual activation error: ' . $e->getMessage());

            return $this->safeJsonError('Manual activation failed.', 500);
        }
    }

    /**
     * Subscription success page
     */
    public function success()
    {
        $subscriptionId = session('subscription_id');
        $subscription = null;

        if ($subscriptionId) {
            $subscription = Subscription::with('payment')->find($subscriptionId);
        }

        return view('subscription.success', compact('subscription'));
    }

    /**
     * Get subscription history for user
     */
    public function history()
    {
        $user = Auth::user();
        $subscriptions = $user->subscriptions()
            ->with('payment')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('subscription.history', compact('subscriptions'));
    }

    /**
     * Cancel subscription
     */
    public function cancel($id)
    {
        DB::beginTransaction();

        try {
            $subscription = Subscription::where('user_id', Auth::id())
                ->where('id', $id)
                ->firstOrFail();

            if ($subscription->status !== 'active') {
                throw new Exception('Only active subscriptions can be cancelled');
            }

            $subscription->update([
                'status' => 'cancelled',
                'cancelled_at' => now()
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Subscription cancelled successfully'
            ]);

        } catch (Exception $e) {
            DB::rollBack();

            Log::warning('Subscription cancellation error: ' . $e->getMessage(), [
                'subscription_id' => $id,
                'user_id' => Auth::id(),
            ]);

            return $this->safeJsonError('Unable to cancel subscription at this time.', 400);
        }
    }

    /**
     * Quick activation endpoint referenced by subscription routes.
     */
    public function quickActivate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'checkout_request_id' => 'nullable|string',
            'payment_id' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid request',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $payment = null;

            if ($request->filled('payment_id')) {
                $payment = MpesaPayment::find($request->input('payment_id'));
            }

            if (! $payment && $request->filled('checkout_request_id')) {
                $payment = MpesaPayment::where('checkout_request_id', $request->input('checkout_request_id'))->first();
            }

            if (! $payment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment not found',
                ], 404);
            }

            $isAdmin = optional(Auth::user())->role === 'admin';
            if ((int) $payment->user_id !== (int) Auth::id() && ! $isAdmin) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized action.',
                ], 403);
            }

            $status = $this->resolvePaymentStatus($payment->checkout_request_id);

            if ($status === self::PAYMENT_SUCCESS) {
                $payment->update(['transaction_status' => 'paid']);
                $activated = $this->activateSubscription($payment);

                return response()->json([
                    'success' => $activated,
                    'transaction_status' => 'paid',
                    'message' => $activated ? 'Subscription activated successfully' : 'Payment confirmed but activation failed',
                ], $activated ? 200 : 500);
            }

            if ($status === self::PAYMENT_FAILED) {
                $payment->update(['transaction_status' => 'failed']);
            }

            return response()->json([
                'success' => true,
                'transaction_status' => $status,
                'message' => 'Payment is not yet confirmed',
            ]);
        } catch (Exception $e) {
            Log::error('quickActivate error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Quick activation failed',
            ], 500);
        }
    }

    /**
     * Activate current authenticated user after payment confirmation.
     */
    public function activateUser(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'checkout_request_id' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid request',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $payment = MpesaPayment::where('checkout_request_id', $request->input('checkout_request_id'))
                ->where('user_id', Auth::id())
                ->first();

            if (! $payment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment not found',
                ], 404);
            }

            $status = $this->resolvePaymentStatus($payment->checkout_request_id);
            if ($status !== self::PAYMENT_SUCCESS) {
                return response()->json([
                    'success' => false,
                    'transaction_status' => $status,
                    'message' => 'Payment not yet confirmed',
                ], 400);
            }

            $payment->update(['transaction_status' => 'paid']);
            $activated = $this->activateSubscription($payment);

            return response()->json([
                'success' => $activated,
                'transaction_status' => 'paid',
                'message' => $activated ? 'User activated successfully' : 'Activation failed',
            ], $activated ? 200 : 500);
        } catch (Exception $e) {
            Log::error('activateUser error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Activation failed',
            ], 500);
        }
    }

    public function activateAfterPayment(Request $request)
    {
        try {
            $checkoutRequestId = $request->input('checkout_request_id');
            
            // Find the payment
            $payment = MpesaPayment::where('checkout_request_id', $checkoutRequestId)->first();
            
            if (!$payment) {
                return response()->json(['success' => false, 'message' => 'Payment not found']);
            }
            
            // Find the associated subscription
            $subscription = Subscription::where('mpesa_receipt', $payment->mpesa_receipt_number)
                          ->orWhere('checkout_request_id', $checkoutRequestId)
                          ->first();
            
            // Treat both 'success' and 'paid' as confirmed
            $isConfirmed = in_array($payment->transaction_status, ['success', 'paid']);

            if ($subscription && $isConfirmed) {
                // Use payment date as start date
                $startDate = $payment->created_at;
                
                // Activate the subscription with proper dates
                $subscription->status = 'active';
                $subscription->start_date = $startDate;
                $subscription->end_date = $this->calculateEndDate($subscription->billing_cycle, $startDate);
                $subscription->save();
                
                // Persist canonical paid status
                if ($payment->transaction_status !== 'paid') {
                    $payment->update(['transaction_status' => 'paid']);
                }

                return response()->json(['success' => true, 'message' => 'Subscription activated']);
            }
            
            return response()->json(['success' => false, 'message' => 'Subscription not found or payment not confirmed']);
            
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error activating subscription']);
        }
    }
}