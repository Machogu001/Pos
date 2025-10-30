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
     * Show available plans to the user.
     */
    public function showPlans()
    {
        try {
            $settings = AdminSetting::firstOrFail();
            $user = Auth::user();
            
            // Get active subscription with eager loading
            $activeSubscription = $user->subscriptions()
                ->where('status', 'active')
                ->where('end_date', '>', now())
                ->latest()
                ->first();

            $plans = $this->getSubscriptionPlans($settings);

            // Only show mpesa payments that are related to subscriptions.
            // We consider a payment related to a subscription when it has a subscription_id
            // or its account_reference is prefixed by SUB (legacy heuristics).
            $paymentHistory = $user->mpesaPayments()
                ->where(function ($q) {
                    $q->whereNotNull('subscription_id')
                      ->orWhere('account_reference', 'like', 'SUB%');
                })
                ->orderBy('created_at', 'desc')
                ->get();

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
                'phone' => 'required|regex:/^254[17]\d{8}$/',
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
            $amount = $settings->{$billingCycle . '_price'};
            $checkoutRequestId = $request->checkout_request_id;

            // Validate amount
            if ($amount <= 0) {
                throw new Exception('Invalid subscription price');
            }

            // Check for existing pending subscription
            $subscription = $this->getOrCreateSubscription($user, $billingCycle, $amount);

            // Check for existing payment
            $payment = $this->getOrCreatePayment($user, $subscription, $request->phone, $amount);

            // If we have a checkout_request_id, check payment status
            if ($checkoutRequestId) {
                $payment->update(['checkout_request_id' => $checkoutRequestId]);
                
                $paymentStatus = $this->checkPaymentStatus($checkoutRequestId);
                
                if ($paymentStatus === self::PAYMENT_SUCCESS) {
                    $this->activateSubscription($payment);
                    DB::commit();
                    
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
            $response = $mpesaController->initiatePaymentDirect(
                $request->phone, 
                $amount, 
                'SUB' . $subscription->id,
                $user->first_name,
                $user->last_name
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

            return response()->json([
                'success' => false,
                'message' => 'Payment failed: ' . $e->getMessage()
            ], 400);
        }
    }

    /**
     * Get or create subscription
     */
    private function getOrCreateSubscription($user, $billingCycle, $amount)
    {
        return Subscription::firstOrCreate(
            [
                'user_id' => $user->id,
                'status' => 'pending'
            ],
            [
                'plan_name' => ucfirst($billingCycle) . ' Plan',
                'billing_cycle' => $billingCycle,
                'amount' => $amount,
                'start_date' => now(),
                'end_date' => $this->calculateEndDate($billingCycle),
                'status' => 'pending'
            ]
        );
    }

    /**
     * Get or create payment record
     */
    private function getOrCreatePayment($user, $subscription, $phone, $amount)
    {
        return MpesaPayment::firstOrCreate(
            [
                'subscription_id' => $subscription->id,
                'transaction_status' => self::PAYMENT_PENDING
            ],
            [
                'user_id' => $user->id,
                'phone_number' => $phone,
                'amount' => $amount,
                'account_reference' => 'SUB' . $subscription->id,
                'payment_type' => 'subscription',
                'transaction_status' => self::PAYMENT_PENDING,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'payer_name' => $user->first_name . ' ' . $user->last_name
            ]
        );
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
                'phone' => 'required|regex:/^254[17]\d{8}$/',
                'plan_key' => 'required|in:monthly,quarterly,yearly'
            ]);

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

            // Create subscription
            $subscription = $this->getOrCreateSubscription($user, $billingCycle, $amount);

            // Create payment record
            $payment = MpesaPayment::create([
                'user_id' => $user->id,
                'subscription_id' => $subscription->id,
                'phone_number' => $request->phone,
                'amount' => $amount,
                'account_reference' => 'SUB' . $subscription->id,
                'payment_type' => 'subscription',
                'transaction_status' => self::PAYMENT_PENDING,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'payer_name' => $user->first_name . ' ' . $user->last_name,
            ]);

            // Initiate STK push
            $mpesaController = new MpesaController();
            $response = $mpesaController->initiatePaymentDirect(
                $request->phone, 
                $amount, 
                'SUB' . $subscription->id,
                $user->first_name,
                $user->last_name
            );

            if (isset($response['success']) && $response['success']) {
                $payment->update([
                    'checkout_request_id' => $response['checkout_request_id'],
                    'merchant_request_id' => $response['merchant_request_id']
                ]);

                $subscription->update([
                    'checkout_request_id' => $response['checkout_request_id']
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

            return response()->json([
                'success' => false,
                'message' => 'Failed to send STK push: ' . $e->getMessage()
            ], 400);
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
                'phone' => 'required|regex:/^254[17]\d{8}$/'
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
            $amount = $settings->{$billingCycle . '_price'};

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

            // Create renewal subscription
            $newSub = Subscription::create([
                'user_id' => $user->id,
                'plan_name' => ucfirst($billingCycle) . ' Plan (Renewal)',
                'billing_cycle' => $billingCycle,
                'amount' => $amount,
                'start_date' => $renewalStartDate,
                'end_date' => $this->calculateEndDate($billingCycle, $renewalStartDate),
                'status' => 'pending',
                'is_renewal' => true,
                'previous_subscription_id' => $latestSub->id
            ]);

            // Create payment record
            $payment = MpesaPayment::create([
                'user_id' => $user->id,
                'subscription_id' => $newSub->id,
                'phone_number' => $request->phone,
                'amount' => $amount,
                'account_reference' => 'RENEW' . $newSub->id,
                'payment_type' => 'subscription',
                'transaction_status' => self::PAYMENT_PENDING,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'payer_name' => $user->first_name . ' ' . $user->last_name,
            ]);

            // Initiate payment
            $mpesaController = new MpesaController();
            $response = $mpesaController->initiatePaymentDirect(
                $request->phone, 
                $amount, 
                'RENEW' . $newSub->id,
                $user->first_name,
                $user->last_name
            );

            if ($response['success']) {
                $payment->update([
                    'checkout_request_id' => $response['checkout_request_id'],
                    'merchant_request_id' => $response['merchant_request_id']
                ]);

                $newSub->update([
                    'checkout_request_id' => $response['checkout_request_id']
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

            return response()->json([
                'success' => false,
                'message' => 'Renewal failed: ' . $e->getMessage()
            ], 400);
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
    private function checkPaymentStatus($checkoutRequestId)
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
            $paymentStatus = $this->checkPaymentStatus($checkoutRequestId);
            
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

            Log::info("Found subscription ID: {$subscription->id}, Current status: {$subscription->status}");

            // USE PAYMENT DATE AS START DATE (as requested)
            $startDate = $payment->created_at;
            
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
            'phone' => 'required|regex:/^254[17]\d{8}$/',
        ]);

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
            ->where('transaction_status', self::PAYMENT_PENDING)
            ->where('created_at', '>', Carbon::now()->subMinutes(30))
            ->first();
            
        if ($duplicatePayment) {
            return response()->json([
                'success' => false,
                'message' => 'You already have a pending payment for this subscription. Please wait for it to complete.'
            ], 400);
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
                    'success' => true,
                    'transaction_status' => $dbStatus,
                    'receipt_number' => $payment->mpesa_receipt_number,
                    'start_date' => $subscription?->start_date,
                    'end_date' => $subscription?->end_date,
                ]);
            }

            $checkoutRequestId = $request->checkout_request_id;
            $paymentStatus = $this->checkPaymentStatus($checkoutRequestId);

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
            
            return response()->json([
                'success' => false,
                'message' => 'Status check failed: ' . $e->getMessage()
            ], 500);
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
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
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
            
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
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