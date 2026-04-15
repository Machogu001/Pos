# Chart of Accounts - Implementation Guide for Priority Issues

## Issue #1: Add Reconciliation Status Tracking
**Priority:** HIGH  
**Effort:** 1-2 hours  
**Impact:** Enables persistent bank reconciliation records

### Step 1: Create Migration
```bash
php artisan make:migration add_reconciliation_to_transaction_payments
```

### Step 2: Migration Code
File: `database/migrations/YYYY_MM_DD_add_reconciliation_to_transaction_payments.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddReconciliationToTransactionPayments extends Migration
{
    public function up()
    {
        Schema::table('transaction_payments', function (Blueprint $table) {
            // Add reconciliation tracking columns
            $table->boolean('is_reconciled')->default(false)->after('bank_account_number');
            $table->timestamp('reconciliation_date')->nullable()->after('is_reconciled');
            $table->string('reconciliation_reference')->nullable()->after('reconciliation_date');
            $table->index(['is_reconciled', 'reconciliation_date']);
        });
    }

    public function down()
    {
        Schema::table('transaction_payments', function (Blueprint $table) {
            $table->dropIndex(['is_reconciled', 'reconciliation_date']);
            $table->dropColumn(['is_reconciled', 'reconciliation_date', 'reconciliation_reference']);
        });
    }
}
```

### Step 3: Update Bank Reconciliation Upload Logic
File: `app/Http/Controllers/AccountReportsController.php`

**In `uploadBankReconciliation()` method, after matching transactions:**

```php
// Current code around line 1150-1200:
// After successfully matching, add:

DB::beginTransaction();

try {
    // For matched transactions
    foreach ($matched_results as $match) {
        $payment = TransactionPayment::find($match['payment_id']);
        if ($payment) {
            $payment->update([
                'is_reconciled' => true,
                'reconciliation_date' => now(),
                'reconciliation_reference' => $match['statement_reference'] ?? null,
            ]);
        }
    }

    DB::commit();

    return response()->json([
        'success' => true,
        'msg' => __('account.reconciliation_saved_successfully'),
        'matched_count' => count($matched_results),
        'ambiguous_count' => count($ambiguous_results),
        'unmatched_count' => count($unmatched_results),
    ]);

} catch (\Exception $e) {
    DB::rollBack();
    \Log::error('Bank reconciliation error: ' . $e->getMessage());
    
    return response()->json([
        'success' => false,
        'msg' => __('messages.something_went_wrong'),
    ]);
}
```

### Step 4: Update Reconciliation View
File: `resources/views/account_reports/bank_reconciliation.blade.php`

**Add display of reconciliation status:**

```blade
<!-- In matched transactions table section, add: -->
<thead>
    <tr>
        <th>@lang('messages.date')</th>
        <th>@lang('sale.amount')</th>
        <th>@lang('lang_v1.description')</th>
        <th>@lang('account.payment_ref_no')</th>
        <th>@lang('account.invoice_ref_no')</th>
        <th>@lang('lang_v1.payment_type')</th>
        <th>@lang('account.reconciliation_status')</th>  <!-- NEW -->
        <th>@lang('account.reconciliation_date')</th>    <!-- NEW -->
    </tr>
</thead>

<!-- Add to table body output JavaScript: -->
<td>
    @if($payment->is_reconciled)
        <span class="label label-success">@lang('account.reconciled')</span>
    @else
        <span class="label label-warning">@lang('account.pending')</span>
    @endif
</td>
<td>{{ $payment->reconciliation_date ? format_date($payment->reconciliation_date) : '-' }}</td>
```

### Step 5: Add Language Keys
File: `lang/en/account.php`

```php
'reconciliation_status' => 'Reconciliation Status',
'reconciliation_date' => 'Reconciliation Date',
'reconciled' => 'Reconciled',
'pending' => 'Pending',
'reconciliation_saved_successfully' => 'Reconciliation completed and saved successfully.',
```

