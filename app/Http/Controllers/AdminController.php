<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Artisan;
use App\User;
use App\Subscription;
use App\AdminSetting;
use App\Business;
use App\BusinessLocation;
use App\Services\SellPostingAuditService;

class AdminController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    // ============================
    // DASHBOARD
    // ============================
    public function index()
{
    $user = auth()->user();
    $settings = AdminSetting::first() ?? new AdminSetting();
        $accountingBackfillStatus = $this->buildAccountingBackfillStatus($settings);
        $stockCostingBackfillStatus = $this->buildStockCostingBackfillStatus($settings);
    $auditBusinessId = $user->role === 'admin' ? null : optional($user->business)->id;
    $sellPostingAuditSummary = app(SellPostingAuditService::class)->summarize($auditBusinessId);

    // Configurable limits (default 5)
    $subscriptionsLimit = $settings->recent_subscriptions_limit ?? 5;
    $usersLimit = $settings->recent_users_limit ?? 5;

    if ($user->role === 'admin') {
        // Fetch users with their business and latest successful M-Pesa payment
        $users = User::with(['business', 'mpesaPayments' => function($query) {
            $query->where('result_code', 0) // Successful M-Pesa transaction (result_code 0 means success)
                  ->orderBy('created_at', 'desc')
                  ->limit(1000);
        }])
        ->where('role', '!=', 'admin')
        ->get();

        // Process each user to get their phone number from M-Pesa payments
        $users->each(function($user) {
            $user->phone = optional($user->mpesaPayments->first())->phone_number ?? 'N/A';
        });

        $recentSubscriptions = Subscription::with(['user.business'])
            ->latest()
            ->take($subscriptionsLimit)
            ->get();

        $recentUsers = $users
            ->sortByDesc('created_at')
            ->take($usersLimit);

        $totalUsers = $users->count();
        $activeUsers = $users->where('status', 'active')->count();
        $inactiveUsers = $users->where('status', 'inactive')->count();
        $terminatedUsers = $users->where('status', 'terminated')->count();

        $activeSubscriptions = Subscription::where('status', 'active')->count();
        $pendingSubscriptions = Subscription::where('status', 'pending')->count();
        $monthlyRevenue = Subscription::where('status', 'active')
            ->whereMonth('created_at', now()->month)
            ->sum('amount');

        $schedulerBusinesses = Business::select('id', 'name')->orderBy('name')->get();
        $schedulerLocations = BusinessLocation::select('id', 'business_id', 'name')->orderBy('name')->get();

        return view('admin.dashboard', compact(
            'users', 'recentSubscriptions', 'recentUsers', 'settings',
            'totalUsers', 'activeUsers', 'inactiveUsers', 'terminatedUsers',
            'activeSubscriptions', 'pendingSubscriptions', 'monthlyRevenue',
            'sellPostingAuditSummary', 'accountingBackfillStatus', 'stockCostingBackfillStatus',
            'schedulerBusinesses', 'schedulerLocations'
        ));
    }

    // Normal user sees only their business users
    $business = $user->business;

    $users = $business
        ? User::with(['subscriptions', 'business', 'mpesaPayments' => function($query) {
            $query->where('result_code', 0) // Successful M-Pesa transaction
                  ->orderBy('created_at', 'desc')
                  ->limit(1);
        }])
            ->where('business_id', $business->id)
            ->where('role', '!=', 'admin')
            ->get()
        : collect();

    // Process each user to get their phone number from M-Pesa payments
    $users->each(function($user) {
        $user->phone = optional($user->mpesaPayments->first())->phone_number ?? 'N/A';
    });

    $recentSubscriptions = $users->pluck('subscriptions')->flatten()
        ->sortByDesc('created_at')
        ->take($subscriptionsLimit);

    $recentUsers = $users
        ->sortByDesc('created_at')
        ->take($usersLimit);

    $totalUsers = $users->count();
    $activeUsers = $users->where('status', 'active')->count();
    $inactiveUsers = $users->where('status', 'inactive')->count();
    $terminatedUsers = $users->where('status', 'terminated')->count();

    $activeSubscriptions = $users->pluck('subscriptions')->flatten()
        ->where('status', 'active')->count();
    $pendingSubscriptions = $users->pluck('subscriptions')->flatten()
        ->where('status', 'pending')->count();
    $monthlyRevenue = $users->pluck('subscriptions')->flatten()
        ->where('status', 'active')
        ->where('created_at', '>=', now()->startOfMonth())
        ->sum('amount');

    $schedulerBusinesses = Business::select('id', 'name')->orderBy('name')->get();
    $schedulerLocations = BusinessLocation::select('id', 'business_id', 'name')->orderBy('name')->get();

    return view('admin.dashboard', compact(
        'users', 'recentSubscriptions', 'recentUsers', 'settings',
        'totalUsers', 'activeUsers', 'inactiveUsers', 'terminatedUsers',
        'activeSubscriptions', 'pendingSubscriptions', 'monthlyRevenue',
        'sellPostingAuditSummary', 'accountingBackfillStatus', 'stockCostingBackfillStatus',
        'schedulerBusinesses', 'schedulerLocations'
    ));
}

    /**
     * Build user-friendly scheduler status for dashboard display.
     */
    protected function buildAccountingBackfillStatus(AdminSetting $settings): array
    {
        $frequency = $settings->accounting_backfill_frequency ?? 'hourly';
        $time = $settings->accounting_backfill_time ?? '02:00';
        $nextRun = null;
        $now = now();

        switch ($frequency) {
            case 'every_fifteen_minutes':
                $nextRun = $now->copy()->addMinutes(15 - ($now->minute % 15))->startOfMinute();
                break;
            case 'every_thirty_minutes':
                $nextRun = $now->copy()->addMinutes(30 - ($now->minute % 30))->startOfMinute();
                break;
            case 'daily':
                if (! preg_match('/^\d{2}:\d{2}$/', (string) $time)) {
                    $time = '02:00';
                }
                [$hour, $minute] = array_map('intval', explode(':', $time));
                $candidate = $now->copy()->setTime($hour, $minute, 0);
                $nextRun = $candidate->lessThanOrEqualTo($now) ? $candidate->addDay() : $candidate;
                break;
            case 'hourly':
            default:
                $nextRun = $now->copy()->addHour()->startOfHour();
                break;
        }

        return [
            'last_run' => $settings->accounting_backfill_last_run_at,
            'next_run' => $nextRun,
        ];
    }

    /**
     * Build user-friendly scheduler status for stock costing layer backfill display.
     */
    protected function buildStockCostingBackfillStatus(AdminSetting $settings): array
    {
        $frequency = $settings->stock_costing_backfill_frequency ?? 'daily';
        $time = $settings->stock_costing_backfill_time ?? '01:30';
        $nextRun = null;
        $now = now();

        switch ($frequency) {
            case 'every_fifteen_minutes':
                $nextRun = $now->copy()->addMinutes(15 - ($now->minute % 15))->startOfMinute();
                break;
            case 'every_thirty_minutes':
                $nextRun = $now->copy()->addMinutes(30 - ($now->minute % 30))->startOfMinute();
                break;
            case 'daily':
                if (! preg_match('/^\d{2}:\d{2}$/', (string) $time)) {
                    $time = '01:30';
                }
                [$hour, $minute] = array_map('intval', explode(':', $time));
                $candidate = $now->copy()->setTime($hour, $minute, 0);
                $nextRun = $candidate->lessThanOrEqualTo($now) ? $candidate->addDay() : $candidate;
                break;
            case 'hourly':
            default:
                $nextRun = $now->copy()->addHour()->startOfHour();
                break;
        }

        return [
            'last_run' => $settings->stock_costing_backfill_last_run_at,
            'next_run' => $nextRun,
        ];
    }

    public function fixSellPostings()
    {
        $this->authorize('admin');

        $user = auth()->user();
        $businessId = $user->role === 'admin' ? null : optional($user->business)->id;
        $result = app(SellPostingAuditService::class)->backfill($businessId);

        if ($result['initial_missing_count'] === 0) {
            return back()->with('success', 'No missing item-sell accounting transactions were found.');
        }

        if ($result['error_count'] > 0) {
            return back()->with(
                'error',
                'Sell posting repair completed with '.$result['error_count'].' error(s). Remaining missing COGS: '
                .$result['summary']['missing_cogs_count'].', inventory: '.$result['summary']['missing_inventory_count'].'.'
            );
        }

        return back()->with(
            'success',
            'Sell posting repair processed '.$result['processed_count'].' transaction(s). Remaining missing COGS: '
            .$result['summary']['missing_cogs_count'].', inventory: '.$result['summary']['missing_inventory_count'].'.'
        );
    }

    // ============================
    // USERS MANAGEMENT
    // ============================
    public function users(Request $request)
    {
        $this->authorize('admin');

        // Build query with filters
        $query = User::where('role', '!=', 'admin')
            ->withCount('subscriptions')
            ->with('business');

        // Apply status filter
        if ($request->has('status') && $request->status) {
            $query->where('transaction_status', 'completed') // Use transaction_status instead of result_code
      ->orderBy('created_at', 'desc')
      ->limit(1);
        }

        // Apply business filter
        if ($request->has('business_id') && $request->business_id) {
            $query->where('business_id', $request->business_id);
        }

        // Apply sorting
        switch ($request->get('sort', 'newest')) {
            case 'oldest':
                $query->orderBy('created_at', 'asc');
                break;
            case 'name':
                $query->orderBy('name', 'asc');
                break;
            case 'newest':
            default:
                $query->orderBy('created_at', 'desc');
                break;
        }

        // Get paginated results
        $perPage = $request->get('per_page', 10);
        $users = $query->paginate($perPage);

        // Manually load the latest subscription for each user
        $users->getCollection()->each(function ($user) {
            $user->latest_subscription = $user->subscriptions()->latest()->first();
        });

        $stats = [
            'total' => User::where('role', '!=', 'admin')->count(),
            'active' => User::where('status', 'active')->count(),
            'inactive' => User::where('status', 'inactive')->count(),
            'terminated' => User::where('status', 'terminated')->count(),
        ];

        $businesses = Business::all();

        return view('admin.users', compact('users', 'stats', 'businesses'));
    }

    // ============================
    // DELETE USER
    // ============================
    public function destroy(User $user)
    {
        $this->authorize('admin');

        // Prevent deleting admin users
        if ($user->role === 'admin') {
            if (request()->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete admin users.',
                ], 403);
            }
            return back()->with('error', 'Cannot delete admin users.');
        }

        // Delete user subscriptions first
        $user->subscriptions()->delete();

        // Delete the user
        $user->delete();

        if (request()->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'User deleted successfully.',
            ]);
        }

        return back()->with('success', 'User deleted successfully.');
    }

    // ============================
    // BULK ACTIONS FOR USERS
    // ============================
    public function bulkAction(Request $request)
    {
        $this->authorize('admin');

        $request->validate([
            'action' => 'required|in:activate,deactivate,terminate,delete',
            'users' => 'required|array',
            'users.*' => 'exists:users,id'
        ]);

        $userIds = $request->users;
        $action = $request->action;

        try {
            switch ($action) {
                case 'activate':
                    User::whereIn('id', $userIds)
                        ->where('role', '!=', 'admin')
                        ->update(['status' => 'active']);
                    $message = 'Users activated successfully.';
                    break;

                case 'deactivate':
                    User::whereIn('id', $userIds)
                        ->where('role', '!=', 'admin')
                        ->update(['status' => 'inactive']);
                    $message = 'Users deactivated successfully.';
                    break;

                case 'terminate':
                    User::whereIn('id', $userIds)
                        ->where('role', '!=', 'admin')
                        ->update(['status' => 'terminated']);
                    $message = 'Users terminated successfully.';
                    break;

                case 'delete':
                    // Delete subscriptions first for selected users
                    Subscription::whereIn('user_id', $userIds)->delete();
                    
                    // Prevent deleting admin users
                    User::whereIn('id', $userIds)
                        ->where('role', '!=', 'admin')
                        ->delete();
                    $message = 'Users deleted successfully.';
                    break;
            }

            return response()->json([
                'success' => true,
                'message' => $message,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to process bulk action: ' . $e->getMessage(),
            ], 500);
        }
    }

    // ============================
    // SUBSCRIPTIONS MANAGEMENT
    // ============================
    public function subscriptions()
    {
        $user = auth()->user();

        if ($user->role === 'admin') {
            $subscriptions = Subscription::with(['user', 'user.business'])
                ->latest()
                ->paginate(10);

            // Eager-load any pending MpesaPayment records referenced by subscriptions
            $pendingIds = $subscriptions->getCollection()->pluck('pending_mpesa_payment_id')->filter()->unique()->toArray();
            $mpesaMap = [];
            if (!empty($pendingIds)) {
                $mpesaPayments = \App\MpesaPayment::whereIn('id', $pendingIds)->get()->keyBy('id');
                foreach ($mpesaPayments as $id => $mp) {
                    $mpesaMap[$id] = $mp;
                }
            }

            // Attach a convenience property to each subscription for blade usage
            $subscriptions->getCollection()->each(function ($sub) use ($mpesaMap) {
                $sub->pending_mpesa = null;
                if (!empty($sub->pending_mpesa_payment_id) && isset($mpesaMap[$sub->pending_mpesa_payment_id])) {
                    $sub->pending_mpesa = $mpesaMap[$sub->pending_mpesa_payment_id];
                }
            });

            return view('admin.subscriptions', compact('subscriptions'));
        }

        $subscriptions = $user->subscriptions()->latest()->paginate(10);

        // Also attach pending mpesa payments for non-admin user view
        $pendingIds = $subscriptions->getCollection()->pluck('pending_mpesa_payment_id')->filter()->unique()->toArray();
        $mpesaMap = [];
        if (!empty($pendingIds)) {
            $mpesaPayments = \App\MpesaPayment::whereIn('id', $pendingIds)->get()->keyBy('id');
            foreach ($mpesaPayments as $id => $mp) {
                $mpesaMap[$id] = $mp;
            }
        }

        $subscriptions->getCollection()->each(function ($sub) use ($mpesaMap) {
            $sub->pending_mpesa = null;
            if (!empty($sub->pending_mpesa_payment_id) && isset($mpesaMap[$sub->pending_mpesa_payment_id])) {
                $sub->pending_mpesa = $mpesaMap[$sub->pending_mpesa_payment_id];
            }
        });

        return view('user.subscriptions', compact('subscriptions'));
    }

    public function mySubscriptions()
    {
        $user = auth()->user();
        $subscriptions = $user->subscriptions()->latest()->paginate(10);

        return view('user.subscriptions', compact('subscriptions'));
    }

    // ============================
    // SETTINGS
    // ============================
    public function updateSettings(Request $request)
    {
        $this->authorize('admin');

        $request->validate([
            'monthly_price' => 'required|numeric|min:0',
            'quarterly_price' => 'required|numeric|min:0',
            'yearly_price' => 'required|numeric|min:0',
            'registration_price' => 'nullable|numeric|min:0|max:500000',
            'grace_period_days' => 'required|integer|min:0',
            'recent_limit' => 'required|integer|min:1|max:100',
            // Company / invoice fields
            'company_name' => 'nullable|string|max:255',
            'company_contact_phone' => 'nullable|string|max:100',
            'company_contact_email' => 'nullable|email|max:255',
            // invoice_pin must start and end with a letter (e.g., P052182616N)
            'invoice_pin' => ['nullable', 'string', 'max:100', 'regex:/^[A-Za-z][A-Za-z0-9]*[A-Za-z]$/'],
            // Subscription-specific invoice sequence
            'subscription_invoice_prefix' => 'nullable|string|max:10',
            'subscription_invoice_next' => 'nullable|integer|min:0',
            // Subscription VAT percentage (0-100)
            'subscription_vat_percent' => 'nullable|numeric|min:0|max:100',
            // Rounding precision for subscription final total (0 = whole number)
            'subscription_round_precision' => 'nullable|integer|min:0|max:6',
            'invoice_footer' => 'nullable|string',
            'statement_footer' => 'nullable|string',
            'company_logo' => 'nullable|file|image|max:2048',
            'payroll_nssf_percent' => 'nullable|numeric',
            'payroll_shif_percent' => 'nullable|numeric',
            'payroll_housing_percent' => 'nullable|numeric',
            'payroll_tax_percent' => 'nullable|numeric',
            'payroll_personal_relief' => 'nullable|numeric',
            'payroll_tax_bands' => 'nullable|string',
            // eTIMS Integration fields
            'etims_api_url' => 'nullable|url|max:500',
            'etims_api_token' => 'nullable|string',
            'etims_branch_id' => 'nullable|string|max:10',
            'etims_auto_transmit' => 'nullable|boolean',
            'etims_transmit_subscriptions' => 'nullable|boolean',
            'etims_transmit_registrations' => 'nullable|boolean',
            'auto_close_register' => 'nullable|boolean',
            'auto_close_register_time' => 'nullable|date_format:H:i',
            'accounting_backfill_enabled' => 'nullable|boolean',
            'accounting_backfill_frequency' => 'nullable|in:every_fifteen_minutes,every_thirty_minutes,hourly,daily',
            'accounting_backfill_time' => 'nullable|date_format:H:i',
            'stock_costing_backfill_enabled' => 'nullable|boolean',
            'stock_costing_backfill_frequency' => 'nullable|in:every_fifteen_minutes,every_thirty_minutes,hourly,daily',
            'stock_costing_backfill_time' => 'nullable|date_format:H:i',
            'stock_costing_backfill_business_id' => [
                (
                    $request->boolean('stock_costing_backfill_enabled')
                    || $request->boolean('run_stock_costing_backfill_now')
                    || $request->boolean('run_stock_costing_backfill_dry_run')
                ) ? 'required' : 'nullable',
                'integer', 'min:1',
            ],
            'stock_costing_backfill_location_id' => [
                (
                    $request->boolean('stock_costing_backfill_enabled')
                    || $request->boolean('run_stock_costing_backfill_now')
                    || $request->boolean('run_stock_costing_backfill_dry_run')
                ) ? 'required' : 'nullable',
                'integer', 'min:1',
            ],
            'run_stock_costing_backfill_now' => 'nullable|boolean',
            'run_stock_costing_backfill_dry_run' => 'nullable|boolean',
        ]);

        $settings = AdminSetting::firstOrCreate([]);
        // handle logo upload separately
        if ($request->hasFile('company_logo')) {
            try {
                $path = $request->file('company_logo')->store('company_logos', 'public');
                // store public path
                $request->merge(['company_logo' => 'storage/' . $path]);
            } catch (\Exception $e) {
                // ignore upload errors; will be handled by validation in normal cases
            }
        }

        $settings->update($request->only([
            'monthly_price', 'quarterly_price', 'yearly_price',
            'registration_price',
            'auto_renewal', 'grace_period_days', 'recent_limit',
            'company_name', 'company_logo', 'company_contact_phone', 'company_contact_email', 'invoice_pin', 'invoice_footer', 'statement_footer',
            // subscription sequence fields
            'subscription_invoice_prefix', 'subscription_invoice_next', 'subscription_vat_percent', 'subscription_round_precision',
            // eTIMS fields
            'etims_api_url', 'etims_api_token', 'etims_branch_id', 'etims_auto_transmit', 'etims_transmit_subscriptions', 'etims_transmit_registrations',
            'auto_close_register',
            'auto_close_register_time',
            'accounting_backfill_enabled',
            'accounting_backfill_frequency',
            'accounting_backfill_time',
            'stock_costing_backfill_enabled',
            'stock_costing_backfill_frequency',
            'stock_costing_backfill_time',
            'stock_costing_backfill_business_id',
            'stock_costing_backfill_location_id',
        ]));

        // update payroll-related settings if present
        $payrollKeys = ['payroll_nssf_percent','payroll_shif_percent','payroll_housing_percent','payroll_tax_percent','payroll_personal_relief'];
        $updatePayroll = [];
        foreach ($payrollKeys as $k) {
            if ($request->has($k)) {
                $updatePayroll[$k] = $request->input($k);
            }
        }
        // allow saving payroll tax bands JSON
        if ($request->has('payroll_tax_bands')) {
            $updatePayroll['payroll_tax_bands'] = $request->input('payroll_tax_bands');
        }
        if (!empty($updatePayroll)) {
            $settings->update($updatePayroll);
        }

        if ($request->boolean('run_stock_costing_backfill_now') || $request->boolean('run_stock_costing_backfill_dry_run')) {
            $businessId = (int) ($settings->stock_costing_backfill_business_id ?? 0);
            $locationId = (int) ($settings->stock_costing_backfill_location_id ?? 0);
            $dryRun = $request->boolean('run_stock_costing_backfill_dry_run');

            if ($businessId < 1 || $locationId < 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please select Business and Location before running stock costing backfill manually.',
                ], 422);
            }

            $params = [
                '--business-id' => $businessId,
                '--location-id' => $locationId,
            ];
            if ($dryRun) {
                $params['--dry-run'] = true;
            }

            $exitCode = Artisan::call('stock:backfill-costing-layers', $params);

            if ($exitCode !== 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Stock costing backfill run failed. Please check logs/console output.',
                ], 500);
            }

            $output = (string) Artisan::output();
            $needsLayer = null;
            $layerQty = null;
            if (preg_match('/Rows needing layer backfill:\s*(\d+)/', $output, $m)) {
                $needsLayer = (int) $m[1];
            }
            if (preg_match('/Layer qty backfilled:\s*([0-9.]+)/', $output, $m)) {
                $layerQty = $m[1];
            }

            $runSummary = $dryRun
                ? 'Stock costing backfill dry-run completed.'
                : 'Manual stock costing backfill completed.';
            if ($needsLayer !== null && $layerQty !== null) {
                $runSummary .= ' Rows '.($dryRun ? 'needing fix' : 'fixed').': '.$needsLayer.', layer qty: '.$layerQty.'.';
            }

            return response()->json([
                'success' => true,
                'message' => $runSummary,
                'settings' => $settings->fresh(),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Settings updated successfully',
            'settings' => $settings->fresh()
        ]);
    }

    /**
     * Toggle subscription requirement on/off
     */
    public function toggleSubscriptionRequirement(Request $request)
    {
        $this->authorize('admin');

        $settings = AdminSetting::firstOrCreate([]);
        $settings->subscription_required = !($settings->subscription_required ?? false);
        $settings->save();

        $status = $settings->subscription_required ? 'enabled' : 'disabled';

        return response()->json([
            'success' => true,
            'message' => "Subscription requirement has been {$status} successfully",
            'subscription_required' => $settings->subscription_required
        ]);
    }

    /**
     * Update subscription-specific M-Pesa credentials.
     */
    public function updateSubscriptionMpesaCredentials(Request $request)
    {
        $this->authorize('admin');

        $validated = $request->validate([
            'subscription_mpesa_consumer_key' => 'nullable|string|max:255',
            'subscription_mpesa_consumer_secret' => 'nullable|string|max:255',
            'subscription_mpesa_shortcode' => 'nullable|string|max:50',
            'subscription_mpesa_passkey' => 'nullable|string|max:255',
            'subscription_mpesa_callback' => 'nullable|url|max:255',
        ]);

        // If any field is filled, all required fields must be filled
        $filledFields = array_filter($validated);
        if (!empty($filledFields) && count($filledFields) < 5) {
            return response()->json([
                'success' => false,
                'message' => 'All M-Pesa credential fields must be filled if you want to configure subscription-specific credentials'
            ], 422);
        }

        $settings = AdminSetting::firstOrCreate([]);
        $settings->update($validated);

        return response()->json([
            'success' => true,
            'message' => empty($filledFields) 
                ? 'Subscription M-Pesa credentials cleared. System will use default (.env) credentials.' 
                : 'Subscription M-Pesa credentials updated successfully'
        ]);
    }

    /**
     * Preview registration email for admins.
     */
    public function previewRegistrationEmail()
    {
        $this->authorize('admin');

        // Build sample data
        $settings = AdminSetting::first() ?? new AdminSetting();
        $registrationPrice = $settings->registration_price ?? 0;

        $business = (object) [ 'name' => config('app.name') . ' (Demo)' ];
        $user = (object) [ 'username' => 'demo_user' ];
        $receipt = 'TJPDEMO1234';

        return view('emails.registration', compact('business', 'user', 'registrationPrice', 'receipt'));
    }

   // ============================
