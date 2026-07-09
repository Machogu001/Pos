<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\MpesaPayment;

class MpesaPaymentController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'admin']);
    }

    public function show(MpesaPayment $mpesaPayment)
    {
        $user = auth()->user();
        if (! request()->routeIs('superadmin.admin.mpesa_payments.show')
            && ! empty($user)
            && $user->role === 'admin'
            && empty($user->business_id)) {
            return redirect()->route('superadmin.admin.mpesa_payments.show', ['mpesaPayment' => $mpesaPayment->id]);
        }

        // Load relations useful for admin inspection
        $mpesaPayment->load(['user', 'subscription']);

        return view('admin.mpesa_payments.show', ['mpesa' => $mpesaPayment]);
    }
}
