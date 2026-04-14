<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\MpesaPayment;
use Illuminate\Support\Facades\Log;

class MpesaLogController extends Controller
{
    private function mapPayment(MpesaPayment $payment, ?string $targetCheckoutRequestId = null): array
    {
        $createdAt = null;
        if (!empty($payment->created_at)) {
            $createdAt = is_string($payment->created_at)
                ? $payment->created_at
                : (method_exists($payment->created_at, 'toDateTimeString') ? $payment->created_at->toDateTimeString() : null);
        }

        $paidAt = null;
        if (!empty($payment->paid_at)) {
            $paidAt = is_string($payment->paid_at)
                ? $payment->paid_at
                : (method_exists($payment->paid_at, 'toDateTimeString') ? $payment->paid_at->toDateTimeString() : null);
        }

        $checkoutRequestId = $this->cleanString($payment->checkout_request_id);

        return [
            'id' => $payment->id,
            'checkout_request_id' => $checkoutRequestId,
            'merchant_request_id' => $this->cleanString($payment->merchant_request_id),
            'phone_number' => $this->cleanString($payment->phone_number),
            'amount' => $payment->amount,
            'transaction_status' => $this->cleanString($payment->transaction_status),
            'mpesa_receipt_number' => $this->cleanString($payment->mpesa_receipt_number),
            'result_desc' => $this->cleanString($payment->result_desc),
            'result_code' => is_null($payment->result_code) ? null : (string) $payment->result_code,
            'paid_at' => $this->cleanString($paidAt),
            'created_at' => $this->cleanString($createdAt),
            'is_target' => $targetCheckoutRequestId !== null && $checkoutRequestId === $targetCheckoutRequestId,
        ];
    }

    private function cleanString($value): ?string
    {
        if (is_null($value)) {
            return null;
        }

        $stringValue = (string) $value;

        if ($stringValue === '') {
            return '';
        }

        $cleaned = @iconv('UTF-8', 'UTF-8//IGNORE', $stringValue);

        return $cleaned === false ? $stringValue : $cleaned;
    }

    /**
     * Return mpesa payment log(s) for a given checkout_request_id or phone.
     */
    public function paymentLogs(Request $request)
    {
        try {
            $checkoutRequestId = trim((string) $request->input('checkout_request_id'));
            $phone = trim((string) $request->input('phone'));
            $normalizedPhone = $phone !== ''
                ? (MpesaPayment::normalizePhoneNumber($phone) ?? preg_replace('/^(\+?254|0)/', '254', $phone))
                : '';

            $targetPayments = collect();
            $relatedPayments = collect();

            if ($checkoutRequestId !== '') {
                $targetPayments = MpesaPayment::where('checkout_request_id', $checkoutRequestId)
                    ->orderBy('created_at', 'desc')
                    ->take(10)
                    ->get();

                if ($normalizedPhone === '' && $targetPayments->isNotEmpty()) {
                    $normalizedPhone = (string) optional($targetPayments->first())->phone_number;
                }

                if ($normalizedPhone !== '') {
                    $relatedPayments = MpesaPayment::where('phone_number', $normalizedPhone)
                        ->when($checkoutRequestId !== '', function ($query) use ($checkoutRequestId) {
                            $query->where('checkout_request_id', '!=', $checkoutRequestId);
                        })
                        ->orderBy('created_at', 'desc')
                        ->take(10)
                        ->get();
                }
            } elseif ($normalizedPhone !== '') {
                $targetPayments = MpesaPayment::where('phone_number', $normalizedPhone)
                    ->orderBy('created_at', 'desc')
                    ->take(10)
                    ->get();
            } else {
                return response()->json(['success' => false, 'message' => 'Provide checkout_request_id or phone'], 400);
            }

            $payload = $targetPayments
                ->map(function ($payment) use ($checkoutRequestId) {
                    return $this->mapPayment($payment, $checkoutRequestId !== '' ? $checkoutRequestId : null);
                })
                ->values();

            $relatedPayload = $relatedPayments
                ->map(function ($payment) use ($checkoutRequestId) {
                    return $this->mapPayment($payment, $checkoutRequestId !== '' ? $checkoutRequestId : null);
                })
                ->values();

            if ($checkoutRequestId !== '' && $payload->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No transaction is related to this payment checkout.',
                    'payments' => [],
                    'related_payments' => $relatedPayload,
                    'checkout_request_id' => $checkoutRequestId,
                ], 200, [], JSON_INVALID_UTF8_SUBSTITUTE);
            }

            return response()->json([
                'success' => true,
                'payments' => $payload,
                'related_payments' => $relatedPayload,
                'checkout_request_id' => $checkoutRequestId,
            ], 200, [], JSON_INVALID_UTF8_SUBSTITUTE);
        } catch (\Throwable $exception) {
            Log::error('MpesaLogController paymentLogs failed', [
                'checkout_request_id' => $request->input('checkout_request_id'),
                'phone' => $request->input('phone'),
                'message' => $exception->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unable to fetch M-Pesa logs right now.',
            ], 500, [], JSON_INVALID_UTF8_SUBSTITUTE);
        }
    }
}