### Step 6: Run Migration
```bash
php artisan migrate
```

---

## Issue #2: Account Closure Validation
**Priority:** HIGH  
**Effort:** 1 hour  
**Impact:** Prevents posting to closed accounts

### Step 1: Update storeJournalEntry() Method
File: `app/Http/Controllers/AccountReportsController.php`

**Replace the existing validation:**

```php
public function storeJournalEntry(Request $request)
{
    if (! auth()->user()->can('account.access')) {
        abort(403, 'Unauthorized action.');
    }

    try {
        $business_id = session()->get('user.business_id');
        $user_id = session()->get('user.id');

        $validated = $request->validate([
            'debit_account_id' => 'required|integer|different:credit_account_id',
            'credit_account_id' => 'required|integer',
            'amount' => 'required',
            'operation_date' => 'required',
        ]);

        // ADD THIS NEW VALIDATION BLOCK:
        $debit_account = Account::find($validated['debit_account_id']);
        $credit_account = Account::find($validated['credit_account_id']);

        // Validate both accounts exist and belong to this business
        if (!$debit_account || $debit_account->business_id != $business_id) {
            throw new \Exception(__('account.invalid_debit_account'));
        }

        if (!$credit_account || $credit_account->business_id != $business_id) {
            throw new \Exception(__('account.invalid_credit_account'));
        }

        // NEW: Validate accounts are not closed
        if ($debit_account->is_closed) {
            throw new \Exception(
                __('account.account_closed_error', ['account' => $debit_account->name])
            );
        }

        if ($credit_account->is_closed) {
            throw new \Exception(
                __('account.account_closed_error', ['account' => $credit_account->name])
            );
        }

        $amount = $this->transactionUtil->num_uf($validated['amount']);
        if ($amount <= 0) {
            throw new \Exception('Amount must be greater than zero.');
        }

        DB::beginTransaction();

        // ... rest of existing code ...
    } catch (\Exception $e) {
        DB::rollBack();
        // ... error handling ...
    }
}
```

### Step 2: Update postDeposit() Method
File: `app/Http/Controllers/AccountController.php`

**Add similar validation for deposits:**

```php
public function postDeposit(Request $request)
{
    // ... existing code ...

    $account = Account::find($request->input('account_id'));
    
    // ADD: Validate account is not closed
    if ($account->is_closed) {
        throw new \Exception(
            __('account.account_closed_error', ['account' => $account->name])
        );
    }

    // ... rest of existing code ...
}
```

### Step 3: Update postFundTransfer() Method
File: `app/Http/Controllers/AccountController.php`

**Add validation for fund transfers:**

```php
public function postFundTransfer(Request $request)
{
    // ... existing code ...

    $from_account = Account::find($request->input('from_account_id'));
    $to_account = Account::find($request->input('to_account_id'));

    // ADD: Validate neither account is closed
    if ($from_account->is_closed || $to_account->is_closed) {
        throw new \Exception(__('account.cannot_transfer_to_closed_account'));
    }

    // ... rest of existing code ...
}
```

### Step 4: Add Language Keys
File: `lang/en/account.php`

```php
'account_closed_error' => 'Account ":account" is closed and cannot receive transactions.',
'invalid_debit_account' => 'Invalid debit account selected.',
'invalid_credit_account' => 'Invalid credit account selected.',
'cannot_transfer_to_closed_account' => 'Cannot transfer funds to or from a closed account.',
```

### Step 5: Test Validation
```php
// Test: Try to post journal entry to closed account
// Expected: Error message showing account is closed
```

---

## Issue #3: Opening Balance Entry Feature
**Priority:** HIGH  
**Effort:** 2-3 hours  
**Impact:** Enables period-to-period balance continuity

### Step 1: Create Opening Balance Journal Entry View
File: `resources/views/account_reports/opening_balance.blade.php`

