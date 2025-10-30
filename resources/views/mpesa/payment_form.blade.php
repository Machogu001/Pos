<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>M-Pesa Payment</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap CDN (Optional but improves UI) -->
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
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
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

        <!-- Alert Messages -->
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @elseif(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <!-- Validation Errors -->
        @if($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('business.payment.initiate') }}">
            @csrf

            <div class="mb-3">
                <label for="phone_number" class="form-label">Phone Number</label>
                <input type="text" id="phone_number" name="phone_number"
                       class="form-control" placeholder="e.g. 2547XXXXXXXX"
                       pattern="^2547\d{8}$" required
                       title="Use format like 2547XXXXXXXX" value="{{ old('phone_number') }}">
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

            <p class="note text-center mt-3">
                You will receive an M-Pesa prompt. Enter your PIN to complete the payment.
            </p>
        </form>
    </div>

    <!-- Optional JS for Bootstrap -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
