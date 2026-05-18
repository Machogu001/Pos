<?php

namespace App\Http\Controllers;

use App\Account;
use App\AccountTransaction;
use App\AccountType;
use App\BusinessLocation;
use App\Utils\BusinessUtil;
use App\TransactionPayment;
use App\Utils\TransactionUtil;
use DB;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class AccountReportsController extends Controller
{
    /**
     * All Utils instance.
     */
    protected $transactionUtil;

    protected $businessUtil;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(TransactionUtil $transactionUtil, BusinessUtil $businessUtil)
    {
        $this->transactionUtil = $transactionUtil;
        $this->businessUtil = $businessUtil;
    }

    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function balanceSheet()
    {
        if (! auth()->user()->can('account.access')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = session()->get('user.business_id');
        if (request()->ajax()) {
            $end_date = ! empty(request()->input('end_date')) ? $this->transactionUtil->uf_date(request()->input('end_date')) : \Carbon::now()->format('Y-m-d');
            $location_id = ! empty(request()->input('location_id')) ? request()->input('location_id') : null;

            $purchase_details = $this->transactionUtil->getPurchaseTotals(
                $business_id,
                null,
                $end_date,
                $location_id
            );
            $sell_details = $this->transactionUtil->getSellTotals(
                $business_id,
                null,
                $end_date,
                $location_id
            );

            $transaction_types = ['sell_return'];

            $sell_return_details = $this->transactionUtil->getTransactionTotals(
                $business_id,
                $transaction_types,
                null,
                $end_date,
                $location_id
            );

            $account_details = collect($this->getStructuredAccountBalances($business_id, $end_date, $location_id));

            $retained_earnings = collect($account_details)
                ->filter(function ($account) {
                    return in_array($account['major_type'], ['income', 'expense', 'cost'], true);
                })
                ->reduce(function ($carry, $account) {
                    $balance = (float) $account['balance'];

                    if ($account['major_type'] === 'income') {
                        return $carry + $balance;
                    }

                    return $carry - $balance;
                }, 0.0);

            if (abs($retained_earnings) >= 0.00001) {
                $account_details->push([
                    'name' => __('account.retained_earnings'),
                    'balance' => $retained_earnings,
                    'display_balance' => \App\Account::getDisplayBalance($retained_earnings),
                    'side' => strtolower(\App\Account::getBalanceSide('equity', $retained_earnings)) === 'dr' ? 'debit' : 'credit',
                    'account_type_label' => __('account.retained_earnings'),
                    'major_type' => 'equity',
                ]);
            }

            $balance_sheet_account_details = $account_details
                ->filter(function ($account) {
                    return in_array($account['major_type'], ['asset', 'liability', 'equity'], true);
                });

            $account_details = $balance_sheet_account_details->map(function ($account) {
                $balance = (float) $account['balance'];
                $side = \App\Account::getBalanceSide($account['account_type_label'], $balance);
                $account['display_balance'] = \App\Account::getDisplayBalance($balance);
                $account['side'] = strtolower($side) === 'dr' ? 'debit' : (strtolower($side) === 'cr' ? 'credit' : '');
                $account['balance_sheet_side'] = $this->resolveBalanceSheetSide($account['side']);

                return $account;
            })->values();

            $asset_account_details = $account_details
                ->filter(function ($account) {
                    return $account['balance_sheet_side'] === 'asset' && abs((float) $account['balance']) >= 0.00001;
                })
                ->values();

            $liability_account_details = $account_details
                ->filter(function ($account) {
                    return $account['balance_sheet_side'] === 'liability' && abs((float) $account['balance']) >= 0.00001;
                })
                ->values();

            //Get Closing stock
            $permitted_locations = auth()->user()->permitted_locations();
            
            $closing_stock = $this->transactionUtil->getStockValueForDate(
                $business_id,
                $end_date,
                $location_id,
                [],
                $permitted_locations
            );

            $output = [
                'supplier_due' => $purchase_details['purchase_due'],
                'customer_due' => $sell_details['invoice_due'] - $sell_return_details['total_sell_return_inc_tax'],
                'asset_account_balances' => $asset_account_details,
                'liability_account_balances' => $liability_account_details,
                'closing_stock' => $closing_stock,
            ];

            return $output;
        }

        $business_locations = BusinessLocation::forDropdown($business_id, true);

        return view('account_reports.balance_sheet')->with(compact('business_locations'));
    }

    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function trialBalance()
    {
        if (! auth()->user()->can('account.access')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = session()->get('user.business_id');

        if (request()->ajax()) {
            $end_date = ! empty(request()->input('end_date')) ? $this->transactionUtil->uf_date(request()->input('end_date')) : \Carbon::now()->format('Y-m-d');
            $location_id = ! empty(request()->input('location_id')) ? request()->input('location_id') : null;

            $purchase_details = $this->transactionUtil->getPurchaseTotals(
                $business_id,
                null,
                $end_date,
                $location_id
            );
            $sell_details = $this->transactionUtil->getSellTotals(
                $business_id,
                null,
                $end_date,
                $location_id
            );

            $account_details = $this->getTrialBalanceAccountBalances($business_id, $end_date, 'others', $location_id);

            // $capital_account_details = $this->getAccountBalance($business_id, $end_date, 'capital');

            $output = [
                'supplier_due' => $purchase_details['purchase_due'],
                'customer_due' => $sell_details['invoice_due'],
                'account_balances' => $account_details,
            ];

            return $output;
        }

        $business_locations = BusinessLocation::forDropdown($business_id, true);

        return view('account_reports.trial_balance')->with(compact('business_locations'));
    }

    /**
     * Display finance dashboard.
     *
     * @return Response
     */
    public function dashboard()
    {
        if (! auth()->user()->can('account.access')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = session()->get('user.business_id');

        $active_accounts = Account::where('business_id', $business_id)->where('is_closed', 0)->count();
        $closed_accounts = Account::where('business_id', $business_id)->where('is_closed', 1)->count();
        $total_accounts = $active_accounts + $closed_accounts;

        $dashboard_account_balances = $this->getStructuredAccountBalances($business_id, \Carbon::now()->format('Y-m-d'));
        $retained_earnings = collect($dashboard_account_balances)
            ->filter(function ($account) {
                return in_array($account['major_type'], ['income', 'expense', 'cost'], true);
            })
            ->reduce(function ($carry, $account) {
                $balance = (float) $account['balance'];

                if ($account['major_type'] === 'income') {
                    return $carry + $balance;
                }

                return $carry - $balance;
            }, 0.0);

        $not_linked_payments = TransactionPayment::leftJoin('transactions as T', 'transaction_payments.transaction_id', '=', 'T.id')
            ->whereNull('transaction_payments.parent_id')
            ->where('method', '!=', 'advance')
            ->where('transaction_payments.business_id', $business_id)
            ->whereNull('account_id')
            ->count();

        $backfill_missing_count = TransactionPayment::whereNotNull('account_id')
            ->whereNull('parent_id')
            ->where('method', '!=', 'advance')
            ->where('business_id', $business_id)
            ->whereDoesntHave('account_transactions')
            ->count();

        $fy = $this->businessUtil->getCurrentFinancialYear($business_id);
        $profit_loss = $this->transactionUtil->getProfitLossDetails(
            $business_id,
            null,
            $fy['start'],
            $fy['end'],
            null,
            auth()->user()->permitted_locations()
        );

        $recent_accounts = Account::leftJoin('account_transactions as AT', function ($join) {
                $join->on('AT.account_id', '=', 'accounts.id')
                    ->whereNull('AT.deleted_at');
            })
            ->leftJoin('account_types as ats', 'accounts.account_type_id', '=', 'ats.id')
            ->leftJoin('account_types as pat', 'ats.parent_account_type_id', '=', 'pat.id')
            ->where('accounts.business_id', $business_id)
            ->select([
                'accounts.id',
                'accounts.name',
                'accounts.account_number',
                'accounts.is_closed',
                'ats.name as account_type_name',
                'pat.name as parent_account_type_name',
                DB::raw(\App\Account::typeAwareBalanceExpression('COALESCE(pat.name, ats.name)', 'AT.type', 'AT.amount', 'AT.sub_type').' as balance'),
            ])
            ->groupBy('accounts.id')
            ->orderByDesc('accounts.id')
            ->limit(8)
            ->get()
            ->map(function ($account) {
                $accountTypeLabel = ! empty($account->parent_account_type_name) ? $account->parent_account_type_name : $account->account_type_name;
                $account->display_balance = \App\Account::getDisplayBalance($account->balance);
                $account->balance_side = \App\Account::getBalanceSide($accountTypeLabel, $account->balance);

                return $account;
            });

        return view('account_reports.dashboard', compact(
            'active_accounts',
            'closed_accounts',
            'total_accounts',
            'retained_earnings',
            'not_linked_payments',
            'backfill_missing_count',
            'fy',
            'profit_loss',
            'recent_accounts'
        ));
    }

    /**
     * Display chart of accounts.
     *
     * @return Response
     */
    public function chartOfAccounts()
    {
        if (! auth()->user()->can('account.access')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = session()->get('user.business_id');

        if (request()->ajax()) {
            $status = request()->input('account_status', 'active');

            $query = Account::leftJoin('account_transactions as AT', function ($join) {
                    $join->on('AT.account_id', '=', 'accounts.id')
                        ->whereNull('AT.deleted_at');
                })
                ->leftJoin('account_types as ats', 'accounts.account_type_id', '=', 'ats.id')
                ->leftJoin('account_types as pat', 'ats.parent_account_type_id', '=', 'pat.id')
                ->leftJoin('users AS u', 'accounts.created_by', '=', 'u.id')
                ->where('accounts.business_id', $business_id)
                ->select([
                    'accounts.name',
                    'accounts.account_number',
                    'accounts.note',
                    'accounts.id',
                    'accounts.account_type_id',
                    'ats.name as account_type_name',
                    'pat.name as parent_account_type_name',
                    'accounts.account_details',
                    'is_closed',
                    DB::raw(\App\Account::typeAwareBalanceExpression('COALESCE(pat.name, ats.name)', 'AT.type', 'AT.amount', 'AT.sub_type').' as balance'),
                    DB::raw("CONCAT(COALESCE(u.surname, ''),' ',COALESCE(u.first_name, ''),' ',COALESCE(u.last_name,'')) as added_by"),
                ]);

            $query->where('is_closed', $status == 'closed' ? 1 : 0)
                ->groupBy('accounts.id')
                ->orderByRaw(\App\AccountType::majorTypeOrderCase('COALESCE(pat.name, ats.name)'))
                ->orderByRaw("CASE WHEN accounts.account_number REGEXP '^[0-9]+$' THEN CAST(accounts.account_number AS UNSIGNED) ELSE 99999999 END")
                ->orderBy('accounts.account_number')
                ->orderBy('accounts.name');

            return DataTables::of($query)
                ->editColumn('name', function ($row) {
                    if ($row->is_closed == 1) {
                        return $row->name.' <small class="label pull-right bg-red no-print">'.__('account.closed').'</small><span class="print_section">('.__('account.closed').')</span>';
                    }

                    return $row->name;
                })
                ->editColumn('balance', function ($row) {
                    $accountTypeLabel = ! empty($row->parent_account_type_name) ? $row->parent_account_type_name : $row->account_type_name;
                    $displayBalance = \App\Account::getDisplayBalance($row->balance);
                    $balanceSide = \App\Account::getBalanceSide($accountTypeLabel, $row->balance);

                    return '<span class="balance" data-orig-value="'.$row->balance.'" data-display-value="'.$displayBalance.'" data-balance-side="'.$balanceSide.'">'.$this->transactionUtil->num_f($displayBalance, true).(! empty($balanceSide) ? ' <small class="text-muted">'.$balanceSide.'</small>' : '').'</span>';
                })
                ->editColumn('account_type', function ($row) {
                    $account_type = '';
                    if (! empty($row->parent_account_type_name)) {
                        $account_type .= $row->parent_account_type_name.' - ';
                    }

                    if (! empty($row->account_type_name)) {
                        $account_type .= $row->account_type_name;
                    }

                    return $account_type;
                })
                ->editColumn('account_details', function ($row) {
                    $html = '';
                    if (! empty($row->account_details)) {
                        foreach ($row->account_details as $account_detail) {
                            if (! empty($account_detail['label']) && ! empty($account_detail['value'])) {
                                $html .= $account_detail['label'].' : '.$account_detail['value'].'<br>';
                            }
                        }
                    }

                    return $html;
                })
                ->addColumn('action', function ($row) {
                    return '<a href="'.action([\App\Http\Controllers\AccountController::class, 'show'], [$row->id]).'" class="tw-dw-btn tw-dw-btn-outline tw-dw-btn-xs tw-dw-btn-warning"><i class="fa fa-book"></i> '.__('account.account_book').'</a>';
                })
                ->rawColumns(['action', 'balance', 'name', 'account_details'])
                ->make(true);
        }

        $account_types = AccountType::where('business_id', $business_id)
            ->whereNull('parent_account_type_id')
            ->orderedForChart()
            ->with(['sub_types'])
            ->get();

        return view('account_reports.chart_of_accounts')->with(compact('account_types'));
    }

    /**
     * Display general ledger / journal entries.
     *
     * @return Response
     */
    public function generalLedger()
    {
        if (! auth()->user()->can('account.access')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = session()->get('user.business_id');

        if (request()->ajax()) {
            $query = AccountTransaction::join('accounts as A', 'account_transactions.account_id', '=', 'A.id')
                ->leftJoin('account_types as ats', 'A.account_type_id', '=', 'ats.id')
                ->leftJoin('account_types as pat', 'ats.parent_account_type_id', '=', 'pat.id')
                ->leftJoin('transaction_payments as tp', 'account_transactions.transaction_payment_id', '=', 'tp.id')
                ->leftJoin('transactions as T', 'account_transactions.transaction_id', '=', 'T.id')
                ->leftJoin('contacts as c', 'tp.payment_for', '=', 'c.id')
                ->leftJoin('users as u', 'account_transactions.created_by', '=', 'u.id')
                ->where('A.business_id', $business_id)
                ->select([
                    'account_transactions.id',
                    'account_transactions.type',
                    'account_transactions.amount',
                    'account_transactions.operation_date',
                    'account_transactions.sub_type',
                    'account_transactions.note',
                    'account_transactions.transaction_id',
                    'A.id as account_id',
                    'A.name as account_name',
                    'A.account_number',
                    'A.is_closed',
                    'tp.method',
                    'tp.payment_ref_no',
                    'tp.transaction_no',
                    'tp.card_transaction_number',
                    'tp.card_number',
                    'tp.card_type',
                    'tp.card_holder_name',
                    'tp.card_month',
                    'tp.card_year',
                    'tp.card_security',
                    'tp.cheque_number',
                    'tp.bank_account_number',
                    'T.type as transaction_type',
                    'T.ref_no',
                    'T.invoice_no',
                    'T.id as transaction_row_id',
                    'c.name as payment_for_contact',
                    'c.type as payment_for_type',
                    'c.supplier_business_name as payment_for_business_name',
                    DB::raw("CONCAT(COALESCE(u.surname, ''),' ',COALESCE(u.first_name, ''),' ',COALESCE(u.last_name,'')) as added_by"),
                ]);

            $account_id = request()->input('account_id');
            if (! empty($account_id)) {
                $query->where('account_transactions.account_id', $account_id);
            }

            $account_status = request()->input('account_status', 'all');
            if ($account_status == 'active') {
                $query->where('A.is_closed', 0);
            } elseif ($account_status == 'closed') {
                $query->where('A.is_closed', 1);
            }

            $transaction_type = request()->input('transaction_type');
            if (! empty($transaction_type)) {
                $query->where('account_transactions.type', $transaction_type);
            }

            $start_date = request()->input('start_date');
            $end_date = request()->input('end_date');
            if (! empty($start_date) && ! empty($end_date)) {
                $query->whereBetween(DB::raw('date(account_transactions.operation_date)'), [$start_date, $end_date]);
            }

            return DataTables::of($query)
                ->editColumn('operation_date', function ($row) {
                    return $this->transactionUtil->format_date($row->operation_date, true);
                })
                ->editColumn('type', function ($row) {
                    return $row->type == 'credit' ? __('account.credit') : __('account.debit');
                })
                ->addColumn('debit', function ($row) {
                    if ($row->type == 'debit') {
                        return '<span class="debit" data-orig-value="'.$row->amount.'">'.$this->transactionUtil->num_f($row->amount, true).'</span>';
                    }

                    return '';
                })
                ->addColumn('credit', function ($row) {
                    if ($row->type == 'credit') {
                        return '<span class="credit" data-orig-value="'.$row->amount.'">'.$this->transactionUtil->num_f($row->amount, true).'</span>';
                    }

                    return '';
                })
                ->addColumn('payment_method', function ($row) {
                    if (empty($row->method)) {
                        return '';
                    }

                    $payment_types = $this->transactionUtil->payment_types(null, true, $business_id);

                    return $payment_types[$row->method] ?? $row->method;
                })
                ->addColumn('payment_details', function ($row) {
                    $details = [];

                    if (! empty($row->transaction_no)) {
                        $details[] = '<b>'.__('lang_v1.transaction_no').'</b>: '.$row->transaction_no;
                    }
                    if ($row->method == 'card' && ! empty($row->card_transaction_number)) {
                        $details[] = '<b>'.__('lang_v1.card_transaction_no').'</b>: '.$row->card_transaction_number;
                    }
                    if ($row->method == 'card' && ! empty($row->card_number)) {
                        $details[] = '<b>'.__('lang_v1.card_no').'</b>: '.$row->card_number;
                    }
                    if ($row->method == 'card' && ! empty($row->card_type)) {
                        $details[] = '<b>'.__('lang_v1.card_type').'</b>: '.$row->card_type;
                    }
                    if ($row->method == 'card' && ! empty($row->card_holder_name)) {
                        $details[] = '<b>'.__('lang_v1.card_holder_name').'</b>: '.$row->card_holder_name;
                    }
                    if ($row->method == 'card' && ! empty($row->card_month)) {
                        $details[] = '<b>'.__('lang_v1.month').'</b>: '.$row->card_month;
                    }
                    if ($row->method == 'card' && ! empty($row->card_year)) {
                        $details[] = '<b>'.__('lang_v1.year').'</b>: '.$row->card_year;
                    }
                    if ($row->method == 'card' && ! empty($row->card_security)) {
                        $details[] = '<b>'.__('lang_v1.security_code').'</b>: '.$row->card_security;
                    }
                    if (! empty($row->cheque_number)) {
                        $details[] = '<b>'.__('lang_v1.cheque_no').'</b>: '.$row->cheque_number;
                    }
                    if (! empty($row->bank_account_number)) {
                        $details[] = '<b>'.__('lang_v1.bank_account_number').'</b>: '.$row->bank_account_number;
                    }

                    return implode(', ', $details);
                })
                ->editColumn('sub_type', function ($row) {
                    if (empty($row->sub_type)) {
                        return '';
                    }

                    return __('account.'.$row->sub_type);
                })
                ->addColumn('account', function ($row) {
                    return $row->account_name.' - '.$row->account_number;
                })
                ->addColumn('related_transaction', function ($row) {
                    $html = $row->ref_no;
                    if (! empty($row->invoice_no)) {
                        $html = $row->invoice_no;
                    }

                    return $html;
                })
                ->addColumn('action', function ($row) {
                    $action = '<a href="'.action([\App\Http\Controllers\AccountController::class, 'show'], [$row->account_id]).'" class="tw-dw-btn tw-dw-btn-outline tw-dw-btn-xs tw-dw-btn-warning"><i class="fa fa-book"></i> '.__('account.account_book').'</a>';

                    if (auth()->user()->can('delete_account_transaction')) {
                        if ($row->sub_type == 'fund_transfer' || $row->sub_type == 'deposit' || $row->sub_type == 'journal_entry') {
                            $action .= ' <button type="button" class="tw-dw-btn tw-dw-btn-xs tw-dw-btn-outline tw-dw-btn-error delete_account_transaction" data-href="'.action([\App\Http\Controllers\AccountController::class, 'destroyAccountTransaction'], [$row->id]).'"><i class="fa fa-trash"></i> '.__('messages.delete').'</button>';
                        }
                    }

                    if (auth()->user()->can('edit_account_transaction')) {
                        if ($row->sub_type == 'fund_transfer' || $row->sub_type == 'deposit' || $row->sub_type == 'opening_balance') {
                            $action .= ' <button type="button" class="tw-dw-btn tw-dw-btn-xs tw-dw-btn-outline tw-dw-btn-primary btn-modal" data-container="#edit_account_transaction" data-href="'.action([\App\Http\Controllers\AccountController::class, 'editAccountTransaction'], [$row->id]).'"><i class="fa fa-edit"></i> '.__('messages.edit').'</button>';
                        }
                    }

                    return $action;
                })
                ->rawColumns(['payment_details', 'action', 'debit', 'credit'])
                ->make(true);
        }

        $accounts = Account::forDropdown($business_id, true, true, true);

        return view('account_reports.general_ledger')->with(compact('accounts'));
    }

    /**
     * Show manual journal entry form and recent entries.
     *
     * @return Response
     */
    public function journalEntry()
    {
        if (! auth()->user()->can('account.access')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = session()->get('user.business_id');

        if (request()->ajax()) {
            $query = AccountTransaction::join('accounts as A', 'account_transactions.account_id', '=', 'A.id')
                ->leftJoin('users as u', 'account_transactions.created_by', '=', 'u.id')
                ->where('A.business_id', $business_id)
                ->where('account_transactions.sub_type', 'journal_entry')
                ->select([
                    'account_transactions.id',
                    'account_transactions.type',
                    'account_transactions.amount',
                    'account_transactions.operation_date',
                    'account_transactions.sub_type',
                    'account_transactions.note',
                    'account_transactions.transfer_transaction_id',
                    'A.id as account_id',
                    'A.name as account_name',
                    'A.account_number',
                    DB::raw("CONCAT(COALESCE(u.surname, ''),' ',COALESCE(u.first_name, ''),' ',COALESCE(u.last_name,'')) as added_by"),
                ]);

            $start_date = request()->input('start_date');
            $end_date = request()->input('end_date');
            if (! empty($start_date) && ! empty($end_date)) {
                $query->whereBetween(DB::raw('date(account_transactions.operation_date)'), [$start_date, $end_date]);
            }

            return DataTables::of($query)
                ->editColumn('operation_date', function ($row) {
                    return $this->transactionUtil->format_date($row->operation_date, true);
                })
                ->addColumn('account', function ($row) {
                    return $row->account_name.' - '.$row->account_number;
                })
                ->addColumn('debit', function ($row) {
                    if ($row->type == 'debit') {
                        return '<span class="debit" data-orig-value="'.$row->amount.'">'.$this->transactionUtil->num_f($row->amount, true).'</span>';
                    }

                    return '';
                })
                ->addColumn('credit', function ($row) {
                    if ($row->type == 'credit') {
                        return '<span class="credit" data-orig-value="'.$row->amount.'">'.$this->transactionUtil->num_f($row->amount, true).'</span>';
                    }

                    return '';
                })
                ->addColumn('action', function ($row) {
                    $action = '';
                    if (auth()->user()->can('delete_account_transaction')) {
                        $action .= '<button type="button" class="tw-dw-btn tw-dw-btn-xs tw-dw-btn-outline tw-dw-btn-error delete_account_transaction" data-href="'.action([\App\Http\Controllers\AccountController::class, 'destroyAccountTransaction'], [$row->id]).'"><i class="fa fa-trash"></i> '.__('messages.delete').'</button>';
                    }

                    return $action;
                })
                ->rawColumns(['debit', 'credit', 'action'])
                ->make(true);
        }

        $accounts = Account::forDropdown($business_id, true, true, true);

        return view('account_reports.journal_entry')->with(compact('accounts'));
    }

    /**
     * Store a manual journal entry.
     *
     * @return Response
     */
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

            $amount = $this->transactionUtil->num_uf($validated['amount']);
            if ($amount <= 0) {
                throw new \Exception('Amount must be greater than zero.');
            }

            DB::beginTransaction();

            $debit_data = [
                'amount' => $amount,
                'account_id' => $validated['debit_account_id'],
                'type' => 'debit',
                'sub_type' => 'journal_entry',
                'created_by' => $user_id,
                'note' => $request->input('note'),
                'operation_date' => $this->transactionUtil->uf_date($validated['operation_date'], true),
            ];

            $debit = AccountTransaction::createAccountTransaction($debit_data);

            $credit_data = [
                'amount' => $amount,
                'account_id' => $validated['credit_account_id'],
                'type' => 'credit',
                'sub_type' => 'journal_entry',
                'created_by' => $user_id,
                'note' => $request->input('note'),
                'operation_date' => $this->transactionUtil->uf_date($validated['operation_date'], true),
                'transfer_transaction_id' => $debit->id,
            ];

            $credit = AccountTransaction::createAccountTransaction($credit_data);
            $debit->transfer_transaction_id = $credit->id;
            $debit->save();

            DB::commit();

            $output = ['success' => true, 'msg' => __('messages.success')];
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());
            $output = ['success' => false, 'msg' => __('messages.something_went_wrong')];
        }

        if (request()->ajax()) {
            return $output;
        }

        return redirect()->back()->with('status', $output);
    }

    /**
     * Retrives account balances.
     *
     * @return Obj
     */
    private function getAccountBalance($business_id, $end_date, $account_type = 'others', $location_id = null)
    {
        $query = $this->getStructuredAccountBalances($business_id, $end_date, $location_id);

        return collect($query)
            ->pluck('balance', 'name');
    }

    private function getTrialBalanceAccountBalances($business_id, $end_date, $account_type = 'others', $location_id = null)
    {
        return collect($this->getStructuredAccountBalances($business_id, $end_date, $location_id))
            ->filter(function ($account) {
                return abs((float) $account['balance']) >= 0.00001;
            })
            ->values();
    }

    private function getStructuredAccountBalances($business_id, $end_date, $location_id = null)
    {
        $query = $this->getAccountBalanceQuery($business_id, $end_date, $location_id);

        return $query->select([
                'accounts.name as name',
                'accounts.account_number',
                DB::raw('COALESCE(pat.name, ats.name) as account_type_label'),
                DB::raw(\App\Account::typeAwareBalanceExpression('COALESCE(pat.name, ats.name)', 'AT.type', 'AT.amount', 'AT.sub_type').' as balance'),
            ])
            ->groupBy('accounts.id')
            ->orderByRaw(\App\AccountType::majorTypeOrderCase('COALESCE(pat.name, ats.name)'))
            ->orderByRaw("CASE WHEN accounts.account_number REGEXP '^[0-9]+$' THEN CAST(accounts.account_number AS UNSIGNED) ELSE 99999999 END")
            ->orderBy('accounts.account_number')
            ->orderBy('accounts.name')
            ->get()
            ->map(function ($account) {
                $balance = (float) $account->balance;
                $side = \App\Account::getBalanceSide($account->account_type_label, $balance);

                return [
                    'name' => $account->name,
                    'balance' => $balance,
                    'display_balance' => \App\Account::getDisplayBalance($balance),
                    'side' => strtolower($side) === 'dr' ? 'debit' : (strtolower($side) === 'cr' ? 'credit' : ''),
                    'account_type_label' => $account->account_type_label,
                    'major_type' => \App\Account::majorTypeFromLabel($account->account_type_label),
                ];
            })
            ->values();
    }

    private function resolveBalanceSheetSide($side)
    {
        if ($side === 'debit') {
            return 'asset';
        }

        if ($side === 'credit') {
            return 'liability';
        }

        return null;
    }

    private function getAccountBalanceQuery($business_id, $end_date, $location_id = null)
    {
        $query = Account::leftjoin(
            'account_transactions as AT',
            'AT.account_id',
            '=',
            'accounts.id'
        )
                                ->leftJoin('account_types as ats', 'accounts.account_type_id', '=', 'ats.id')
                                ->leftJoin('account_types as pat', 'ats.parent_account_type_id', '=', 'pat.id')
                                // ->NotClosed()
                                ->whereNull('AT.deleted_at')
                                ->where('accounts.business_id', $business_id)
                                ->whereDate('AT.operation_date', '<=', $end_date);

        // if ($account_type == 'others') {
        //    $query->NotCapital();
        // } elseif ($account_type == 'capital') {
        //     $query->where('account_type', 'capital');
        // }

        $permitted_locations = auth()->user()->permitted_locations();
        $account_ids = [];
        if ($permitted_locations != 'all') {
            $locations = BusinessLocation::where('business_id', $business_id)
                            ->whereIn('id', $permitted_locations)
                            ->get();

            foreach ($locations as $location) {
                if (! empty($location->default_payment_accounts)) {
                    $default_payment_accounts = json_decode($location->default_payment_accounts, true);
                    foreach ($default_payment_accounts as $key => $account) {
                        if (! empty($account['is_enabled']) && ! empty($account['account'])) {
                            $account_ids[] = $account['account'];
                        }
                    }
                }
            }

            $account_ids = array_unique($account_ids);
        }

        if ($permitted_locations != 'all') {
            $query->whereIn('accounts.id', $account_ids);
        }

        if (! empty($location_id)) {
            $location = BusinessLocation::find($location_id);
            if (! empty($location->default_payment_accounts)) {
                $default_payment_accounts = json_decode($location->default_payment_accounts, true);
                $account_ids = [];
                foreach ($default_payment_accounts as $key => $account) {
                    if (! empty($account['is_enabled']) && ! empty($account['account'])) {
                        $account_ids[] = $account['account'];
                    }
                }

                $query->whereIn('accounts.id', $account_ids);
            }
        }

        return $query;
    }

    /**
     * Displays payment account report.
     *
     * @return Response
     */
    public function paymentAccountReport()
    {
        if (! auth()->user()->can('account.access')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = session()->get('user.business_id');

        if (request()->ajax()) {
            $query = TransactionPayment::leftjoin(
                'transactions as T',
                'transaction_payments.transaction_id',
                '=',
                'T.id'
            )
                                    ->leftjoin('accounts as A', 'transaction_payments.account_id', '=', 'A.id')
                                    ->where('transaction_payments.business_id', $business_id)
                                    ->whereNull('transaction_payments.parent_id')
                                    ->where('transaction_payments.method', '!=', 'advance')
                                    ->leftjoin('contacts as c', 'transaction_payments.payment_for', '=', 'c.id')
                                    ->select([
                                        'paid_on',
                                        'payment_ref_no',
                                        'T.ref_no',
                                        'T.invoice_no',
                                        'T.type',
                                        'T.id as transaction_id',
                                        'A.name as account_name',
                                        'A.account_number',
                                        'transaction_payments.id as payment_id',
                                        'transaction_payments.account_id',
                                        'c.name as contact_name',
                                        'c.type as contact_type',
                                        'transaction_payments.is_advance',
                                        'transaction_payments.amount',
                                    ]);

            $permitted_locations = auth()->user()->permitted_locations();
            if ($permitted_locations != 'all') {
                $query->whereIn('T.location_id', $permitted_locations);
            }

            $start_date = ! empty(request()->input('start_date')) ? request()->input('start_date') : '';
            $end_date = ! empty(request()->input('end_date')) ? request()->input('end_date') : '';

            if (! empty($start_date) && ! empty($end_date)) {
                $query->whereBetween(DB::raw('date(paid_on)'), [$start_date, $end_date]);
            }

            $account_id = ! empty(request()->input('account_id')) ? request()->input('account_id') : '';

            if ($account_id == 'none') {
                $query->whereNull('account_id');
            } elseif (! empty($account_id)) {
                $query->where('account_id', $account_id);
            }

            return DataTables::of($query)
                    ->editColumn('paid_on', function ($row) {
                        return $this->transactionUtil->format_date($row->paid_on, true);
                    })
                    ->editColumn('amount', function ($row) {
                        return $this->transactionUtil->num_f($row->amount, true);
                    })
                    ->addColumn('details', function ($row) {
                        $details = '';

                        if ($row->contact_type == 'supplier') {
                            $details = '<b>'.__('role.supplier').':</b> '.$row->contact_name;
                        } else {
                            $details = '<b>'.__('role.customer').':</b> '.$row->contact_name;
                        }

                        return $details;
                    })
                    ->addColumn('action', function ($row) {
                        $action = '<button type="button" class="tw-dw-btn tw-dw-btn-xs tw-dw-btn-outline  tw-dw-btn-info
                        tw-dw-btn-xs btn-modal"
                        data-container=".view_modal" 
                        data-href="'.action([\App\Http\Controllers\AccountReportsController::class, 'getLinkAccount'], [$row->payment_id]).'">'.__('account.link_account').'</button>';

                        return $action;
                    })
                    ->addColumn('account', function ($row) {
                        $account = '';
                        if (! empty($row->account_id)) {
                            $account = $row->account_name.' - '.$row->account_number;
                        }

                        return $account;
                    })
                    ->addColumn('transaction_number', function ($row) {
                        $html = $row->ref_no;
                        if ($row->type == 'sell') {
                            $html = '<button type="button" class="tw-dw-btn tw-dw-btn-xs tw-dw-btn-outline  tw-dw-btn-info btn-modal"
                                    data-href="'.action([\App\Http\Controllers\SellController::class, 'show'], [$row->transaction_id]).'" data-container=".view_modal">'.$row->invoice_no.'</button>';
                        } elseif ($row->type == 'purchase') {
                            $html = '<button type="button" class="tw-dw-btn tw-dw-btn-xs tw-dw-btn-outline  tw-dw-btn-info btn-modal"
                                    data-href="'.action([\App\Http\Controllers\PurchaseController::class, 'show'], [$row->transaction_id]).'" data-container=".view_modal">'.$row->ref_no.'</button>';
                        }

                        return $html;
                    })
                    ->editColumn('type', function ($row) {
                        $type = $row->type;
                        if ($row->type == 'sell') {
                            $type = __('sale.sale');
                        } elseif ($row->type == 'purchase') {
                            $type = __('lang_v1.purchase');
                        } elseif ($row->type == 'expense') {
                            $type = __('lang_v1.expense');
                        } elseif ($row->is_advance == 1) {
                            $type = __('lang_v1.advance');
                        }

                        return $type;
                    })
                    ->filterColumn('account', function ($query, $keyword) {
                        $query->where('A.name', 'like', ["%{$keyword}%"])
                            ->orWhere('account_number', 'like', ["%{$keyword}%"]);
                    })
                    ->filterColumn('transaction_number', function ($query, $keyword) {
                        $query->where('T.invoice_no', 'like', ["%{$keyword}%"])
                            ->orWhere('T.ref_no', 'like', ["%{$keyword}%"]);
                    })
                    ->rawColumns(['action', 'transaction_number', 'details'])
                    ->make(true);
        }

        $accounts = Account::forDropdown($business_id, false);
        $accounts = ['' => __('messages.all'), 'none' => __('lang_v1.none')] + $accounts;

        return view('account_reports.payment_account_report')
                ->with(compact('accounts'));
    }

    /**
     * Show bank reconciliation upload & results page.
     */
    public function showBankReconciliation()
    {
        if (! auth()->user()->can('account.access')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = session()->get('user.business_id');
        $accounts = Account::forDropdown($business_id, false);

        return view('account_reports.bank_reconciliation')
                ->with(compact('accounts'));
    }

    /**
     * Download a CSV template for bank reconciliation upload.
     */
    public function downloadBankReconciliationTemplate()
    {
        if (! auth()->user()->can('account.access')) {
            abort(403, 'Unauthorized action.');
        }

        $fileName = 'bank_statement_template.csv';

        $callback = function () {
            $handle = fopen('php://output', 'w');

            // Header row expected by uploadBankReconciliation
            fputcsv($handle, ['Date', 'Amount', 'Description', 'Reference']);

            // Example row for guidance
            fputcsv($handle, [
                now()->format('Y-m-d'),
                '1234.56',
                'Sample payment description',
                'BANK-REF-001',
            ]);

            fclose($handle);
        };

        return response()->streamDownload($callback, $fileName, [
            'Content-Type' => 'text/csv',
        ]);
    }

    /**
     * Handle uploaded bank statement and attempt reconciliation against transaction payments.
     */
    public function uploadBankReconciliation(Request $request)
    {
        if (! auth()->user()->can('account.access')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = session()->get('user.business_id');

        $validated = $request->validate([
            'statement' => 'required|file|mimes:csv,txt',
            'account_id' => 'nullable|integer',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
        ]);

        $file = $request->file('statement');
        $handle = fopen($file->getRealPath(), 'r');
        if (! $handle) {
            return response()->json([
                'success' => false,
                'msg' => __('messages.something_went_wrong'),
            ]);
        }

        $header = fgetcsv($handle, 0, ',');
        if ($header === false) {
            fclose($handle);

            return response()->json([
                'success' => false,
                'msg' => __('lang_v1.no_data_for_date_range'),
            ]);
        }

        $normalized_header = [];
        foreach ($header as $index => $col) {
            $normalized_header[$index] = strtolower(trim($col));
        }

        $date_index = array_search('date', $normalized_header, true);
        $amount_index = array_search('amount', $normalized_header, true);
        $description_index = array_search('description', $normalized_header, true);
        $reference_index = array_search('reference', $normalized_header, true);

        if ($date_index === false || $amount_index === false) {
            fclose($handle);

            return response()->json([
                'success' => false,
                'msg' => __('messages.custom_error_message', ['msg' => 'CSV must include Date and Amount columns.']),
            ]);
        }

        $statement_lines = [];
        $total_statement_amount = 0;

        while (($row = fgetcsv($handle, 0, ',')) !== false) {
            if (count(array_filter($row, function ($v) { return $v !== null && $v !== ''; })) === 0) {
                continue;
            }

            $raw_date = $row[$date_index] ?? '';
            $raw_amount = $row[$amount_index] ?? '';

            if ($raw_date === '' || $raw_amount === '') {
                continue;
            }

            try {
                $date = \Carbon\Carbon::parse($raw_date)->format('Y-m-d');
            } catch (\Exception $e) {
                continue;
            }

            $amount_sanitized = preg_replace('/[^0-9\-\.]/', '', (string) $raw_amount);
            if ($amount_sanitized === '' || ! is_numeric($amount_sanitized)) {
                continue;
            }

            $amount = (float) $amount_sanitized;
            $total_statement_amount += $amount;

            $statement_lines[] = [
                'date' => $date,
                'amount' => $amount,
                'description' => $description_index !== false ? ($row[$description_index] ?? '') : '',
                'reference' => $reference_index !== false ? ($row[$reference_index] ?? '') : '',
            ];
        }

        fclose($handle);

        $matched = [];
        $ambiguous = [];
        $unmatched = [];
        $total_matched_amount = 0;

        $account_id = $validated['account_id'] ?? null;

        foreach ($statement_lines as $line) {
            $date = $line['date'];
            $amount = $line['amount'];

            $reference = $line['reference'];

            // Build a base query scoped to this business
            $baseQuery = function () use ($business_id, $account_id, $validated) {
                $q = TransactionPayment::where('business_id', $business_id);
                if (! empty($account_id) && $account_id !== 'none') {
                    $q->where('account_id', $account_id);
                }
                if (! empty($validated['start_date']) && ! empty($validated['end_date'])) {
                    $q->whereBetween(DB::raw('date(paid_on)'), [$validated['start_date'], $validated['end_date']]);
                }
                return $q;
            };

            // Allow a ±3-day window around the statement date
            $start = \Carbon\Carbon::parse($date)->subDays(3)->format('Y-m-d');
            $end   = \Carbon\Carbon::parse($date)->addDays(3)->format('Y-m-d');

            // 1. Try reference match first (most precise)
            $candidates = collect();
            if ($reference !== '') {
                $candidates = $baseQuery()
                    ->where('amount', $amount)
                    ->whereBetween(DB::raw('date(paid_on)'), [$start, $end])
                    ->where('payment_ref_no', $reference)
                    ->with(['transaction'])
                    ->get();
            }

            // 2. Fall back to amount + date window
            if ($candidates->isEmpty()) {
                $candidates = $baseQuery()
                    ->where('amount', $amount)
                    ->whereBetween(DB::raw('date(paid_on)'), [$start, $end])
                    ->with(['transaction'])
                    ->get();
            }

            $formatPayment = function ($payment) {
                $txn = $payment->transaction;
                return [
                    'id'              => $payment->id,
                    'paid_on'         => $payment->paid_on,
                    'amount'          => $payment->amount,
                    'payment_ref_no'  => $payment->payment_ref_no,
                    'invoice_no'      => optional($txn)->invoice_no ?? optional($txn)->ref_no ?? '',
                    'transaction_type' => optional($txn)->type ?? '',
                    'method'          => $payment->method ?? '',
                ];
            };

            if ($candidates->count() === 1) {
                $matched[] = [
                    'statement' => $line,
                    'payment'   => $formatPayment($candidates->first()),
                ];
                $total_matched_amount += $amount;
            } elseif ($candidates->count() > 1) {
                $ambiguous[] = [
                    'statement'  => $line,
                    'candidates' => $candidates->take(5)->map($formatPayment)->values(),
                ];
            } else {
                $unmatched[] = $line;
            }
        }

        return response()->json([
            'success' => true,
            'summary' => [
                'total_statement_lines' => count($statement_lines),
                'total_statement_amount' => $total_statement_amount,
                'matched_count' => count($matched),
                'ambiguous_count' => count($ambiguous),
                'unmatched_count' => count($unmatched),
                'total_matched_amount' => $total_matched_amount,
            ],
            'matched' => $matched,
            'ambiguous' => $ambiguous,
            'unmatched' => $unmatched,
        ]);
    }

    /**
     * Shows form to link account with a payment.
     *
     * @return Response
     */
    public function getLinkAccount($id)
    {
        if (! auth()->user()->can('account.access')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = session()->get('user.business_id');
        if (request()->ajax()) {
            $payment = TransactionPayment::where('business_id', $business_id)->findOrFail($id);
            $accounts = Account::forDropdown($business_id, false);

            return view('account_reports.link_account_modal')
                ->with(compact('accounts', 'payment'));
        }
    }

    /**
     * Links account with a payment.
     *
     * @param  Request  $request
     * @return Response
     */
    public function postLinkAccount(Request $request)
    {
        if (! auth()->user()->can('account.access')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = session()->get('user.business_id');
            if (request()->ajax()) {
                $payment_id = $request->input('transaction_payment_id');
                $account_id = $request->input('account_id');

                $payment = TransactionPayment::with(['transaction'])->where('business_id', $business_id)->findOrFail($payment_id);
                $payment->account_id = $account_id;
                $payment->save();

                $payment_type = ! empty($payment->transaction->type) ? $payment->transaction->type : null;
                if (empty($payment_type)) {
                    $child_payment = TransactionPayment::where('parent_id', $payment->id)->first();
                    $payment_type = ! empty($child_payment->transaction->type) ? $child_payment->transaction->type : null;
                }

                AccountTransaction::updateAccountTransaction($payment, $payment_type);
            }
            $output = ['success' => true,
                'msg' => __('account.account_linked_success'),
            ];
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

            $output = ['success' => false,
                'msg' => __('messages.something_went_wrong'),
            ];
        }

        return $output;
    }
}
