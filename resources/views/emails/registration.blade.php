<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>{{ __('business.business_created_succesfully') }}</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial; color: #333; }
        .container { max-width: 600px; margin: 24px auto; padding: 20px; border: 1px solid #eee; border-radius: 8px; }
        h1 { color: #1f2937; font-size: 20px; margin-bottom: 8px; }
        p { margin: 6px 0; }
        .footer { font-size: 12px; color: #6b7280; margin-top: 18px; }
        .badge { display:inline-block; padding:6px 8px; background:#10b981; color:white; border-radius:4px; font-weight:600 }
    </style>
</head>
<body>
    <div class="container">
        <h1>{{ __('business.business_created_succesfully') }}</h1>

        <p>{{ __('business.thank_you_for_registering') ?? 'Thank you for registering with us.' }}</p>

        <p><strong>{{ __('business.business_name') }}:</strong> {{ $business->name }}</p>
        <p><strong>{{ __('business.username') }}:</strong> {{ $user->username }}</p>

        <p><strong>{{ __('payment.registration_price') }}:</strong> Ksh {{ number_format($registrationPrice, 2) }}</p>

        @if(!empty($receipt))
            <p><strong>{{ __('payment.receipt') }}:</strong> {{ $receipt }}</p>
        @endif

        <div class="footer">
            <p>If you have questions, reply to this email or contact support.</p>
            <p class="muted">{{ config('app.name') }} • {{ url('/') }}</p>
        </div>
    </div>
</body>
</html>
