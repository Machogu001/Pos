<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\MpesaPayment;
use App\Subscription;
use App\Http\Controllers\SubscriptionController;
class MpesaController extends Controller
{
    private $consumerKey;
    private $consumerSecret;
    private $shortCode;
    private $passkey;
    private $callbackUrl;
    /** 'paybill' → CustomerPayBillOnline | 'till' → CustomerBuyGoodsOnline */
    private $shortcodeType;
    /** For Till (Buy Goods): PartyB = store/head-office number; for PayBill: same as shortCode */
    private $storeNumber;

    public function __construct()
    {
        $this->consumerKey   = env('MPESA_CONSUMER_KEY');
        $this->consumerSecret = env('MPESA_CONSUMER_SECRET');
        $this->shortCode     = env('MPESA_SHORTCODE');
        $this->passkey       = env('MPESA_PASSKEY');
        $this->callbackUrl   = env('MPESA_CALLBACK');
        $this->shortcodeType = strtolower(trim((string) env('MPESA_SHORTCODE_TYPE', 'paybill')));
        if (! in_array($this->shortcodeType, ['paybill', 'till'], true)) {
            $this->shortcodeType = 'paybill';
        }
        // For Till STK Push, PartyB must be the store/head-office number, not the Till number.
        // Falls back to shortCode (correct for PayBill where BusinessShortCode == PartyB).
        $this->storeNumber = env('MPESA_STORE_NUMBER') ?: $this->shortCode;
    }

    /**
     * Set custom M-Pesa credentials (e.g., for subscription payments).
     * $shortcodeType: 'paybill' or 'till' (defaults to 'paybill' when omitted)
     */
    public function setCustomCredentials($consumerKey, $consumerSecret, $shortCode, $passkey, $callbackUrl, $shortcodeType = 'paybill', $storeNumber = null)
    {
        $this->consumerKey   = $consumerKey;
        $this->consumerSecret = $consumerSecret;
        $this->shortCode     = $shortCode;
        $this->passkey       = $passkey;
        $this->callbackUrl   = $callbackUrl;
        $normalized          = strtolower(trim((string) $shortcodeType));
        $this->shortcodeType = in_array($normalized, ['paybill', 'till'], true) ? $normalized : 'paybill';
        $this->storeNumber   = $storeNumber ?: $shortCode;
    }

    public function showPaymentForm()
    {
        return view('business.payment');
    }

    private function generateUniqueAccountReference(?string $preferredReference = null, ?int $ignorePaymentId = null): string
    {
        $candidate = strtoupper(trim((string) $preferredReference));

        if ($candidate !== '' && ! $this->accountReferenceExists($candidate, $ignorePaymentId)) {
            return $candidate;
        }

        do {
            $candidate = strtoupper(Str::random(8));
        } while ($this->accountReferenceExists($candidate, $ignorePaymentId));

        return $candidate;
    }

    private function accountReferenceExists(string $accountReference, ?int $ignorePaymentId = null): bool
    {
        return MpesaPayment::query()
            ->when($ignorePaymentId, function ($query) use ($ignorePaymentId) {
                $query->where('id', '!=', $ignorePaymentId);
            })
            ->where('account_reference', $accountReference)
            ->exists();
    }

