<?php

namespace App\Http\Controllers;

use App\Subscription;
use Illuminate\Http\Request;
use PDF;
use Illuminate\Support\Facades\Auth;
use App\Transaction;
use App\Contact;
use App\Utils\TransactionUtil;

class SubscriptionInvoiceController extends Controller
{
    public function downloadInvoice($id)
    {
        $subscription = Subscription::with('user')->findOrFail($id);

        // Authorization: allow owner or admin
        $user = Auth::user();
        $isAdmin = $user->role === 'admin';
        
        if ($user->id !== $subscription->user_id && !$isAdmin) {
            abort(403, 'Unauthorized action.');
        }

    $settings = \App\AdminSetting::first();
    $pdf = PDF::loadView('subscriptions.invoice_pdf', compact('subscription', 'settings'));

        $fileName = 'subscription_invoice_'.$subscription->id.'.pdf';

        return $pdf->download($fileName);
    }

    /**
     * Download account statement for subscription (optionally accept start/end as query params)
     */
    public function downloadStatement(Request $request, $id)
    {
        $user = Auth::user();
        $isAdmin = $user->role === 'admin';
        
        // Regular users can ONLY download their own statements - ignore URL parameter
        if (!$isAdmin) {
            // Force use of authenticated user's latest subscription
            $subscription = Subscription::where('user_id', $user->id)
                ->with('user')
                ->latest()
                ->firstOrFail();
            $targetUserId = $user->id;
        } else {
            // Admin can download for any user
            $subscription = Subscription::with('user')->findOrFail($id);
            $targetUserId = $subscription->user_id;
            
            // If admin selected a different user, load that user's data instead
            if ($request->has('user_id') && $request->user_id) {
                $targetUserId = $request->user_id;
                $targetUser = \App\User::findOrFail($targetUserId);
                // Get the latest subscription for the selected user
                $subscription = Subscription::where('user_id', $targetUserId)->with('user')->latest()->firstOrFail();
            }
        }

        $start = $request->query('start') ? \Carbon\Carbon::parse($request->query('start')) : $subscription->start_date;
        $end = $request->query('end') ? \Carbon\Carbon::parse($request->query('end')) : $subscription->end_date;

        // Collect Mpesa payments for the target user in the period
        $payments = \App\MpesaPayment::where('user_id', $targetUserId)
                    ->whereBetween('created_at', [$start, $end])
                    ->orderBy('created_at', 'asc')
                    ->get();

        // Collect business-side subscription charge transactions for the target user.
        $invoices = collect();
        try {
            if ($subscription->user && $subscription->user->business_id) {
                $businessId = $subscription->user->business_id;
                
                // Get all subscription IDs for the target user to match invoices
                $userSubscriptionIds = Subscription::where('user_id', $targetUserId)->pluck('id')->toArray();
                
                if (!empty($userSubscriptionIds)) {
                    $invoices = Transaction::where('business_id', $businessId)
                        ->where(function ($query) {
                            $query->where(function ($expenseQuery) {
                                $expenseQuery->where('type', 'expense')
                                    ->where('sub_type', 'subscription_fee');
                            })->orWhere(function ($legacyQuery) {
                                $legacyQuery->where('type', 'sell')
                                    ->where('sub_type', 'subscription_invoice');
                            });
                        })
                        ->where(function($q) use ($userSubscriptionIds) {
                            foreach ($userSubscriptionIds as $subId) {
                                $q->orWhere('subscription_no', 'like', 'sub_invoice_' . $subId . '_%');
                                $q->orWhere('subscription_no', 'like', 'sub_expense_' . $subId . '_%');
                            }
                        })
                        ->whereBetween('transaction_date', [$start, $end])
                        ->orderBy('transaction_date', 'asc')
                        ->get();
                }
            }
        } catch (\Exception $e) {
            // ignore and continue with empty invoices
            $invoices = collect();
        }

    $settings = \App\AdminSetting::first();
    $pdf = PDF::loadView('subscriptions.statement_pdf', compact('subscription', 'payments', 'invoices', 'start', 'end', 'settings'));
        $fileName = 'subscription_statement_'.$subscription->id.'.pdf';

        return $pdf->download($fileName);
    }

