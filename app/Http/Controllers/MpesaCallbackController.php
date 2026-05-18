<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use App\MpesaPayment;
use App\Http\Controllers\SubscriptionController;
use App\Subscription;
use App\Services\MobileSasaSmsService;
use Carbon\Carbon;

class MpesaCallbackController extends Controller
{
    protected $smsService;

    public function __construct(MobileSasaSmsService $smsService)
    {
        $this->smsService = $smsService;
    }

    public function handleCallback(Request $request)
{
    Log::info('📩 M-Pesa Callback Received:', $request->all());

    $callback = $request->input('Body.stkCallback');

    if (!$callback) {
        Log::error('❌ Invalid M-Pesa callback structure');
        return response()->json(['error' => 'Invalid callback structure'], 400);
    }

    $resultCode = $callback['ResultCode'] ?? null;
    $resultDesc = $callback['ResultDesc'] ?? '';
    $merchantRequestID = $callback['MerchantRequestID'] ?? null;
    $checkoutRequestID = $callback['CheckoutRequestID'] ?? null;

    if (!$merchantRequestID || !$checkoutRequestID) {
        Log::error('❌ Missing Merchant or Checkout Request ID in callback');
        return response()->json(['error' => 'Missing IDs'], 400);
    }

    $payment = MpesaPayment::where('merchant_request_id', $merchantRequestID)
        ->where('checkout_request_id', $checkoutRequestID)
        ->first();

    // If not found by merchant/checkout IDs, try to match by AccountReference (invoice number) or BillRefNumber
    if (! $payment) {
        // Attempt to extract AccountReference or BillRefNumber from the callback body
        try {
            $raw = $request->all();
            // Look for possible locations
            $accountRef = null;
            if (isset($raw['Body']['stkCallback']['CallbackMetadata']['Item'])) {
                foreach ($raw['Body']['stkCallback']['CallbackMetadata']['Item'] as $item) {
                    if (isset($item['Name']) && in_array($item['Name'], ['AccountReference', 'BillRefNumber'])) {
                        $accountRef = $item['Value'] ?? null;
                        break;
                    }
                }
            }
            // Some providers send account reference at top-level or different key
            if (empty($accountRef)) {
                $accountRef = $request->input('account_reference') ?? $request->input('AccountReference') ?? $request->input('BillRefNumber');
            }

            if (! empty($accountRef)) {
                // Try exact match first
                $payment = MpesaPayment::where('account_reference', $accountRef)->latest()->first();
                if (! $payment) {
                    // Try case-insensitive / contains match
                    $payment = MpesaPayment::where('account_reference', 'like', '%' . $accountRef . '%')->latest()->first();
                }
            }
        } catch (\Exception $e) {
            Log::warning('Error extracting account reference from callback: ' . $e->getMessage());
        }
    }

    if (!$payment) {
        Log::warning("⚠️ No matching M-Pesa payment found for CheckoutRequestID: $checkoutRequestID");
        return response()->json(['error' => 'Payment not found'], 404);
    }

    if ($payment->transaction_status !== 'pending') {
        Log::info("ℹ️ Payment already processed for CheckoutRequestID: $checkoutRequestID. Skipping...");
        return response()->json(['status' => 'Already processed'], 200);
    }

    $payment->result_code = $resultCode;
    $payment->result_desc = $resultDesc;

    $shouldSendResumeSms = false;

    if ($resultCode == 0) {
        $metadata = collect($callback['CallbackMetadata']['Item'] ?? []);

        Log::info("📦 Callback Metadata: ", $metadata->toArray()); // Useful for debugging

        $amount   = $metadata->firstWhere('Name', 'Amount')['Value'] ?? null;
        $receipt  = $metadata->firstWhere('Name', 'MpesaReceiptNumber')['Value'] ?? null;
        $phone    = $metadata->firstWhere('Name', 'PhoneNumber')['Value'] ?? null;
        $firstName  = $metadata->firstWhere('Name', 'FirstName')['Value'] ?? '';
        $middleName = $metadata->firstWhere('Name', 'MiddleName')['Value'] ?? '';
        $lastName   = $metadata->firstWhere('Name', 'LastName')['Value'] ?? '';

        // Use names from callback if available, otherwise use user input names from database
        $payerName = trim("{$firstName} {$middleName} {$lastName}");
        
        // If callback names are empty, use the user input names stored in the payment record
        if (empty($payerName)) {
            $payerName = trim("{$payment->first_name} {$payment->middle_name} {$payment->last_name}");
            Log::info("📝 Using user input names: $payerName");
        }
        
        // If both callback and user input names are empty, use the user's first name + last name
        if (empty($payerName)) {
            // Build name from available user input fields
            $nameParts = [];
            if (!empty($payment->first_name)) $nameParts[] = $payment->first_name;
            if (!empty($payment->last_name)) $nameParts[] = $payment->last_name;
            
            $payerName = implode(' ', $nameParts);
            
            if (!empty($payerName)) {
                Log::info("👤 Using combined user names: $payerName");
            } else {
                // Final fallback - use account reference or phone
                $payerName = !empty($payment->account_reference) ? "Customer {$payment->account_reference}" : "Customer";
                Log::info("🔤 Using account reference fallback: $payerName");
            }
        }

    $shouldSendResumeSms = empty($payment->paid_at)
        && ($payment->payment_type ?? null) === MpesaPayment::TYPE_REGISTRATION
        && empty($payment->business_id)
        && empty($payment->consumed_at)
        && ! empty($phone ?: $payment->phone_number);

    $payment->amount               = $amount;
    $payment->mpesa_receipt_number = $receipt;
    $payment->phone_number         = $phone;
    $payment->payer_name           = $payerName;
    $payment->paid_at              = Carbon::now();
    // Persist canonical status 'paid' so other codepaths treat it consistently
    $payment->transaction_status   = 'paid';

    Log::info("✅ M-Pesa Payment success | Receipt: $receipt | Phone: $phone | Amount: $amount | Payer: $payerName");
    } else {
        $payment->transaction_status = 'failed';
        Log::warning("❌ M-Pesa Payment Failed | Reason: $resultDesc | ID: $merchantRequestID");
    }

    $payment->save();

    if ($shouldSendResumeSms) {
        try {
            $resumeUrl = BusinessController::registrationResumeUrlForPayment($payment);
            $this->smsService->sendRegistrationResumeInstructions(
                $payment->phone_number,
                $payment->account_reference,
                $resumeUrl
            );
        } catch (\Throwable $exception) {
            Log::warning('Unable to send registration resume SMS', [
                'payment_id' => $payment->id,
                'message' => $exception->getMessage(),
            ]);
        }
    }

    // Attempt to activate subscription only when the payment succeeded and is a subscription type
    if ($resultCode == 0 && ($payment->payment_type ?? null) === 'subscription') {
        try {
            $subscriptionController = new SubscriptionController();
            $activated = $subscriptionController->activateSubscription($payment);

            if ($activated) {
                Log::info("✅ Subscription activated by API callback for payment ID: {$payment->id}");
            } else {
                Log::info("ℹ️ Subscription not activated automatically for payment ID: {$payment->id} - will require manual sync or check.");
            }
        } catch (\Exception $e) {
            Log::error('Error activating subscription from API callback: ' . $e->getMessage());
        }
    }

    return response()->json(['status' => 'Callback processed'], 200);
}
}