    public function initiatePayment(Request $request)
    {
        // Allow this endpoint to be used by POS (which may only send phone & amount)
        $request->validate([
            'first_name' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'phone' => 'required|string|min:10|max:15',
            'amount' => 'nullable|numeric',
            // allow caller to indicate the type of payment (sell, purchase, registration, subscription)
            'payment_type' => 'nullable|string|in:registration,purchase,sell,subscription',
            'account_reference' => 'nullable|string|max:255',
            'registration_payment_id' => 'nullable|integer',
        ]);

        // Determine payment type early so we can pick the correct credential source.
        // Rule: subscription payments use admin-set subscription credentials; everything else uses .env.
        $paymentType = $request->input('payment_type', MpesaPayment::TYPE_REGISTRATION);

        $referer = strtolower($request->header('referer') ?? '');
        $refererPath = strtolower((string) parse_url($referer, PHP_URL_PATH));
        $isPosFlag = $request->input('is_pos') == 1 || $request->input('from_pos') == 1;

        // Check referer contains any of the known POS path segments
        $posPaths = ['/sale_pos', '/pos', '/sale-pos', '/sale_pos'];
        $refererContainsPos = false;
        if (!empty($refererPath)) {
            foreach ($posPaths as $p) {
                if (str_contains($refererPath, $p)) {
                    $refererContainsPos = true;
                    break;
                }
            }
        }

        // If this looks like a POS request, force 'sell' unless explicitly marked as 'subscription'
        if ($paymentType !== MpesaPayment::TYPE_SUBSCRIPTION && ($isPosFlag || $refererContainsPos)) {
            $paymentType = MpesaPayment::TYPE_SELL;
        }

        if (! in_array($paymentType, [MpesaPayment::TYPE_REGISTRATION, MpesaPayment::TYPE_PURCHASE, MpesaPayment::TYPE_SELL, MpesaPayment::TYPE_SUBSCRIPTION], true)) {
            $paymentType = MpesaPayment::TYPE_REGISTRATION;
        }

    $rawPhone = $request->phone;
    $phone = MpesaPayment::normalizePhoneNumber($rawPhone);

        // Defensive validation: accept local and international Kenyan mobile formats.
        if (empty($phone)) {
            Log::warning('MpesaController initiatePayment called with invalid phone', [
                'user_id' => auth()->id(),
                'raw_phone' => $rawPhone,
                'normalized' => $phone,
            ]);

            return response()->json([
                'transaction_status' => 'error',
                'message' => __('payment.invalid_phone_format')
            ], 422);
        }

    // Use admin-configured registration price if available, but allow caller to override via request
    $settings = \App\AdminSetting::first();
    $amount = $request->input('amount');
    if (empty($amount)) {
        $amount = ! empty($settings) && ! is_null($settings->registration_price)
            ? $settings->registration_price
            : 5;
    }

    // Use subscription-specific M-Pesa credentials for subscription AND new business registration payments.
    // Falls back to .env credentials when no subscription credentials are configured.
    if (in_array($paymentType, [MpesaPayment::TYPE_SUBSCRIPTION, MpesaPayment::TYPE_REGISTRATION], true) && $settings && $settings->subscription_mpesa_consumer_key) {
        $this->setCustomCredentials(
            $settings->subscription_mpesa_consumer_key,
            $settings->subscription_mpesa_consumer_secret,
            $settings->subscription_mpesa_shortcode,
            $settings->subscription_mpesa_passkey,
            $settings->subscription_mpesa_callback,
            $settings->subscription_mpesa_shortcode_type ?? 'paybill',
            $settings->subscription_mpesa_store_number ?: null
        );
    }

        // Store names/phone in session for later use (may be blank for POS flow)
        session([
            'payment_phone' => $phone,
            'first_name' => $request->first_name ?? null,
            'middle_name' => $request->middle_name ?? null,
            'last_name' => $request->last_name ?? null,
        ]);
        session()->save();

        // Log the initiation attempt (sanitized)
        Log::info('MpesaController initiatePayment', [
            'user_id' => auth()->id(),
            'phone' => $phone,
            'amount' => $amount,
            'payment_type' => $paymentType,
        ]);

        // Check for existing pending transaction
        $existingPaymentId = $paymentType === MpesaPayment::TYPE_REGISTRATION
            ? ($request->integer('registration_payment_id') ?: session('registration_payment_id'))
            : null;

        $requestedAccountRef = trim((string) $request->input('account_reference'));
        if ($requestedAccountRef === '' && $paymentType === MpesaPayment::TYPE_REGISTRATION) {
            $requestedAccountRef = trim((string) (session('account_reference') ?? session('account_ref')));
        }

        $payment = null;
        if (! empty($existingPaymentId)) {
            $payment = MpesaPayment::where('id', $existingPaymentId)
                ->where(function ($query) use ($paymentType) {
                    $query->whereNull('payment_type')
                        ->orWhere('payment_type', $paymentType);
                })
                ->first();
        }

        if (! $payment && ! empty($requestedAccountRef)) {
            $payment = MpesaPayment::where('account_reference', $requestedAccountRef)
                ->where(function ($query) use ($paymentType) {
                    $query->whereNull('payment_type')
                        ->orWhere('payment_type', $paymentType);
                })
                ->latest()
                ->first();
        }

        if (! $payment) {
            $payment = MpesaPayment::where('phone_number', $phone)
                ->whereIn('transaction_status', ['pending', 'failed'])
                ->where(function ($query) use ($paymentType) {
                    $query->whereNull('payment_type')
                        ->orWhere('payment_type', $paymentType);
                })
                ->when(in_array($paymentType, [MpesaPayment::TYPE_SELL, MpesaPayment::TYPE_PURCHASE], true), function ($query) {
                    $query->whereNull('consumed_by_transaction_id');
                })
                ->latest()
                ->first();
        }

        if (! $payment) {
            $accountRef = $this->generateUniqueAccountReference($requestedAccountRef ?: null);
        } else {
            $accountRef = $payment->account_reference;
        }

        $businessId = auth()->user()->business_id ?? null;
        if ($paymentType === MpesaPayment::TYPE_REGISTRATION) {
            $businessId = null;
        }

        // Send STK Push
        $response = $this->sendStkPush($phone, $amount, $accountRef, $paymentType);
        $responseBody = is_array($response) ? $response : json_decode($response, true);

        if (isset($responseBody['ResponseCode']) && $responseBody['ResponseCode'] === '0') {
            $merchantRequestID = $responseBody['MerchantRequestID'] ?? null;
            $checkoutRequestID = $responseBody['CheckoutRequestID'] ?? null;

                if (!$payment) {
                    $payment = MpesaPayment::create([
                        'user_id' => auth()->id(), // ✅ Use auth()->id() instead of $userId
                        'business_id' => $businessId,
                        'phone_number' => $phone,
                        'amount' => $amount,
                        'account_reference' => $accountRef,
                        'merchant_request_id' => $merchantRequestID,
                        'checkout_request_id' => $checkoutRequestID,
                        'payment_type' => $paymentType,
                        'transaction_status' => 'pending',
                        'first_name' => $request->first_name,
                        'middle_name' => $request->middle_name,
                        'last_name' => $request->last_name,
                    ]);
                } else {
                    $payment->update([
                        'user_id' => auth()->id(), // ✅ Fixed: Use auth()->id()
                        'business_id' => $businessId,
                        'merchant_request_id' => $merchantRequestID,
                        'checkout_request_id' => $checkoutRequestID,
                        'payment_type' => $paymentType,
                        'result_code' => null,
                        'result_desc' => null,
                        'mpesa_receipt_number' => null,
                        'paid_at' => null,
                        'transaction_status' => 'pending',
                        'first_name' => $request->first_name,
                        'middle_name' => $request->middle_name,
                        'last_name' => $request->last_name,
                    ]);
                }

            // Store checkout request ID in session for status checking
            session([
                'checkout_request_id' => $checkoutRequestID,
                'account_reference' => $accountRef,
                'account_ref' => $accountRef,
                'registration_payment_id' => $payment->id,
            ]);
            session()->save();

            return response()->json([
                'transaction_status' => 'success',
                'payment_id' => $payment->id,
                'account_ref' => $accountRef,
                'checkout_request_id' => $checkoutRequestID, 
                'message' => 'STK push sent. Enter PIN on your phone.',
            ]);
        }

        Log::error('STK Push Failed', ['response' => $responseBody]);
        $errorMessage = $responseBody['CustomerMessage'] ?? $responseBody['errorMessage'] ?? $responseBody['errorDesc'] ?? $responseBody['ResponseDescription'] ?? 'STK Push failed.';
        return response()->json(['transaction_status' => 'error', 'message' => $errorMessage], 400);
    }

