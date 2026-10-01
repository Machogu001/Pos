<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Services\MobileLoginService;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthController extends BaseMobileController
{
    public function __construct(protected MobileLoginService $loginService)
    {
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
            'device_name' => ['required', 'string', 'max:255'],
            'otp_delivery_method' => ['nullable', 'in:sms,email'],
        ]);

        try {
            $user = User::where('username', $data['username'])->first();
            if (! $user || ! Hash::check($data['password'], $user->password)) {
                return $this->error('These credentials do not match our records.', 422, null, [
                    'username' => ['These credentials do not match our records.'],
                ]);
            }

            if ($message = $this->loginService->restrictionMessage($user)) {
                return $this->error($message, 403, 'forbidden');
            }

            if (! $this->loginService->otpLoginEnabled($user)) {
                return $this->authenticatedResponse($request, $user, $data['device_name']);
            }

            return $this->createOtpSession($user, $request->input('otp_delivery_method'));
        } catch (\Throwable $exception) {
            return $this->serverError($exception, ['action' => 'mobile_login']);
        }
    }

    public function verifyOtp(Request $request)
    {
        $data = $request->validate([
            'otp_session' => ['required', 'string'],
            'otp' => ['required', 'digits:6'],
            'device_name' => ['required', 'string', 'max:255'],
        ]);

        try {
            $key = $this->otpCacheKey($data['otp_session']);
            $otpData = Cache::get($key);

            if (empty($otpData) || empty($otpData['user_id']) || now()->timestamp > (int) ($otpData['expires_at'] ?? 0)) {
                Cache::forget($key);
                return $this->error('Your OTP session has expired. Please sign in again.', 410, 'otp_expired');
            }

            if ((int) ($otpData['attempts'] ?? 0) >= MobileLoginService::OTP_MAX_ATTEMPTS) {
                Cache::forget($key);
                return $this->error('Your OTP session has expired. Please sign in again.', 410, 'otp_expired');
            }

            if (! Hash::check($data['otp'], $otpData['otp_hash'])) {
                $otpData['attempts'] = (int) ($otpData['attempts'] ?? 0) + 1;
                if ($otpData['attempts'] >= MobileLoginService::OTP_MAX_ATTEMPTS) {
                    Cache::forget($key);
                    return $this->error('Your OTP session has expired. Please sign in again.', 410, 'otp_expired');
                }
                Cache::put($key, $otpData, now()->addSeconds(max((int) $otpData['expires_at'] - now()->timestamp, 1)));

                return $this->error('The OTP code is invalid.', 422, null, ['otp' => ['The OTP code is invalid.']]);
            }

            $user = User::with('business.currency')->find($otpData['user_id']);
            if (! $user) {
                Cache::forget($key);
                return $this->error('Your OTP session has expired. Please sign in again.', 410, 'otp_expired');
            }

            if ($message = $this->loginService->restrictionMessage($user)) {
                Cache::forget($key);
                return $this->error($message, 403, 'forbidden');
            }

            Cache::forget($key);

            return $this->authenticatedResponse($request, $user, $data['device_name']);
        } catch (\Throwable $exception) {
            return $this->serverError($exception, ['action' => 'mobile_otp_verify']);
        }
    }

    public function resendOtp(Request $request)
    {
        $data = $request->validate([
            'otp_session' => ['required', 'string'],
            'otp_delivery_method' => ['nullable', 'in:sms,email'],
        ]);

        try {
            $key = $this->otpCacheKey($data['otp_session']);
            $otpData = Cache::get($key);
            if (empty($otpData) || empty($otpData['user_id']) || now()->timestamp > (int) ($otpData['expires_at'] ?? 0)) {
                Cache::forget($key);
                return $this->error('Your OTP session has expired. Please sign in again.', 410, 'otp_expired');
            }

            $resendAvailableAt = (int) ($otpData['resend_available_at'] ?? 0);
            if ($resendAvailableAt > now()->timestamp) {
                return $this->error('Please wait before requesting another OTP.', 429, 'too_many_requests', [
                    'otp_session' => ['Please wait '.($resendAvailableAt - now()->timestamp).' seconds before requesting another OTP.'],
                ]);
            }

            $user = User::find($otpData['user_id']);
            if (! $user) {
                Cache::forget($key);
                return $this->error('Your OTP session has expired. Please sign in again.', 410, 'otp_expired');
            }

            $deliveryMethod = $this->loginService->normalizeOtpDeliveryMethod($data['otp_delivery_method'] ?? ($otpData['delivery_method'] ?? null));
            $deliveryTarget = $this->loginService->resolveOtpDeliveryTarget($user, $deliveryMethod);
            if (empty($deliveryTarget)) {
                return $this->error('The account does not have a valid OTP delivery target.', 422, null, [
                    'otp_delivery_method' => ['The account does not have a valid OTP delivery target.'],
                ]);
            }

            $otp = (string) random_int(100000, 999999);
            $delivery = $this->loginService->sendOtpCode($deliveryMethod, $deliveryTarget, $otp, $user);
            if (! $delivery['sent']) {
                return $this->error('We could not send the OTP code right now. Please try again.', 422);
            }

            $otpData = array_merge($otpData, [
                'delivery_method' => $delivery['method'],
                'delivery_target' => $delivery['target'],
                'otp_hash' => Hash::make($otp),
                'expires_at' => now()->addSeconds(MobileLoginService::OTP_TTL_SECONDS)->timestamp,
                'resend_available_at' => now()->addSeconds(MobileLoginService::OTP_RESEND_COOLDOWN_SECONDS)->timestamp,
                'attempts' => 0,
            ]);
            Cache::put($key, $otpData, now()->addSeconds(MobileLoginService::OTP_TTL_SECONDS));

            return $this->success($this->otpResponseData($otpData));
        } catch (\Throwable $exception) {
            return $this->serverError($exception, ['action' => 'mobile_otp_resend']);
        }
    }

    public function logout(Request $request)
    {
        try {
            $token = $request->user()?->token();
            if ($token) {
                $token->revoke();
            }
            if ($request->user()) {
                $this->loginService->logAuthenticationEvent($request, $request->user(), 'logout');
            }

            return $this->success(null);
        } catch (\Throwable $exception) {
            return $this->serverError($exception, ['action' => 'mobile_logout']);
        }
    }

    protected function authenticatedResponse(Request $request, User $user, string $deviceName)
    {
        $this->loginService->logAuthenticationEvent($request, $user, 'login');
        $token = $user->createToken('mobile:'.$deviceName)->accessToken;
        $user->loadMissing('business.currency');

        return $this->success([
            'status' => 'authenticated',
            'token' => $token,
            'user' => $this->userPayload($user),
            'business' => $this->businessPayload($user->business),
        ]);
    }

    protected function createOtpSession(User $user, ?string $requestedDeliveryMethod)
    {
        $deliveryMethod = $this->loginService->normalizeOtpDeliveryMethod($requestedDeliveryMethod);
        $deliveryTarget = $this->loginService->resolveOtpDeliveryTarget($user, $deliveryMethod);
        if (empty($deliveryTarget)) {
            return $this->error('The account does not have a valid OTP delivery target.', 422, null, [
                'otp_delivery_method' => ['The account does not have a valid OTP delivery target.'],
            ]);
        }

        $otp = (string) random_int(100000, 999999);
        $delivery = $this->loginService->sendOtpCode($deliveryMethod, $deliveryTarget, $otp, $user);
        if (! $delivery['sent']) {
            return $this->error('We could not send the OTP code right now. Please try again.', 422);
        }

        $otpSession = Str::random(64);
        $otpData = [
            'user_id' => $user->id,
            'delivery_method' => $delivery['method'],
            'delivery_target' => $delivery['target'],
            'otp_hash' => Hash::make($otp),
            'expires_at' => now()->addSeconds(MobileLoginService::OTP_TTL_SECONDS)->timestamp,
            'resend_available_at' => now()->addSeconds(MobileLoginService::OTP_RESEND_COOLDOWN_SECONDS)->timestamp,
            'attempts' => 0,
        ];
        Cache::put($this->otpCacheKey($otpSession), $otpData, now()->addSeconds(MobileLoginService::OTP_TTL_SECONDS));

        return $this->success(['status' => 'otp_required', 'otp_session' => $otpSession] + $this->otpResponseData($otpData));
    }

    protected function otpResponseData(array $otpData): array
    {
        return [
            'delivery_method' => $otpData['delivery_method'],
            'delivery_target' => $this->loginService->maskOtpDeliveryTarget($otpData['delivery_method'], $otpData['delivery_target']),
            'expires_in' => max((int) $otpData['expires_at'] - now()->timestamp, 0),
            'resend_in' => max((int) $otpData['resend_available_at'] - now()->timestamp, 0),
        ];
    }

    protected function otpCacheKey(string $otpSession): string
    {
        return 'mobile_login_otp:'.hash('sha256', $otpSession);
    }
}
