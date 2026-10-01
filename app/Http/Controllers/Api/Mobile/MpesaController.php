<?php

namespace App\Http\Controllers\Api\Mobile;

use App\MpesaPayment;
use Illuminate\Http\Request;

class MpesaController extends BaseMobileController
{
    public function stkPush(Request $request)
    {
        $data = $request->validate([
            'phone' => ['required', 'string', 'min:10', 'max:15'],
            'amount' => ['required', 'numeric', 'min:1'],
            'location_id' => ['required', 'integer'],
        ]);

        try {
            $user = $request->user();
            if (! $this->canAccessLocation($user, (int) $data['location_id'])) {
                return $this->error('Forbidden.', 403, 'forbidden');
            }

            $posRequest = Request::create('/mpesa/initiate', 'POST', [
                'phone' => $data['phone'],
                'amount' => $data['amount'],
                'payment_type' => 'sell',
                'is_pos' => 1,
                'account_reference' => 'MOB-'.now()->format('YmdHis').'-'.$user->id,
            ]);
            $posRequest->headers->set('Accept', 'application/json');
            $posRequest->setLaravelSession($request->session());
            $response = app(\App\Http\Controllers\MpesaController::class)->initiatePayment($posRequest);
            $payload = method_exists($response, 'getData') ? (array) $response->getData(true) : [];
            $status = method_exists($response, 'getStatusCode') ? $response->getStatusCode() : 200;

            if ($status >= 400 || ($payload['transaction_status'] ?? null) === 'error') {
                return $this->error($payload['message'] ?? 'STK Push failed.', $status >= 400 ? $status : 422, 'mpesa_error');
            }

            if (! empty($payload['checkout_request_id'])) {
                MpesaPayment::where('checkout_request_id', $payload['checkout_request_id'])
                    ->where('payment_type', 'sell')
                    ->update(['business_id' => $user->business_id, 'user_id' => $user->id]);
            }

            return $this->success([
                'checkout_request_id' => $payload['checkout_request_id'] ?? null,
                'status' => 'pending',
                'message' => $payload['message'] ?? 'STK push sent. Enter PIN on your phone.',
            ]);
        } catch (\Throwable $exception) {
            return $this->serverError($exception, ['action' => 'mobile_mpesa_stk']);
        }
    }

    public function status(Request $request, string $checkoutRequestId)
    {
        try {
            $user = $request->user();
            $payment = MpesaPayment::where('checkout_request_id', $checkoutRequestId)
                ->where('payment_type', 'sell')
                ->where('business_id', $user->business_id)
                ->latest()
                ->first();

            if (! $payment) {
                return $this->error('Payment not found.', 404, 'not_found');
            }

            if ($payment->transaction_status === 'pending' && $payment->created_at && $payment->created_at->diffInSeconds(now()) > 10) {
                app(\App\Http\Controllers\MpesaController::class)->queryMpesaPaymentStatus($checkoutRequestId);
                $payment->refresh();
            }

            $status = match ($payment->transaction_status) {
                'paid' => 'paid',
                'failed' => 'failed',
                'cancelled' => 'cancelled',
                default => 'pending',
            };

            return $this->success([
                'checkout_request_id' => $payment->checkout_request_id,
                'status' => $status,
                'receipt_number' => $payment->mpesa_receipt_number,
                'message' => $payment->result_desc ?: 'Payment status: '.$status,
            ]);
        } catch (\Throwable $exception) {
            return $this->serverError($exception, ['action' => 'mobile_mpesa_status']);
        }
    }
}
