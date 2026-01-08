<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PaymentAccount; // Make sure this model exists

class PaymentAccountController extends Controller
{
    /**
     * Display a listing of payment accounts.
     */
    public function index()
    {
        $accounts = PaymentAccount::latest()->paginate(10);
        return view('payment-accounts.index', compact('accounts'));
    }

    /**
     * Show the form for creating a new payment account.
     */
    public function create()
    {
        return view('payment-accounts.create');
    }

    /**
     * Store a newly created payment account.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'account_name' => 'required|string|max:255',
            'account_number' => 'required|string|unique:payment_accounts',
            'bank_name' => 'nullable|string|max:255',
            'is_active' => 'boolean'
        ]);

        PaymentAccount::create($validated);

        return redirect()->route('payment-account.index')
                         ->with('success', 'Payment account created successfully.');
    }

    /**
     * Display the specified payment account.
     */
    public function show(PaymentAccount $paymentAccount)
    {
        return view('payment-accounts.show', compact('paymentAccount'));
    }

    /**
     * Show the form for editing the specified payment account.
     */
    public function edit(PaymentAccount $paymentAccount)
    {
        return view('payment-accounts.edit', compact('paymentAccount'));
    }

    /**
     * Update the specified payment account.
     */
    public function update(Request $request, PaymentAccount $paymentAccount)
    {
        $validated = $request->validate([
            'account_name' => 'required|string|max:255',
            'account_number' => 'required|string|unique:payment_accounts,account_number,'.$paymentAccount->id,
            'bank_name' => 'nullable|string|max:255',
            'is_active' => 'boolean'
        ]);

        $paymentAccount->update($validated);

        return redirect()->route('payment-account.index')
                         ->with('success', 'Payment account updated successfully.');
    }

    /**
     * Remove the specified payment account.
     */
    public function destroy(PaymentAccount $paymentAccount)
    {
        $paymentAccount->delete();
        return redirect()->route('payment-account.index')
                         ->with('success', 'Payment account deleted successfully.');
    }
}