    /**
     * Download a specific transaction invoice (by transaction id).
     */
    public function downloadTransactionInvoice($transactionId)
    {
        $tx = Transaction::with('contact')->findOrFail($transactionId);

        $user = Auth::user();
        $isAdmin = $user->role === 'admin';
        
        if (!$isAdmin) {
            $allowed = false;
            // First: try strict match against the transaction contact (email/mobile)
            if ($tx->contact) {
                $c = $tx->contact;
                if (!empty($user->email) && $c->email === $user->email) $allowed = true;
                if (!empty($user->phone) && $c->mobile === $user->phone) $allowed = true;
            }

            // Second: if not allowed yet, check if this transaction is a subscription-linked
            // charge belonging to the current user.
            if (!$allowed && !empty($tx->subscription_no) && (strpos($tx->subscription_no, 'sub_invoice_') === 0 || strpos($tx->subscription_no, 'sub_expense_') === 0)) {
                try {
                    $parts = explode('_', $tx->subscription_no);
                    if (isset($parts[2]) && is_numeric($parts[2])) {
                        $subId = (int) $parts[2];
                        $sub = \App\Subscription::find($subId);
                        if ($sub && $sub->user_id === $user->id) {
                            $allowed = true;
                        }
                    }
                } catch (\Exception $e) {
                    // ignore parsing errors
                }
            }

            if (!$allowed) abort(403, 'Unauthorized action.');
        }

        // Render PDF using the same sample layout style
        $settings = \App\AdminSetting::first();
        $pdf = \PDF::loadView('subscriptions.transaction_invoice_pdf', compact('tx', 'settings'));
        $fileName = 'INVOICE-' . ($tx->invoice_no ?? $tx->ref_no ?? $tx->id) . '.pdf';

        return $pdf->download($fileName);
    }

    /**
     * Download statements for multiple subscriptions as a ZIP
     * Accepts: subscription_ids[] OR user_id, start, end
     */
    public function downloadBulkStatements(Request $request)
    {
        $this->authorize('admin');

    $subscriptionIds = $request->input('subscription_ids', []);
        $userId = $request->input('user_id');
        $start = $request->input('start') ? \Carbon\Carbon::parse($request->input('start')) : null;
        $end = $request->input('end') ? \Carbon\Carbon::parse($request->input('end')) : null;

        // Normalize subscription_ids: accept JSON string, array of ids or a single id
        if (is_string($subscriptionIds)) {
            $decoded = json_decode($subscriptionIds, true);
            if (is_array($decoded)) {
                $subscriptionIds = $decoded;
            } elseif (!empty($subscriptionIds)) {
                // maybe comma separated
                $subscriptionIds = array_filter(array_map('trim', explode(',', $subscriptionIds)));
            } else {
                $subscriptionIds = [];
            }
        }

        if (empty($subscriptionIds) && $userId) {
            $subscriptionIds = Subscription::where('user_id', $userId)->pluck('id')->toArray();
        }

        if (empty($subscriptionIds)) {
            return back()->with('error', 'No subscriptions selected');
        }

        $tempDir = storage_path('app/temp/subscription_statements/' . uniqid());
        if (!\File::exists($tempDir)) {
            \File::makeDirectory($tempDir, 0755, true);
        }

        $files = [];

        foreach ($subscriptionIds as $sid) {
            $sid = is_array($sid) && isset($sid['id']) ? $sid['id'] : $sid;
            $subscription = Subscription::with('user')->find($sid);
            if (!$subscription) continue;
            $startRange = $start ?: $subscription->start_date;
            $endRange = $end ?: $subscription->end_date;

            $payments = [];
            if (method_exists($subscription, 'payments')) {
                $payments = $subscription->payments()->whereBetween('created_at', [$startRange, $endRange])->get();
            }

            // Use variable names expected by the view: start, end
            $settings = \App\AdminSetting::first();
            // Manually pass start/end to the view by rendering with data array
            $pdf = PDF::loadView('subscriptions.statement_pdf', ['subscription' => $subscription, 'payments' => $payments, 'invoices' => collect(), 'start' => $startRange, 'end' => $endRange, 'settings' => $settings]);
            $fileName = 'subscription_'.$subscription->id.'_statement.pdf';
            $path = $tempDir . '/' . $fileName;
            $pdf->save($path);
            $files[] = $path;
        }

        if (empty($files)) {
            return back()->with('error', 'No statements generated');
        }

        $zipName = 'subscription_statements_'.date('Y_m_d_H_i_s').'.zip';
        $zipPath = storage_path('app/temp/' . $zipName);

        $zip = new \ZipArchive();
        if ($zip->open($zipPath, \ZipArchive::CREATE) !== true) {
            return back()->with('error', 'Unable to create ZIP archive');
        }

        foreach ($files as $file) {
            $zip->addFile($file, basename($file));
        }
        $zip->close();

        // Clean up temp dir files
        foreach ($files as $file) {
            @unlink($file);
        }
        @rmdir($tempDir);

        return response()->download($zipPath)->deleteFileAfterSend(true);
    }
}
