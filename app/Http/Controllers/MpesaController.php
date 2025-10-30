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

    public function __construct()
    {
        $this->consumerKey = env('MPESA_CONSUMER_KEY');
        $this->consumerSecret = env('MPESA_CONSUMER_SECRET');
        $this->shortCode = env('MPESA_SHORTCODE');
        $this->passkey = env('MPESA_PASSKEY');
        $this->callbackUrl = env('MPESA_CALLBACK');
    }

    public function showPaymentForm()
    {
        return view('business.payment');
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
            // allow caller to indicate the type of payment (sell, registration, subscription)
            'payment_type' => 'nullable|string|in:registration,sell,subscription',
        ]);

    $rawPhone = $request->phone;
    $phone = preg_replace('/^(\+?254|0)/', '254', $rawPhone);

        // Defensive validation: require Kenyan mobile format 2547XXXXXXXX
        if (!preg_match('/^2547\d{8}$/', $phone)) {
            Log::warning('MpesaController initiatePayment called with invalid phone', [
                'user_id' => auth()->id(),
                'raw_phone' => $rawPhone,
                'normalized' => $phone,
            ]);

            return response()->json([
                'transaction_status' => 'error',
                'message' => 'Invalid phone. Use 2547XXXXXXXX format.'
            ], 422);
        }

    // Use admin-configured registration price if available, but allow caller to override via request
    $settings = \App\AdminSetting::first();
    $amount = $request->input('amount');
    if (empty($amount)) {
        $amount = $settings->registration_price ?? 5;
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
        ]);

        // Check for existing pending transaction
        $payment = MpesaPayment::where('phone_number', $phone)
            ->where('transaction_status', 'pending')
            ->latest()
            ->first();

        if (!$payment) {
            $accountRef = strtoupper(Str::random(8));
        } else {
            $accountRef = $payment->account_reference;
        }

        // Send STK Push
        $response = $this->sendStkPush($phone, $amount, $accountRef);
        $responseBody = is_array($response) ? $response : json_decode($response, true);

        if (isset($responseBody['ResponseCode']) && $responseBody['ResponseCode'] === '0') {
            $merchantRequestID = $responseBody['MerchantRequestID'] ?? null;
            $checkoutRequestID = $responseBody['CheckoutRequestID'] ?? null;

                // Allow caller to set payment_type (default to registration)
                // But force 'sell' when request appears to originate from POS (referer or explicit flag)
                $defaultType = 'registration';
                $paymentType = $request->input('payment_type', $defaultType);

                // If the frontend explicitly indicates this is a POS request, or the referer contains POS paths, force 'sell'
                $referer = strtolower($request->header('referer') ?? '');
                $isPosFlag = $request->input('is_pos') == 1 || $request->input('from_pos') == 1;
                // Check referer contains any of the known POS path segments
                $posPaths = ['/sale_pos', '/pos', '/sale-pos', '/sale_pos'];
                $refererContainsPos = false;
                if (!empty($referer)) {
                    foreach ($posPaths as $p) {
                        if (str_contains($referer, $p)) {
                            $refererContainsPos = true;
                            break;
                        }
                    }
                }

                if ($isPosFlag || $refererContainsPos) {
                    $paymentType = 'sell';
                }

                if (!$payment) {
                    $payment = MpesaPayment::create([
                        'user_id' => auth()->id(), // ✅ Use auth()->id() instead of $userId
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
                        'merchant_request_id' => $merchantRequestID,
                        'checkout_request_id' => $checkoutRequestID,
                        'payment_type' => $paymentType,
                        'first_name' => $request->first_name,
                        'middle_name' => $request->middle_name,
                        'last_name' => $request->last_name,
                    ]);
                }

            // Store checkout request ID in session for status checking
            session(['checkout_request_id' => $checkoutRequestID]);
            session()->save();

            return response()->json([
                'transaction_status' => 'success',
                'account_ref' => $accountRef,
                'checkout_request_id' => $checkoutRequestID, 
                'message' => 'STK push sent. Enter PIN on your phone.',
            ]);
        }

        Log::error('STK Push Failed', ['response' => $responseBody]);
        $errorMessage = $responseBody['errorMessage'] ?? $responseBody['errorDesc'] ?? 'STK Push failed.';
        return response()->json(['transaction_status' => 'error', 'message' => $errorMessage], 400);
    }

    private function generateAccessToken()
    {
        $response = Http::withBasicAuth($this->consumerKey, $this->consumerSecret)
            ->get('https://api.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials');

        if ($response->successful()) {
            return $response['access_token'];
        }

        Log::error('Failed to generate access token', ['response' => $response->body()]);
        return null;
    }

    private function sendStkPush($phone, $amount, $accountRef)
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
            "TransactionType" => "CustomerPayBillOnline",
            "Amount" => $amount,
            "PartyA" => $phone,
            "PartyB" => $this->shortCode,
            "PhoneNumber" => $phone,
            "CallBackURL" => $this->callbackUrl,
            "AccountReference" => $accountRef,
            "TransactionDesc" => "Registration Payment"
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
            
            // Try to get phone from session first
            $phone = session('payment_phone');
            $checkoutRequestId = session('checkout_request_id');
            
            Log::info('Session payment_phone: ' . ($phone ?: 'empty'));
            Log::info('Session checkout_request_id: ' . ($checkoutRequestId ?: 'empty'));
            
            // If we have checkout request ID, use that for more precise lookup
            if ($checkoutRequestId) {
                $payment = MpesaPayment::where('checkout_request_id', $checkoutRequestId)->first();
                
                if ($payment) {
                    Log::info('Payment found by checkout ID: ' . $payment->id);
                    return response()->json([
                        'success' => true,
                        'transaction_status' => $payment->transaction_status,
                        'message' => 'Payment status: ' . $payment->transaction_status
                    ]);
                }
            }
            
            // If session is empty, get the VERY LATEST payment (regardless of status)
            if (!$phone) {
                $latestPayment = MpesaPayment::latest()->first();
                
                if ($latestPayment) {
                    $phone = $latestPayment->phone_number;
                    Log::info('Found latest payment phone from DB: ' . $phone);
                    
                    // Update session for future requests
                    session(['payment_phone' => $phone]);
                    session()->save();
                }
            }

            // If still no phone, check the request for phone number
            if (!$phone && $request->has('phone_number')) {
                $phone = $request->phone_number;
                Log::info('Using phone from request: ' . $phone);
            }

            if (!$phone) {
                Log::warning('No phone number found for payment confirmation');
                return response()->json([
                    'success' => false,
                    'message' => 'No payment session found'
                ], 400);
            }

            Log::info('Checking payment for phone: ' . $phone);
            
            // Check for successful payment - look for ANY payment with this phone
            $payment = MpesaPayment::where('phone_number', $phone)
                ->latest() // Get the most recent one
                ->first();

            if (!$payment) {
                Log::warning('No payment found for phone: ' . $phone);
                return response()->json([
                    'success' => false,
                    'message' => 'No payment found for this phone number'
                ], 404);
            }

            Log::info('Payment found with status: ' . $payment->transaction_status . ', ID: ' . $payment->id);
            
            // Return the actual payment status
            return response()->json([
                'success' => true,
                'transaction_status' => $payment->transaction_status,
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
        
        // If not found by checkout ID, try by phone (latest payment)
        if (!$payment && $phone) {
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

            // Only attempt activation for subscription payments
            if (($payment->payment_type ?? null) === 'subscription') {
                Log::info("Activating subscription for paid payment: {$payment->id}");
                $subscriptionController = new SubscriptionController();
                $activationResult = $subscriptionController->activateSubscription($payment);

                DB::commit();

                return response()->json([
                    'success' => true,
                    'status' => 'success',
                    'message' => 'Payment confirmed and subscription activated!',
                    'activated' => $activationResult,
                    'payment_id' => $payment->id
                ]);
            }

            Log::info("Paid payment is not a subscription payment; skipping activation", ['payment_id' => $payment->id, 'payment_type' => $payment->payment_type ?? 'null']);

            DB::commit();

            return response()->json([
                'success' => true,
                'status' => 'success',
                'message' => 'Payment confirmed (non-subscription). No subscription action taken.',
                'activated' => false,
                'payment_id' => $payment->id
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
                        'message' => 'Payment confirmed and subscription activated!',
                        'activated' => $activationResult,
                        'payment_id' => $payment->id
                    ]);
                }

                Log::info('Payment confirmed via query but not a subscription payment; skipping activation', ['payment_id' => $payment->id, 'payment_type' => $payment->payment_type ?? 'null']);

                DB::commit();

                return response()->json([
                    'success' => true,
                    'status' => 'success',
                    'message' => 'Payment confirmed (non-subscription). No subscription action taken.',
                    'activated' => false,
                    'payment_id' => $payment->id
                ]);
            }
            
            DB::commit();
            return response()->json([
                'success' => true,
                'status' => $payment->transaction_status,
                'message' => 'Payment is still pending',
                'payment_id' => $payment->id
            ]);
        }

        DB::commit();
        return response()->json([
            'success' => true,
            'status' => $payment->transaction_status,
            'message' => 'Payment status: ' . $payment->transaction_status,
            'payment_id' => $payment->id
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
            "TransactionType" => "CustomerPayBillOnline",
            "Amount" => $amount,
            "PartyA" => $phone,
            "PartyB" => $this->shortCode,
            "PhoneNumber" => $phone,
            "CallBackURL" => $this->callbackUrl,
            "AccountReference" => $accountReference,
            "TransactionDesc" => "Subscription Payment"
        ];

        $response = Http::withToken($accessToken)
            ->post('https://api.safaricom.co.ke/mpesa/stkpush/v1/processrequest', $payload);

        Log::info('STK Push Request (Direct)', ['payload' => $payload]);
        Log::info('STK Push Response (Direct)', ['body' => $response->body()]);

        $responseData = $response->json();
        
        if (isset($responseData['ResponseCode']) && $responseData['ResponseCode'] === '0') {
            return [
                'success' => true,
                'merchant_request_id' => $responseData['MerchantRequestID'],
                'checkout_request_id' => $responseData['CheckoutRequestID']
            ];
        }
        
        return [
            'success' => false,
            'errorMessage' => $responseData['errorMessage'] ?? $responseData['ResponseDescription'] ?? 'STK Push failed'
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

        if (isset($responseData['ResultCode'])) {
            // ResultCode 0 means success
            if ($responseData['ResultCode'] == 0) {
                return [
                    'success' => true,
                    'transaction_status' => 'success',
                    'result_code' => $responseData['ResultCode'],
                    'result_desc' => $responseData['ResultDesc']
                ];
            } else {
                return [
                    'success' => false,
                    'transaction_status' => 'failed',
                    'result_code' => $responseData['ResultCode'],
                    'result_desc' => $responseData['ResultDesc']
                ];
            }
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