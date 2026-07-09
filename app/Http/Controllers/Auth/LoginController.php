<?php

namespace App\Http\Controllers\Auth;

use App\Mail\LoginOtpMail;
use App\Services\MobileSasaSmsService;
use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use App\Utils\BusinessUtil;
use App\Utils\ModuleUtil;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use App\Rules\ReCaptcha;
use App\User;


class LoginController extends Controller
{
    private const OTP_DELIVERY_SMS = 'sms';

    private const OTP_DELIVERY_EMAIL = 'email';

    private const OTP_RESEND_COOLDOWN_SECONDS = 59;

    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = RouteServiceProvider::HOME;

    /**
     * All Utils instance.
     */
    protected $businessUtil;

    protected $moduleUtil;

    protected $smsService;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(BusinessUtil $businessUtil, ModuleUtil $moduleUtil, MobileSasaSmsService $smsService)
    {
        $this->middleware('guest')->except('logout');
        $this->businessUtil = $businessUtil;
        $this->moduleUtil = $moduleUtil;
        $this->smsService = $smsService;
    }

    public function showLoginForm()
    {
        return view('auth.login');
    }

    /**
     * Change authentication from email to username
     *
     * @return void
     */
    public function username()
    {
        return 'username';
    }

    public function logout(Request $request)
    {
        if (auth()->check()) {
            $this->logAuthenticationEvent($request, auth()->user(), 'logout');

            // Clear the remember_me token from DB so the remember cookie cannot re-authenticate the user.
            auth()->user()->forceFill(['remember_token' => null])->save();
        }

        Auth::logout();

        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect('/login');
    }

    /**
     * The user has been authenticated.
     * Check if the business is active or not.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  mixed  $user
     * @return mixed
     */
    protected function authenticated(Request $request, $user)
    {
        if ($response = $this->loginRestrictionResponse($user)) {
            return $response;
        }

        if (empty($user->otp_login_enabled)) {
            $this->logAuthenticationEvent($request, $user, 'login');

            return null;
        }

        $deliveryMethod = $this->normalizeOtpDeliveryMethod($request->input('otp_delivery_method'));
        $deliveryTarget = $this->resolveOtpDeliveryTarget($user, $deliveryMethod);

        if (empty($deliveryTarget)) {
            Auth::logout();

            return redirect('/login')->with('status', [
                'success' => 0,
                'msg' => $deliveryMethod === self::OTP_DELIVERY_EMAIL
                    ? __('The account does not have a valid email address for OTP login.')
                    : __('The account does not have a valid phone number for OTP login.'),
            ]);
        }

        $otp = (string) random_int(100000, 999999);

        Auth::logout();
        $request->session()->regenerate();
        $request->session()->put('login_otp', [
            'user_id' => $user->id,
            'remember' => $request->boolean('remember'),
            'delivery_method' => $deliveryMethod,
            'delivery_target' => $deliveryTarget,
            'phone' => $this->normalizePhoneNumber($user->contact_number ?? null),
            'email' => $user->email,
            'otp_hash' => Hash::make($otp),
            'expires_at' => now()->addMinutes(5)->timestamp,
            'resend_available_at' => now()->addSeconds(self::OTP_RESEND_COOLDOWN_SECONDS)->timestamp,
            'attempts' => 0,
        ]);

        $otpDelivery = $this->sendOtpCode($deliveryMethod, $deliveryTarget, $otp, $user);

        if (! $otpDelivery['sent']) {
            $request->session()->forget('login_otp');

            return redirect('/login')->with('status', [
                'success' => 0,
                'msg' => __('We could not send the OTP code right now. Please try again.'),
            ]);
        }

        $request->session()->put('login_otp', array_merge($request->session()->get('login_otp', []), [
            'delivery_method' => $otpDelivery['method'],
            'delivery_target' => $otpDelivery['target'],
        ]));

        $destinationMessage = $otpDelivery['method'] === self::OTP_DELIVERY_EMAIL
            ? __('email address')
            : __('phone');

        return redirect()->route('login.otp.form')->with('status', [
            'success' => 1,
            'msg' => __('We sent a one-time code to your :destination. Enter it to finish signing in.', [
                'destination' => $destinationMessage,
            ]),
        ]);
    }

    protected function redirectTo()
    {
        $user = \Auth::user();
        if (! $user->can('dashboard.data') && $user->can('sell.create')) {
            return '/pos/create';
        }

        if ($user->user_type == 'user_customer') {
            return 'contact/contact-dashboard';
        }

        return '/home';
    }

