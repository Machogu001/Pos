<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Transaction;
use Carbon\Carbon;

class TransactionController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'subscription']);
    }
    
    public function index()
    {
        $transactions = Auth::user()->transactions()->latest()->paginate(10);
        return view('transactions.index', compact('transactions'));
    }
    
    public function store(Request $request)
    {
        // Additional check to ensure subscription is valid
        if (!$this->validateSubscriptionPeriod()) {
            return response()->json([
                'success' => false,
                'message' => 'Your subscription has expired. Please renew to perform transactions.'
            ], 403);
        }
        
        // Validate transaction request
        $request->validate([
            'amount' => 'required|numeric|min:1',
            'type' => 'required|in:deposit,withdrawal,transfer',
            'description' => 'nullable|string|max:255'
        ]);
        
        // Process the transaction
        try {
            $transaction = new Transaction();
            $transaction->user_id = Auth::id();
            $transaction->amount = $request->amount;
            $transaction->type = $request->type;
            $transaction->description = $request->description;
            $transaction->status = 'completed';
            $transaction->save();
            
            return response()->json([
                'success' => true,
                'message' => 'Transaction completed successfully',
                'transaction' => $transaction
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Transaction failed: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Validate that the user's subscription is active for the current period
     */
    private function validateSubscriptionPeriod()
    {
        $user = Auth::user();
        
        $activeSubscription = $user->subscriptions()
            ->where('status', 'active')
            ->where('start_date', '<=', Carbon::now())
            ->where('end_date', '>', Carbon::now())
            ->first();
            
        return !is_null($activeSubscription);
    }
}