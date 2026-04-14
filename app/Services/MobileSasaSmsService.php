<?php

namespace App\Services;

use App\User;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class MobileSasaSmsService
{
    protected $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function sendLoginOtp(string $phoneNumber, string $otp, ?User $user = null): bool
    {
        $senderId = trim((string) config('services.mobilesasa.sender_id'));
        $token = trim((string) config('services.mobilesasa.token'));
        $baseUrl = rtrim((string) config('services.mobilesasa.base_url', 'https://api.mobilesasa.com/v1'), '/');

        if (empty($senderId) || empty($token) || empty($phoneNumber) || empty($otp)) {
            return false;
        }

        $message = 'Your login OTP is '.$otp.'. It expires in 5 minutes.';

        $appHost = parse_url((string) config('app.url'), PHP_URL_HOST);
        if (! empty($appHost)) {
            $message .= "\n\n@{$appHost} #{$otp}";
        }

        if (! empty($user?->first_name)) {
            $message = 'Hello '.$user->first_name.', '.$message;
        }

        return $this->sendMessage($phoneNumber, $message, [
            'phone' => $phoneNumber,
            'user_id' => $user?->id,
        ], 'MobileSasa OTP delivery failed');
    }

    public function sendRegistrationResumeInstructions(string $phoneNumber, string $accountReference, string $resumeUrl): bool
    {
        if (empty($phoneNumber) || empty($accountReference) || empty($resumeUrl)) {
            return false;
        }

        $message = sprintf(
            'BreMac registration payment received. Resume using Ref %s or open %s . This link stops working after the payment is used.',
            $accountReference,
            $resumeUrl
        );

        return $this->sendMessage($phoneNumber, $message, [
            'phone' => $phoneNumber,
            'account_reference' => $accountReference,
        ], 'MobileSasa registration resume SMS failed');
    }

    protected function sendMessage(string $phoneNumber, string $message, array $context = [], string $failureLog = 'MobileSasa SMS delivery failed'): bool
    {
        $senderId = trim((string) config('services.mobilesasa.sender_id'));
        $token = trim((string) config('services.mobilesasa.token'));
        $baseUrl = rtrim((string) config('services.mobilesasa.base_url', 'https://api.mobilesasa.com/v1'), '/');

        if (empty($senderId) || empty($token) || empty($phoneNumber) || empty($message)) {
            return false;
        }

        try {
            $response = $this->client->post($baseUrl.'/send/message', [
                'headers' => [
                    'Authorization' => 'Bearer '.$token,
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'senderID' => $senderId,
                    'message' => $message,
                    'phone' => $phoneNumber,
                ],
                'timeout' => 15,
            ]);

            return $response->getStatusCode() >= 200 && $response->getStatusCode() < 300;
        } catch (\Throwable $exception) {
            Log::warning($failureLog, array_merge($context, [
                'message' => $exception->getMessage(),
            ]));

            return false;
        }
    }
}