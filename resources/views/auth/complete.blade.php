<h2>{{ __('ui.payment_complete') }}</h2>
<p><strong>{{ __('ui.account_ref') }}</strong> {{ $account_ref }}</p>
<p><strong>{{ __('ui.mpesa_code') }}</strong> {{ $mpesa_code }}</p>
<p><strong>{{ __('ui.amount_2') }}</strong> {{ __('ui.kes') }} {{ $amount }}</p>
<a href="{{ route('register') }}">{{ __('ui.proceed_to_finish_registration') }}</a>
