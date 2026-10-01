<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\AccountReportsController;
use App\Http\Controllers\AccountTypeController;
// use App\Http\Controllers\Auth;
use App\Http\Controllers\BackUpController;
use App\Http\Controllers\BarcodeController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\BusinessController;
use App\Http\Controllers\BusinessLocationController;
use App\Http\Controllers\CashRegisterController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CombinedPurchaseReturnController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\CustomerGroupController;
use App\Http\Controllers\DashboardConfiguratorController;
use App\Http\Controllers\DiscountController;
use App\Http\Controllers\DocumentAndNoteController;
use App\Http\Controllers\ExpenseCategoryController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\GroupTaxController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ImportOpeningStockController;
use App\Http\Controllers\ImportProductsController;
use App\Http\Controllers\ImportSalesController;
use App\Http\Controllers\Install;
use App\Http\Controllers\InvoiceLayoutController;
use App\Http\Controllers\InvoiceSchemeController;
use App\Http\Controllers\LabelsController;
use App\Http\Controllers\LedgerDiscountController;
use App\Http\Controllers\LocationSettingsController;
use App\Http\Controllers\ManageUserController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\NotificationTemplateController;
use App\Http\Controllers\OpeningStockController;
use App\Http\Controllers\PrinterController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\PurchaseRequisitionController;
use App\Http\Controllers\PurchaseReturnController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\Restaurant;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SalesCommissionAgentController;
use App\Http\Controllers\SalesOrderController;
use App\Http\Controllers\SellController;
use App\Http\Controllers\SellingPriceGroupController;
use App\Http\Controllers\SellPosController;
use App\Http\Controllers\SellReturnController;
use App\Http\Controllers\StockAdjustmentController;
use App\Http\Controllers\StockTransferController;
use App\Http\Controllers\TaxonomyController;
use App\Http\Controllers\TaxRateController;
use App\Http\Controllers\TransactionPaymentController;
use App\Http\Controllers\TypesOfServiceController;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VariationTemplateController;
use App\Http\Controllers\WarrantyController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\StocktakeController;
use App\Http\Controllers\MpesaController;
use App\Http\Controllers\MpesaCallbackController;
use App\Http\Controllers\MpesaLogController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\SubscriptionInvoiceController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\PaymentAccountController;
use App\Http\Controllers\AccountingDashboardStubController;
/*|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

include_once 'install_r.php';

// Dynamic manifest route to include business name from session when available.
// This helps show a branded name in the install prompt. If your webserver
// serves the static `public/manifest.json` first, keep that as a fallback.
Route::get('/manifest.json', function () {
    // Keep fallback dynamic manifest that uses session business where available
    $name = session('business.name') ?? config('app.name');
    // Prefer business theme_color and logo from session when available
    $theme_color = session('business.theme_color') ?? '#2b6cb0';
    $icon192 = session('business.logo') ? asset(session('business.logo')) : asset('icons/icon-192.png');
    $icon512 = session('business.logo') ? asset(session('business.logo')) : asset('icons/icon-512.png');

    $manifest = [
        'name' => $name,
        'short_name' => 'POS',
        'start_url' => url('/'),
        'scope' => url('/'),
        'display' => 'standalone',
        'background_color' => '#ffffff',
        'theme_color' => $theme_color,
        'description' => 'Point of sale application',
        'icons' => [
            [
                'src' => $icon192,
                'sizes' => '192x192',
                'type' => 'image/png'
            ],
            [
                'src' => $icon512,
                'sizes' => '512x512',
                'type' => 'image/png'
            ],
            // SVG fallback
            [
                'src' => asset('icons/icon-192.svg'),
                'sizes' => '192x192',
                'type' => 'image/svg+xml'
            ],
            [
                'src' => asset('icons/icon-512.svg'),
                'sizes' => '512x512',
                'type' => 'image/svg+xml'
            ]
        ]
    ];

    return response()->json($manifest)->header('Content-Type', 'application/manifest+json');
});

// PWA endpoints to persist user install/dismissed state
Route::middleware(['auth'])->group(function () {
    Route::post('/pwa/installed', [\App\Http\Controllers\PwaController::class, 'markInstalled']);
    Route::post('/pwa/dismissed', [\App\Http\Controllers\PwaController::class, 'markDismissed']);
    // Telemetry endpoint: accepts events like 'shown','accepted','dismissed' for analytics.
    Route::post('/pwa/telemetry', [\App\Http\Controllers\PwaTelemetryController::class, 'store']);
});

// Public telemetry endpoint: allow anonymous visitors to post telemetry (uses same store method)
Route::post('/pwa/telemetry-public', [\App\Http\Controllers\PwaTelemetryController::class, 'store']);

// Single-use sign-in link issued by the mobile app (POST api/mobile/v1/web-session).
Route::get('/mobile/web-login/{token}', [\App\Http\Controllers\Api\Mobile\WebSessionController::class, 'consume'])
    ->middleware(['setData', 'throttle:20,1'])
    ->where('token', '[A-Za-z0-9]{64}');

Route::middleware(['setData'])->group(function () {
    Route::get('/', function () {
        return view('welcome');
    });

    Auth::routes();

    Route::get('/login/otp', [App\Http\Controllers\Auth\LoginController::class, 'showOtpForm'])->name('login.otp.form');
    Route::post('/login/otp', [App\Http\Controllers\Auth\LoginController::class, 'verifyOtp'])->name('login.otp.verify');
    Route::post('/login/otp/resend', [App\Http\Controllers\Auth\LoginController::class, 'resendOtp'])->name('login.otp.resend');

    Route::get('/business/register', [BusinessController::class, 'getRegister'])->name('business.getRegister');
    Route::post('/business/register', [BusinessController::class, 'postRegister'])
    ->name('business.postRegister');
    Route::post('/business/register/resume', [BusinessController::class, 'resumeRegistrationPayment'])->name('business.registration.resume');
    Route::get('/business/register/resume/{payment}', [BusinessController::class, 'resumeRegistrationFromLink'])
        ->middleware('signed')
        ->name('business.registration.resume.link');
    Route::post('/business/register/check-username', [BusinessController::class, 'postCheckUsername'])->name('business.postCheckUsername');
    Route::post('/business/register/check-email', [BusinessController::class, 'postCheckEmail'])->name('business.postCheckEmail');

    Route::get('/invoice/{token}', [SellPosController::class, 'showInvoice'])
        ->name('show_invoice');
    Route::get('/quote/{token}', [SellPosController::class, 'showInvoice'])
        ->name('show_quote');

    Route::get('/pay/{token}', [SellPosController::class, 'invoicePayment'])
        ->name('invoice_payment');
    Route::post('/confirm-payment/{id}', [SellPosController::class, 'confirmPayment'])
        ->name('confirm_payment');
    //Mpesa Payment
   // Business payment routes
Route::prefix('business')->group(function () {
    Route::post('/payment/initiate', [MpesaController::class, 'initiatePayment'])->name('business.payment.initiate');
    Route::post('/payment/confirm', [MpesaController::class, 'confirmPayment'])->name('business.payment.confirm');
});

// M-Pesa routes (remove the space from prefix)
Route::prefix('mpesa')->group(function () {
    Route::post('/initiate', [MpesaController::class, 'initiatePayment'])->name('mpesa.initiate');
    Route::post('/validate', [MpesaController::class, 'validatePayment'])->name('mpesa.validate');
    // API-style callback endpoint (kept for internal/testing) - renamed to avoid collision
    Route::post('/callback-api', [MpesaCallbackController::class, 'handleCallback'])->name('mpesa.callback.api');
    Route::get('/payment-form', [MpesaController::class, 'showPaymentForm'])->name('payment.form');
});

// Protect registration form
Route::get('/business/register/form', [BusinessController::class, 'showRegistrationForm'])->name('business.register.form');
Route::get('/register/form', [BusinessController::class, 'showRegistrationForm'])->name('registration.complete');

Route::post('/register/form', [BusinessController::class, 'postRegister'])
    ->name('business.formRegister');
Route::get('/register', [BusinessController::class, 'startRegistration'])->name('registration.start');
Route::post('/payment/retry', [\App\Http\Controllers\MpesaController::class, 'retry'])->name('mpesa.retry');

// Payment status route (fixed - routes were inside the closure)
Route::get('/payment-status/{checkoutRequestId}', function ($checkoutRequestId) {
    $payment = \App\MpesaPayment::where('checkout_request_id', $checkoutRequestId)->latest()->first();

    if (!$payment) {
        return response()->json(['transaction_status' => 'NOT_FOUND']);
    }

    return response()->json([
        'transaction_status' => $payment->transaction_status
    ]);
})->name('payment.status');

// Additional M-Pesa status routes (moved outside the closure)
Route::post('/api/mpesa/status/result', [MpesaController::class, 'handleStatusResult'])->name('mpesa.status-result');
Route::post('/api/mpesa/status/timeout', [MpesaController::class, 'handleStatusTimeout'])->name('mpesa.status-timeout');
// MPESA: registration/other direct STK push endpoint (kept separate from subscription controller)
Route::post('/subscription/stk-push-mpesa', [MpesaController::class, 'initiatePayment'])->name('mpesa.subscription.stkPush');

});
// M-Pesa status query routes
Route::post('/mpesa/query-status', [MpesaController::class, 'queryMpesaPaymentStatus'])->name('mpesa.queryStatus');
Route::post('/mpesa/sync-subscription', [MpesaController::class, 'syncSubscriptionStatus'])->name('mpesa.syncSubscription');
//     Route::middleware('check.mpesa.payment')->group(function () {
//     Auth::routes(['register' => true]);
// });
    //Mpesa Payment

// ============================
// Routes that require subscription (for regular users)
// ============================
Route::middleware(['setData', 'auth', 'SetSessionData', 'language', 'timezone', 'AdminSidebarMenu', 'CheckUserLogin', 'subscription'])->group(function () {
    // All business operation routes that require active subscription
    Route::prefix('hrm-admin')->name('hrm_admin.')->group(function () {
        Route::resource('companies', \Modules\Hrm\Http\Controllers\CompanyController::class);
        Route::resource('departments', \Modules\Hrm\Http\Controllers\DepartmentsController::class);
        Route::post('departments/{department}/head', [\Modules\Hrm\Http\Controllers\DepartmentsController::class, 'setHead'])->name('departments.set_head');
        Route::delete('departments/{department}/head', [\Modules\Hrm\Http\Controllers\DepartmentsController::class, 'removeHead'])->name('departments.remove_head');
    });

    Route::get('pos/payment/{id}', [SellPosController::class, 'edit'])->name('edit-pos-payment');
    Route::get('service-staff-availability', [SellPosController::class, 'showServiceStaffAvailibility']);
    Route::get('pause-resume-service-staff-timer/{user_id}', [SellPosController::class, 'pauseResumeServiceStaffTimer']);
    Route::get('mark-as-available/{user_id}', [SellPosController::class, 'markAsAvailable']);

    Route::resource('purchase-requisition', PurchaseRequisitionController::class)->except(['edit', 'update']);
    Route::post('/get-requisition-products', [PurchaseRequisitionController::class, 'getRequisitionProducts'])->name('get-requisition-products');
    Route::get('get-purchase-requisitions/{location_id}', [PurchaseRequisitionController::class, 'getPurchaseRequisitions']);
    Route::get('get-purchase-requisition-lines/{purchase_requisition_id}', [PurchaseRequisitionController::class, 'getPurchaseRequisitionLines']);

    Route::get('/sign-in-as-user/{id}', [ManageUserController::class, 'signInAsUser'])->name('sign-in-as-user');

    Route::get('/home', [HomeController::class, 'index'])->name('home');
    Route::get('/home/get-totals', [HomeController::class, 'getTotals']);
    Route::get('/home/product-stock-alert', [HomeController::class, 'getProductStockAlert']);
    Route::get('/home/purchase-payment-dues', [HomeController::class, 'getPurchasePaymentDues']);
    Route::get('/home/sales-payment-dues', [HomeController::class, 'getSalesPaymentDues']);
    Route::post('/attach-medias-to-model', [HomeController::class, 'attachMediasToGivenModel'])->name('attach.medias.to.model');
    Route::get('/calendar', [HomeController::class, 'getCalendar'])->name('calendar');

    Route::post('/test-email', [BusinessController::class, 'testEmailConfiguration']);
    Route::post('/test-sms', [BusinessController::class, 'testSmsConfiguration']);
    Route::get('/business/settings', [BusinessController::class, 'getBusinessSettings'])->name('business.getBusinessSettings');
    Route::post('/business/update', [BusinessController::class, 'postBusinessSettings'])->name('business.postBusinessSettings');
    Route::get('/user/profile', [UserController::class, 'getProfile'])->name('user.getProfile');
    Route::post('/user/update', [UserController::class, 'updateProfile'])->name('user.updateProfile');
    Route::post('/user/update-password', [UserController::class, 'updatePassword'])->name('user.updatePassword');

    Route::resource('brands', BrandController::class);

    Route::resource('payment-account', PaymentAccountController::class);

    Route::resource('tax-rates', TaxRateController::class);

    Route::post('/units/default-product-units', [UnitController::class, 'updateDefaultProductUnits'])->name('units.updateDefaultProductUnits');
    Route::resource('units', UnitController::class);

    Route::resource('ledger-discount', LedgerDiscountController::class)->only('edit', 'destroy', 'store', 'update');

    Route::post('check-mobile', [ContactController::class, 'checkMobile']);
    Route::get('/get-contact-due/{contact_id}', [ContactController::class, 'getContactDue']);
    Route::get('/contacts/payments/{contact_id}', [ContactController::class, 'getContactPayments']);
    Route::get('/contacts/map', [ContactController::class, 'contactMap']);
    Route::get('/contacts/update-status/{id}', [ContactController::class, 'updateStatus']);
    Route::get('/contacts/stock-report/{supplier_id}', [ContactController::class, 'getSupplierStockReport']);
    Route::get('/contacts/ledger', [ContactController::class, 'getLedger']);
    Route::post('/contacts/send-ledger', [ContactController::class, 'sendLedger']);
    Route::get('/contacts/import', [ContactController::class, 'getImportContacts'])->name('contacts.import');
    Route::post('/contacts/import', [ContactController::class, 'postImportContacts']);
    Route::post('/contacts/check-contacts-id', [ContactController::class, 'checkContactId']);
    Route::get('/contacts/customers', [ContactController::class, 'getCustomers']);
    Route::resource('contacts', ContactController::class);

    Route::get('taxonomies-ajax-index-page', [TaxonomyController::class, 'getTaxonomyIndexPage']);
    Route::resource('taxonomies', TaxonomyController::class);

    Route::resource('variation-templates', VariationTemplateController::class);

    Route::get('/products/download-excel', [ProductController::class, 'downloadExcel']);

    Route::get('/products/stock-history/{id}', [ProductController::class, 'productStockHistory']);
    Route::get('/delete-media/{media_id}', [ProductController::class, 'deleteMedia']);
    Route::post('/products/mass-deactivate', [ProductController::class, 'massDeactivate']);
    Route::get('/products/activate/{id}', [ProductController::class, 'activate']);
    Route::get('/products/view-product-group-price/{id}', [ProductController::class, 'viewGroupPrice']);
    Route::get('/products/add-selling-prices/{id}', [ProductController::class, 'addSellingPrices']);
    Route::post('/products/save-selling-prices', [ProductController::class, 'saveSellingPrices']);
    Route::post('/products/mass-delete', [ProductController::class, 'massDestroy']);
    Route::get('/products/view/{id}', [ProductController::class, 'view']);
    Route::get('/products/list', [ProductController::class, 'getProducts']);
    Route::get('/products/list-no-variation', [ProductController::class, 'getProductsWithoutVariations']);
    Route::post('/products/bulk-edit', [ProductController::class, 'bulkEdit']);
    Route::post('/products/bulk-update', [ProductController::class, 'bulkUpdate']);
    Route::post('/products/bulk-update-location', [ProductController::class, 'updateProductLocation']);
    Route::get('/products/get-product-to-edit/{product_id}', [ProductController::class, 'getProductToEdit']);

    Route::post('/products/get_sub_categories', [ProductController::class, 'getSubCategories']);
    Route::get('/products/get_sub_units', [ProductController::class, 'getSubUnits']);
    Route::post('/products/product_form-part', [ProductController::class, 'getProductVariationFormPart']);
    Route::post('/products/get_product_variation_row', [ProductController::class, 'getProductVariationRow']);
    Route::post('/products/get_variation_template', [ProductController::class, 'getVariationTemplate']);
    Route::get('/products/get_variation_value_row', [ProductController::class, 'getVariationValueRow']);
    Route::post('/products/check_product_sku', [ProductController::class, 'checkProductSku']);
    Route::post('/products/validate_variation_skus', [ProductController::class, 'validateVaritionSkus']); //validates multiple skus at once
    Route::get('/products/quick_add', [ProductController::class, 'quickAdd']);
    Route::post('/products/save_quick_product', [ProductController::class, 'saveQuickProduct']);
    Route::get('/products/get-combo-product-entry-row', [ProductController::class, 'getComboProductEntryRow']);
    Route::post('/products/toggle-woocommerce-sync', [ProductController::class, 'toggleWooCommerceSync']);

    Route::resource('products', ProductController::class);
    Route::get('/toggle-subscription/{id}', 'SellPosController@toggleRecurringInvoices');
    Route::post('/sells/pos/get-types-of-service-details', 'SellPosController@getTypesOfServiceDetails');
    Route::get('/sells/subscriptions', 'SellPosController@listSubscriptions');
    Route::get('/sells/duplicate/{id}', 'SellController@duplicateSell');
    Route::get('/sells/drafts', 'SellController@getDrafts');
    Route::get('/sells/convert-to-draft/{id}', 'SellPosController@convertToInvoice');
    Route::get('/sells/convert-to-proforma/{id}', 'SellPosController@convertToProforma');
    Route::get('/sells/quotations', 'SellController@getQuotations');
    Route::get('/sells/draft-dt', 'SellController@getDraftDatables');
    Route::resource('sells', 'SellController')->except(['show']);
    Route::get('/sells/copy-quotation/{id}', [SellPosController::class, 'copyQuotation']);

    Route::post('/import-purchase-products', [PurchaseController::class, 'importPurchaseProducts']);
    Route::post('/purchases/update-status', [PurchaseController::class, 'updateStatus']);
    Route::get('/purchases/get_products', [PurchaseController::class, 'getProducts']);
    Route::get('/purchases/get_suppliers', [PurchaseController::class, 'getSuppliers']);
    Route::post('/purchases/get_purchase_entry_row', [PurchaseController::class, 'getPurchaseEntryRow']);
    Route::post('/purchases/check_ref_number', [PurchaseController::class, 'checkRefNumber']);
    Route::resource('purchases', PurchaseController::class)->except(['show']);

    Route::get('/toggle-subscription/{id}', [SellPosController::class, 'toggleRecurringInvoices']);
    Route::post('/sells/pos/get-types-of-service-details', [SellPosController::class, 'getTypesOfServiceDetails']);
    Route::get('/sells/subscriptions', [SellPosController::class, 'listSubscriptions']);
    Route::get('/sells/duplicate/{id}', [SellController::class, 'duplicateSell']);
    Route::get('/sells/drafts', [SellController::class, 'getDrafts']);
    Route::get('/sells/convert-to-draft/{id}', [SellPosController::class, 'convertToInvoice']);
    Route::get('/sells/convert-to-proforma/{id}', [SellPosController::class, 'convertToProforma']);
    Route::get('/sells/quotations', [SellController::class, 'getQuotations']);
    Route::get('/sells/draft-dt', [SellController::class, 'getDraftDatables']);
    Route::resource('sells', SellController::class)->except(['show']);

    Route::get('/import-sales', [ImportSalesController::class, 'index']);
    Route::post('/import-sales/preview', [ImportSalesController::class, 'preview']);
    Route::post('/import-sales', [ImportSalesController::class, 'import']);
    Route::get('/revert-sale-import/{batch}', [ImportSalesController::class, 'revertSaleImport']);

    Route::get('/sells/pos/get_product_row/{variation_id}/{location_id}', [SellPosController::class, 'getProductRow']);
    Route::post('/sells/pos/get_payment_row', [SellPosController::class, 'getPaymentRow']);
    Route::post('/sells/pos/get-reward-details', [SellPosController::class, 'getRewardDetails']);
    Route::get('/sells/pos/get-recent-transactions', [SellPosController::class, 'getRecentTransactions']);
    Route::get('/sells/pos/get-product-suggestion', [SellPosController::class, 'getProductSuggestion']);
    Route::get('/sells/pos/get-featured-products/{location_id}', [SellPosController::class, 'getFeaturedProducts']);
    Route::get('/reset-mapping', [SellController::class, 'resetMapping']);

    Route::resource('pos', SellPosController::class);

    Route::resource('roles', RoleController::class);

    Route::resource('users', ManageUserController::class);
    Route::post('/users/{id}/toggle-otp', [ManageUserController::class, 'toggleOtpLoginEnabled'])
        ->name('users.toggle_otp');

    Route::resource('group-taxes', GroupTaxController::class);

    Route::get('/barcodes/set_default/{id}', [BarcodeController::class, 'setDefault']);
    Route::resource('barcodes', BarcodeController::class);

    //Invoice schemes..
    Route::get('/invoice-schemes/set_default/{id}', [InvoiceSchemeController::class, 'setDefault']);
    Route::resource('invoice-schemes', InvoiceSchemeController::class);

    //Print Labels
    Route::get('/labels/show', [LabelsController::class, 'show']);
    Route::get('/labels/add-product-row', [LabelsController::class, 'addProductRow']);
    Route::get('/labels/preview', [LabelsController::class, 'preview']);

    //Reports...
    Route::get('/reports/gst-purchase-report', [ReportController::class, 'gstPurchaseReport']);
    Route::get('/reports/gst-sales-report', [ReportController::class, 'gstSalesReport']);
    Route::get('/reports/get-stock-by-sell-price', [ReportController::class, 'getStockBySellingPrice']);
    Route::get('/reports/purchase-report', [ReportController::class, 'purchaseReport']);
    Route::get('/reports/sale-report', [ReportController::class, 'saleReport']);
    Route::get('/reports/service-staff-report', [ReportController::class, 'getServiceStaffReport']);
    Route::get('/reports/service-staff-line-orders', [ReportController::class, 'serviceStaffLineOrders']);
    Route::get('/reports/table-report', [ReportController::class, 'getTableReport']);
    Route::get('/reports/profit-loss', [ReportController::class, 'getProfitLoss']);
    Route::get('/reports/get-opening-stock', [ReportController::class, 'getOpeningStock']);
    Route::get('/reports/purchase-sell', [ReportController::class, 'getPurchaseSell']);
    Route::get('/reports/customer-supplier', [ReportController::class, 'getCustomerSuppliers']);
    Route::get('/reports/stock-valuation-report', [ReportController::class, 'getStockValuationReport']);
    Route::get('/reports/stock-costing-layer-gap-report', [ReportController::class, 'getStockCostingLayerGapReport']);
    Route::get('/reports/stock-report', [ReportController::class, 'getStockReport']);
    Route::get('/reports/stock-details', [ReportController::class, 'getStockDetails']);
    Route::get('/reports/tax-report', [ReportController::class, 'getTaxReport']);
    Route::get('/reports/tax-details', [ReportController::class, 'getTaxDetails']);
    Route::get('/reports/trending-products', [ReportController::class, 'getTrendingProducts']);
    Route::get('/reports/expense-report', [ReportController::class, 'getExpenseReport']);
    Route::get('/reports/stock-adjustment-report', [ReportController::class, 'getStockAdjustmentReport']);
    Route::get('/reports/register-report', [ReportController::class, 'getRegisterReport']);
    Route::get('/reports/sales-representative-report', [ReportController::class, 'getSalesRepresentativeReport']);
    Route::get('/reports/sales-representative-total-expense', [ReportController::class, 'getSalesRepresentativeTotalExpense']);
    Route::get('/reports/sales-representative-total-sell', [ReportController::class, 'getSalesRepresentativeTotalSell']);
    Route::get('/reports/sales-representative-total-commission', [ReportController::class, 'getSalesRepresentativeTotalCommission']);
    Route::get('/reports/stock-expiry', [ReportController::class, 'getStockExpiryReport']);
    Route::get('/reports/stock-expiry-edit-modal/{purchase_line_id}', [ReportController::class, 'getStockExpiryReportEditModal']);
    Route::post('/reports/stock-expiry-update', [ReportController::class, 'updateStockExpiryReport'])->name('updateStockExpiryReport');
    Route::get('/reports/customer-group', [ReportController::class, 'getCustomerGroup']);
    Route::get('/reports/product-purchase-report', [ReportController::class, 'getproductPurchaseReport']);
    Route::get('/reports/product-sell-grouped-by', [ReportController::class, 'productSellReportBy']);
    Route::get('/reports/product-sell-report', [ReportController::class, 'getproductSellReport']);
    Route::get('/reports/product-sell-report-with-purchase', [ReportController::class, 'getproductSellReportWithPurchase']);
    Route::get('/reports/product-sell-grouped-report', [ReportController::class, 'getproductSellGroupedReport']);
    Route::get('/reports/lot-report', [ReportController::class, 'getLotReport']);
    Route::get('/reports/purchase-payment-report', [ReportController::class, 'purchasePaymentReport']);
    Route::get('/reports/sell-payment-report', [ReportController::class, 'sellPaymentReport']);
    Route::get('/reports/product-stock-details', [ReportController::class, 'productStockDetails']);
    Route::get('/reports/adjust-product-stock', [ReportController::class, 'adjustProductStock']);
    Route::get('/reports/get-profit/{by?}', [ReportController::class, 'getProfit']);
    Route::get('/reports/items-report', [ReportController::class, 'itemsReport']);
    Route::get('/reports/get-stock-value', [ReportController::class, 'getStockValue']);

    Route::get('business-location/activate-deactivate/{location_id}', [BusinessLocationController::class, 'activateDeactivateLocation']);

    //Business Location Settings...
    Route::prefix('business-location/{location_id}')->name('location.')->group(function () {
        Route::get('settings', [LocationSettingsController::class, 'index'])->name('settings');
        Route::post('settings', [LocationSettingsController::class, 'updateSettings'])->name('settings_update');
    });

    //Business Locations...
    Route::post('business-location/check-location-id', [BusinessLocationController::class, 'checkLocationId']);
    Route::resource('business-location', BusinessLocationController::class);

    // Legacy underscore URL compatibility (e.g. links generated by older accounting journal entry views)
    Route::get('business_location/{id}/show', function ($id) {
        return redirect()->route('business-location.index');
    });
    Route::get('user/{id}/show', function ($id) {
        return redirect()->to('users/' . $id);
    });

    //Invoice layouts..
    Route::resource('invoice-layouts', InvoiceLayoutController::class);

    Route::post('get-expense-sub-categories', [ExpenseCategoryController::class, 'getSubCategories']);

    //Expense Categories...
    Route::resource('expense-categories', ExpenseCategoryController::class);

    //Expenses...
    Route::resource('expenses', ExpenseController::class);

    //Transaction payments...
    // Route::get('/payments/opening-balance/{contact_id}', 'TransactionPaymentController@getOpeningBalancePayments');
    Route::get('/payments/show-child-payments/{payment_id}', [TransactionPaymentController::class, 'showChildPayments']);
    Route::get('/payments/view-payment/{payment_id}', [TransactionPaymentController::class, 'viewPayment']);
    Route::get('/payments/add_payment/{transaction_id}', [TransactionPaymentController::class, 'addPayment']);
    Route::get('/payments/pay-contact-due/{contact_id}', [TransactionPaymentController::class, 'getPayContactDue']);
    Route::post('/payments/pay-contact-due', [TransactionPaymentController::class, 'postPayContactDue']);
    Route::resource('payments', TransactionPaymentController::class);

    //Printers...
    Route::resource('printers', PrinterController::class);

    Route::get('/stock-adjustments/remove-expired-stock/{purchase_line_id}', [StockAdjustmentController::class, 'removeExpiredStock']);
    Route::post('/stock-adjustments/get_product_row', [StockAdjustmentController::class, 'getProductRow']);
    //used by stocktake module
    Route::resource('stock-adjustments', StockAdjustmentController::class)->names('stock-adjustment');

    Route::get('/cash-register/register-details', [CashRegisterController::class, 'getRegisterDetails']);
    Route::get('/cash-register/close-register/{id?}', [CashRegisterController::class, 'getCloseRegister']);
    Route::post('/cash-register/close-register', [CashRegisterController::class, 'postCloseRegister']);
    Route::resource('cash-register', CashRegisterController::class);

    //Import products
    Route::get('/import-products', [ImportProductsController::class, 'index']);
    Route::post('/import-products/store', [ImportProductsController::class, 'store']);

    //Sales Commission Agent
    Route::resource('sales-commission-agents', SalesCommissionAgentController::class);

    //Stock Transfer
    Route::get('stock-transfers/print/{id}', [StockTransferController::class, 'printInvoice']);
    Route::post('stock-transfers/update-status/{id}', [StockTransferController::class, 'updateStatus']);
    Route::resource('stock-transfers', StockTransferController::class);

    Route::get('/opening-stock/add/{product_id}', [OpeningStockController::class, 'add']);
    Route::post('/opening-stock/save', [OpeningStockController::class, 'save']);

    //Customer Groups
    Route::resource('customer-group', CustomerGroupController::class);

    //Import opening stock
    Route::get('/import-opening-stock', [ImportOpeningStockController::class, 'index']);
    Route::post('/import-opening-stock/store', [ImportOpeningStockController::class, 'store']);

    //Sell return
    Route::get('validate-invoice-to-return/{invoice_no}', [SellReturnController::class, 'validateInvoiceToReturn']);
    // service staff replacement
    Route::get('validate-invoice-to-service-staff-replacement/{invoice_no}', [SellPosController::class, 'validateInvoiceToServiceStaffReplacement']);
    Route::put('change-service-staff/{id}', [SellPosController::class, 'change_service_staff'])->name('change_service_staff');

    Route::resource('sell-return', SellReturnController::class);
    Route::get('sell-return/get-product-row', [SellReturnController::class, 'getProductRow']);
    Route::get('/sell-return/print/{id}', [SellReturnController::class, 'printInvoice']);
    Route::get('/sell-return/add/{id}', [SellReturnController::class, 'add']);

    //Backup
    Route::get('backup/download/{file_name}', [BackUpController::class, 'download']);
    Route::get('backup/{id}/delete', [BackUpController::class, 'delete'])->name('delete_backup');
    Route::resource('backup', BackUpController::class)->only('index', 'create', 'store');

    Route::get('selling-price-group/activate-deactivate/{id}', [SellingPriceGroupController::class, 'activateDeactivate']);
    Route::get('update-product-price', [SellingPriceGroupController::class, 'updateProductPrice'])->name('update-product-price');
    Route::get('export-product-price', [SellingPriceGroupController::class, 'export']);
    Route::post('import-product-price', [SellingPriceGroupController::class, 'import']);

    Route::resource('selling-price-group', SellingPriceGroupController::class);

    Route::resource('notification-templates', NotificationTemplateController::class)->only(['index', 'store']);
    Route::get('notification/get-template/{transaction_id}/{template_for}', [NotificationController::class, 'getTemplate']);
    Route::post('notification/send', [NotificationController::class, 'send']);

    Route::post('/purchase-return/update', [CombinedPurchaseReturnController::class, 'update']);
    Route::get('/purchase-return/edit/{id}', [CombinedPurchaseReturnController::class, 'edit']);
    Route::post('/purchase-return/save', [CombinedPurchaseReturnController::class, 'save']);
    Route::post('/purchase-return/get_product_row', [CombinedPurchaseReturnController::class, 'getProductRow']);
    Route::get('/purchase-return/create', [CombinedPurchaseReturnController::class, 'create']);
    Route::get('/purchase-return/add/{id}', [PurchaseReturnController::class, 'add']);
    Route::resource('/purchase-return', PurchaseReturnController::class)->except('create');

    Route::get('/discount/activate/{id}', [DiscountController::class, 'activate']);
    Route::post('/discount/mass-deactivate', [DiscountController::class, 'massDeactivate']);
    Route::resource('discount', DiscountController::class);

    Route::prefix('account')->group(function () {
        Route::resource('/account', AccountController::class);
        Route::post('/backfill-default-accounts', [AccountController::class, 'backfillDefaultAccounts'])
            ->name('account.backfill_default_accounts');
        Route::get('/fund-transfer/{id}', [AccountController::class, 'getFundTransfer']);
        Route::post('/fund-transfer', [AccountController::class, 'postFundTransfer']);
        Route::get('/deposit/{id}', [AccountController::class, 'getDeposit']);
        Route::post('/deposit', [AccountController::class, 'postDeposit']);
        Route::get('/close/{id}', [AccountController::class, 'close']);
        Route::get('/activate/{id}', [AccountController::class, 'activate']);
        Route::get('/delete-account-transaction/{id}', [AccountController::class, 'destroyAccountTransaction']);
        Route::get('/edit-account-transaction/{id}', [AccountController::class, 'editAccountTransaction']);
        Route::post('/update-account-transaction/{id}', [AccountController::class, 'updateAccountTransaction']);
        Route::get('/get-account-balance/{id}', [AccountController::class, 'getAccountBalance']);
        Route::get('/dashboard', [AccountReportsController::class, 'dashboard']);
        Route::get('/balance-sheet', [AccountReportsController::class, 'balanceSheet']);
        Route::get('/trial-balance', [AccountReportsController::class, 'trialBalance']);
        Route::get('/chart-of-accounts', [AccountReportsController::class, 'chartOfAccounts']);
        Route::get('/general-ledger', [AccountReportsController::class, 'generalLedger']);
        Route::get('/journal-entry', [AccountReportsController::class, 'journalEntry']);
        Route::post('/journal-entry', [AccountReportsController::class, 'storeJournalEntry']);
        Route::get('/profit-loss', [ReportController::class, 'getProfitLoss']);
        Route::get('/payment-account-report', [AccountReportsController::class, 'paymentAccountReport']);
        Route::get('/bank-reconciliation', [AccountReportsController::class, 'showBankReconciliation']);
        Route::get('/bank-reconciliation/template', [AccountReportsController::class, 'downloadBankReconciliationTemplate']);
        Route::post('/bank-reconciliation/upload', [AccountReportsController::class, 'uploadBankReconciliation']);
        Route::post('/bank-reconciliation/{id}/finalize', [AccountReportsController::class, 'finalizeBankReconciliation']);
        Route::post('/bank-reconciliation/{id}/undo', [AccountReportsController::class, 'undoBankReconciliation']);
        Route::get('/bank-reconciliation/{id}/details', [AccountReportsController::class, 'bankReconciliationDetails']);
        Route::get('/bank-reconciliation/{id}/audit-logs', [AccountReportsController::class, 'bankReconciliationAuditLogs']);
        Route::post('/bank-reconciliation/{runId}/lines/{lineId}/manual-match', [AccountReportsController::class, 'manualMatchBankReconciliationLine']);
        Route::post('/bank-reconciliation/{runId}/lines/{lineId}/manual-unmatch', [AccountReportsController::class, 'manualUnmatchBankReconciliationLine']);
        Route::post('/bank-reconciliation/{runId}/lines/{lineId}/create-payment', [AccountReportsController::class, 'createMissingPaymentFromBankReconciliationLine']);
        Route::get('/bank-reconciliation/{id}/export', [AccountReportsController::class, 'exportBankReconciliationPackage']);
        Route::get('/bank-reconciliation/{id}/export/pdf', [AccountReportsController::class, 'exportBankReconciliationPdf']);
        Route::get('/bank-reconciliation/{id}/export/excel', [AccountReportsController::class, 'exportBankReconciliationExcel']);
        Route::get('/link-account/{id}', [AccountReportsController::class, 'getLinkAccount']);
        Route::post('/link-account', [AccountReportsController::class, 'postLinkAccount']);
        Route::get('/cash-flow', [AccountController::class, 'cashFlow']);
    });

    Route::resource('account-types', AccountTypeController::class);

    //Restaurant module
    Route::prefix('modules')->group(function () {
        Route::resource('tables', Restaurant\TableController::class);
        Route::resource('modifiers', Restaurant\ModifierSetsController::class);

        //Map modifier to products
        Route::get('/product-modifiers/{id}/edit', [Restaurant\ProductModifierSetController::class, 'edit']);
        Route::post('/product-modifiers/{id}/update', [Restaurant\ProductModifierSetController::class, 'update']);
        Route::get('/product-modifiers/product-row/{product_id}', [Restaurant\ProductModifierSetController::class, 'product_row']);

        Route::get('/add-selected-modifiers', [Restaurant\ProductModifierSetController::class, 'add_selected_modifiers']);

        Route::get('/kitchen', [Restaurant\KitchenController::class, 'index']);
        Route::get('/kitchen/mark-as-cooked/{id}', [Restaurant\KitchenController::class, 'markAsCooked']);
        Route::post('/refresh-orders-list', [Restaurant\KitchenController::class, 'refreshOrdersList']);
        Route::post('/refresh-line-orders-list', [Restaurant\KitchenController::class, 'refreshLineOrdersList']);

        Route::get('/orders', [Restaurant\OrderController::class, 'index']);
        Route::get('/orders/mark-as-served/{id}', [Restaurant\OrderController::class, 'markAsServed']);
        Route::get('/data/get-pos-details', [Restaurant\DataController::class, 'getPosDetails']);
        Route::get('/data/check-staff-pin', [Restaurant\DataController::class, 'checkStaffPin']);
        Route::get('/orders/mark-line-order-as-served/{id}', [Restaurant\OrderController::class, 'markLineOrderAsServed']);
        Route::get('/print-line-order', [Restaurant\OrderController::class, 'printLineOrder']);
    });

    Route::get('bookings/get-todays-bookings', [Restaurant\BookingController::class, 'getTodaysBookings']);
    Route::resource('bookings', Restaurant\BookingController::class);

    Route::resource('types-of-service', TypesOfServiceController::class);
    Route::get('sells/edit-shipping/{id}', [SellController::class, 'editShipping']);
    Route::put('sells/update-shipping/{id}', [SellController::class, 'updateShipping']);
    Route::get('shipments', [SellController::class, 'shipments']);

    Route::middleware('superadmin')->group(function () {
        Route::post('upload-module', [Install\ModulesController::class, 'uploadModule']);
        Route::delete('manage-modules/destroy/{module_name}', [Install\ModulesController::class, 'destroy']);
        Route::get('manage-modules/install/{module_name}', [Install\ModulesController::class, 'installByModuleName'])->name('manage-modules.install');
        Route::get('manage-modules/uninstall/{module_name}', [Install\ModulesController::class, 'uninstallByModuleName'])->name('manage-modules.uninstall');
        Route::get('manage-modules/update/{module_name}', [Install\ModulesController::class, 'updateByModuleName'])->name('manage-modules.update-by-name');
        Route::resource('manage-modules', Install\ModulesController::class)
            ->only(['index', 'update']);
        Route::get('regenerate', [Install\ModulesController::class, 'regenerate']);

        // Module install / uninstall routes (must be in the main route group for correct middleware handling)
        Route::get('hrm/install', [\Modules\Hrm\Http\Controllers\InstallController::class, 'index'])->name('hrm.install');
        Route::get('hrm/install/update', [\Modules\Hrm\Http\Controllers\InstallController::class, 'update'])->name('hrm.update');
        Route::get('hrm/uninstall', [\Modules\Hrm\Http\Controllers\InstallController::class, 'uninstall'])->name('hrm.uninstall');
        Route::get('stocktake/install', [\Modules\Stocktake\Http\Controllers\InstallController::class, 'index'])->name('stocktake.install');
        Route::get('stocktake/install/update', [\Modules\Stocktake\Http\Controllers\InstallController::class, 'update'])->name('stocktake.update');
        Route::get('stocktake/uninstall', [\Modules\Stocktake\Http\Controllers\InstallController::class, 'uninstall'])->name('stocktake.uninstall');
        Route::get('essentials/install', [\Modules\Essentials\Http\Controllers\InstallController::class, 'index'])->name('essentials.install');
        Route::get('essentials/install/update', [\Modules\Essentials\Http\Controllers\InstallController::class, 'update'])->name('essentials.update');
        Route::get('essentials/uninstall', [\Modules\Essentials\Http\Controllers\InstallController::class, 'uninstall'])->name('essentials.uninstall');
        Route::get('superadmin/install', [\Modules\Superadmin\Http\Controllers\InstallController::class, 'index'])->name('superadmin.install');
        Route::get('superadmin/install/update', [\Modules\Superadmin\Http\Controllers\InstallController::class, 'update'])->name('superadmin.update');
        Route::get('superadmin/uninstall', [\Modules\Superadmin\Http\Controllers\InstallController::class, 'uninstall'])->name('superadmin.uninstall');
        Route::get('woocommerce/install', [\Modules\Woocommerce\Http\Controllers\InstallController::class, 'index'])->name('woocommerce.install');
        Route::get('woocommerce/install/update', [\Modules\Woocommerce\Http\Controllers\InstallController::class, 'update'])->name('woocommerce.update');
        Route::get('woocommerce/uninstall', [\Modules\Woocommerce\Http\Controllers\InstallController::class, 'uninstall'])->name('woocommerce.uninstall');
        Route::get('manufacturing/install', [\Modules\Manufacturing\Http\Controllers\InstallController::class, 'index'])->name('manufacturing.install');
        Route::get('manufacturing/install/update', [\Modules\Manufacturing\Http\Controllers\InstallController::class, 'update'])->name('manufacturing.update');
        Route::get('manufacturing/uninstall', [\Modules\Manufacturing\Http\Controllers\InstallController::class, 'uninstall'])->name('manufacturing.uninstall');
        Route::get('project/install', [\Modules\Project\Http\Controllers\InstallController::class, 'index'])->name('project.install');
        Route::get('project/install/update', [\Modules\Project\Http\Controllers\InstallController::class, 'update'])->name('project.update');
        Route::get('project/uninstall', [\Modules\Project\Http\Controllers\InstallController::class, 'uninstall'])->name('project.uninstall');
        Route::get('repair/install', [\Modules\Repair\Http\Controllers\InstallController::class, 'index'])->name('repair.install');
        Route::get('repair/install/update', [\Modules\Repair\Http\Controllers\InstallController::class, 'update'])->name('repair.update');
        Route::get('repair/uninstall', [\Modules\Repair\Http\Controllers\InstallController::class, 'uninstall'])->name('repair.uninstall');
        Route::get('crm/install', [\Modules\Crm\Http\Controllers\InstallController::class, 'index'])->name('crm.install');
        Route::get('crm/install/update', [\Modules\Crm\Http\Controllers\InstallController::class, 'update'])->name('crm.update');
        Route::get('crm/uninstall', [\Modules\Crm\Http\Controllers\InstallController::class, 'uninstall'])->name('crm.uninstall');
        Route::get('productcatalogue/install', [\Modules\ProductCatalogue\Http\Controllers\InstallController::class, 'index'])->name('productcatalogue.install');
        Route::get('productcatalogue/install/update', [\Modules\ProductCatalogue\Http\Controllers\InstallController::class, 'update'])->name('productcatalogue.update');
        Route::get('productcatalogue/uninstall', [\Modules\ProductCatalogue\Http\Controllers\InstallController::class, 'uninstall'])->name('productcatalogue.uninstall');
        Route::get('accounting/install', [\Modules\Accounting\Http\Controllers\InstallController::class, 'index'])->name('accounting.install');
        Route::get('accounting/install/update', [\Modules\Accounting\Http\Controllers\InstallController::class, 'update'])->name('accounting.update');
        Route::get('accounting/uninstall', [\Modules\Accounting\Http\Controllers\InstallController::class, 'uninstall'])->name('accounting.uninstall');
        Route::get('aiassistance/install', [\Modules\AiAssistance\Http\Controllers\InstallController::class, 'index'])->name('aiassistance.install');
        Route::get('aiassistance/install/update', [\Modules\AiAssistance\Http\Controllers\InstallController::class, 'update'])->name('aiassistance.update');
        Route::get('aiassistance/uninstall', [\Modules\AiAssistance\Http\Controllers\InstallController::class, 'uninstall'])->name('aiassistance.uninstall');
        Route::get('assetmanagement/install', [\Modules\AssetManagement\Http\Controllers\InstallController::class, 'index'])->name('assetmanagement.install');
        Route::get('assetmanagement/install/update', [\Modules\AssetManagement\Http\Controllers\InstallController::class, 'update'])->name('assetmanagement.update');
        Route::get('assetmanagement/uninstall', [\Modules\AssetManagement\Http\Controllers\InstallController::class, 'uninstall'])->name('assetmanagement.uninstall');
        Route::get('cms/install', [\Modules\Cms\Http\Controllers\InstallController::class, 'index'])->name('cms.install');
        Route::get('cms/install/update', [\Modules\Cms\Http\Controllers\InstallController::class, 'update'])->name('cms.update');
        Route::get('cms/uninstall', [\Modules\Cms\Http\Controllers\InstallController::class, 'uninstall'])->name('cms.uninstall');
        Route::get('connector/install', [\Modules\Connector\Http\Controllers\InstallController::class, 'index'])->name('connector.install');
        Route::get('connector/install/update', [\Modules\Connector\Http\Controllers\InstallController::class, 'update'])->name('connector.update');
        Route::get('connector/uninstall', [\Modules\Connector\Http\Controllers\InstallController::class, 'uninstall'])->name('connector.uninstall');
        Route::get('spreadsheet/install', [\Modules\Spreadsheet\Http\Controllers\InstallController::class, 'index'])->name('spreadsheet.install');
        Route::get('spreadsheet/install/update', [\Modules\Spreadsheet\Http\Controllers\InstallController::class, 'update'])->name('spreadsheet.update');
        Route::get('spreadsheet/uninstall', [\Modules\Spreadsheet\Http\Controllers\InstallController::class, 'uninstall'])->name('spreadsheet.uninstall');
    });

    // Essentials module stub routes
    Route::get('essentials/todos/create', [\Modules\Essentials\Http\Controllers\ToDoController::class, 'create'])->name('essentials.todos.create');
    Route::post('essentials/todos', [\Modules\Essentials\Http\Controllers\ToDoController::class, 'store'])->name('essentials.todos.store');
    Route::get('essentials/todos', [\Modules\Essentials\Http\Controllers\ToDoController::class, 'index'])->name('essentials.todos.index');
    Route::get('essentials/todos/{id}/edit', [\Modules\Essentials\Http\Controllers\ToDoController::class, 'edit'])->name('essentials.todos.edit');
    Route::put('essentials/todos/{id}', [\Modules\Essentials\Http\Controllers\ToDoController::class, 'update'])->name('essentials.todos.update');
    Route::delete('essentials/todos/{id}', [\Modules\Essentials\Http\Controllers\ToDoController::class, 'destroy'])->name('essentials.todos.destroy');

    // Crm module stub routes
    Route::get('crm/dashboard', [\Modules\Crm\Http\Controllers\DashboardController::class, 'index'])->name('crm.dashboard');

    // Accounting module stub route (keeps dashboard reachable if module routes are not bootstrapped yet)
    Route::get('accounting/dashboard', AccountingDashboardStubController::class)->name('accounting.dashboard.stub');

    // Repair module stub routes
    Route::get('repair', [\Modules\Repair\Http\Controllers\RepairController::class, 'index']);
    Route::get('repair/{id}/print-label', [\Modules\Repair\Http\Controllers\RepairController::class, 'printLabel'])->name('repair.print_label');
    Route::get('repair-status', [\Modules\Repair\Http\Controllers\CustomerRepairStatusController::class, 'index'])->name('repair-status');

    // Superadmin module stub routes
    Route::get('subscription', [\Modules\Superadmin\Http\Controllers\SubscriptionController::class, 'index'])->name('superadmin.subscription.index');
    Route::get('pricing', [\Modules\Superadmin\Http\Controllers\PricingController::class, 'index'])->name('pricing');
    Route::get('pages/{slug}', [\Modules\Superadmin\Http\Controllers\PageController::class, 'showPage']);

    // Superadmin: switch active business context
    Route::get('superadmin/switch-business/{id}', function ($id) {
        $user = auth()->user();
        if ($user->role !== 'admin') {
            abort(403);
        }
        $business = \App\Business::findOrFail($id);
        // Flush business-related session keys so SetSessionData rebuilds them
        session()->forget(['business', 'currency', 'financial_year', 'user', 'superadmin_active_business_id']);
        session(['superadmin_active_business_id' => $business->id]);
        return redirect('/home')->with('status', ['success' => true, 'msg' => 'Switched to ' . $business->name]);
    })->name('superadmin.switch-business');

    Route::resource('warranties', WarrantyController::class);

    Route::resource('dashboard-configurator', DashboardConfiguratorController::class)
    ->only(['edit', 'update']);

    Route::get('view-media/{model_id}', [SellController::class, 'viewMedia']);

    //common controller for document & note
    Route::get('get-document-note-page', [DocumentAndNoteController::class, 'getDocAndNoteIndexPage']);
    Route::post('post-document-upload', [DocumentAndNoteController::class, 'postMedia']);
    Route::resource('note-documents', DocumentAndNoteController::class);
    Route::resource('purchase-order', PurchaseOrderController::class);
    Route::get('get-purchase-orders/{contact_id}', [PurchaseOrderController::class, 'getPurchaseOrders']);
    Route::get('get-purchase-order-lines/{purchase_order_id}', [PurchaseController::class, 'getPurchaseOrderLines']);
    Route::get('edit-purchase-orders/{id}/status', [PurchaseOrderController::class, 'getEditPurchaseOrderStatus']);
    Route::put('update-purchase-orders/{id}/status', [PurchaseOrderController::class, 'postEditPurchaseOrderStatus']);
    Route::resource('sales-order', SalesOrderController::class)->only(['index']);
    Route::get('get-sales-orders/{customer_id}', [SalesOrderController::class, 'getSalesOrders']);
    Route::get('get-sales-order-lines', [SellPosController::class, 'getSalesOrderLines']);
    Route::get('edit-sales-orders/{id}/status', [SalesOrderController::class, 'getEditSalesOrderStatus']);
    Route::put('update-sales-orders/{id}/status', [SalesOrderController::class, 'postEditSalesOrderStatus']);
    Route::get('reports/activity-log', [ReportController::class, 'activityLog']);
    Route::get('reports/active-user-sessions', [ReportController::class, 'activeUserSessions']);
    Route::get('user-location/{latlng}', [HomeController::class, 'getUserLocation']);
});

// ============================
// Routes that don't require subscription (accessible to all authenticated users)
// ============================
Route::middleware(['setData', 'auth', 'SetSessionData', 'language', 'timezone', 'AdminSidebarMenu', 'CheckUserLogin'])->group(function () {
    // These routes are accessible without active subscription
    // Add any routes that should be accessible even without subscription here
});

// Route::middleware(['EcomApi'])->prefix('api/ecom')->group(function () {
//     Route::get('products/{id?}', [ProductController::class, 'getProductsApi']);
//     Route::get('categories', [CategoryController::class, 'getCategoriesApi']);
//     Route::get('brands', [BrandController::class, 'getBrandsApi']);
//     Route::post('customers', [ContactController::class, 'postCustomersApi']);
//     Route::get('settings', [BusinessController::class, 'getEcomSettings']);
//     Route::get('variations', [ProductController::class, 'getVariationsApi']);
//     Route::post('orders', [SellPosController::class, 'placeOrdersApi']);
// });

//common route
    Route::middleware(['auth'])->group(function () {
    // Keep a GET logout route for convenience but avoid naming it 'logout'
    // because the auth scaffolding registers a POST route named 'logout'.
    // Naming both routes the same causes route:cache serialization errors.
    Route::get('/logout', [App\Http\Controllers\Auth\LoginController::class, 'logout']);
});

Route::middleware(['setData', 'auth', 'SetSessionData', 'language', 'timezone'])->group(function () {
    Route::get('/load-more-notifications', [HomeController::class, 'loadMoreNotifications']);
    Route::get('/get-total-unread', [HomeController::class, 'getTotalUnreadNotifications']);
    Route::get('/notifications', [HomeController::class, 'clearAllNotifications']);
    Route::get('/notifications/mark-all-read', [HomeController::class, 'markAllNotificationsAsRead']);
    Route::get('/notifications/clear-all', [HomeController::class, 'clearAllNotifications']);
    Route::get('/notifications/delete/{id}', [HomeController::class, 'deleteNotification']);
    Route::get('/notifications/{id}', [HomeController::class, 'deleteNotification']);
    Route::post('/notifications/mark-all-read', [HomeController::class, 'markAllNotificationsAsRead']);
    Route::delete('/notifications/{id}', [HomeController::class, 'deleteNotification']);
    Route::delete('/notifications', [HomeController::class, 'clearAllNotifications']);
    Route::get('/purchases/print/{id}', [PurchaseController::class, 'printInvoice'])->name('purchases.print');
    Route::get('/purchases/{id}', [PurchaseController::class, 'show'])->name('purchases.show');
    Route::get('/download-purchase-order/{id}/pdf', [PurchaseOrderController::class, 'downloadPdf'])->name('purchaseOrder.downloadPdf');
    Route::get('/sells/{id}', [SellController::class, 'show']);
    Route::get('/sells/{transaction_id}/print', [SellPosController::class, 'printInvoice'])->name('sell.printInvoice');
    Route::get('/download-sells/{transaction_id}/pdf', [SellPosController::class, 'downloadPdf'])->name('sell.downloadPdf');
    Route::get('/download-quotation/{id}/pdf', [SellPosController::class, 'downloadQuotationPdf'])
        ->name('quotation.downloadPdf');
    Route::get('/download-packing-list/{id}/pdf', [SellPosController::class, 'downloadPackingListPdf'])
        ->name('packing.downloadPdf');
    Route::get('/sells/invoice-url/{id}', [SellPosController::class, 'showInvoiceUrl']);
    Route::get('/show-notification/{id}', [HomeController::class, 'showNotification']);
  
    });
// ==========================
// StockTake Routes 
// ==========================
Route::group([
    'middleware' => ['auth', 'language', 'timezone', 'AdminSidebarMenu'],
    'prefix' => 'stocktakes',
    'as' => 'stocktakes.'
], function () {

    // === Standard CRUD Routes (static first) ===
    Route::get('/', [StocktakeController::class, 'index'])->name('index');
    Route::get('/create', [StocktakeController::class, 'create'])->name('create');
    Route::post('/', [StocktakeController::class, 'store'])->name('store');

    // === Utility & Product-related Routes ===
    Route::get('/get-location-stats', [StocktakeController::class, 'getLocationStats'])->name('getLocationStats');
    Route::get('/get-products', [StocktakeController::class, 'getProductsForStocktake'])->name('get-products');
    Route::get('/search-products', [StocktakeController::class, 'searchProducts'])->name('search-products');
    Route::get('/product/{productId}/history', [StocktakeController::class, 'productHistory'])->name('product_history');

    // === Navigation & Reporting Routes ===  
    Route::get('/history', [StocktakeController::class, 'history'])->name('history');
    Route::get('/data/history', [StocktakeController::class, 'getHistoryData'])->name('data.history');
    Route::get('/export/history', [StocktakeController::class, 'exportHistory'])->name('export.history');
    Route::get('/variance-report', [StocktakeController::class, 'varianceReport'])->name('variance_report');
    Route::get('/export-variance-report', [StocktakeController::class, 'quickExportVarianceReport'])->name('quickExportVarianceReport');
    Route::get('/{id}/export', [StocktakeController::class, 'exportStocktake'])->name('export');
    Route::get('/stats/performance', [StocktakeController::class, 'getPerformanceMetrics'])->name('stats.performance');

    // === Debug & Maintenance Routes (local environment only) ===
    if (app()->environment('local')) {
        Route::get('/debug/{stocktake_id}', [StocktakeController::class, 'debugStockCalculation'])->name('debug');
        Route::get('/debug-product/{product_id}', [StocktakeController::class, 'debugProductStock'])->name('debug-product');
        Route::get('/fix-negative-stock', [StocktakeController::class, 'fixNegativeStockHistory'])->name('fix-negative-stock');
        Route::get('/stock-summary/{product_id}', [StocktakeController::class, 'getStockSummary'])->name('stock-summary');
        Route::get('/debug/routes-permissions', [StocktakeController::class, 'debugRoutesAndPermissions'])->name('debug.routes_permissions');
    }

    // === Dynamic Routes (MUST stay last) ===
    Route::get('/{id}', [StocktakeController::class, 'show'])->name('show');
    Route::get('/{id}/edit', [StocktakeController::class, 'edit'])->name('edit');
    Route::put('/{id}', [StocktakeController::class, 'update'])->name('update');
    Route::post('/{id}/complete', [StocktakeController::class, 'complete'])->name('complete');
    Route::post('/{id}/cancel', [StocktakeController::class, 'cancel'])->name('cancel');
    Route::delete('/{id}', [StocktakeController::class, 'destroy'])->name('destroy');
});
// ==========================
// StockTake Routes 
// ==========================
// ============================
// Subscription routes (for users) - accessible without active subscription
// ============================
Route::post('/subscription/callback', [SubscriptionController::class, 'paymentCallback'])
    ->name('subscription.callback');

Route::middleware(['auth', 'AdminSidebarMenu'])->group(function () {
    // Plans & Payment
    Route::get('/subscription/plans', [SubscriptionController::class, 'showPlans'])->name('subscription.plans');
    Route::post('/subscription/process-payment', [SubscriptionController::class, 'processPayment'])->name('subscription.processPayment');
    Route::post('/subscription/renew', [SubscriptionController::class, 'renew'])->name('subscription.renew');
    Route::post('/subscription/stk-push', [SubscriptionController::class, 'stkPush'])->name('subscription.stkPush');
    Route::post('/subscription/manual-status-check', [SubscriptionController::class, 'manualStatusCheck'])->name('subscription.manualStatusCheck');
    Route::post('/subscription/quick-activate', [SubscriptionController::class, 'quickActivate'])->name('subscription.quickActivate');
    Route::post('/subscription/activate-user', [SubscriptionController::class, 'activateUser'])->name('subscription.activateUser');

    // Poll subscription payment status
    Route::get('/subscription/check-status/{checkoutRequestId}', [SubscriptionController::class, 'checkPaymentStatus'])->name('subscription.check-status');
    Route::get('/subscription/poll-status/{checkoutRequestId}', [SubscriptionController::class, 'pollPaymentStatus'])
    ->name('subscription.pollStatus');
    Route::post('/subscription/activate-after-payment', [SubscriptionController::class, 'activateAfterPayment'])->name('subscription.activateAfterPayment');

    // User Dashboard & Subscriptions
    Route::get('/dashboard', [AdminController::class, 'index'])->name('user.dashboard');
    Route::get('/subscriptions', [AdminController::class, 'subscriptions'])->name('user.subscriptions');

    // Subscription History
    Route::get('/subscription/history', [SubscriptionController::class, 'history'])->name('subscription.history');
    Route::get('/subscription/success/{subscription_id?}', [SubscriptionController::class, 'success'])->name('subscription.success');

    // Subscription invoice and statement downloads
    Route::get('/subscription/invoice/{id}/download', [SubscriptionInvoiceController::class, 'downloadInvoice'])->name('subscription.invoice.download');
    Route::get('/subscription/statement/{id}/download', [SubscriptionInvoiceController::class, 'downloadStatement'])->name('subscription.statement.download');
    Route::get('/invoice/transaction/{transactionId}/download', [SubscriptionInvoiceController::class, 'downloadTransactionInvoice'])->name('transaction.invoice.download');
});

// ============================
// Admin routes - excluded from subscription check
// ============================
Route::prefix('admin')->middleware(['auth', 'admin', 'AdminSidebarMenu'])->group(function () {
    Route::get('/dashboard', [AdminController::class, 'index'])->name('admin.dashboard');
    Route::post('/dashboard/fix-sell-postings', [AdminController::class, 'fixSellPostings'])->name('admin.dashboard.fix-sell-postings');

    // Users - FIXED THIS SECTION
    Route::get('/users', [AdminController::class, 'users'])->name('admin.users'); // ADDED MISSING ROUTE
    Route::patch('/users/{user}/status', [AdminController::class, 'updateUserStatus'])->name('admin.users.update-status');
    Route::get('/users/{user}', [AdminController::class, 'showUser'])->name('admin.users.show');
    Route::delete('/users/{user}', [AdminController::class, 'destroy'])->name('admin.users.destroy');
    Route::post('/users/bulk-action', [AdminController::class, 'bulkAction'])->name('admin.users.bulk-action'); // ADDED MISSING ROUTE

    // Business status
    Route::patch('/business/{business}/status', [AdminController::class, 'updateBusinessStatus'])->name('admin.business.update-status');

    // Settings
    Route::post('/settings/update', [AdminController::class, 'updateSettings'])->name('admin.settings.update');
    Route::post('/settings/toggle-subscription-requirement', [AdminController::class, 'toggleSubscriptionRequirement'])->name('admin.settings.toggle-subscription-requirement');
    Route::post('/settings/update-subscription-mpesa', [AdminController::class, 'updateSubscriptionMpesaCredentials'])->name('admin.settings.update-subscription-mpesa');
    Route::get('/settings/preview-registration-email', [AdminController::class, 'previewRegistrationEmail'])->name('admin.settings.previewRegistrationEmail');

    // eTIMS Integration
    Route::post('/etims/transmit/{transaction}', [AdminController::class, 'transmitInvoiceToEtims'])->name('admin.etims.transmit');
    Route::get('/etims/invoices', [AdminController::class, 'getEtimsInvoices'])->name('admin.etims.invoices');

    // ============================
    // Admin Subscription Routes
    // ============================
    Route::prefix('subscriptions')->group(function () {
        Route::get('/', [AdminController::class, 'subscriptions'])->name('admin.subscriptions');
        Route::get('/{subscription}', [AdminController::class, 'showSubscription'])->name('admin.subscriptions.show');
        Route::post('/manual', [AdminController::class, 'createManualSubscription'])->name('admin.subscriptions.manual');
        Route::patch('/{subscription}/renew', [AdminController::class, 'renewSubscription'])->name('admin.subscriptions.renew');
        Route::patch('/{subscription}/activate', [AdminController::class, 'activateSubscription'])->name('admin.subscriptions.activate');
        Route::patch('/{subscription}/update-status', [AdminController::class, 'updateSubscriptionStatus'])->name('admin.subscriptions.update-status');
        Route::delete('/subscriptions/{subscription}', [AdminController::class, 'destroySubscription'])->name('admin.subscriptions.destroy');
    });
    // Admin view for individual M-Pesa payments
    Route::get('/mpesa-payments/{mpesaPayment}', [\App\Http\Controllers\Admin\MpesaPaymentController::class, 'show'])
        ->name('admin.mpesa_payments.show');
    // Bulk download statements for selected subscriptions (admin)
    Route::post('/subscriptions/download-statements', [\App\Http\Controllers\SubscriptionInvoiceController::class, 'downloadBulkStatements'])
        ->name('admin.subscriptions.download_statements');
    Route::post('subscriptions/{subscription}/cancel', [SubscriptionController::class, 'cancel'])
    ->name('admin.subscriptions.cancel');

    // Company <-> Business mapping (Admin)
    Route::get('/company-business-mapping', [\App\Http\Controllers\Admin\CompanyBusinessMappingController::class, 'index'])->name('admin.company.business.mapping');
    Route::post('/company-business-mapping/{company}', [\App\Http\Controllers\Admin\CompanyBusinessMappingController::class, 'update']);
});

// ============================
// M-Pesa routes
// ============================
// Canonical M-Pesa callback endpoint used by STK pushes (keeps legacy name)
Route::post('/mpesa/callback', [MpesaController::class, 'handleCallback'])->name('mpesa.callback');
Route::post('/payment/check-status', [MpesaController::class, 'checkPaymentStatus'])->name('payment.checkStatus');
// MPESA log viewer for POS
Route::get('/mpesa/logs', [MpesaLogController::class, 'paymentLogs'])->name('mpesa.logs');