```blade
@extends('layouts.app')
@section('title', __('account.opening_balance_entry'))

@section('content')

<section class="content-header">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">
        @lang('account.opening_balance_entry')
    </h1>
</section>

<section class="content">
    <div class="row">
        <div class="col-md-8 col-md-offset-2">
            @component('components.widget')
                <p class="text-muted tw-mb-4">
                    {{ __('account.opening_balance_help') ?? 
                    'Create opening balance entries to carry forward balances from the prior fiscal period. These are special journal entries dated at the start of the new period.' }}
                </p>

                {!! Form::open(['url' => action([\App\Http\Controllers\AccountReportsController::class, 'storeOpeningBalance']), 'method' => 'post', 'id' => 'opening_balance_form']) !!}
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            {!! Form::label('opening_date', __('account.opening_date') . ':') !!}
                            {!! Form::text('opening_date', null, ['class' => 'form-control', 'id' => 'opening_date', 'required']) !!}
                            <small class="text-muted">First day of the new fiscal period</small>
                        </div>
                    </div>
                </div>

                <h4 class="tw-font-semibold tw-mt-4 tw-mb-3">@lang('account.select_prior_period')</h4>

                <div class="table-responsive tw-mb-4">
                    <table class="table table-bordered" id="opening_balance_table">
                        <thead>
                            <tr class="bg-gray">
                                <th style="width: 30px">
                                    <input type="checkbox" id="check_all">
                                </th>
                                <th>@lang('lang_v1.account_name')</th>
                                <th>@lang('lang_v1.account_type')</th>
                                <th>@lang('lang_v1.balance')</th>
                                <th>@lang('account.entry_type')</th>
                            </tr>
                        </thead>
                        <tbody id="account_list">
                            <!-- Populated via AJAX -->
                        </tbody>
                    </table>
                </div>

                <div class="form-group tw-mt-4">
                    <button type="submit" class="tw-dw-btn tw-dw-btn-primary tw-text-white">
                        <i class="fa fa-save"></i> @lang('messages.save')
                    </button>
                </div>

                {!! Form::close() !!}

            @endcomponent
        </div>
    </div>
</section>

@endsection

@section('javascript')
<script>
$(document).ready(function() {
    $('#opening_date').datepicker({
        autoclose: true,
        format: datepicker_date_format
    });

    // Load prior period balances
    $('#opening_date').change(function() {
        loadPriorBalances($(this).val());
    });

    // Check all / uncheck all
    $('#check_all').change(function() {
        $('input[name="account_ids[]"]').prop('checked', this.checked);
    });
});

function loadPriorBalances(date) {
    $.ajax({
        url: "{{ action([\App\Http\Controllers\AccountReportsController::class, 'getPriorPeriodBalances']) }}",
        data: { opening_date: date },
        dataType: 'json',
        success: function(result) {
            // Populate table with account balances
            let html = '';
            result.forEach(function(account) {
                html += '<tr>' +
                    '<td><input type="checkbox" name="account_ids[]" value="' + account.id + '"></td>' +
                    '<td>' + account.name + '</td>' +
                    '<td>' + account.account_type_name + '</td>' +
                    '<td>' + __currency_trans_from_en(account.balance, true) + '</td>' +
                    '<td>' + (account.balance >= 0 ? 'Debit' : 'Credit') + '</td>' +
                    '</tr>';
            });
            $('#account_list').html(html);
        }
    });
}
</script>
@endsection
```

### Step 2: Add Controller Methods
File: `app/Http/Controllers/AccountReportsController.php`