    private function generateAccessToken()
    {
        $response = Http::withBasicAuth($this->consumerKey, $this->consumerSecret)
            ->timeout(15)
            ->get('https://api.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials');

        if ($response->successful()) {
            return $response['access_token'];
        }

        $statusCode = $response->status();
        $hint = ($statusCode === 400 && empty(trim($response->body())))
            ? ' Credentials may be sandbox/test credentials used against the production API, or the Daraja app has not been go-live approved.'
            : '';

        Log::error('Failed to generate access token', [
            'http_status' => $statusCode,
            'response'    => $response->body(),
            'hint'        => $hint,
            'shortcode'   => $this->shortCode,
        ]);
        return null;
    }

    private function sendStkPush($phone, $amount, $accountRef, $paymentType = 'registration')
    {
        $accessToken = $this->generateAccessToken();
        if (!$accessToken) {
            return ['errorMessage' => 'Unable to generate access token'];
        }

        $timestamp = now()->format('YmdHis');
        $password = base64_encode($this->shortCode . $this->passkey . $timestamp);

        // Convert amount to integer
        $amount = (int) round(floatval($amount));

        $payload = [
            "BusinessShortCode" => $this->shortCode,
            "Password" => $password,
            "Timestamp" => $timestamp,
            "TransactionType" => $this->shortcodeType === 'till' ? 'CustomerBuyGoodsOnline' : 'CustomerPayBillOnline',
            "Amount" => $amount,
            "PartyA" => $phone,
            "PartyB" => $this->storeNumber,
            "PhoneNumber" => $phone,
            "CallBackURL" => $this->callbackUrl,
            "AccountReference" => $accountRef,
            "TransactionDesc" => match ($paymentType) {
                'sell'         => 'Invoice Payment',
                'subscription' => 'Subscription Payment',
                'purchase'     => 'Purchase Payment',
                default        => 'Registration Payment',
            },
        ];

        $response = Http::withToken($accessToken)
            ->post('https://api.safaricom.co.ke/mpesa/stkpush/v1/processrequest', $payload);

        // Avoid logging sensitive fields such as Password
        $logPayload = $payload;
        if (is_array($logPayload) && array_key_exists('Password', $logPayload)) {
            $logPayload['Password'] = '[REDACTED]';
        }
        Log::info('STK Push Request', ['payload' => $logPayload]);
        Log::info('STK Push Response', ['body' => $response->body()]);

        return $response->json();
    }

    public function handleCallback(Request $request)
    {
        Log::info("M-Pesa Callback Received", $request->all());

        $body = $request->getContent();
        $callback = json_decode($body, true);

        if (!$callback || !isset($callback['Body']['stkCallback'])) {
            Log::error("❌ Invalid M-Pesa callback format");
            return response()->json(['error' => 'Invalid callback format'], 400);
        }

        $stkCallback = $callback['Body']['stkCallback'];
        $resultCode = $stkCallback['ResultCode'];
        $resultDesc = $stkCallback['ResultDesc'];
        $merchantRequestID = $stkCallback['MerchantRequestID'] ?? null;
        $checkoutRequestID = $stkCallback['CheckoutRequestID'] ?? null;

        // Find payment by checkout request ID
        $payment = MpesaPayment::where('checkout_request_id', $checkoutRequestID)->first();

        if ($resultCode == 0) {
            // ✅ Successful payment
            $amount = null;
            $phone = null;
            $mpesaCode = null;

            if (isset($stkCallback['CallbackMetadata']['Item'])) {
                foreach ($stkCallback['CallbackMetadata']['Item'] as $item) {
                    if ($item['Name'] == 'Amount') $amount = $item['Value'];
                    if ($item['Name'] == 'MpesaReceiptNumber') $mpesaCode = $item['Value'];
                    if ($item['Name'] == 'PhoneNumber') $phone = $item['Value'];
                }
            }

            if ($payment) {
                $payment->update([
                    'mpesa_receipt_number' => $mpesaCode,
                    'transaction_status' => 'paid',
                    'paid_at' => Carbon::now(),
                    'phone_number' => $phone,
                    'amount' => $amount,
                    'checkout_request_id' => $checkoutRequestID,
                    'merchant_request_id' => $merchantRequestID,
                    'result_code' => $resultCode,
                    'result_desc' => $resultDesc,
                ]);

                // Only activate subscription if this payment is explicitly a subscription
                if (isset($payment->payment_type) && $payment->payment_type === 'subscription') {
                    $subscriptionController = new SubscriptionController();
                    $subscriptionController->activateSubscription($payment);
                    Log::info("✅ Subscription activation triggered for payment: {$payment->id}");
                } else {
                    Log::info("Payment processed but not a subscription payment; skipping activation", [
                        'payment_id' => $payment->id,
                        'payment_type' => $payment->payment_type ?? 'null'
                    ]);
                }

                Log::info("✅ Payment SUCCESS: $mpesaCode | $phone | $amount");
            }
        } else {
            // Failed payment
            if ($payment) {
                $payment->update([
                    'transaction_status' => 'failed',
                    'result_code' => $resultCode,
                    'result_desc' => $resultDesc,
                ]);

                // Only mark subscription failed if this was a subscription payment
                if (($payment->payment_type ?? null) === 'subscription' && $payment->subscription_id) {
                    $subscription = Subscription::find($payment->subscription_id);
                    if ($subscription) {
                        $subscription->update([
                            'status' => 'failed',
                        ]);
                        Log::info("❌ Subscription {$subscription->id} marked as failed");
                    }
                }
            }
            Log::warning("❌ Payment FAILED: $resultDesc");
        }

        return response()->json(['ResultCode' => 0, 'ResultDesc' => 'Callback processed successfully']);
    }

