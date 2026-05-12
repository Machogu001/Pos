<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ __('ui.m_pesa_payment') }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }

        .payment-container {
            max-width: 500px;
            margin: 60px auto;
            background: #fff;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        .note {
            font-size: 0.9rem;
            color: #6c757d;
        }

        .form-control:read-only {
            background-color: #e9ecef;
        }
    </style>
</head>
<body>
    <div class="container payment-container">
        <h3 class="text-center mb-4">{{ __('ui.m_pesa_payment') }}</h3>

        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('business.payment.initiate') }}">
            @csrf

            <div class="mb-3">
                <label for="phone_number" class="form-label">{{ __('ui.phone_number') }}</label>
                <input type="text" id="phone_number" name="phone_number" class="form-control" placeholder="{{ __('ui.e_g_254712345678_0712345678_or_0112345678') }}" pattern="^(?:\+?254|0)(?:7|1)\d{8}$" required title="{{ __('ui.use_254712345678_0712345678_or_0112345678') }}" value="{{ old('phone_number') }}">
            </div>

            <div class="mb-3">
                <label for="amount" class="form-label">{{ __('ui.amount_kes') }}</label>
                <input type="number" id="amount" name="amount" value="500" readonly class="form-control">
            </div>

            <div class="mb-3">
                <label for="note" class="form-label">{{ __('ui.note_optional') }}</label>
                <textarea id="note" name="note" rows="3" class="form-control" placeholder="{{ __('ui.enter_a_note_optional') }}">{{ old('note') }}</textarea>
            </div>

            <button type="submit" class="btn btn-success w-100">{{ __('ui.pay_now') }}</button>

            <p class="note text-center mt-3">{{ __('ui.you_will_receive_an_m_pesa_prompt_enter_your_pin_to_complete_the_payment') }}</p>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        (function () {
            function showToast(message, type) {
                var toast = document.createElement('div');
                toast.style.position = 'fixed';
                toast.style.right = '20px';
                toast.style.top = '20px';
                toast.style.zIndex = '9999';
                toast.style.padding = '12px 16px';
                toast.style.borderRadius = '10px';
                toast.style.color = '#fff';
                toast.style.boxShadow = '0 12px 30px rgba(0,0,0,0.18)';
                toast.style.background = type === 'error' ? '#dc3545' : '#198754';
                toast.textContent = message;
                document.body.appendChild(toast);

                setTimeout(function () {
                    toast.remove();
                }, 3500);
            }

            document.addEventListener('DOMContentLoaded', function () {
                @if (session('success'))
                    showToast(@json(session('success')), 'success');
                @endif

                @if (session('error'))
                    showToast(@json(session('error')), 'error');
                @endif
            });
        })();
    </script>
</body>
</html>