```php
/**
 * Show opening balance entry form
 */
public function showOpeningBalance()
{
    if (! auth()->user()->can('account.access')) {
        abort(403, 'Unauthorized action.');
    }

    return view('account_reports.opening_balance');
}

/**
 * Get prior period account balances
 */
public function getPriorPeriodBalances(Request $request)
{
    if (! auth()->user()->can('account.access')) {
        abort(403, 'Unauthorized action.');
    }

    $business_id = session()->get('user.business_id');
    $opening_date = $this->transactionUtil->uf_date($request->input('opening_date'));

    // Get end of prior period (day before opening date)
    $prior_period_end = \Carbon::parse($opening_date)->subDay()->format('Y-m-d');

    $accounts = Account::leftJoin('account_transactions as AT', function ($join) {
            $join->on('AT.account_id', '=', 'accounts.id')
                ->whereNull('AT.deleted_at');
        })
        ->leftJoin('account_types as ats', 'accounts.account_type_id', '=', 'ats.id')
        ->leftJoin('account_types as pat', 'ats.parent_account_type_id', '=', 'pat.id')
        ->where('accounts.business_id', $business_id)
        ->where('accounts.is_closed', 0)
        ->where(DB::raw('DATE(AT.operation_date)'), '<=', $prior_period_end)
        ->select([
            'accounts.id',
            'accounts.name',
            'ats.name as account_type_name',
            DB::raw(\App\Account::typeAwareBalanceExpression('COALESCE(pat.name, ats.name)', 'AT.type', 'AT.amount', 'AT.sub_type').' as balance'),
        ])
        ->groupBy('accounts.id')
        ->having(DB::raw('ABS(balance)'), '>=', 0.01)
        ->get();

    return response()->json($accounts->map(function($acc) {
        return [
            'id' => $acc->id,
            'name' => $acc->name,
            'account_type_name' => $acc->account_type_name,
            'balance' => $acc->balance,
        ];
    }));
}

/**
 * Store opening balance entries
 */
public function storeOpeningBalance(Request $request)
{
    if (! auth()->user()->can('account.access')) {
        abort(403, 'Unauthorized action.');
    }

    try {
        $business_id = session()->get('user.business_id');
        $user_id = session()->get('user.id');

        $validated = $request->validate([
            'opening_date' => 'required|date',
            'account_ids' => 'required|array|min:1',
            'account_ids.*' => 'integer|exists:accounts,id',
        ]);

        $opening_date = $this->transactionUtil->uf_date($validated['opening_date']);
        $prior_period_end = \Carbon::parse($opening_date)->subDay()->format('Y-m-d');

        DB::beginTransaction();

        // Get prior balances for selected accounts
        $prior_balances = Account::leftJoin('account_transactions as AT', function ($join) {
                $join->on('AT.account_id', '=', 'accounts.id')
                    ->whereNull('AT.deleted_at');
            })
            ->whereIn('accounts.id', $validated['account_ids'])
            ->where('accounts.business_id', $business_id)
            ->where(DB::raw('DATE(AT.operation_date)'), '<=', $prior_period_end)
            ->select([
                'accounts.id',
                DB::raw('SUM(CASE WHEN AT.type="debit" THEN AT.amount ELSE -AT.amount END) as balance'),
            ])
            ->groupBy('accounts.id')
            ->get();

        // Create equity account to balance entries
        $equity_account = Account::where('business_id', $business_id)
            ->where('name', 'Opening Balance')
            ->first();

        if (!$equity_account) {
            throw new \Exception('Opening Balance equity account not found. Please create it first.');
        }

        // Create journal entries
        foreach ($prior_balances as $item) {
            if (abs($item->balance) >= 0.01) {
                $account = Account::find($item->id);

                if ($item->balance >= 0) {
                    // Asset/Expense account - debit it
                    AccountTransaction::create([
                        'account_id' => $account->id,
                        'type' => 'debit',
                        'amount' => abs($item->balance),
                        'operation_date' => $opening_date,
                        'sub_type' => 'opening_balance',
                        'note' => 'Opening balance for ' . \Carbon::parse($opening_date)->format('Y'),
                        'created_by' => $user_id,
                    ]);

                    // Credit equity
                    AccountTransaction::create([
                        'account_id' => $equity_account->id,
                        'type' => 'credit',
                        'amount' => abs($item->balance),
                        'operation_date' => $opening_date,
                        'sub_type' => 'opening_balance',
                        'note' => 'Opening balance credit',
                        'created_by' => $user_id,
                    ]);
                } else {
                    // Credit account - credit it
                    AccountTransaction::create([
                        'account_id' => $account->id,
                        'type' => 'credit',
                        'amount' => abs($item->balance),
                        'operation_date' => $opening_date,
                        'sub_type' => 'opening_balance',
                        'note' => 'Opening balance for ' . \Carbon::parse($opening_date)->format('Y'),
                        'created_by' => $user_id,
                    ]);

                    // Debit equity
                    AccountTransaction::create([
                        'account_id' => $equity_account->id,
                        'type' => 'debit',
                        'amount' => abs($item->balance),
                        'operation_date' => $opening_date,
                        'sub_type' => 'opening_balance',
                        'note' => 'Opening balance debit',
                        'created_by' => $user_id,
                    ]);
                }
            }
        }

        DB::commit();

        return redirect()->route('account.journal-entry')
            ->with('status', ['success' => true, 'msg' => __('account.opening_balance_created')]);

    } catch (\Exception $e) {
        DB::rollBack();
        \Log::error('Opening balance error: ' . $e->getMessage());
        
        return redirect()->back()
            ->withInput()
            ->withErrors(['error' => $e->getMessage()]);
    }
}
```

