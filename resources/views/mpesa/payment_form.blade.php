<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>M-Pesa Payment</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap CDN (Optional but improves UI) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
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
                    margin: auto;
                    background: white;
                    padding: 30px;
                    border-radius: 12px;
                    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
                }

                h2 {
                    text-align: center;
                    margin-bottom: 25px;
                }

                label {
                    font-weight: bold;
                }

                input[type="text"],
                input[type="number"],
                textarea {
                    width: 100%;
                    padding: 10px;
                    margin-top: 6px;
                    margin-bottom: 16px;
                    border: 1px solid #ccc;
                    border-radius: 6px;
                }

                input[readonly] {
                    background-color: #f9f9f9;
                }

                button {
                    background-color: #28a745;
                    color: white;
                    padding: 12px;
                    width: 100%;
                    border: none;
                    border-radius: 6px;
                    font-size: 16px;
                    cursor: pointer;
                }

                button:hover {
                    background-color: #218838;
                }

                .note {
                    font-size: 12px;
                    color: #666;
                    text-align: center;
                    margin-top: 10px;
                }
            </style>
        </head>
        <body>
            <div class="container">
                <h2>M-Pesa Payment</h2>

                @if (session('success') || session('error'))
                    <script>
                        document.addEventListener('DOMContentLoaded', function () {
                            if (typeof window.showToast === 'function') {
                                @if (session('success'))
                                    window.showToast('success', @json(session('success')));
                                @elseif (session('error'))
                                    window.showToast('error', @json(session('error')));
                                @endif
                            } else {
                                alert(@json(session('success') ?? session('error')));
                            }
                        });
                    </script>
                @endif

                <form method="POST" action="{{ route('mpesa.initiate') }}">
                    @csrf

                    <label for="phone_number">Phone Number</label>
                    <input type="text" id="phone_number" name="phone_number" placeholder="e.g. 254712345678, 0712345678, or 0112345678" required pattern="^(?:\+?254|0)(?:7|1)\d{8}$" title="Use 254712345678, 0712345678, or 0112345678">

                    <label for="amount">Amount (KES)</label>
                    <input type="number" id="amount" name="amount" value="500" readonly>

                    <label for="note">Note (Optional)</label>
                    <textarea id="note" name="note" rows="3" placeholder="Enter a note or reason (optional)"></textarea>

                    <button type="submit">Pay Now</button>

                    <div class="note">You will receive an M-Pesa prompt. Enter your PIN to complete the payment.</div>
                </form>
            </div>
        </body>
        </html>