    /**
     * Calculate subscription end date from billing cycle
     */
    // private function calculateEndDate($billingCycle, $startDate = null)
    // {
    //     $start = $startDate ? Carbon::parse($startDate) : now();

    //     return match($billingCycle) {
    //         'monthly' => $start->copy()->addMonth(),
    //         'quarterly' => $start->copy()->addMonths(3),
    //         'yearly' => $start->copy()->addYear(),
    //         default => $start->copy()->addMonth(),
    //     };
    // }

    public function confirmPayment(Request $request)
    {
        try {
            Log::info('MpesaController confirmPayment called');
            
            $registrationPaymentId = session('registration_payment_id') ?? $request->input('registration_payment_id');
            $checkoutRequestId = session('checkout_request_id') ?? $request->input('checkout_request_id');
            $accountReference = session('account_reference') ?? session('account_ref') ?? $request->input('account_reference') ?? $request->input('account_ref');
            $phone = session('payment_phone') ?? $request->input('phone_number');
            $normalizedPhone = $phone ? preg_replace('/[^0-9]/', '', $phone) : null;
            
            Log::info('Session payment_phone: ' . ($phone ?: 'empty'));
            Log::info('Session checkout_request_id: ' . ($checkoutRequestId ?: 'empty'));
            Log::info('Session registration_payment_id: ' . ($registrationPaymentId ?: 'empty'));
            
            $paymentQuery = MpesaPayment::query()
                ->where(function ($query) {
                    $query->whereNull('payment_type')
                        ->orWhere('payment_type', 'registration');
                });

            if (! empty($registrationPaymentId)) {
                $paymentQuery->where('id', $registrationPaymentId);
            } else {
                $paymentQuery->where(function ($query) use ($checkoutRequestId, $accountReference, $normalizedPhone) {
                    if (! empty($checkoutRequestId)) {
                        $query->orWhere('checkout_request_id', $checkoutRequestId);
                    }

                    if (! empty($accountReference)) {
                        $query->orWhere('account_reference', $accountReference);
                    }

                    if (! empty($normalizedPhone)) {
                        $query->orWhere('phone_number', $normalizedPhone)
                            ->orWhere('phone_number', ltrim($normalizedPhone, '+'));
                    }
                });
            }

            if (empty($registrationPaymentId) && empty($checkoutRequestId) && empty($accountReference) && empty($normalizedPhone)) {
                Log::warning('No phone number found for payment confirmation');
                return response()->json([
                    'success' => false,
                    'message' => 'No payment session found'
                ], 400);
            }

            Log::info('Checking registration payment status', [
                'registration_payment_id' => $registrationPaymentId,
                'checkout_request_id' => $checkoutRequestId,
                'account_reference' => $accountReference,
                'phone' => $normalizedPhone,
            ]);

            $payment = $paymentQuery
                ->latest()
                ->first();

            if (!$payment) {
                Log::warning('No registration payment found for confirmation');
                return response()->json([
                    'success' => false,
                    'message' => 'No registration payment found'
                ], 404);
            }

            Log::info('Payment found with status: ' . $payment->transaction_status . ', ID: ' . $payment->id);
            session([
                'registration_payment_id' => $payment->id,
                'payment_phone' => $payment->phone_number,
                'checkout_request_id' => $payment->checkout_request_id,
                'account_reference' => $payment->account_reference,
                'account_ref' => $payment->account_reference,
            ]);
            session()->save();
            
            return response()->json([
                'success' => true,
                'payment_id' => $payment->id,
                'checkout_request_id' => $payment->checkout_request_id,
                'account_ref' => $payment->account_reference,
                'transaction_status' => $payment->transaction_status,
                'result_desc' => $payment->result_desc,
                'message' => 'Payment status: ' . $payment->transaction_status
            ]);

        } catch (\Exception $e) {
            Log::error('Payment confirmation error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error confirming payment: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
 * Check payment status and activate subscription if successful
 */
public function checkPaymentStatus(Request $request)
{
    DB::beginTransaction();

    try {
        $checkoutRequestId = $request->checkout_request_id;
        $phone = $request->phone;
        
        Log::info("Checking payment status for checkout ID: " . $checkoutRequestId);
        Log::info("Phone provided: " . ($phone ?? 'NULL'));

        // First check database by checkout_request_id
        $payment = MpesaPayment::where('checkout_request_id', $checkoutRequestId)->first();
        
        // Only fall back to latest phone payment when no explicit checkout ID was provided.
        if (!$payment && !$checkoutRequestId && $phone) {
            Log::info("Payment not found by checkout ID, trying by phone: " . $phone);
            $payment = MpesaPayment::where('phone_number', $phone)
                ->orderBy('created_at', 'desc')
                ->first();
        }

        // If still not found, try any payment with this checkout ID (case insensitive)
        if (!$payment && $checkoutRequestId) {
            Log::info("Payment not found by exact checkout ID, trying case-insensitive search");
            $payment = MpesaPayment::where('checkout_request_id', 'like', '%' . $checkoutRequestId . '%')
                ->orderBy('created_at', 'desc')
                ->first();
        }

        if (!$payment) {
            Log::warning("Payment record not found for checkout ID: " . $checkoutRequestId);
            DB::rollBack();
            return response()->json([
                'success' => false,
                'status' => 'not_found',
                'message' => 'Payment record not found. Please try manual payment check.'
            ]);
        }

        Log::info("Found payment ID: {$payment->id}, Status: {$payment->transaction_status}");

        // If payment is already successful, activate subscription
        if ($payment->transaction_status === 'paid') {
            Log::info("Payment already paid");
            $resultDescription = $payment->result_desc ?: 'Payment confirmed';

            // Only attempt activation for subscription payments
            if (($payment->payment_type ?? null) === 'subscription') {
                Log::info("Activating subscription for paid payment: {$payment->id}");
                $subscriptionController = new SubscriptionController();
                $activationResult = $subscriptionController->activateSubscription($payment);

                DB::commit();

                return response()->json([
                    'success' => true,
                    'status' => 'success',
                    'transaction_status' => 'paid',
                    'message' => $resultDescription,
                    'result_desc' => $resultDescription,
                    'result_code' => $payment->result_code,
                    'mpesa_receipt_number' => $payment->mpesa_receipt_number,
                    'activated' => $activationResult,
                    'payment_id' => $payment->id
                ]);
            }

            Log::info("Paid payment is not a subscription payment; skipping activation", ['payment_id' => $payment->id, 'payment_type' => $payment->payment_type ?? 'null']);

            DB::commit();

            return response()->json([
                'success' => true,
                'status' => 'success',
                'transaction_status' => 'paid',
                'message' => $resultDescription,
                'result_desc' => $resultDescription,
                'result_code' => $payment->result_code,
                'mpesa_receipt_number' => $payment->mpesa_receipt_number,
                'activated' => false,
                'payment_id' => $payment->id
            ]);
        }

        if ($payment->transaction_status === 'failed') {
            $resultDescription = $payment->result_desc ?: 'Payment failed';

            DB::commit();

            return response()->json([
                'success' => false,
                'status' => 'failed',
                'transaction_status' => 'failed',
                'message' => $resultDescription,
                'result_desc' => $resultDescription,
                'result_code' => $payment->result_code,
                'mpesa_receipt_number' => $payment->mpesa_receipt_number,
                'payment_id' => $payment->id,
            ]);
        }

        // If payment is still pending, query M-Pesa directly
        if ($payment->transaction_status === 'pending') {
            Log::info("Payment pending, querying M-Pesa directly...");
            $queryResult = $this->queryMpesaPaymentStatus($checkoutRequestId);
            
            Log::info("M-Pesa query result: ", $queryResult);
            
            if ($queryResult['success'] && $queryResult['transaction_status'] === 'success') {
                // Update payment status
                $payment->update([
                    'transaction_status' => 'paid',
                    'paid_at' => Carbon::now(),
                    'result_code' => $queryResult['result_code'] ?? 0,
                    'result_desc' => $queryResult['result_desc'] ?? 'Payment confirmed via query'
                ]);
                
                Log::info("Payment updated to paid, activating subscription...");
                // Only activate subscription when payment_type indicates subscription
                if (($payment->payment_type ?? null) === 'subscription') {
                    $subscriptionController = new SubscriptionController();
                    $activationResult = $subscriptionController->activateSubscription($payment);

                    DB::commit();

                    return response()->json([
                        'success' => true,
                        'status' => 'success',
                        'transaction_status' => 'paid',
                        'message' => $payment->result_desc ?: ($queryResult['result_desc'] ?? 'Payment confirmed'),
                        'result_desc' => $payment->result_desc ?: ($queryResult['result_desc'] ?? 'Payment confirmed'),
                        'result_code' => $payment->result_code,
                        'mpesa_receipt_number' => $payment->mpesa_receipt_number,
                        'activated' => $activationResult,
                        'payment_id' => $payment->id
                    ]);
                }

                Log::info('Payment confirmed via query but not a subscription payment; skipping activation', ['payment_id' => $payment->id, 'payment_type' => $payment->payment_type ?? 'null']);

                DB::commit();

                return response()->json([
                    'success' => true,
                    'status' => 'success',
                    'transaction_status' => 'paid',
                    'message' => $payment->result_desc ?: ($queryResult['result_desc'] ?? 'Payment confirmed'),
                    'result_desc' => $payment->result_desc ?: ($queryResult['result_desc'] ?? 'Payment confirmed'),
                    'result_code' => $payment->result_code,
                    'mpesa_receipt_number' => $payment->mpesa_receipt_number,
                    'activated' => false,
                    'payment_id' => $payment->id
                ]);
            }
            
            $payment->refresh();

            if ($payment->transaction_status === 'paid') {
                DB::commit();

                return response()->json([
                    'success' => true,
                    'status' => 'success',
                    'transaction_status' => 'paid',
                    'message' => $payment->result_desc ?: 'Payment confirmed',
                    'result_desc' => $payment->result_desc ?: 'Payment confirmed',
                    'result_code' => $payment->result_code,
                    'mpesa_receipt_number' => $payment->mpesa_receipt_number,
                    'payment_id' => $payment->id,
                ]);
            }

            if ($payment->transaction_status === 'failed') {
                DB::commit();

                return response()->json([
                    'success' => false,
                    'status' => 'failed',
                    'transaction_status' => 'failed',
                    'message' => $payment->result_desc ?: 'Payment failed',
                    'result_desc' => $payment->result_desc ?: 'Payment failed',
                    'result_code' => $payment->result_code,
                    'mpesa_receipt_number' => $payment->mpesa_receipt_number,
                    'payment_id' => $payment->id,
                ]);
            }

            if (!empty($queryResult['transaction_status']) && $queryResult['transaction_status'] === 'failed') {
                $payment->update([
                    'transaction_status' => 'failed',
                    'result_code' => $queryResult['result_code'] ?? $payment->result_code,
                    'result_desc' => $queryResult['result_desc'] ?? $payment->result_desc,
                ]);

                DB::commit();

                return response()->json([
                    'success' => false,
                    'status' => 'failed',
                    'transaction_status' => 'failed',
                    'message' => $queryResult['result_desc'] ?? $payment->result_desc ?? 'Payment failed',
                    'result_desc' => $queryResult['result_desc'] ?? $payment->result_desc ?? 'Payment failed',
                    'payment_id' => $payment->id,
                    'result_code' => $queryResult['result_code'] ?? null,
                    'mpesa_receipt_number' => $payment->mpesa_receipt_number,
                ]);
            }

            DB::commit();
            return response()->json([
                'success' => true,
                'status' => 'pending',
                'transaction_status' => $payment->transaction_status,
                'message' => $queryResult['result_desc'] ?? $payment->result_desc ?? 'Payment is still pending',
                'result_desc' => $queryResult['result_desc'] ?? $payment->result_desc,
                'result_code' => $queryResult['result_code'] ?? $payment->result_code,
                'mpesa_receipt_number' => $payment->mpesa_receipt_number,
                'payment_id' => $payment->id
            ]);
        }

        DB::commit();
        return response()->json([
            'success' => $payment->transaction_status !== 'failed',
            'status' => $payment->transaction_status,
            'transaction_status' => $payment->transaction_status,
            'message' => $payment->result_desc ?: ('Payment status: ' . $payment->transaction_status),
            'result_desc' => $payment->result_desc,
            'payment_id' => $payment->id,
            'result_code' => $payment->result_code,
            'mpesa_receipt_number' => $payment->mpesa_receipt_number,
        ]);

    } catch (Exception $e) {
        DB::rollBack();
        Log::error('Payment status check error: ' . $e->getMessage());
        Log::error($e->getTraceAsString());
        return response()->json([
            'success' => false,
            'status' => 'error',
            'message' => 'Error checking payment status: ' . $e->getMessage()
        ], 500);
    }
}

    public function retry(Request $request)
    {
        $phone = $request->input('phone_number');
        $checkoutRequestId = $request->input('checkout_request_id');

        // First, try by checkoutRequestId if provided
        $payment = null;
        if ($checkoutRequestId) {
            $payment = MpesaPayment::where('checkout_request_id', $checkoutRequestId)->first();
        }

        // If not found, try latest by phone
        if (!$payment && $phone) {
            $payment = MpesaPayment::where('phone_number', $phone)->latest()->first();
        }

        // If no payment found
        if (!$payment) {
            return back()->with('status', 'not_found');
        }

        // Normalize status
        $status = strtolower($payment->transaction_status);

        if (in_array($status, ['success', 'paid'])) {
            return back()->with('status', 'success');
        }

        return back()->with('status', 'pending');
    }

    /**
     * Direct STK push initiation for subscriptions (used by SubscriptionController)
     */
    public function initiatePaymentDirect($phone, $amount, $accountReference)
    {
        $accessToken = $this->generateAccessToken();
        if (!$accessToken) {
            return ['success' => false, 'errorMessage' => 'Unable to generate access token'];
        }

        $timestamp = now()->format('YmdHis');
        $password = base64_encode($this->shortCode . $this->passkey . $timestamp);

        // Convert amount to integer (M-Pesa requires whole numbers)
        $amount = (int) round(floatval($amount));

        $payload = [
            "BusinessShortCode" => $this->shortCode,
            "Password" => $password,
            "Timestamp" => $timestamp,
            "TransactionType" => $this->shortcodeType === 'till' ? 'CustomerBuyGoodsOnline' : 'CustomerPayBillOnline',
            "Amount" => $amount,
            "PartyA" => $phone,
            "PartyB" => $this->storeNumber,
            "PhoneNumber" => $phone,
            "CallBackURL" => $this->callbackUrl,
            "AccountReference" => $accountReference,
            "TransactionDesc" => "Subscription Payment"
        ];

        $response = Http::withToken($accessToken)
            ->post('https://api.safaricom.co.ke/mpesa/stkpush/v1/processrequest', $payload);

        // Avoid logging sensitive fields such as Password
        $logPayload = $payload;
        if (is_array($logPayload) && array_key_exists('Password', $logPayload)) {
            $logPayload['Password'] = '[REDACTED]';
        }
        Log::info('STK Push Request (Direct)', ['payload' => $logPayload]);
        Log::info('STK Push Response (Direct)', ['http_status' => $response->status(), 'body' => $response->body()]);

        $responseData = $response->json();
        
        if (isset($responseData['ResponseCode']) && $responseData['ResponseCode'] === '0') {
            return [
                'success' => true,
                'merchant_request_id' => $responseData['MerchantRequestID'],
                'checkout_request_id' => $responseData['CheckoutRequestID']
            ];
        }

        $safaricomError = $responseData['errorMessage']
            ?? $responseData['errorDesc']
            ?? $responseData['ResponseDescription']
            ?? $responseData['CustomerMessage']
            ?? null;

        Log::error('STK Push (Direct) failed', [
            'http_status'    => $response->status(),
            'safaricom_code' => $responseData['errorCode'] ?? $responseData['ResponseCode'] ?? null,
            'safaricom_msg'  => $safaricomError,
            'shortcode'      => $this->shortCode,
        ]);

        return [
            'success' => false,
            'errorMessage' => $safaricomError ?? 'STK Push failed'
        ];
    }

    /**
     * Direct payment status check for subscriptions
     */
    public function checkPaymentStatusDirect($checkoutRequestId)
    {
        $payment = MpesaPayment::where('checkout_request_id', $checkoutRequestId)->first();
        
        if (!$payment) {
            return ['transaction_status' => 'not_found'];
        }
        
        return ['transaction_status' => $payment->transaction_status];
    }

    /**
 * NEW METHOD: Query M-Pesa API directly for payment status
 */
public function queryMpesaPaymentStatus($requestOrCheckoutId = null)
{
    // If this was called as a controller action without an explicit argument,
    // fall back to the current HTTP request instance so Laravel route calls still work.
    if (is_null($requestOrCheckoutId)) {
        $requestOrCheckoutId = request();
    }
    // This method can be called in two ways:
    // - From an HTTP route: pass an Illuminate\Http\Request instance containing 'checkout_request_id' or 'phone'.
    // - Internally: pass the checkout_request_id string directly.

    $checkoutRequestId = null;
    $phone = null;

    if ($requestOrCheckoutId instanceof Request) {
        $checkoutRequestId = $requestOrCheckoutId->input('checkout_request_id') ?? $requestOrCheckoutId->input('checkoutRequestId');
        $phone = $requestOrCheckoutId->input('phone');
    } else {
        // Expecting a plain checkout_request_id string
        $checkoutRequestId = $requestOrCheckoutId;
    }

    // If no checkout id provided, but phone is provided, try to find latest payment to get checkout id
    if (empty($checkoutRequestId) && !empty($phone)) {
        $normalized = preg_replace('/^(\+?254|0)/', '254', $phone);
        $payment = MpesaPayment::where('phone_number', $normalized)->latest()->first();
        if ($payment) {
            $checkoutRequestId = $payment->checkout_request_id;
        }
    }

    $payment = null;
    if (!empty($checkoutRequestId)) {
        $payment = MpesaPayment::where('checkout_request_id', $checkoutRequestId)->latest()->first();
    }

        if (!$payment && empty($checkoutRequestId) && !empty($phone)) {
        $normalized = preg_replace('/^(\+?254|0)/', '254', $phone);
        $payment = MpesaPayment::where('phone_number', $normalized)->latest()->first();
    }

    if ($payment && in_array($payment->transaction_status, ['paid', 'failed'], true)) {
        $resultDescription = $payment->result_desc ?: ('Payment status: ' . $payment->transaction_status);

        return [
            'success' => $payment->transaction_status === 'paid',
            'status' => $payment->transaction_status,
            'transaction_status' => $payment->transaction_status,
            'checkout_request_id' => $payment->checkout_request_id,
            'result_code' => $payment->result_code,
            'result_desc' => $resultDescription,
            'message' => $resultDescription,
            'mpesa_receipt_number' => $payment->mpesa_receipt_number,
            'payment_id' => $payment->id,
        ];
    }

    if (empty($checkoutRequestId)) {
        return ['success' => false, 'error' => 'Missing checkout_request_id', 'message' => 'Missing checkout_request_id'];
    }

    $accessToken = $this->generateAccessToken();
    if (!$accessToken) {
        Log::error('Failed to generate access token for M-Pesa query');
        return ['success' => false, 'error' => 'Unable to generate access token', 'message' => 'Unable to generate access token'];
    }

    $timestamp = now()->format('YmdHis');
    $password = base64_encode($this->shortCode . $this->passkey . $timestamp);

    $payload = [
        "BusinessShortCode" => $this->shortCode,
        "Password" => $password,
        "Timestamp" => $timestamp,
        "CheckoutRequestID" => $checkoutRequestId
    ];

    try {
        // Redact sensitive fields before logging
        $logPayload = $payload;
        if (is_array($logPayload) && array_key_exists('Password', $logPayload)) {
            $logPayload['Password'] = '[REDACTED]';
        }
        Log::info('M-Pesa Query Request', ['payload' => $logPayload]);

        $response = Http::withToken($accessToken)
            ->timeout(30)
            ->post('https://api.safaricom.co.ke/mpesa/stkpushquery/v1/query', $payload);

        Log::info('M-Pesa Query Response', ['body' => $response->body()]);

        $responseData = $response->json();

        if ($payment) {
            $payment->refresh();

            if (in_array($payment->transaction_status, ['paid', 'failed'], true)) {
                $resultDescription = $payment->result_desc ?: ('Payment status: ' . $payment->transaction_status);

                return [
                    'success' => $payment->transaction_status === 'paid',
                    'status' => $payment->transaction_status,
                    'transaction_status' => $payment->transaction_status,
                    'checkout_request_id' => $payment->checkout_request_id,
                    'result_code' => $payment->result_code,
                    'result_desc' => $resultDescription,
                    'message' => $resultDescription,
                    'mpesa_receipt_number' => $payment->mpesa_receipt_number,
                    'payment_id' => $payment->id,
                ];
            }
        }

        if (isset($responseData['ResultCode'])) {
            $resultCode = (string) $responseData['ResultCode'];
            $resultDescription = $responseData['ResultDesc'] ?? 'Payment status unavailable';

            if ($resultCode === '4999') {
                return [
                    'success' => true,
                    'status' => 'pending',
                    'transaction_status' => 'pending',
                    'checkout_request_id' => $payment->checkout_request_id ?? $checkoutRequestId,
                    'result_code' => $responseData['ResultCode'],
                    'result_desc' => $resultDescription,
                    'message' => $resultDescription,
                    'mpesa_receipt_number' => $payment->mpesa_receipt_number ?? null,
                    'payment_id' => $payment->id ?? null,
                ];
            }

            // ResultCode 0 means success
            if ($resultCode === '0') {
                $resultDescription = $responseData['ResultDesc'] ?? 'Payment confirmed';
                return [
                    'success' => true,
                    'status' => 'success',
                    'transaction_status' => 'success',
                    'checkout_request_id' => $payment->checkout_request_id ?? $checkoutRequestId,
                    'result_code' => $responseData['ResultCode'],
                    'result_desc' => $resultDescription,
                    'message' => $resultDescription,
                    'mpesa_receipt_number' => $payment->mpesa_receipt_number ?? null,
                    'payment_id' => $payment->id ?? null,
                ];
            } else {
                $resultDescription = $responseData['ResultDesc'] ?? 'Payment failed';

                if ($payment && $payment->transaction_status !== 'failed') {
                    $payment->update([
                        'transaction_status' => 'failed',
                        'result_code' => $responseData['ResultCode'],
                        'result_desc' => $resultDescription,
                    ]);
                }

                return [
                    'success' => false,
                    'status' => 'failed',
                    'transaction_status' => 'failed',
                    'checkout_request_id' => $payment->checkout_request_id ?? $checkoutRequestId,
                    'result_code' => $responseData['ResultCode'],
                    'result_desc' => $resultDescription,
                    'message' => $resultDescription,
                    'mpesa_receipt_number' => $payment->mpesa_receipt_number ?? null,
                    'payment_id' => $payment->id ?? null,
                ];
            }
        }

        // Safaricom error-envelope: has 'errorCode'/'errorMessage' instead of 'ResultCode'.
        // 500.001.1001 = "Transaction within processing limit" (queried too soon) → still pending.
        if (isset($responseData['errorCode'])) {
            $errorCode    = (string) ($responseData['errorCode'] ?? '');
            $errorMessage = $responseData['errorMessage'] ?? 'M-Pesa query error';

            if ($errorCode === '500.001.1001') {
                Log::debug('M-Pesa query returned processing-limit error; treating as pending', [
                    'checkout_request_id' => $checkoutRequestId,
                    'errorCode'           => $errorCode,
                    'errorMessage'        => $errorMessage,
                ]);

                return [
                    'success'             => false,
                    'status'              => 'pending',
                    'transaction_status'  => 'pending',
                    'checkout_request_id' => $payment->checkout_request_id ?? $checkoutRequestId,
                    'result_code'         => null,
                    'result_desc'         => $errorMessage,
                    'message'             => $errorMessage,
                    'mpesa_receipt_number' => $payment->mpesa_receipt_number ?? null,
                    'payment_id'          => $payment->id ?? null,
                ];
            }

            // Any other Safaricom errorCode is a genuine query failure — do not overwrite a
            // payment that the callback has already resolved to 'paid' or 'failed'.
            Log::warning('M-Pesa query returned errorCode', [
                'checkout_request_id' => $checkoutRequestId,
                'errorCode'           => $errorCode,
                'errorMessage'        => $errorMessage,
            ]);

            return [
                'success'             => false,
                'status'              => 'failed',
                'transaction_status'  => 'failed',
                'checkout_request_id' => $payment->checkout_request_id ?? $checkoutRequestId,
                'result_code'         => null,
                'result_desc'         => $errorMessage,
                'message'             => $errorMessage,
                'mpesa_receipt_number' => $payment->mpesa_receipt_number ?? null,
                'payment_id'          => $payment->id ?? null,
            ];
        }

    return ['success' => false, 'error' => 'Invalid response from M-Pesa', 'message' => 'Invalid response from M-Pesa'];

    } catch (\Exception $e) {
        Log::error('M-Pesa Query Exception: ' . $e->getMessage());
        return ['success' => false, 'error' => 'Query failed: ' . $e->getMessage(), 'message' => 'Query failed: ' . $e->getMessage()];
    }
}
    /**
     * NEW METHOD: Sync subscription status with payment status
     */
//     public function syncSubscriptionStatus($checkoutRequestId)
// {
//     $payment = MpesaPayment::where('checkout_request_id', $checkoutRequestId)->first();
    
//     if (!$payment) {
//         return ['success' => false, 'error' => 'Payment not found'];
//     }

//     // If payment is successful but subscription is not active, activate it
//     if ($payment->transaction_status === 'paid' && $payment->subscription_id) {
//         $subscription = Subscription::find($payment->subscription_id);
        
//         if ($subscription && $subscription->status !== 'active') {
//             // Use the proper base date calculation method
//             $baseDate = $this->getSubscriptionBaseDate($subscription);

//             $subscription->update([
//                 'status' => 'active',
//                 'mpesa_receipt' => $payment->mpesa_receipt_number,
//                 'checkout_request_id' => $payment->checkout_request_id,
//                 'start_date' => $baseDate, // Use the calculated base date
//                 'end_date' => $this->calculateEndDate($subscription->billing_cycle, $baseDate),
//                 'activated_at' => Carbon::now(),
//             ]);

//             // Also update the user's subscription status
//             $user = User::find($subscription->user_id);
//             if ($user) {
//                 $user->update([
//                     'has_active_subscription' => true,
//                     'subscription_expires_at' => $subscription->end_date,
//                 ]);
//             }

//             Log::info("✅ Subscription {$subscription->id} synced and activated for user {$subscription->user_id}");
//             return ['success' => true, 'message' => 'Subscription activated'];
//         }
//     }

//     return ['success' => false, 'message' => 'No action needed'];
// }
}