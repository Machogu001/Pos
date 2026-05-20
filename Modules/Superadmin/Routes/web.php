<?php

// use App\Http\Controllers\BusinessController;
// use App\Http\Controllers\Modules;
// use Illuminate\Support\Facades\Route;

Route::get('/pricing', [Modules\Superadmin\Http\Controllers\PricingController::class, 'index'])->name('pricing');

// Public version-check endpoint — no auth required.
// Other installations poll this to discover new releases.
Route::get('/version-check', [\Modules\Superadmin\Http\Controllers\UpdateController::class, 'versionInfo'])->name('superadmin.version.info');

// Update notification routes — available to ALL authenticated users (not superadmin-only)
// so that every user can check status and dismiss the banner.
// The 'run' (apply update) action enforces superadmin inside the controller.
// ── Release download (no session auth — bearer token only) ─────────────────
Route::get('/superadmin/update/release-info', [\Modules\Superadmin\Http\Controllers\UpdateController::class, 'releaseInfo'])->name('superadmin.update.release-info');
Route::get('/superadmin/update/package',      [\Modules\Superadmin\Http\Controllers\UpdateController::class, 'downloadPackage'])->name('superadmin.update.package');

Route::middleware('web', 'auth', 'language', 'AdminSidebarMenu')->prefix('superadmin')->group(function () {
    Route::get('/update/status',        [\Modules\Superadmin\Http\Controllers\UpdateController::class, 'status'])->name('superadmin.update.status');
    Route::get('/update/progress',      [\Modules\Superadmin\Http\Controllers\UpdateController::class, 'progress'])->name('superadmin.update.progress');
    Route::get('/update/pull-progress', [\Modules\Superadmin\Http\Controllers\UpdateController::class, 'pullProgress'])->name('superadmin.update.pull-progress');
    Route::get('/update/push-all',      [\Modules\Superadmin\Http\Controllers\UpdateController::class, 'pushAll'])->name('superadmin.update.push-all');
    Route::post('/update/run',          [\Modules\Superadmin\Http\Controllers\UpdateController::class, 'run'])->name('superadmin.update.run');
    Route::post('/update/dismiss',      [\Modules\Superadmin\Http\Controllers\UpdateController::class, 'dismiss'])->name('superadmin.update.dismiss');
    // Client registry (central server)
    Route::get('/update/clients',           [\Modules\Superadmin\Http\Controllers\UpdateController::class, 'clients'])->name('superadmin.update.clients');
    Route::post('/update/clients',          [\Modules\Superadmin\Http\Controllers\UpdateController::class, 'storeClient'])->name('superadmin.update.clients.store');
    Route::delete('/update/clients/{id}',   [\Modules\Superadmin\Http\Controllers\UpdateController::class, 'destroyClient'])->name('superadmin.update.clients.destroy');
    Route::post('/update/clients/{id}/push',[\Modules\Superadmin\Http\Controllers\UpdateController::class, 'pushToClient'])->name('superadmin.update.clients.push');
    Route::get('/update/build-package',    [\Modules\Superadmin\Http\Controllers\UpdateController::class, 'buildPackage'])->name('superadmin.update.build-package');
});

