<?php

use App\Http\Controllers\Api\Mobile\AuthController;
use App\Http\Controllers\Api\Mobile\CashRegisterController;
use App\Http\Controllers\Api\Mobile\CustomerController;
use App\Http\Controllers\Api\Mobile\DashboardController;
use App\Http\Controllers\Api\Mobile\MeController;
use App\Http\Controllers\Api\Mobile\MpesaController;
use App\Http\Controllers\Api\Mobile\PaymentMethodController;
use App\Http\Controllers\Api\Mobile\ProductController;
use App\Http\Controllers\Api\Mobile\SaleController;
use App\Http\Controllers\Api\Mobile\WebSessionController;
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:10,1')->group(function () {
    Route::post('auth/login', [AuthController::class, 'login']);
    Route::post('auth/otp/verify', [AuthController::class, 'verifyOtp']);
    Route::post('auth/otp/resend', [AuthController::class, 'resendOtp']);
});

Route::middleware(['auth:api', 'mobile.api'])->group(function () {
    Route::post('auth/logout', [AuthController::class, 'logout']);
    Route::get('me', [MeController::class, 'show']);
    Route::get('dashboard', [DashboardController::class, 'show']);
    Route::get('products', [ProductController::class, 'index']);
    Route::get('products/lookup', [ProductController::class, 'lookup']);
    Route::get('customers', [CustomerController::class, 'index']);
    Route::post('customers', [CustomerController::class, 'store']);
    Route::get('payment-methods', [PaymentMethodController::class, 'index']);
    Route::get('cash-register', [CashRegisterController::class, 'show']);
    Route::post('cash-register/open', [CashRegisterController::class, 'open']);
    Route::post('cash-register/close', [CashRegisterController::class, 'close']);
    Route::post('sales', [SaleController::class, 'store']);
    Route::get('sales', [SaleController::class, 'index']);
    Route::get('sales/{id}', [SaleController::class, 'show'])->whereNumber('id');
    Route::post('mpesa/stk-push', [MpesaController::class, 'stkPush']);
    Route::get('mpesa/status/{checkoutRequestId}', [MpesaController::class, 'status']);
    Route::post('web-session', [WebSessionController::class, 'create'])->middleware('throttle:20,1');
    Route::get('web-menu', [WebSessionController::class, 'menu']);
});
