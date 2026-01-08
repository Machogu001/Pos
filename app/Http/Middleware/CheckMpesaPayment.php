<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckMpesaPayment
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        // Check session for M-Pesa payment status
        $paymentStatus = session('mpesa_payment_status');

        if ($paymentStatus !== 'paid') {
            return redirect()->route('payment.form')->with('error', __('payment.payment_required'));
        }

        return $next($request);
    }
}
