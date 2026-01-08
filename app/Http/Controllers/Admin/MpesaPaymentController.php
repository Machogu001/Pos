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
        // Load relations useful for admin inspection
        $mpesaPayment->load(['user', 'subscription']);

        return view('admin.mpesa_payments.show', ['mpesa' => $mpesaPayment]);
    }
}
