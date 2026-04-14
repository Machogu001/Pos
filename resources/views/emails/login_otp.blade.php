<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>{{ __('Your login OTP code') }}</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; color: #111827; background: #f9fafb; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 24px auto; padding: 24px; background: #ffffff; border: 1px solid #e5e7eb; border-radius: 12px; }
        .eyebrow { font-size: 12px; letter-spacing: .08em; text-transform: uppercase; color: #6b7280; font-weight: 700; }
        h1 { margin: 8px 0 12px; font-size: 22px; line-height: 1.2; }
        p { margin: 8px 0; font-size: 15px; line-height: 1.6; color: #374151; }
        .code { display: inline-block; margin: 18px 0; padding: 14px 20px; border-radius: 10px; background: #eef2ff; color: #1d4ed8; font-size: 28px; font-weight: 800; letter-spacing: .24em; }
        .footer { margin-top: 20px; font-size: 12px; color: #6b7280; }
        .muted { color: #6b7280; }
    </style>
</head>
<body>
    <div class="container">
        <div class="eyebrow">{{ $appName }}</div>
        <h1>{{ __('Your login OTP code') }}</h1>

        <p>
            {{ !empty($user?->first_name) ? __('Hello :name,', ['name' => $user->first_name]) : __('Hello,') }}
            {{ __('use the code below to finish signing in.') }}
        </p>

        <div class="code">{{ $otp }}</div>

        <p>{{ __('This code expires in 5 minutes.') }}</p>
        <p class="muted">{{ __('If you did not request this code, you can ignore this email.') }}</p>

        <div class="footer">
            <p>{{ config('app.name') }} • {{ url('/') }}</p>
        </div>
    </div>
</body>
</html>
