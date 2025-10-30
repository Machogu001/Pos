<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\MpesaPayment;

class MpesaLogController extends Controller
{
    /**
     * Return mpesa payment log(s) for a given checkout_request_id or phone.
     */
    public function paymentLogs(Request $request)
    {
        $checkoutRequestId = $request->input('checkout_request_id');
        $phone = $request->input('phone');

        if (!empty($checkoutRequestId)) {
            $payments = MpesaPayment::where('checkout_request_id', $checkoutRequestId)
                ->orderBy('created_at', 'desc')
                ->take(10)
                ->get();
        } elseif (!empty($phone)) {
            $rawPhone = $phone;
            $normalized = preg_replace('/^(\+?254|0)/', '254', $rawPhone);
            $payments = MpesaPayment::where('phone_number', $normalized)
                ->orderBy('created_at', 'desc')
                ->take(10)
                ->get();
        } else {
            return response()->json(['success' => false, 'message' => 'Provide checkout_request_id or phone'], 400);
        }

        $payload = $payments->map(function ($p) {
            // created_at / paid_at may be strings depending on casts; normalize safely
            $createdAt = null;
            if (!empty($p->created_at)) {
                if (is_string($p->created_at)) {
                    $createdAt = $p->created_at;
                } elseif (method_exists($p->created_at, 'toDateTimeString')) {
                    $createdAt = $p->created_at->toDateTimeString();
                }
            }

            $paidAt = null;
            if (!empty($p->paid_at)) {
                if (is_string($p->paid_at)) {
                    $paidAt = $p->paid_at;
                } elseif (method_exists($p->paid_at, 'toDateTimeString')) {
                    $paidAt = $p->paid_at->toDateTimeString();
                }
            }

            return [
                'id' => $p->id,
                'checkout_request_id' => $p->checkout_request_id,
                'merchant_request_id' => $p->merchant_request_id,
                'phone_number' => $p->phone_number,
                'amount' => $p->amount,
                'transaction_status' => $p->transaction_status,
                'mpesa_receipt_number' => $p->mpesa_receipt_number,
                'result_desc' => $p->result_desc,
                'result_code' => $p->result_code,
                'paid_at' => $paidAt,
                'created_at' => $createdAt,
            ];
        });

        return response()->json(['success' => true, 'payments' => $payload]);
    }
}
