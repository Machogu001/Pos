<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>M-Pesa Payment</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background-color: #f0f2f5;
            padding: 40px;
        }
        .container {
            max-width: 400px;
            <!DOCTYPE html>
            <html lang="en">
            <head>
                <meta charset="UTF-8">
                <title>M-Pesa Payment</title>
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
                    <h3 class="text-center mb-4">M-Pesa Payment</h3>

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
                            <label for="phone_number" class="form-label">Phone Number</label>
                            <input type="text" id="phone_number" name="phone_number" class="form-control" placeholder="e.g. 254712345678, 0712345678, or 0112345678" pattern="^(?:\+?254|0)(?:7|1)\d{8}$" required title="Use 254712345678, 0712345678, or 0112345678" value="{{ old('phone_number') }}">
                        </div>

                        <div class="mb-3">
                            <label for="amount" class="form-label">Amount (KES)</label>
                            <input type="number" id="amount" name="amount" value="500" readonly class="form-control">
                        </div>

                        <div class="mb-3">
                            <label for="note" class="form-label">Note (Optional)</label>
                            <textarea id="note" name="note" rows="3" class="form-control" placeholder="Enter a note (optional)">{{ old('note') }}</textarea>
                        </div>

                        <button type="submit" class="btn btn-success w-100">Pay Now</button>

                        <p class="note text-center mt-3">You will receive an M-Pesa prompt. Enter your PIN to complete the payment.</p>
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

            <label for="amount">Amount (KES)</label>
            <input
                type="number"
                id="amount"
                name="amount"
                value="500"
                readonly
            >

            <label for="note">Note (Optional)</label>
            <textarea
                id="note"
                name="note"
                rows="3"
                placeholder="Enter a note or reason (optional)"
            ></textarea>

            <button type="submit">Pay Now</button>

            <div class="note">
                You will receive an M-Pesa prompt. Enter your PIN to complete the payment.
            </div>
        </form>
    </div>
</body>
</html>
