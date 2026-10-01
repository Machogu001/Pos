<?php

namespace App\Http\Controllers;

use App\Business;
use App\BusinessLocation;
use App\Currency;
use App\Notifications\TestEmailNotification;
use App\System;
use App\TaxRate;
use App\Transaction;
use App\TransactionPayment;
use App\Unit;
use App\User;
use App\Utils\BusinessUtil;
use App\Utils\ModuleUtil;
use App\Utils\RestaurantUtil;
use App\Utils\TransactionUtil;
use Carbon\Carbon;
use DateTimeZone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use App\MpesaPayment;
use App\AdminSetting;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\URL;
use App\Mail\RegistrationMail;
class BusinessController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | BusinessController
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new business/business as well as their
    | validation and creation.
    |
    */

    /**
     * All Utils instance.
     */
    protected $businessUtil;

    protected $restaurantUtil;

    protected $moduleUtil;

    protected $mailDrivers;

    /**
     * Constructor
     *
     * @param  ProductUtils  $product
     * @return void
     */

//     public function startRegistration()
// {
//     return view('business.register'); // Make sure this view shows the "Pay via M-Pesa" button
// }

// public function showRegistrationForm(Request $request)
// {
//     $phone = session('payment_phone');
    
//     if (!$phone) {
//         return redirect()->route('registration.start')
//             ->with('error', __('payment.payment_not_confirmed'));
//     }

//     $latestPayment = \App\MpesaPayment::where('phone_number', $phone)
//         ->whereIn('transaction_status', ['SUCCESS', 'success', 'paid'])
//         ->latest()
//         ->first();

//     if (!$latestPayment) {
//         return redirect()->route('registration.start')
//             ->with('error', __('payment.payment_not_confirmed'));
//     }

//     // Regenerate session for security
//     $request->session()->regenerate();

//     return view('business.register', [  // ← CHANGE THIS LINE
//         'phone' => $phone,
//         'latestPayment' => $latestPayment,
//         'isPaid' => true,
//         'package_id' => $request->package_id
//     ]);
// }

// public function confirmPayment(Request $request)
// {
//     try {
//         \Log::info('BusinessController confirmPayment called');
        
//         // Method 1: Try to get phone from session first
//         $phone = session('payment_phone');
//         \Log::info('Session payment_phone: ' . ($phone ?: 'empty'));
        
//         // Method 2: If session is empty, get the VERY LATEST payment (regardless of status)
//         if (!$phone) {
//             $latestPayment = MpesaPayment::latest()->first();
            
//             if ($latestPayment) {
//                 $phone = $latestPayment->phone_number;
//                 \Log::info('Found latest payment phone: ' . $phone);
                
//                 // Update session for future requests
//                 session(['payment_phone' => $phone]);
//                 session()->save();
//             }
//         }

//         if (!$phone) {
//             \Log::warning('No phone number found for payment confirmation');
//             return response()->json([
//                 'success' => false,
//                 'message' => 'No payment session found'
//             ], 400);
//         }

//         \Log::info('Checking payment for phone: ' . $phone);
        
//         // Check for successful payment - look for ANY payment with this phone
//         $payment = MpesaPayment::where('phone_number', $phone)
//             ->latest() // Get the most recent one regardless of status
//             ->first();

//         if (!$payment) {
//             \Log::warning('No payment found for phone: ' . $phone);
//             return response()->json([
//                 'success' => false,
//                 'message' => 'No payment found for this phone number'
//             ], 404);
//         }

//         \Log::info('Payment found with status: ' . $payment->transaction_status . ', ID: ' . $payment->id);
        
//         // Return the actual payment status
//         return response()->json([
//             'success' => true,
//             'transaction_status' => $payment->transaction_status,
//             'message' => 'Payment status: ' . $payment->transaction_status
//         ]);