Route::middleware('web', 'auth', 'language', 'AdminSidebarMenu', 'superadmin')->prefix('superadmin')->group(function () {
    Route::get('/install', [Modules\Superadmin\Http\Controllers\InstallController::class, 'index']);
    Route::get('/install/update', [Modules\Superadmin\Http\Controllers\InstallController::class, 'update']);
    Route::get('/install/uninstall', [Modules\Superadmin\Http\Controllers\InstallController::class, 'uninstall']);

    Route::get('/', [Modules\Superadmin\Http\Controllers\SuperadminController::class, 'index']);
    Route::get('/stats', [Modules\Superadmin\Http\Controllers\SuperadminController::class, 'stats']);

    Route::get('/{business_id}/toggle-active/{is_active}', [Modules\Superadmin\Http\Controllers\BusinessController::class, 'toggleActive']);

    Route::get('/users/{business_id}', [Modules\Superadmin\Http\Controllers\BusinessController::class, 'usersList']);
    Route::post('/update-password', [Modules\Superadmin\Http\Controllers\BusinessController::class, 'updatePassword']);

    Route::resource('/business', Modules\Superadmin\Http\Controllers\BusinessController::class);
    Route::get('/business/{id}/destroy', [Modules\Superadmin\Http\Controllers\BusinessController::class, 'destroy']);

    Route::resource('/packages', 'Modules\Superadmin\Http\Controllers\PackagesController');
    Route::get('/packages/{id}/destroy', [Modules\Superadmin\Http\Controllers\PackagesController::class, 'destroy']);

    Route::get('/settings', [Modules\Superadmin\Http\Controllers\SuperadminSettingsController::class, 'edit']);
    Route::put('/settings', [Modules\Superadmin\Http\Controllers\SuperadminSettingsController::class, 'update']);
    Route::get('/edit-subscription/{id}', [Modules\Superadmin\Http\Controllers\SuperadminSubscriptionsController::class, 'editSubscription']);
    Route::post('/update-subscription', [Modules\Superadmin\Http\Controllers\SuperadminSubscriptionsController::class, 'updateSubscription']);
    Route::resource('/superadmin-subscription', 'Modules\Superadmin\Http\Controllers\SuperadminSubscriptionsController');

    Route::get('/communicator', [Modules\Superadmin\Http\Controllers\CommunicatorController::class, 'index']);
    Route::post('/communicator/send', [Modules\Superadmin\Http\Controllers\CommunicatorController::class, 'send']);
    Route::get('/communicator/get-history', [Modules\Superadmin\Http\Controllers\CommunicatorController::class, 'getHistory']);

    Route::resource('/frontend-pages', 'Modules\Superadmin\Http\Controllers\PageController');

    // SLA
    Route::get('/sla', [\Modules\Superadmin\Http\Controllers\SlaController::class, 'show'])->name('superadmin.sla.show');
    Route::get('/sla/edit', [\Modules\Superadmin\Http\Controllers\SlaController::class, 'edit'])->name('superadmin.sla.edit');
    Route::post('/sla', [\Modules\Superadmin\Http\Controllers\SlaController::class, 'update'])->name('superadmin.sla.update');
});

Route::middleware('web', 'SetSessionData', 'auth', 'language', 'timezone', 'AdminSidebarMenu')->group(function () {
    //Routes related to paypal checkout
    Route::get('/subscription/{package_id}/paypal-express-checkout', [Modules\Superadmin\Http\Controllers\SubscriptionController::class, 'paypalExpressCheckout']);

    Route::get('/subscription/post-flutterwave-payment', [Modules\Superadmin\Http\Controllers\SubscriptionController::class, 'postFlutterwavePaymentCallback']);

    Route::post('/subscription/pay-stack', [Modules\Superadmin\Http\Controllers\SubscriptionController::class, 'getRedirectToPaystack']);
    Route::get('/subscription/post-payment-pay-stack-callback', [Modules\Superadmin\Http\Controllers\SubscriptionController::class, 'postPaymentPaystackCallback']);

    //Routes related to pesapal checkout
    Route::get('/subscription/{package_id}/pesapal-callback', [Modules\Superadmin\Http\Controllers\SubscriptionController::class, 'pesapalCallback'])->name('pesapalCallback');

    Route::get('/subscription/{package_id}/pay', [Modules\Superadmin\Http\Controllers\SubscriptionController::class, 'pay']);
    Route::any('/subscription/{package_id}/confirm', [Modules\Superadmin\Http\Controllers\SubscriptionController::class, 'confirm'])->name('subscription-confirm');
    Route::get('/all-subscriptions', [Modules\Superadmin\Http\Controllers\SubscriptionController::class, 'allSubscriptions']);

    Route::get('/subscription/{package_id}/register-pay', [Modules\Superadmin\Http\Controllers\SubscriptionController::class, 'registerPay'])->name('register-pay');

    Route::resource('/subscription', 'Modules\Superadmin\Http\Controllers\SubscriptionController');
});

Route::get('/page/{slug}', [Modules\Superadmin\Http\Controllers\PageController::class, 'showPage'])->name('frontend-pages');