    public function validateLogin(Request $request)
    {
        if(config('constants.enable_recaptcha')){
            $this->validate($request, [
                $this->username() => 'required|string',
                'password' => 'required|string',
                'otp_delivery_method' => ['nullable', 'in:sms,email'],
                'g-recaptcha-response' => ['required', new ReCaptcha]
            ]);
        }else{
            $this->validate($request, [
                $this->username() => 'required|string',
                'password' => 'required|string',
                'otp_delivery_method' => ['nullable', 'in:sms,email'],
            ]);
        }
       
    }

    public function showOtpForm(Request $request)
    {
        $otpData = $request->session()->get('login_otp');

        if (empty($otpData) || empty($otpData['user_id']) || $this->isOtpExpired($otpData)) {
            $request->session()->forget('login_otp');

            return redirect('/login')->with('status', [
                'success' => 0,
                'msg' => __('Your OTP session has expired. Please sign in again.'),
            ]);
        }

        $user = User::find($otpData['user_id']);

        if (empty($user)) {
            $request->session()->forget('login_otp');

            return redirect('/login')->with('status', [
                'success' => 0,
                'msg' => __('The account could not be found.'),
            ]);
        }

        return view('auth.otp', [
            'deliveryMethod' => $otpData['delivery_method'] ?? self::OTP_DELIVERY_SMS,
            'deliveryTargetMask' => $this->maskOtpDeliveryTarget($otpData),
            'deliveryMethodLabel' => $this->otpDeliveryMethodLabel($otpData['delivery_method'] ?? self::OTP_DELIVERY_SMS),
            'availableDeliveryMethods' => $this->availableOtpDeliveryMethods($user),
            'resendAvailableAt' => (int) ($otpData['resend_available_at'] ?? 0),
            'resendCooldownSeconds' => self::OTP_RESEND_COOLDOWN_SECONDS,
        ]);
    }

    public function verifyOtp(Request $request)
    {
        $request->validate([
            'otp' => ['required', 'digits:6'],
        ]);

        $otpData = $request->session()->get('login_otp');

        if (empty($otpData) || empty($otpData['user_id']) || $this->isOtpExpired($otpData)) {
            $request->session()->forget('login_otp');

            return redirect('/login')->with('status', [
                'success' => 0,
                'msg' => __('Your OTP session has expired. Please sign in again.'),
            ]);
        }

        if (! Hash::check($request->input('otp'), $otpData['otp_hash'])) {
            $otpData['attempts'] = (int) ($otpData['attempts'] ?? 0) + 1;
            $request->session()->put('login_otp', $otpData);

            if ($otpData['attempts'] >= 5) {
                $request->session()->forget('login_otp');

                return redirect('/login')->with('status', [
                    'success' => 0,
                    'msg' => __('Too many invalid OTP attempts. Please sign in again.'),
                ]);
            }

            return back()->withErrors([
                'otp' => __('The OTP code is invalid.'),
            ]);
        }

        $user = User::find($otpData['user_id']);

        if (empty($user)) {
            $request->session()->forget('login_otp');

            return redirect('/login')->with('status', [
                'success' => 0,
                'msg' => __('The account could not be found.'),
            ]);
        }

        if ($response = $this->loginRestrictionResponse($user)) {
            $request->session()->forget('login_otp');

            return $response;
        }

        $remember = ! empty($otpData['remember']);

        $request->session()->forget('login_otp');
        Auth::login($user, $remember);
        $request->session()->regenerate();
        $this->logAuthenticationEvent($request, $user, 'login');

        return redirect()->intended($this->redirectPath());
    }

    private function logAuthenticationEvent(Request $request, User $user, string $action): void
    {
        if ($action === 'login') {
            $this->updateLastLoginDetails($user, $request);
        }

        $this->businessUtil->activityLog(
            $user,
            $action,
            null,
            $this->businessUtil->getAuthActivityProperties($request),
            false,
            $user->business_id
        );
    }

    private function updateLastLoginDetails(User $user, Request $request): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        $updates = [];
        if (Schema::hasColumn('users', 'last_login_at')) {
            $updates['last_login_at'] = now();
        }
        if (Schema::hasColumn('users', 'last_login_ip')) {
            $updates['last_login_ip'] = $this->businessUtil->resolveClientIp($request);
        }
        if (Schema::hasColumn('users', 'last_login_user_agent')) {
            $updates['last_login_user_agent'] = substr((string) ($request->userAgent() ?? ''), 0, 1000);
        }