### Step 3: Add Routes
File: `routes/web.php`

```php
Route::group(['prefix' => 'account'], function () {
    // ... existing routes ...
    
    Route::get('/opening-balance', [AccountReportsController::class, 'showOpeningBalance'])
        ->name('account.opening_balance');
    Route::get('/prior-period-balances', [AccountReportsController::class, 'getPriorPeriodBalances'])
        ->name('account.prior_period_balances');
    Route::post('/opening-balance', [AccountReportsController::class, 'storeOpeningBalance'])
        ->name('account.store_opening_balance');
});
```

### Step 4: Add Language Keys
File: `lang/en/account.php`

```php
'opening_balance_entry' => 'Opening Balance Entry',
'opening_balance_help' => 'Create opening balance entries to carry forward balances from the prior fiscal period.',
'opening_date' => 'Opening Date',
'opening_balance' => 'Opening Balance',
'select_prior_period' => 'Select Accounts to Carry Forward',
'entry_type' => 'Entry Type',
'opening_balance_created' => 'Opening balance entries created successfully.',
```

### Step 5: Create Opening Balance Equity Account (if not exists)
```php
// Run once in database seeder or manually:

Account::firstOrCreate([
    'business_id' => $business_id,
    'name' => 'Opening Balance',
    'account_number' => '3999',
    'account_type_id' => AccountType::where('name', 'Retained Earnings')->first()->id,
], [
    'is_closed' => 0,
]);
```

---

## Testing the Implementations

### Test Bank Reconciliation Status:
```bash
1. Upload bank statement
2. Verify matched transactions marked as is_reconciled = 1
3. Re-upload same statement
4. Verify previously reconciled items shown in reconciliation history
```

### Test Account Closure:
```bash
1. Create journal entry to closed account
2. Verify error message: "Account XYZ is closed"
3. Try fund transfer from closed account
4. Verify error message
```

### Test Opening Balance:
```bash
1. End fiscal year 2025
2. Go to Opening Balance Entry page
3. Select opening date for 2026
4. Select accounts to carry forward
5. Submit
6. Verify trial balance is balanced
```

---

## Summary of Changes

| Issue | Files Modified | Lines Added | Effort |
|-------|-----------------|------------|--------|
| Reconciliation Status | 3 files | ~60 | 1-2 hours |
| Account Closure | 3 files | ~50 | 1 hour |
| Opening Balance | 4 files | ~200 | 2-3 hours |
| **TOTAL** | **10 files** | **~310** | **4-6 hours** |

---

**All changes maintain backward compatibility and don't break existing functionality.**