// USER STATUS + STK PUSH
// ============================
public function updateUserStatus(Request $request, User $user)
{
    $this->authorize('admin');

    $request->validate([
        'status' => 'required|in:active,inactive,terminated',
    ]);

    $oldStatus = $user->status;
    $newStatus = $request->status;

    $user->update(['status' => $newStatus]);

    // 🔥 If status changed to active, trigger STK Push + mark subscription pending
    if ($newStatus === 'active' && $oldStatus !== 'active') {
        // Get the phone number from the latest successful M-Pesa payment
        $latestPayment = $user->mpesaPayments()
            ->where('result_code', 0) // Successful transaction
            ->orderBy('created_at', 'desc')
            ->first();
        
        $phoneNumber = $latestPayment->phone_number ?? null;

        if (!$phoneNumber) {
            return response()->json([
                'success' => false,
                'message' => 'User activated but no phone number found for payment.',
            ], 400);
        }

        // Create pending subscription
        $settings = AdminSetting::first();
        $amount = $settings->monthly_price ?? 0;

        Subscription::create([
            'user_id' => $user->id,
            'plan_name' => 'Monthly Plan',
            'billing_cycle' => 'monthly',
            'amount' => $amount,
            'start_date' => now(),
            'end_date' => now()->addMonth(),
            'status' => 'pending',
        ]);

        // STK Push
        $mpesaController = app(\App\Http\Controllers\MpesaController::class);
        $mpesaRequest = new \Illuminate\Http\Request([
            'first_name'  => $user->first_name ?? $user->name,
            'middle_name' => $user->middle_name ?? '',
            'last_name'   => $user->last_name ?? '',
            'phone'       => $phoneNumber->phone_number ?? '',// Use the phone from M-Pesa payment
        ]);
        $paymentResponse = $mpesaController->initiatePayment($mpesaRequest);

        if ($request->ajax()) {
            return $paymentResponse;
        }

        return back()->with('success', 'User activated and payment initiated (pending).');
    }

    if ($request->ajax()) {
        return response()->json([
            'success' => true,
            'status' => $user->status,
            'message' => 'User status updated successfully',
        ]);
    }

    return back()->with('success', 'User status updated successfully');
}

    // ============================
    // BUSINESS STATUS
    // ============================
    public function updateBusinessStatus(Request $request, Business $business)
    {
        $this->authorize('admin');

        $request->validate([
            'is_active' => 'required|boolean'
        ]);

        $business->update(['is_active' => $request->is_active]);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'is_active' => $business->is_active,
                'message' => 'Business status updated successfully',
            ]);
        }

        return back()->with('success', 'Business status updated successfully');
    }

    // ============================
    // SUBSCRIPTION STATUS
    // ============================
    public function updateSubscriptionStatus(Request $request, Subscription $subscription)
    {
        $this->authorize('admin');

        $request->validate([
            'status' => 'required|in:active,expired,canceled,pending',
        ]);

        $subscription->update(['status' => $request->status]);
        $this->autoUpdateBusinessStatus(optional($subscription->user)->business);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'status' => $subscription->status,
                'message' => 'Subscription status updated successfully',
            ]);
        }

        return back()->with('success', 'Subscription status updated successfully');
    }

    // ============================
    // SUBSCRIPTION RENEW
    // ============================
    public function renewSubscription(Request $request, Subscription $subscription)
    {
        $user = auth()->user();

        if ($user->role !== 'admin' && $subscription->user_id !== $user->id) {
            abort(403, 'Unauthorized action');
        }

        // Calculate new end date
        $newEndDate = $this->calculateNewEndDate($subscription->billing_cycle, $subscription->end_date);

        // Mark as pending until Mpesa payment clears
        $subscription->update([
            'end_date' => $newEndDate,
            'status'   => 'pending'
        ]);

        // 🔥 STK Push if renewal requires payment
        if ($request->has('with_payment') && $request->with_payment) {
            $userToPay = $subscription->user;

            if (!$userToPay || !$userToPay->phone) {
                return back()->with('error', 'Cannot initiate payment — no phone number found.');
            }

            $mpesaController = app(\App\Http\Controllers\MpesaController::class);
            $mpesaRequest = new \Illuminate\Http\Request([
                'first_name'  => $userToPay->first_name ?? $userToPay->name,
                'middle_name' => $userToPay->middle_name ?? '',
                'last_name'   => $userToPay->last_name ?? '',
                'phone'       => $userToPay->phone,
            ]);
            $paymentResponse = $mpesaController->initiatePayment($mpesaRequest);

            if ($request->ajax()) {
                return $paymentResponse;
            }
        }

        return back()->with('success', 'Subscription renewal initiated (pending payment).');
    }

    public function createManualSubscription(Request $request)
    {
        $this->authorize('admin');

        $request->validate([
            'user_id' => 'required|exists:users,id',
            'billing_cycle' => 'required|in:monthly,quarterly,yearly',
            'custom_amount' => 'nullable|numeric|min:0'
        ]);

        $user = User::findOrFail($request->user_id);
        $settings = AdminSetting::first();
        $amount = $request->custom_amount ?? $settings->{$request->billing_cycle . '_price'};

        $subscription = Subscription::create([
            'user_id' => $user->id,
            'plan_name' => ucfirst($request->billing_cycle) . ' Plan',
            'billing_cycle' => $request->billing_cycle,
            'amount' => $amount,
            'start_date' => now(),
            'end_date' => $this->calculateEndDate($request->billing_cycle),
            'status' => 'pending',
        ]);

        $user->update(['subscription_end_date' => $subscription->end_date]);
        $this->autoUpdateBusinessStatus($user->business);

        return back()->with('success', 'Manual subscription created (pending payment).');
    }

    public function activateSubscription(Subscription $subscription)
    {
        $this->authorize('admin');

        $subscription->update([
            'status' => 'active',
            'mpesa_receipt' => 'MANUAL-ACTIVATION-' . now()->timestamp
        ]);

        optional($subscription->user)->update(['subscription_end_date' => $subscription->end_date]);

        $this->autoUpdateBusinessStatus(optional($subscription->user)->business);

        return back()->with('success', 'Subscription activated successfully.');
    }

    // ============================
    // SHOW SUBSCRIPTION DETAILS
    // ============================
    public function showSubscription(Subscription $subscription)
    {
        $this->authorize('admin');
        
        // Removed 'payments' from the load method to fix the error
        $subscription->load(['user', 'user.business']);
        
        return view('admin.subscriptions.show', compact('subscription'));
    }

    // ============================
    // USER DETAILS
    // ============================
    public function showUser(User $user)
    {
        $this->authorize('admin');

        $user->load('subscriptions', 'business');

        return view('admin.users.show', compact('user'));
    }

    // ============================
    // HELPERS
    // ============================
    private function calculateNewEndDate(string $billingCycle, $currentEndDate)
    {
        $endDate = Carbon::parse($currentEndDate);

        return match($billingCycle) {
            'monthly' => $endDate->addMonth(),
            'quarterly' => $endDate->addMonths(3),
            'yearly' => $endDate->addYear(),
            default => $endDate->addMonth(),
        };
    }

    private function calculateEndDate(string $billingCycle)
    {
        return match($billingCycle) {
            'monthly' => Carbon::now()->addMonth(),
            'quarterly' => Carbon::now()->addMonths(3),
            'yearly' => Carbon::now()->addYear(),
            default => Carbon::now()->addMonth(),
        };
    }

    private function autoUpdateBusinessStatus(?Business $business)
    {
        if (!$business) return;

        $users = $business->users()->with('subscriptions')->get();

        $hasActiveSubscription = $users->pluck('subscriptions')
            ->flatten()
            ->contains(fn($sub) => $sub->status === 'active');

        $business->update(['is_active' => $hasActiveSubscription]);
    }
    // ============================
// DELETE SUBSCRIPTION
// ============================
public function destroySubscription(Subscription $subscription)
{
    $this->authorize('admin');
    
    $subscription->delete();
    
    if (request()->ajax()) {
        return response()->json([
            'success' => true,
            'message' => 'Subscription deleted successfully.',
        ]);
    }
    
    return back()->with('success', 'Subscription deleted successfully.');
}

}