        if (! empty($updates)) {
            $user->forceFill($updates)->saveQuietly();
        }
    }

    public function resendOtp(Request $request)
    {
        $otpData = $request->session()->get('login_otp');

        if (empty($otpData) || empty($otpData['user_id']) || $this->isOtpExpired($otpData)) {
            $request->session()->forget('login_otp');

            return redirect('/login')->with('status', [
                'success' => 0,
                'msg' => __('Your OTP session has expired. Please sign in again.'),
            ]);
        }

        $user = User::find($otpData['user_id']);

        if (empty($user)) {
            $request->session()->forget('login_otp');

            return redirect('/login')->with('status', [
                'success' => 0,
                'msg' => __('The account could not be found.'),
            ]);
        }

        $deliveryMethod = $this->normalizeOtpDeliveryMethod($request->input('otp_delivery_method', $otpData['delivery_method'] ?? self::OTP_DELIVERY_SMS));
        $deliveryTarget = $this->resolveOtpDeliveryTarget($user, $deliveryMethod);

        if (empty($deliveryTarget)) {
            return back()->withErrors([
                'otp' => $deliveryMethod === self::OTP_DELIVERY_EMAIL
                    ? __('The account does not have a valid email address for OTP login.')
                    : __('The account does not have a valid phone number for OTP login.'),
            ]);
        }

        $resendAvailableAt = (int) ($otpData['resend_available_at'] ?? 0);

        if (! empty($resendAvailableAt) && now()->timestamp < $resendAvailableAt) {
            return back()->with('status', [
                'success' => 0,
                'msg' => __('Please wait :seconds seconds before requesting another OTP.', [
                    'seconds' => max($resendAvailableAt - now()->timestamp, 1),
                ]),
            ]);
        }

        $otp = (string) random_int(100000, 999999);

        $otpData['otp_hash'] = Hash::make($otp);
        $otpData['expires_at'] = now()->addMinutes(5)->timestamp;
        $otpData['resend_available_at'] = now()->addSeconds(self::OTP_RESEND_COOLDOWN_SECONDS)->timestamp;
        $otpData['attempts'] = 0;
        $otpData['delivery_method'] = $deliveryMethod;
        $otpData['delivery_target'] = $deliveryTarget;
        $otpData['phone'] = $this->normalizePhoneNumber($user->contact_number ?? null);
        $otpData['email'] = $user->email;
        $request->session()->put('login_otp', $otpData);

        $otpDelivery = $this->sendOtpCode($deliveryMethod, $deliveryTarget, $otp, $user);

        if (! $otpDelivery['sent']) {
            return back()->withErrors([
                'otp' => __('We could not resend the OTP code right now.'),
            ]);
        }

        $otpData['delivery_method'] = $otpDelivery['method'];
        $otpData['delivery_target'] = $otpDelivery['target'];
        $request->session()->put('login_otp', $otpData);

        $destinationMessage = $otpDelivery['method'] === self::OTP_DELIVERY_EMAIL
            ? __('your email address')
            : __('your phone');

        return back()->with('status', [
            'success' => 1,
            'msg' => __('A new OTP code has been sent to :destination.', [
                'destination' => $destinationMessage,
            ]),
        ]);
    }

    protected function sendOtpCode(string $deliveryMethod, string $deliveryTarget, string $otp, ?User $user = null): array
    {
        if ($deliveryMethod === self::OTP_DELIVERY_EMAIL) {
            try {
                Mail::to($deliveryTarget)->send(new LoginOtpMail($otp, $user));

                return [
                    'sent' => true,
                    'method' => self::OTP_DELIVERY_EMAIL,
                    'target' => $deliveryTarget,
                ];
            } catch (\Throwable $exception) {
                Log::warning('Login OTP email delivery failed', [
                    'email' => $deliveryTarget,
                    'user_id' => $user?->id,
                    'message' => $exception->getMessage(),
                ]);

                $smsTarget = $this->resolveOtpDeliveryTarget($user, self::OTP_DELIVERY_SMS);
                if (! empty($smsTarget) && $this->smsService->sendLoginOtp($smsTarget, $otp, $user)) {
                    Log::info('Login OTP email delivery fell back to SMS', [
                        'email' => $deliveryTarget,
                        'phone' => $smsTarget,
                        'user_id' => $user?->id,
                    ]);

                    return [
                        'sent' => true,
                        'method' => self::OTP_DELIVERY_SMS,
                        'target' => $smsTarget,
                    ];
                }

                return [
                    'sent' => false,
                    'method' => self::OTP_DELIVERY_EMAIL,
                    'target' => $deliveryTarget,
                ];
            }
        }

        return [
            'sent' => $this->smsService->sendLoginOtp($deliveryTarget, $otp, $user),
            'method' => self::OTP_DELIVERY_SMS,
            'target' => $deliveryTarget,
        ];
    }

    protected function normalizeOtpDeliveryMethod(?string $deliveryMethod): string
    {
        return in_array(strtolower((string) $deliveryMethod), [self::OTP_DELIVERY_EMAIL], true)
            ? self::OTP_DELIVERY_EMAIL
            : self::OTP_DELIVERY_SMS;
    }

    protected function resolveOtpDeliveryTarget(User $user, string $deliveryMethod): ?string
    {
        if ($deliveryMethod === self::OTP_DELIVERY_EMAIL) {
            $email = trim((string) $user->email);

            return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null;
        }

        return $this->normalizePhoneNumber($user->contact_number ?? null);
    }

    protected function maskOtpDeliveryTarget(array $otpData): string
    {
        $deliveryMethod = $otpData['delivery_method'] ?? self::OTP_DELIVERY_SMS;
        $deliveryTarget = (string) ($otpData['delivery_target'] ?? '');

        if ($deliveryMethod === self::OTP_DELIVERY_EMAIL) {
            return $this->maskEmailAddress($deliveryTarget);
        }

        return $this->maskPhoneNumber($deliveryTarget);
    }

    protected function otpDeliveryMethodLabel(string $deliveryMethod): string
    {
        return $deliveryMethod === self::OTP_DELIVERY_EMAIL ? __('Email') : __('SMS');
    }

    protected function availableOtpDeliveryMethods(User $user): array
    {
        $methods = [self::OTP_DELIVERY_SMS => __('SMS')];

        if (filter_var(trim((string) $user->email), FILTER_VALIDATE_EMAIL)) {
            $methods[self::OTP_DELIVERY_EMAIL] = __('Email');
        }

        return $methods;
    }

    protected function maskEmailAddress(?string $email): string
    {
        $email = trim((string) $email);

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $email;
        }

        [$localPart, $domain] = explode('@', $email, 2);

        if (strlen($localPart) <= 2) {
            $maskedLocalPart = str_repeat('*', strlen($localPart));
        } else {
            $maskedLocalPart = substr($localPart, 0, 1).str_repeat('*', max(strlen($localPart) - 2, 1)).substr($localPart, -1);
        }

        return $maskedLocalPart.'@'.$domain;
    }

    protected function loginRestrictionResponse($user)
    {
        if (! $user->isSuperAdmin() && ! optional($user->business)->is_active) {
            Auth::logout();

            return redirect('/login')
              ->with(
                  'status',
                  ['success' => 0, 'msg' => __('lang_v1.business_inactive')]
              );
        }

        if ($user->status != 'active') {
            Auth::logout();

            return redirect('/login')
              ->with(
                  'status',
                  ['success' => 0, 'msg' => __('lang_v1.user_inactive')]
              );
        }

        if (! $user->allow_login) {
            Auth::logout();

            return redirect('/login')
                ->with(
                    'status',
                    ['success' => 0, 'msg' => __('lang_v1.login_not_allowed')]
                );
        }

        if (($user->user_type == 'user_customer') && ! $this->moduleUtil->hasThePermissionInSubscription($user->business_id, 'crm_module')) {
            Auth::logout();

            return redirect('/login')
                ->with(
                    'status',
                    ['success' => 0, 'msg' => __('lang_v1.business_dont_have_crm_subscription')]
                );
        }

        return null;
    }

    protected function otpLoginEnabled($user)
    {
        return ! empty($user->otp_login_enabled);
    }

    protected function isOtpExpired(array $otpData)
    {
        return empty($otpData['expires_at']) || now()->timestamp > (int) $otpData['expires_at'];
    }

    protected function normalizePhoneNumber($phoneNumber)
    {
        $phoneNumber = preg_replace('/\D+/', '', (string) $phoneNumber);

        if (empty($phoneNumber)) {
            return null;
        }

        if (str_starts_with($phoneNumber, '0')) {
            return '254'.ltrim($phoneNumber, '0');
        }

        if (str_starts_with($phoneNumber, '7') && strlen($phoneNumber) === 9) {
            return '254'.$phoneNumber;
        }

        return $phoneNumber;
    }

    protected function maskPhoneNumber($phoneNumber)
    {
        $phoneNumber = (string) $phoneNumber;

        if (strlen($phoneNumber) <= 4) {
            return $phoneNumber;
        }

        return str_repeat('*', max(strlen($phoneNumber) - 4, 0)).substr($phoneNumber, -4);
    }

}