//     } catch (\Exception $e) {
//         \Log::error('Payment confirmation error: ' . $e->getMessage());
//         return response()->json([
//             'success' => false,
//             'message' => 'Error confirming payment: ' . $e->getMessage()
//         ], 500);
//     }
// }
    public function __construct(BusinessUtil $businessUtil, RestaurantUtil $restaurantUtil, ModuleUtil $moduleUtil)
    {
        $this->businessUtil = $businessUtil;
        $this->moduleUtil = $moduleUtil;

        $this->theme_colors = [
            'primary' => 'Blue',
            // 'black' => 'Black',
            'purple' => 'Purple',
            'green' => 'Green',
            'red' => 'Red',
            'yellow' => 'Yellow',
            'orange' => 'Orange',
            'sky' => 'Sky',
            // 'blue-light' => 'Blue Light',
            // 'black-light' => 'Black Light',
            // 'purple-light' => 'Purple Light',
            // 'green-light' => 'Green Light',
            // 'red-light' => 'Red Light',
        ];

        $this->mailDrivers = [
            'smtp' => 'SMTP',
            // 'sendmail' => 'Sendmail',
            // 'mailgun' => 'Mailgun',
            // 'mandrill' => 'Mandrill',
            // 'ses' => 'SES',
            // 'sparkpost' => 'Sparkpost'
        ];
    }

    /**
     * Shows registration form
     *
     * @return \Illuminate\Http\Response
     */
    public function getRegister()
    {
        if (! config('constants.allow_registration')) {
            return redirect('/');
        }

        $currencies = $this->businessUtil->allCurrencies();

        $timezone_list = $this->businessUtil->allTimeZones();

        $months = [];
        for ($i = 1; $i <= 12; $i++) {
            $months[$i] = __('business.months.'.$i);
        }

        $accounting_methods = $this->businessUtil->allAccountingMethods();
        $package_id = request()->package;

        $system_settings = System::getProperties(['superadmin_enable_register_tc', 'superadmin_register_tc'], true);

        return view('business.register', compact(
            'currencies',
            'timezone_list',
            'months',
            'accounting_methods',
            'package_id',
            'system_settings'
        ));
    }

    /**
     * Handles the registration of a new business and it's owner
     *
     * @return \Illuminate\Http\Response
     */
    public function postRegister(Request $request)
    {
        if (! config('constants.allow_registration')) {
            return redirect('/');
        }

        try {
            $validator = $request->validate(
                [
                    'name' => 'required|max:255',
                    'currency_id' => 'required|numeric',
                    'country' => 'required|max:255',
                    'state' => 'required|max:255',
                    'city' => 'required|max:255',
                    'zip_code' => 'required|max:255',
                    'landmark' => 'required|max:255',
                    'time_zone' => 'required|max:255',
                    'surname' => 'max:10',
                    'email' => 'sometimes|nullable|email|unique:users|max:255',
                    'first_name' => 'required|max:255',
                    'username' => 'required|min:4|max:255|unique:users',
                    'password' => 'required|min:4|max:255',
                    'fy_start_month' => 'required',
                    'accounting_method' => 'required',
                ],
                [
                    'name.required' => __('validation.required', ['attribute' => __('business.business_name')]),
                    'name.currency_id' => __('validation.required', ['attribute' => __('business.currency')]),
                    'country.required' => __('validation.required', ['attribute' => __('business.country')]),
                    'state.required' => __('validation.required', ['attribute' => __('business.state')]),
                    'city.required' => __('validation.required', ['attribute' => __('business.city')]),
                    'zip_code.required' => __('validation.required', ['attribute' => __('business.zip_code')]),
                    'landmark.required' => __('validation.required', ['attribute' => __('business.landmark')]),
                    'time_zone.required' => __('validation.required', ['attribute' => __('business.time_zone')]),
                    'email.email' => __('validation.email', ['attribute' => __('business.email')]),
                    'email.email' => __('validation.unique', ['attribute' => __('business.email')]),
                    'first_name.required' => __('validation.required', ['attribute' => __('business.first_name')]),
                    'username.required' => __('validation.required', ['attribute' => __('business.username')]),
                    'username.min' => __('validation.min', ['attribute' => __('business.username')]),
                    'password.required' => __('validation.required', ['attribute' => __('business.username')]),
                    'password.min' => __('validation.min', ['attribute' => __('business.username')]),
                    'fy_start_month.required' => __('validation.required', ['attribute' => __('business.fy_start_month')]),
                    'accounting_method.required' => __('validation.required', ['attribute' => __('business.accounting_method')]),
                ]
            );

            // Server-side enforcement: if registration fee is > 0, require that a successful
            // M-Pesa payment exists for the phone stored in session('payment_phone').
            $settings = AdminSetting::first();
            $registrationPrice = $settings->registration_price ?? 0;

            if (floatval($registrationPrice) > 0) {
                $payment = $this->findSuccessfulMpesaPayment($request);

                if (! $payment) {
                    return back()->withInput()->withErrors(['payment' => __('payment.payment_not_confirmed')]);
                }
            }

            DB::beginTransaction();

            //Create owner.
            $owner_details = $request->only(['surname', 'first_name', 'last_name', 'username', 'email', 'password', 'language']);

            $owner_details['language'] = empty($owner_details['language']) ? config('app.locale') : $owner_details['language'];

            $user = User::create_user($owner_details);

            $business_details = $request->only(['name', 'start_date', 'currency_id', 'time_zone',
                'fy_start_month', 'accounting_method', 'tax_label_1', 'tax_number_1',
                'tax_label_2', 'tax_number_2', ]);

            $business_location = $request->only(['name', 'country', 'state', 'city', 'zip_code', 'landmark',
                'website', 'mobile', 'alternate_number', ]);

            //Create the business
            $business_details['owner_id'] = $user->id;
            if (! empty($business_details['start_date'])) {
                $business_details['start_date'] = Carbon::createFromFormat(config('constants.default_date_format'), $business_details['start_date'])->toDateString();
            }

            //upload logo
            $logo_name = $this->businessUtil->uploadFile($request, 'business_logo', 'business_logos', 'image');
            if (! empty($logo_name)) {
                $business_details['logo'] = $logo_name;
            }

            //default enabled modules
            $business_details['enabled_modules'] = ['purchases', 'add_sale', 'pos_sale', 'stock_transfers', 'stock_adjustment', 'expenses', 'account'];

            $business = $this->businessUtil->createNewBusiness($business_details);

            //Update user with business id
            $user->business_id = $business->id;
            $user->save();

            $this->businessUtil->newBusinessDefaultResources($business->id, $user->id);
            $new_location = $this->businessUtil->addLocation($business->id, $business_location);

            $this->attachRegistrationPayment($request, $user, $business, $new_location);

            //create new permission with the new location
            Permission::create(['name' => 'location.'.$new_location->id]);

            DB::commit();

            //Module function to be called after after business is created
            if (config('app.env') != 'demo') {
                $this->moduleUtil->getModuleData('after_business_created', ['business' => $business]);
            }

            // Send registration confirmation email/receipt (includes registration price)
            try {
                if (!empty($user->email)) {
                    $settings = AdminSetting::first();
                    $registrationPrice = $settings->registration_price ?? 0;

                    // Try to fetch payment receipt for session phone if available
                    $receipt = null;
                    $phone = session('payment_phone');
                    if ($phone) {
                        $normalizedPhone = preg_replace('/[^0-9]/', '', $phone);
                        $payment = MpesaPayment::where(function($q) use ($normalizedPhone) {
                                $q->where('phone_number', $normalizedPhone)
                                  ->orWhere('phone_number', ltrim($normalizedPhone, '+'));
                            })
                            ->whereIn('transaction_status', ['success', 'paid', 'SUCCESS'])
                            ->latest()
                            ->first();

                        if ($payment) {
                            $receipt = $payment->mpesa_receipt_number ?? $payment->mpesa_receipt ?? $payment->transaction_id ?? null;
                        }
                    }

                    // Use a mailable that renders the registration template
                    Mail::to($user->email)->send(new RegistrationMail($business, $user, $registrationPrice, $receipt));
                }
            } catch (\Exception $e) {
                \Log::warning('Failed to send registration confirmation email: ' . $e->getMessage());
            }

            //Process payment information if superadmin is installed & package information is present
            $is_installed_superadmin = $this->moduleUtil->isSuperadminInstalled();
            $package_id = $request->get('package_id', null);
            if ($is_installed_superadmin && ! empty($package_id) && (config('app.env') != 'demo')) {
                $package = \Modules\Superadmin\Entities\Package::find($package_id);
                if (! empty($package)) {
                    Auth::login($user);
                    return redirect()->route('register-pay', ['package_id' => $package_id]);
                }
            }

            $output = ['success' => 1,
                'msg' => __('business.business_created_succesfully'),
            ];

            return redirect('login')->with('status', $output);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

            $output = ['success' => 0,
                'msg' => __('messages.something_went_wrong'),
            ];

            return back()->with('status', $output)->withInput();
        }
    }

    /**
     * Find a successful MpesaPayment matching the current registration context.
     * Returns the MpesaPayment model or null.
     */
    public function findSuccessfulMpesaPayment(Request $request)
    {
        $registrationPaymentId = session('registration_payment_id') ?? $request->input('registration_payment_id');
        $checkoutRequestId = session('checkout_request_id') ?? $request->input('checkout_request_id');
        $accountRef = session('account_ref') ?? $request->input('account_ref') ?? session('account_reference');
        $phone = session('payment_phone');

        if (empty($registrationPaymentId) && empty($checkoutRequestId) && empty($accountRef)) {
            return null;
        }

        $paymentQuery = MpesaPayment::query();

        if (! empty($registrationPaymentId)) {
            $paymentQuery->where('id', $registrationPaymentId);
        } else {
            $paymentQuery->where(function ($q) use ($checkoutRequestId, $accountRef) {
                if (! empty($checkoutRequestId)) {
                    $q->orWhere('checkout_request_id', $checkoutRequestId);
                }

                if (! empty($accountRef)) {
                    $q->orWhere('account_reference', $accountRef);
                }
            });
        }

        $paymentQuery->whereIn('transaction_status', ['success', 'paid', 'SUCCESS']);
        $paymentQuery->where(function ($query) {
            $query->whereNull('payment_type')
                ->orWhere('payment_type', 'registration');
        });

        if ($this->mpesaPaymentsHasColumn('consumed_at')) {
            $paymentQuery->whereNull('consumed_at');
        }

        if ($this->mpesaPaymentsHasColumn('business_id')) {
            $paymentQuery->whereNull('business_id');
        }

        if (! empty($phone)) {
            $normalizedPhone = preg_replace('/[^0-9]/', '', $phone);

            $paymentQuery->where(function ($q) use ($phone, $normalizedPhone) {
                $q->where('phone_number', $phone);

                if (! empty($normalizedPhone)) {
                    $q->orWhere('phone_number', $normalizedPhone)
                        ->orWhere('phone_number', ltrim($normalizedPhone, '+'));
                }
            });
        }

        return $paymentQuery->latest()->first();
    }

    public function resumeRegistrationPayment(Request $request)
    {
        $validated = $request->validate([
            'phone' => 'required|string|min:10|max:15',
            'payment_reference' => 'required|string|max:255',
        ]);

        $phone = MpesaPayment::normalizePhoneNumber($validated['phone']);
        $paymentReference = trim($validated['payment_reference']);

        if (empty($phone)) {
            return back()->withInput()->withErrors([
                'resume_payment' => __('payment.invalid_phone_format'),
            ]);
        }

        $payment = $this->findResumableRegistrationPayment($phone, $paymentReference);

        if (! $payment) {
            return back()->withInput()->withErrors([
                'resume_payment' => __('payment.resume_not_found'),
            ]);
        }

        $this->storeRegistrationPaymentSession($payment);

        return redirect()->route('business.getRegister')->with('status', [
            'success' => 1,
            'msg' => __('payment.resume_success'),
        ]);
    }

    public function resumeRegistrationFromLink(Request $request, MpesaPayment $payment)
    {
        if (! $request->hasValidSignature()) {
            abort(403);
        }

        $resumablePayment = $this->findResumableRegistrationPayment($payment->phone_number, $payment->account_reference, $payment->id);

        if (! $resumablePayment) {
            return redirect()->route('business.getRegister')->withErrors([
                'resume_payment' => __('payment.resume_already_used'),
            ]);
        }

        $this->storeRegistrationPaymentSession($resumablePayment);

        return redirect()->route('business.getRegister')->with('status', [
            'success' => 1,
            'msg' => __('payment.resume_success'),
        ]);
    }

    protected function findResumableRegistrationPayment(string $phone, string $paymentReference, ?int $paymentId = null): ?MpesaPayment
    {
        $normalizedPhone = MpesaPayment::normalizePhoneNumber($phone) ?? preg_replace('/[^0-9]/', '', $phone);

        $paymentQuery = MpesaPayment::query()
            ->whereIn('transaction_status', ['success', 'paid', 'SUCCESS'])
            ->where(function ($query) {
                $query->whereNull('payment_type')
                    ->orWhere('payment_type', MpesaPayment::TYPE_REGISTRATION);
            })
            ->where(function ($query) use ($paymentReference) {
                $query->where('account_reference', $paymentReference)
                    ->orWhere('mpesa_receipt_number', $paymentReference);
            })
            ->where(function ($query) use ($phone, $normalizedPhone) {
                $query->where('phone_number', $phone)
                    ->orWhere('phone_number', $normalizedPhone)
                    ->orWhere('phone_number', ltrim((string) $normalizedPhone, '+'));
            });

        if (! empty($paymentId)) {
            $paymentQuery->where('id', $paymentId);
        }

        if ($this->mpesaPaymentsHasColumn('consumed_at')) {
            $paymentQuery->whereNull('consumed_at');
        }

        if ($this->mpesaPaymentsHasColumn('business_id')) {
            $paymentQuery->whereNull('business_id');
        }

        return $paymentQuery->latest()->first();
    }

    protected function storeRegistrationPaymentSession(MpesaPayment $payment): void
    {
        session([
            'registration_payment_id' => $payment->id,
            'account_reference' => $payment->account_reference,
            'account_ref' => $payment->account_reference,
            'checkout_request_id' => $payment->checkout_request_id,
            'payment_phone' => $payment->phone_number,
            'first_name' => $payment->first_name,
            'middle_name' => $payment->middle_name,
            'last_name' => $payment->last_name,
        ]);
    }

    public static function registrationResumeUrlForPayment(MpesaPayment $payment): string
    {
        return URL::temporarySignedRoute('business.registration.resume.link', now()->addDay(), [
            'payment' => $payment->id,
        ]);
    }

    protected function attachRegistrationPayment(Request $request, User $user, Business $business, ?BusinessLocation $location = null): void
    {
        $payment = $this->findSuccessfulMpesaPayment($request);

        if (! $payment) {
            return;
        }

        $attributes = [
            'user_id' => $user->id,
        ];

        if ($this->mpesaPaymentsHasColumn('business_id')) {
            $attributes['business_id'] = $business->id;
        }

        $payment->fill($attributes);
        $payment->save();

        if (! empty($location)) {
            $this->recordRegistrationFeeExpense($user, $business, $location, $payment);
        }

        session([
            'registration_payment_id' => $payment->id,
            'account_reference' => $payment->account_reference,
            'account_ref' => $payment->account_reference,
            'checkout_request_id' => $payment->checkout_request_id,
            'payment_phone' => $payment->phone_number,
        ]);
    }

    protected function recordRegistrationFeeExpense(User $user, Business $business, BusinessLocation $location, MpesaPayment $payment): void
    {
        if (! empty($payment->consumed_by_transaction_id)) {
            $existingTransaction = Transaction::find($payment->consumed_by_transaction_id);
            if (! empty($existingTransaction) && $existingTransaction->type === 'expense') {
                return;
            }
        }

        $existingExpense = Transaction::where('business_id', $business->id)
            ->where('type', 'expense')
            ->where('sub_type', 'registration_fee')
            ->where('transaction_date', '>=', now()->subDay())
            ->where('location_id', $location->id)
            ->latest('id')
            ->first();

        if (! empty($existingExpense)) {
            $this->ensureExpensePaymentForMpesa($existingExpense, $payment, 'Registration fee recorded from completed business signup.');
            return;
        }

        $transactionUtil = app(TransactionUtil::class);
        $expenseRequest = new Request([
            'location_id' => $location->id,
            'transaction_date' => $payment->paid_at ?: now()->toDateTimeString(),
            'final_total' => $payment->amount,
            'additional_notes' => 'Registration fee paid during business onboarding.',
        ]);

        $expense = $transactionUtil->createExpense($expenseRequest, $business->id, $user->id, false);
        $expense->sub_type = 'registration_fee';
        $expense->subscription_no = 'registration_fee_' . $payment->id;
        $expense->save();

        $this->ensureExpensePaymentForMpesa($expense, $payment, 'Registration fee recorded from completed business signup.');
    }

    protected function ensureExpensePaymentForMpesa(Transaction $transaction, MpesaPayment $payment, string $note): void
    {
        $existingPayment = TransactionPayment::where('transaction_id', $transaction->id)
            ->where('method', 'mpesa')
            ->where(function ($query) use ($payment) {
                if (! empty($payment->checkout_request_id)) {
                    $query->where('checkout_request_id', $payment->checkout_request_id);
                }

                if (! empty($payment->mpesa_receipt_number)) {
                    $query->orWhere('mpesa_receipt_number', $payment->mpesa_receipt_number);
                }
            })
            ->first();

        if (empty($existingPayment)) {
            $transactionUtil = app(TransactionUtil::class);
            $refCount = $transactionUtil->setAndGetReferenceCount('expense_payment');

            TransactionPayment::create([
                'transaction_id' => $transaction->id,
                'business_id' => $transaction->business_id,
                'created_by' => $transaction->created_by ?: ($payment->user_id ?? 1),
                'payment_for' => $transaction->contact_id,
                'paid_on' => $payment->paid_at ?: now(),
                'amount' => $payment->amount ?? $transaction->final_total,
                'method' => 'mpesa',
                'note' => $note,
                'transaction_no' => $payment->mpesa_receipt_number,
                'mpesa_phone' => $payment->phone_number,
                'checkout_request_id' => $payment->checkout_request_id,
                'mpesa_receipt_number' => $payment->mpesa_receipt_number,
                'mpesa_status' => $payment->transaction_status,
                'payment_ref_no' => $transactionUtil->generateReferenceNumber('expense_payment', $refCount),
                'account_id' => TransactionPayment::resolveDefaultAccountId('mpesa', $transaction->location_id, $transaction->business_id, $transaction->type),
            ]);

            $transaction->payment_status = $transactionUtil->updatePaymentStatus($transaction->id, $transaction->final_total);
            $transaction->save();
        }

        $paymentAttributes = [];
        if ($this->mpesaPaymentsHasColumn('consumed_at') && empty($payment->consumed_at)) {
            $paymentAttributes['consumed_at'] = now();
        }
        if ($this->mpesaPaymentsHasColumn('consumed_by_transaction_id') && empty($payment->consumed_by_transaction_id)) {
            $paymentAttributes['consumed_by_transaction_id'] = $transaction->id;
        }
        if (! empty($paymentAttributes)) {
            $payment->fill($paymentAttributes);
            $payment->save();
        }
    }

    protected function mpesaPaymentsHasColumn(string $column): bool
    {
        static $columnCache = [];

        if (! array_key_exists($column, $columnCache)) {
            $columnCache[$column] = DB::getSchemaBuilder()->hasColumn('mpesa_payments', $column);
        }

        return $columnCache[$column];
    }

    /**
     * Handles the validation username
     *
     * @return \Illuminate\Http\Response
     */
    public function postCheckUsername(Request $request)
    {
        $username = $request->input('username');

        if (! empty($request->input('username_ext'))) {
            $username .= $request->input('username_ext');
        }

        $count = User::where('username', $username)->count();

        if ($count == 0) {
            echo 'true';
            exit;
        } else {
            echo 'false';
            exit;
        }
    }

    /**
     * Shows business settings form
     *
     * @return \Illuminate\Http\Response
     */
    public function getBusinessSettings()
    {
        if (! auth()->user()->can('business_settings.access')) {
            abort(403, 'Unauthorized action.');
        }

        $timezones = DateTimeZone::listIdentifiers(DateTimeZone::ALL);
        $timezone_list = [];
        foreach ($timezones as $timezone) {
            $timezone_list[$timezone] = $timezone;
        }

        $business_id = request()->session()->get('user.business_id');
        $business = Business::where('id', $business_id)->first();

        $currencies = $this->businessUtil->allCurrencies();
        $tax_details = TaxRate::forBusinessDropdown($business_id);
        $tax_rates = $tax_details['tax_rates'];

        $months = [];
        for ($i = 1; $i <= 12; $i++) {
            $months[$i] = __('business.months.'.$i);
        }

        $accounting_methods = [
            'fifo' => __('business.fifo'),
            'lifo' => __('business.lifo'),
        ];
        $commission_agent_dropdown = [
            '' => __('lang_v1.disable'),
            'logged_in_user' => __('lang_v1.logged_in_user'),
            'user' => __('lang_v1.select_from_users_list'),
            'cmsn_agnt' => __('lang_v1.select_from_commisssion_agents_list'),
        ];

        $units_dropdown = Unit::forDropdown($business_id, false);

        $date_formats = Business::date_formats();

        $shortcuts = json_decode($business->keyboard_shortcuts, true);

        $pos_settings = empty($business->pos_settings) ? $this->businessUtil->defaultPosSettings() : json_decode($business->pos_settings, true);

        $email_settings = empty($business->email_settings) ? $this->businessUtil->defaultEmailSettings() : $business->email_settings;

        $sms_settings = empty($business->sms_settings) ? $this->businessUtil->defaultSmsSettings() : $business->sms_settings;

        $modules = $this->moduleUtil->availableModules();

        if (! isset($modules['inventory_management'])) {
            $modules['inventory_management'] = [
                'name' => 'Inventory Management',
                'tooltip' => 'Enable or disable inventory management features and menus.',
            ];
        }

        // Ensure enabled_modules is available to the view for checkbox state.
        $enabled_modules = $business->enabled_modules;
        if (is_string($enabled_modules)) {
            $decoded_modules = json_decode($enabled_modules, true);
            $enabled_modules = is_array($decoded_modules) ? $decoded_modules : [];
        }
        $enabled_modules = is_array($enabled_modules) ? $enabled_modules : [];

        $theme_colors = $this->theme_colors;

        $mail_drivers = $this->mailDrivers;

        $allow_superadmin_email_settings = System::getProperty('allow_email_settings_to_businesses');

        $custom_labels = ! empty($business->custom_labels) ? json_decode($business->custom_labels, true) : [];

        $common_settings = ! empty($business->common_settings) ? $business->common_settings : [];

        $weighing_scale_setting = ! empty($business->weighing_scale_setting) ? $business->weighing_scale_setting : [];

        $payment_types = $this->moduleUtil->payment_types(null, false, $business_id);

        return view('business.settings', compact('business', 'currencies', 'tax_rates', 'timezone_list', 'months', 'accounting_methods', 'commission_agent_dropdown', 'units_dropdown', 'date_formats', 'shortcuts', 'pos_settings', 'modules', 'enabled_modules', 'theme_colors', 'email_settings', 'sms_settings', 'mail_drivers', 'allow_superadmin_email_settings', 'custom_labels', 'common_settings', 'weighing_scale_setting', 'payment_types'));
    }

    /**
     * Updates business settings
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function postBusinessSettings(Request $request)
    {
        if (! auth()->user()->can('business_settings.access')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $notAllowed = $this->businessUtil->notAllowedInDemo();
            if (! empty($notAllowed)) {
                return $notAllowed;
            }

            $business_details = $request->only(['name', 'start_date', 'currency_id', 'tax_label_1', 'tax_number_1', 'tax_label_2', 'tax_number_2', 'default_profit_percent', 'default_sales_tax', 'default_sales_discount', 'sell_price_tax', 'sku_prefix', 'time_zone', 'fy_start_month', 'accounting_method', 'transaction_edit_days', 'sales_cmsn_agnt', 'item_addition_method', 'currency_symbol_placement', 'on_product_expiry',
                'stop_selling_before', 'default_unit', 'expiry_type', 'date_format',
                'time_format', 'ref_no_prefixes', 'theme_color', 'email_settings',
                'sms_settings', 'rp_name', 'amount_for_unit_rp',
                'min_order_total_for_rp', 'max_rp_per_order',
                'redeem_amount_per_unit_rp', 'min_order_total_for_redeem',
                'min_redeem_point', 'max_redeem_point', 'rp_expiry_period',
                'rp_expiry_type', 'custom_labels', 'weighing_scale_setting',
                'code_label_1', 'code_1', 'code_label_2', 'code_2', 'currency_precision', 'quantity_precision', ]);

            if (! empty($request->input('enable_rp')) && $request->input('enable_rp') == 1) {
                $business_details['enable_rp'] = 1;
            } else {
                $business_details['enable_rp'] = 0;
            }

            $business_details['amount_for_unit_rp'] = ! empty($business_details['amount_for_unit_rp']) ? $this->businessUtil->num_uf($business_details['amount_for_unit_rp']) : 1;
            $business_details['min_order_total_for_rp'] = ! empty($business_details['min_order_total_for_rp']) ? $this->businessUtil->num_uf($business_details['min_order_total_for_rp']) : 1;
            $business_details['redeem_amount_per_unit_rp'] = ! empty($business_details['redeem_amount_per_unit_rp']) ? $this->businessUtil->num_uf($business_details['redeem_amount_per_unit_rp']) : 1;
            $business_details['min_order_total_for_redeem'] = ! empty($business_details['min_order_total_for_redeem']) ? $this->businessUtil->num_uf($business_details['min_order_total_for_redeem']) : 1;

            $business_details['default_profit_percent'] = ! empty($business_details['default_profit_percent']) ? $this->businessUtil->num_uf($business_details['default_profit_percent']) : 0;

            $business_details['default_sales_discount'] = ! empty($business_details['default_sales_discount']) ? $this->businessUtil->num_uf($business_details['default_sales_discount']) : 0;

            if (! empty($business_details['start_date'])) {
                $business_details['start_date'] = $this->businessUtil->uf_date($business_details['start_date']);
            }

            if (! empty($request->input('enable_tooltip')) && $request->input('enable_tooltip') == 1) {
                $business_details['enable_tooltip'] = 1;
            } else {
                $business_details['enable_tooltip'] = 0;
            }

            $business_details['enable_product_expiry'] = ! empty($request->input('enable_product_expiry')) && $request->input('enable_product_expiry') == 1 ? 1 : 0;
            if ($business_details['on_product_expiry'] == 'keep_selling') {
                $business_details['stop_selling_before'] = null;
            }

            $business_details['stock_expiry_alert_days'] = ! empty($request->input('stock_expiry_alert_days')) ? $request->input('stock_expiry_alert_days') : 30;

            //Check for Purchase currency
            if (! empty($request->input('purchase_in_diff_currency')) && $request->input('purchase_in_diff_currency') == 1) {
                $business_details['purchase_in_diff_currency'] = 1;
                $business_details['purchase_currency_id'] = $request->input('purchase_currency_id');
                $business_details['p_exchange_rate'] = $request->input('p_exchange_rate');
            } else {
                $business_details['purchase_in_diff_currency'] = 0;
                $business_details['purchase_currency_id'] = null;
                $business_details['p_exchange_rate'] = 1;
            }

            //upload logo
            $logo_name = $this->businessUtil->uploadFile($request, 'business_logo', 'business_logos', 'image');
            if (! empty($logo_name)) {
                $business_details['logo'] = $logo_name;
            }

            $checkboxes = ['enable_editing_product_from_purchase',
                'enable_inline_tax',
                'enable_brand', 'enable_category', 'enable_sub_category', 'enable_price_tax', 'enable_purchase_status',
                'enable_lot_number', 'enable_racks', 'enable_row', 'enable_position', 'enable_sub_units', ];
            foreach ($checkboxes as $value) {
                $business_details[$value] = ! empty($request->input($value)) && $request->input($value) == 1 ? 1 : 0;
            }

            $business_id = request()->session()->get('user.business_id');
            $business = Business::where('id', $business_id)->first();

            //Update business settings
            if (! empty($business_details['logo'])) {
                $business->logo = $business_details['logo'];
            } else {
                unset($business_details['logo']);
            }

            //System settings
            $shortcuts = $request->input('shortcuts');
            $business_details['keyboard_shortcuts'] = json_encode($shortcuts);

            //pos_settings
            $pos_settings = $request->input('pos_settings');
            $default_pos_settings = $this->businessUtil->defaultPosSettings();
            foreach ($default_pos_settings as $key => $value) {
                if (! isset($pos_settings[$key])) {
                    $pos_settings[$key] = $value;
                }
            }
            $business_details['pos_settings'] = json_encode($pos_settings);

            $business_details['custom_labels'] = json_encode($business_details['custom_labels']);

            $business_details['common_settings'] = Business::normalizeCommonSettings(
                ! empty($request->input('common_settings')) ? $request->input('common_settings') : []
            );

            // Enabled modules: only update when module section is present in request.
            // This prevents accidental wipes when large settings forms are truncated.
            if ($request->has('modules_section_present')) {
                $modules_settings_changed = (int) $request->input('modules_settings_changed', 0) === 1;
                $enabled_modules_sent = $request->has('enabled_modules');

                // Persist module selection only when user changed it.
                // This prevents accidental wipes during unrelated settings updates.
                if ($modules_settings_changed || $enabled_modules_sent) {
                    $enabled_modules = $request->input('enabled_modules', []);
                    if (is_string($enabled_modules)) {
                        $decoded_modules = json_decode($enabled_modules, true);
                        $enabled_modules = is_array($decoded_modules) ? $decoded_modules : [];
                    }

                    $business_details['enabled_modules'] = array_values(array_unique((array) $enabled_modules));
                }
            }
            $business->fill($business_details);
            $business->save();

            $this->businessUtil->provisionDefaultAccountMappings($business->id, $request->session()->get('user.id'));

            //update session data
            $request->session()->put('business', $business);

            //Update Currency details
            $currency = Currency::find($business->currency_id);
            $request->session()->put('currency', [
                'id' => $currency->id,
                'code' => $currency->code,
                'symbol' => $currency->symbol,
                'thousand_separator' => $currency->thousand_separator,
                'decimal_separator' => $currency->decimal_separator,
            ]);

            //update current financial year to session
            $financial_year = $this->businessUtil->getCurrentFinancialYear($business->id);
            $request->session()->put('financial_year', $financial_year);

            $output = ['success' => 1,
                'msg' => __('business.settings_updated_success'),
            ];
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

            $output = ['success' => 0,
                'msg' => __('messages.something_went_wrong'),
            ];
        }

        return redirect('business/settings')->with('status', $output);
    }

    /**
     * Handles the validation email
     *
     * @return \Illuminate\Http\Response
     */
    public function postCheckEmail(Request $request)
    {
        $email = $request->input('email');

        $query = User::where('email', $email);

        if (! empty($request->input('user_id'))) {
            $user_id = $request->input('user_id');
            $query->where('id', '!=', $user_id);
        }

        $exists = $query->exists();
        if (! $exists) {
            echo 'true';
            exit;
        } else {
            echo 'false';
            exit;
        }
    }

    public function getEcomSettings()
    {
        try {
            $api_token = request()->header('API-TOKEN');
            $api_settings = $this->moduleUtil->getApiSettings($api_token);

            $settings = Business::where('id', $api_settings->business_id)
                        ->value('ecom_settings');

            $settings_array = ! empty($settings) ? json_decode($settings, true) : [];

            if (! empty($settings_array['slides'])) {
                foreach ($settings_array['slides'] as $key => $value) {
                    $settings_array['slides'][$key]['image_url'] = ! empty($value['image']) ? url('uploads/img/'.$value['image']) : '';
                }
            }
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

            return $this->respondWentWrong($e);
        }

        return $this->respond($settings_array);
    }

    /**
     * Handles the testing of email configuration
     *
     * @return \Illuminate\Http\Response
     */
    public function testEmailConfiguration(Request $request)
    {
        try {
            $email_settings = $request->input();

            $data['email_settings'] = $email_settings;
            \Notification::route('mail', $email_settings['mail_from_address'])
            ->notify(new TestEmailNotification($data));

            $output = [
                'success' => 1,
                'msg' => __('lang_v1.email_tested_successfully'),
            ];
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());
            $output = [
                'success' => 0,
                'msg' => $e->getMessage(),
            ];
        }

        return $output;
    }

    /**
     * Handles the testing of sms configuration
     *
     * @return \Illuminate\Http\Response
     */
    public function testSmsConfiguration(Request $request)
    {
        try {
            $sms_settings = $request->input();

            $data = [
                'sms_settings' => $sms_settings,
                'mobile_number' => $sms_settings['test_number'],
                'sms_body' => 'This is a test SMS',
            ];
            if (! empty($sms_settings['test_number'])) {
                $response = $this->businessUtil->sendSms($data);
            } else {
                $response = __('lang_v1.test_number_is_required');
            }

            $output = [
                'success' => 1,
                'msg' => $response,
            ];
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());
            $output = [
                'success' => 0,
                'msg' => $e->getMessage(),
            ];
        }

        return $output;
    }
}