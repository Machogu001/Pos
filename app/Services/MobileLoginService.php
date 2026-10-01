<?php

namespace App\Services;

use App\Mail\LoginOtpMail;
use App\Services\MobileSasaSmsService;
use App\User;
use App\Utils\BusinessUtil;
use App\Utils\ModuleUtil;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;

class MobileLoginService
{
    public const OTP_DELIVERY_SMS = 'sms';
    public const OTP_DELIVERY_EMAIL = 'email';
    public const OTP_RESEND_COOLDOWN_SECONDS = 59;
    public const OTP_TTL_SECONDS = 300;
    public const OTP_MAX_ATTEMPTS = 5;

    public function __construct(
        protected BusinessUtil $businessUtil,
        protected ModuleUtil $moduleUtil,
        protected MobileSasaSmsService $smsService
    ) {
    }

    public function restrictionMessage(User $user): ?string
    {
        if (! $user->isSuperAdmin() && ! optional($user->business)->is_active) {
            return __('lang_v1.business_inactive');
        }

        if ($user->status !== 'active') {
            return __('lang_v1.user_inactive');
        }

        if (! $user->allow_login) {
            return __('lang_v1.login_not_allowed');
        }

        if (($user->user_type === 'user_customer') && ! $this->moduleUtil->hasThePermissionInSubscription($user->business_id, 'crm_module')) {
            return __('lang_v1.business_dont_have_crm_subscription');
        }

        return null;
    }

    public function otpLoginEnabled(User $user): bool
    {
        return ! empty($user->otp_login_enabled);
    }

    public function normalizeOtpDeliveryMethod(?string $deliveryMethod): string
    {
        return in_array(strtolower((string) $deliveryMethod), [self::OTP_DELIVERY_EMAIL], true)
            ? self::OTP_DELIVERY_EMAIL
            : self::OTP_DELIVERY_SMS;
    }

    public function resolveOtpDeliveryTarget(User $user, string $deliveryMethod): ?string
    {
        if ($deliveryMethod === self::OTP_DELIVERY_EMAIL) {
            $email = trim((string) $user->email);

            return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null;
        }

        return $this->normalizePhoneNumber($user->contact_number ?? null);
    }

    public function maskOtpDeliveryTarget(string $deliveryMethod, ?string $deliveryTarget): string
    {
        $deliveryTarget = (string) $deliveryTarget;
        if ($deliveryMethod === self::OTP_DELIVERY_EMAIL) {
            return $this->maskEmailAddress($deliveryTarget);
        }

        return $this->maskPhoneNumber($deliveryTarget);
    }

    public function sendOtpCode(string $deliveryMethod, string $deliveryTarget, string $otp, ?User $user = null): array
    {
        if ($deliveryMethod === self::OTP_DELIVERY_EMAIL) {
            try {
                Mail::to($deliveryTarget)->send(new LoginOtpMail($otp, $user));

                return ['sent' => true, 'method' => self::OTP_DELIVERY_EMAIL, 'target' => $deliveryTarget];
            } catch (\Throwable $exception) {
                Log::warning('Mobile login OTP email delivery failed', [
                    'email' => $deliveryTarget,
                    'user_id' => $user?->id,
                    'message' => $exception->getMessage(),
                ]);

                $smsTarget = $user ? $this->resolveOtpDeliveryTarget($user, self::OTP_DELIVERY_SMS) : null;
                if (! empty($smsTarget) && $this->smsService->sendLoginOtp($smsTarget, $otp, $user)) {
                    return ['sent' => true, 'method' => self::OTP_DELIVERY_SMS, 'target' => $smsTarget];
                }

                return ['sent' => false, 'method' => self::OTP_DELIVERY_EMAIL, 'target' => $deliveryTarget];
            }
        }

        return [
            'sent' => $this->smsService->sendLoginOtp($deliveryTarget, $otp, $user),
            'method' => self::OTP_DELIVERY_SMS,
            'target' => $deliveryTarget,
        ];
    }

    public function logAuthenticationEvent(Request $request, User $user, string $action): void
    {
        // Auditing must never block sign-in/out, and the public auth routes run without a session.
        try {
            if (! $request->hasSession()) {
                $session = app('session')->driver('array');
                $session->start();
                $request->setLaravelSession($session);
            }

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
        } catch (\Throwable $exception) {
            Log::warning('Mobile '.$action.' activity log failed', [
                'user_id' => $user->id,
                'message' => $exception->getMessage(),
            ]);
        }
    }

    protected function updateLastLoginDetails(User $user, Request $request): void
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

    public function normalizePhoneNumber($phoneNumber): ?string
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

    protected function maskEmailAddress(?string $email): string
    {
        $email = trim((string) $email);
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $email;
        }

        [$localPart, $domain] = explode('@', $email, 2);
        $maskedLocalPart = strlen($localPart) <= 2
            ? str_repeat('*', strlen($localPart))
            : substr($localPart, 0, 1).str_repeat('*', max(strlen($localPart) - 2, 1)).substr($localPart, -1);

        return $maskedLocalPart.'@'.$domain;
    }

    protected function maskPhoneNumber($phoneNumber): string
    {
        $phoneNumber = (string) $phoneNumber;
        if (strlen($phoneNumber) <= 4) {
            return str_repeat('*', strlen($phoneNumber));
        }

        return substr($phoneNumber, 0, 2).str_repeat('*', max(strlen($phoneNumber) - 4, 1)).substr($phoneNumber, -2);
    }
}
