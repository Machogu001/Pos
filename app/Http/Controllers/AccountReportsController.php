<?php

namespace App\Http\Controllers;

use App\Account;
use App\AccountTransaction;
use App\AccountType;
use App\BusinessLocation;
use App\MpesaPayment;
use App\Transaction;
use App\Utils\BusinessUtil;
use App\TransactionPayment;
use App\Utils\TransactionUtil;
use Barryvdh\DomPDF\Facade\Pdf;
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

        $fileName = 'statement_template.csv';

        $callback = function () {
            $handle = fopen('php://output', 'w');

            // Header row expected by uploadBankReconciliation
            fputcsv($handle, ['Date', 'Amount', 'Description', 'Reference']);

            // Example rows for both bank and M-Pesa style statements using the same format.
            fputcsv($handle, [
                now()->format('Y-m-d'),
                '1234.56',
                'Bank deposit for invoice INV-00045',
                'BANK-REF-001',
            ]);

            fputcsv($handle, [
                now()->format('Y-m-d'),
                '1250.00',
                'M-Pesa customer payment for invoice INV-00046',
                'QJD7X8Y9Z1',
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
            'statement' => 'required|file|mimes:csv,txt,xlsx,xls',
            'account_id' => 'nullable|integer',
            'statement_source' => 'nullable|in:auto,bank,mpesa',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'amount_tolerance' => 'nullable|numeric|min:0',
            'date_tolerance_days' => 'nullable|integer|min:0|max:30',
            'opening_balance' => 'nullable|numeric',
            'closing_balance_statement' => 'nullable|numeric',
            'reconciliation_notes' => 'nullable|string|max:2000',
            'preview_only' => 'nullable|boolean',
        ], [
            'statement.required' => __('account.no_statement_attached_to_upload'),
            'statement.file' => __('account.selected_statement_could_not_be_uploaded'),
            'statement.mimes' => __('account.statement_must_be_csv_or_excel'),
            'account_id.integer' => __('account.selected_account_invalid'),
            'statement_source.in' => __('account.selected_statement_source_invalid'),
            'start_date.date' => __('account.start_date_invalid'),
            'end_date.date' => __('account.end_date_invalid'),
            'amount_tolerance.numeric' => __('account.amount_tolerance_invalid'),
            'date_tolerance_days.integer' => __('account.date_tolerance_invalid'),
            'opening_balance.numeric' => __('account.opening_balance_invalid'),
            'closing_balance_statement.numeric' => __('account.statement_closing_balance_invalid'),
        ]);

        // Guard against inverted date ranges for deterministic filtering.
        if (! empty($validated['start_date']) && ! empty($validated['end_date']) && $validated['start_date'] > $validated['end_date']) {
            return response()->json([
                'success' => false,
                'msg' => __('messages.custom_error_message', ['msg' => __('account.start_date_cannot_be_after_end_date')]),
            ]);
        }

        $file = $request->file('statement');
        $rows = $this->readBankStatementRows($file);
        if (empty($rows)) {
            return response()->json([
                'success' => false,
                'msg' => __('messages.something_went_wrong'),
            ]);
        }

        $header = array_shift($rows);
        if (empty($header)) {
            return response()->json([
                'success' => false,
                'msg' => __('lang_v1.no_data_for_date_range'),
            ]);
        }

        $normalized_header = [];
        foreach ($header as $index => $col) {
            $normalized_header[$index] = strtolower(trim($col));
        }

        $date_index = $this->findStatementColumnIndex($normalized_header, [
            'date', 'transaction date', 'completion time', 'value date', 'posting date',
        ]);
        $amount_index = $this->findStatementColumnIndex($normalized_header, [
            'amount', 'paid in', 'paid_in', 'credit', 'transaction amount', 'amount paid',
        ]);
        $description_index = $this->findStatementColumnIndex($normalized_header, [
            'description', 'details', 'narration', 'remarks', 'particulars', 'transaction type',
        ]);
        $reference_index = $this->findStatementColumnIndex($normalized_header, [
            'reference', 'ref', 'reference no', 'reference number', 'receipt', 'receipt no',
            'receipt number', 'transaction id', 'transaction no', 'mpesa receipt', 'mpesa receipt no',
            'mpesa code', 'code', 'external reference',
        ]);

        if ($date_index === false || $amount_index === false) {
            return response()->json([
                'success' => false,
                'msg' => __('messages.custom_error_message', ['msg' => __('account.csv_must_include_date_and_amount_columns')]),
            ]);
        }

        $statement_lines = [];
        $invalid_lines = [];
        $total_statement_amount = 0;
        $line_number = 1; // header row is line 1

        foreach ($rows as $row) {
            $line_number++;

            if (count(array_filter($row, function ($v) { return $v !== null && $v !== ''; })) === 0) {
                continue;
            }

            $raw_date = $row[$date_index] ?? '';
            $raw_amount = $row[$amount_index] ?? '';

            if ($raw_date === '' || $raw_amount === '') {
                $invalid_lines[] = [
                    'line' => $line_number,
                    'date' => $raw_date,
                    'amount' => $raw_amount,
                    'description' => $description_index !== false ? ($row[$description_index] ?? '') : '',
                    'reference' => $reference_index !== false ? ($row[$reference_index] ?? '') : '',
                    'reason' => __('account.missing_date_or_amount'),
                ];
                continue;
            }

            $date = $this->parseBankStatementDate($raw_date);
            if ($date === null) {
                $invalid_lines[] = [
                    'line' => $line_number,
                    'date' => $raw_date,
                    'amount' => $raw_amount,
                    'description' => $description_index !== false ? ($row[$description_index] ?? '') : '',
                    'reference' => $reference_index !== false ? ($row[$reference_index] ?? '') : '',
                    'reason' => __('account.invalid_date_format'),
                ];
                continue;
            }

            $amount = $this->parseBankStatementAmount($raw_amount);
            if ($amount === null) {
                $invalid_lines[] = [
                    'line' => $line_number,
                    'date' => $raw_date,
                    'amount' => $raw_amount,
                    'description' => $description_index !== false ? ($row[$description_index] ?? '') : '',
                    'reference' => $reference_index !== false ? ($row[$reference_index] ?? '') : '',
                    'reason' => __('account.invalid_amount_format'),
                ];
                continue;
            }

            $total_statement_amount += $amount;

            $statement_lines[] = [
                'line' => $line_number,
                'date' => $date,
                'amount' => $amount,
                'description' => $description_index !== false ? ($row[$description_index] ?? '') : '',
                'reference' => $reference_index !== false ? ($row[$reference_index] ?? '') : '',
            ];
        }

        $matched = [];
        $ambiguous = [];
        $unmatched = [];
        $total_matched_amount = 0;
        $used_payment_ids = [];

        $account_id = $validated['account_id'] ?? null;
        $statement_source = $validated['statement_source'] ?? 'auto';
        $start_date_filter = $validated['start_date'] ?? null;
        $end_date_filter = $validated['end_date'] ?? null;
        $amount_tolerance = isset($validated['amount_tolerance']) ? (float) $validated['amount_tolerance'] : 0.01;
        $date_tolerance_days = isset($validated['date_tolerance_days']) ? (int) $validated['date_tolerance_days'] : 3;
        $opening_balance = isset($validated['opening_balance']) ? (float) $validated['opening_balance'] : null;
        $closing_balance_statement = isset($validated['closing_balance_statement']) ? (float) $validated['closing_balance_statement'] : null;
        $reconciliation_notes = $validated['reconciliation_notes'] ?? null;
        $preview_only = $request->boolean('preview_only');

        $duplicate_keys = [];
        foreach ($statement_lines as $line) {
            $dupKey = implode('|', [
                $line['date'] ?? '',
                number_format((float) ($line['amount'] ?? 0), 4, '.', ''),
                mb_strtolower(trim((string) ($line['reference'] ?? ''))),
                mb_strtolower(trim((string) ($line['description'] ?? ''))),
            ]);
            $duplicate_keys[$dupKey] = ($duplicate_keys[$dupKey] ?? 0) + 1;
        }

        $run_id = null;
        if (! $preview_only) {
            $run_id = DB::table('bank_reconciliation_runs')->insertGetId([
                'business_id' => $business_id,
                'user_id' => auth()->id(),
                'account_id' => ! empty($account_id) && $account_id !== 'none' ? (int) $account_id : null,
                'start_date' => $start_date_filter,
                'end_date' => $end_date_filter,
                'statement_filename' => $file->getClientOriginalName(),
                'total_statement_lines' => 0,
                'total_statement_amount' => 0,
                'matched_count' => 0,
                'ambiguous_count' => 0,
                'unmatched_count' => 0,
                'invalid_count' => 0,
                'total_matched_amount' => 0,
                'opening_balance' => $opening_balance,
                'closing_balance_statement' => $closing_balance_statement,
                'amount_tolerance' => $amount_tolerance,
                'date_tolerance_days' => $date_tolerance_days,
                'reconciliation_notes' => $reconciliation_notes,
                'status' => 'processing',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $line_payload = [];

        foreach ($statement_lines as $line) {
            $date = $line['date'];
            $amount = $line['amount'];

            $reference = $this->normalizeStatementReference($line['reference']);
            $statement_description = mb_strtolower(trim((string) ($line['description'] ?? '')));
            $is_mpesa_line = $statement_source === 'mpesa'
                || str_contains($statement_description, 'mpesa')
                || $this->looksLikeMpesaReference($reference);

            $dupKey = implode('|', [
                $line['date'] ?? '',
                number_format((float) ($line['amount'] ?? 0), 4, '.', ''),
                mb_strtolower(trim((string) ($line['reference'] ?? ''))),
                mb_strtolower(trim((string) ($line['description'] ?? ''))),
            ]);

            if (($duplicate_keys[$dupKey] ?? 0) > 1) {
                $invalid_lines[] = [
                    'line' => $line['line'],
                    'date' => $line['date'],
                    'amount' => $line['amount'],
                    'description' => $line['description'],
                    'reference' => $line['reference'],
                    'reason' => __('account.duplicate_statement_line'),
                ];

                $line_payload[] = [
                    'run_id' => $run_id,
                    'line_no' => $line['line'],
                    'statement_date' => $line['date'],
                    'statement_amount' => $line['amount'],
                    'description' => $line['description'],
                    'reference' => $line['reference'],
                    'status' => 'invalid',
                    'match_type' => 'auto',
                    'matched_transaction_payment_id' => null,
                    'candidate_payment_ids' => null,
                    'is_duplicate' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                continue;
            }

            // Build a base query scoped to this business
            $baseQuery = function () use ($business_id, $account_id, $start_date_filter, $end_date_filter, $used_payment_ids) {
                $q = TransactionPayment::where('business_id', $business_id);
                if (! empty($account_id) && $account_id !== 'none') {
                    $q->where('account_id', $account_id);
                }
                if (! empty($start_date_filter)) {
                    $q->whereDate('paid_on', '>=', $start_date_filter);
                }
                if (! empty($end_date_filter)) {
                    $q->whereDate('paid_on', '<=', $end_date_filter);
                }
                if (! empty($used_payment_ids)) {
                    $q->whereNotIn('id', $used_payment_ids);
                }
                return $q;
            };

            // Allow a configurable ±N-day window around the statement date.
            $start = \Carbon\Carbon::parse($date)->subDays($date_tolerance_days)->format('Y-m-d');
            $end   = \Carbon\Carbon::parse($date)->addDays($date_tolerance_days)->format('Y-m-d');

            // 1. Try reference match first (most precise)
            $candidates = collect();
            if ($reference !== '') {
                $candidates = $baseQuery()
                    ->whereBetween('amount', [$amount - $amount_tolerance, $amount + $amount_tolerance])
                    ->whereBetween(DB::raw('date(paid_on)'), [$start, $end])
                    ->where(function ($query) use ($reference) {
                        $query->where('payment_ref_no', $reference)
                            ->orWhere('transaction_no', $reference);
                    })
                    ->when($is_mpesa_line, function ($query) {
                        $query->where('method', 'mpesa');
                    })
                    ->with(['transaction'])
                    ->get();
            }

            // 2. Fall back to amount + date window
            if ($candidates->isEmpty()) {
                $candidates = $baseQuery()
                    ->whereBetween('amount', [$amount - $amount_tolerance, $amount + $amount_tolerance])
                    ->whereBetween(DB::raw('date(paid_on)'), [$start, $end])
                    ->when($is_mpesa_line, function ($query) {
                        $query->where('method', 'mpesa');
                    })
                    ->with(['transaction'])
                    ->get();
            }

            $formatPayment = function ($payment) {
                $txn = $payment->transaction;
                return [
                    'id'              => $payment->id,
                    'paid_on'         => $payment->paid_on,
                    'amount'          => $payment->amount,
                    'payment_ref_no'  => $payment->payment_ref_no ?: $payment->transaction_no,
                    'transaction_no'  => $payment->transaction_no,
                    'invoice_no'      => optional($txn)->invoice_no ?? optional($txn)->ref_no ?? '',
                    'transaction_type' => optional($txn)->type ?? '',
                    'method'          => $payment->method ?? '',
                ];
            };

            if ($candidates->count() === 1) {
                $matched_payment = $candidates->first();
                $matched[] = [
                    'statement' => $line,
                    'payment'   => $formatPayment($matched_payment),
                ];

                $used_payment_ids[] = $matched_payment->id;
                $total_matched_amount += $amount;

                $line_payload[] = [
                    'run_id' => $run_id,
                    'line_no' => $line['line'],
                    'statement_date' => $line['date'],
                    'statement_amount' => $line['amount'],
                    'description' => $line['description'],
                    'reference' => $line['reference'],
                    'status' => 'matched',
                    'match_type' => 'auto',
                    'matched_transaction_payment_id' => $matched_payment->id,
                    'candidate_payment_ids' => null,
                    'is_duplicate' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            } elseif ($candidates->count() > 1) {
                $candidate_ids = $candidates->pluck('id')->values()->all();
                $ambiguous[] = [
                    'statement'  => $line,
                    'candidates' => $candidates->take(5)->map($formatPayment)->values(),
                ];

                $line_payload[] = [
                    'run_id' => $run_id,
                    'line_no' => $line['line'],
                    'statement_date' => $line['date'],
                    'statement_amount' => $line['amount'],
                    'description' => $line['description'],
                    'reference' => $line['reference'],
                    'status' => 'ambiguous',
                    'match_type' => 'auto',
                    'matched_transaction_payment_id' => null,
                    'candidate_payment_ids' => json_encode($candidate_ids),
                    'is_duplicate' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            } else {
                $unmatched[] = $line;

                $line_payload[] = [
                    'run_id' => $run_id,
                    'line_no' => $line['line'],
                    'statement_date' => $line['date'],
                    'statement_amount' => $line['amount'],
                    'description' => $line['description'],
                    'reference' => $line['reference'],
                    'status' => 'unmatched',
                    'match_type' => 'auto',
                    'matched_transaction_payment_id' => null,
                    'candidate_payment_ids' => null,
                    'is_duplicate' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        foreach ($invalid_lines as $line) {
            $line_payload[] = [
                'run_id' => $run_id,
                'line_no' => $line['line'],
                'statement_date' => $this->parseBankStatementDate($line['date']) ?: null,
                'statement_amount' => is_numeric($line['amount']) ? (float) $line['amount'] : null,
                'description' => $line['description'],
                'reference' => $line['reference'],
                'status' => 'invalid',
                'match_type' => 'auto',
                'matched_transaction_payment_id' => null,
                'candidate_payment_ids' => null,
                'is_duplicate' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if (! $preview_only && ! empty($line_payload)) {
            DB::table('bank_reconciliation_lines')->insert($line_payload);
        }

        $ledger_closing_balance = $this->getBankLedgerClosingBalance(
            $business_id,
            ! empty($account_id) && $account_id !== 'none' ? (int) $account_id : null,
            $end_date_filter
        );

        $variance_amount = ! is_null($closing_balance_statement)
            ? round($closing_balance_statement - $ledger_closing_balance, 4)
            : null;

        if (! $preview_only) {
            DB::table('bank_reconciliation_runs')
                ->where('id', $run_id)
                ->update([
                    'total_statement_lines' => count($statement_lines),
                    'total_statement_amount' => $total_statement_amount,
                    'matched_count' => count($matched),
                    'ambiguous_count' => count($ambiguous),
                    'unmatched_count' => count($unmatched),
                    'invalid_count' => count($invalid_lines),
                    'total_matched_amount' => $total_matched_amount,
                    'ledger_closing_balance' => $ledger_closing_balance,
                    'variance_amount' => $variance_amount,
                    'status' => 'completed',
                    'completed_at' => now(),
                    'updated_at' => now(),
                ]);

            $this->logBankReconciliationAudit(
                $run_id,
                'run_created',
                'run',
                $run_id,
                null,
                [
                    'status' => 'completed',
                    'matched_count' => count($matched),
                    'ambiguous_count' => count($ambiguous),
                    'unmatched_count' => count($unmatched),
                    'invalid_count' => count($invalid_lines),
                    'total_statement_amount' => $total_statement_amount,
                    'total_matched_amount' => $total_matched_amount,
                ],
                [
                    'statement_filename' => $file->getClientOriginalName(),
                    'account_id' => ! empty($account_id) && $account_id !== 'none' ? (int) $account_id : null,
                    'statement_source' => $statement_source,
                ]
            );
        }

        return response()->json([
            'success' => true,
            'summary' => [
                'run_id' => $run_id,
                'status' => $preview_only ? 'preview' : 'completed',
                'total_statement_lines' => count($statement_lines),
                'total_statement_amount' => $total_statement_amount,
                'matched_count' => count($matched),
                'ambiguous_count' => count($ambiguous),
                'unmatched_count' => count($unmatched),
                'invalid_count' => count($invalid_lines),
                'total_matched_amount' => $total_matched_amount,
                'opening_balance' => $opening_balance,
                'closing_balance_statement' => $closing_balance_statement,
                'ledger_closing_balance' => $ledger_closing_balance,
                'variance_amount' => $variance_amount,
                'amount_tolerance' => $amount_tolerance,
                'date_tolerance_days' => $date_tolerance_days,
                'statement_source' => $statement_source,
            ],
            'matched' => $matched,
            'ambiguous' => $ambiguous,
            'unmatched' => $unmatched,
            'invalid' => $invalid_lines,
        ]);
    }

    private function findStatementColumnIndex(array $normalizedHeader, array $aliases)
    {
        foreach ($aliases as $alias) {
            $index = array_search($alias, $normalizedHeader, true);
            if ($index !== false) {
                return $index;
            }
        }

        return false;
    }

    private function normalizeStatementReference($reference): string
    {
        return strtoupper(trim((string) $reference));
    }

    private function looksLikeMpesaReference(string $reference): bool
    {
        if ($reference === '') {
            return false;
        }

        return preg_match('/^[A-Z0-9]{8,20}$/', $reference) === 1;
    }

    private function readBankStatementRows($file): array
    {
        $extension = strtolower((string) $file->getClientOriginalExtension());

        if (in_array($extension, ['xlsx', 'xls'], true)) {
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file->getRealPath());

            return collect($spreadsheet->getActiveSheet()->toArray(null, false, false, false))
                ->map(function ($row) {
                    return array_map(function ($value) {
                        return is_string($value) ? trim($value) : $value;
                    }, $row);
                })
                ->filter(function ($row) {
                    return count(array_filter($row, function ($value) {
                        return $value !== null && $value !== '';
                    })) > 0;
                })
                ->values()
                ->all();
        }

        $handle = fopen($file->getRealPath(), 'r');
        if (! $handle) {
            return [];
        }

        $rows = [];
        while (($row = fgetcsv($handle, 0, ',')) !== false) {
            $rows[] = $row;
        }

        fclose($handle);

        return $rows;
    }

    private function findSuggestedSellTransactionForStatementLine(int $business_id, $line): ?array
    {
        $reference = $this->normalizeStatementReference($line->reference ?? '');
        $description = strtoupper(trim((string) ($line->description ?? '')));
        $amount = round((float) ($line->statement_amount ?? 0), 4);

        $references = collect(array_merge([$reference], $this->extractReferenceCandidatesFromText($description)))
            ->filter()
            ->unique()
            ->values();

        if ($references->isEmpty()) {
            return null;
        }

        $transaction = null;
        $mpesaPayment = MpesaPayment::where('business_id', $business_id)
            ->where('payment_type', MpesaPayment::TYPE_SELL)
            ->where(function ($query) use ($references) {
                foreach ($references as $candidate) {
                    $query->orWhere('mpesa_receipt_number', $candidate)
                        ->orWhere('checkout_request_id', $candidate)
                        ->orWhere('account_reference', $candidate);
                }
            })
            ->when($amount > 0, function ($query) use ($amount) {
                $query->whereBetween('amount', [$amount - 0.01, $amount + 0.01]);
            })
            ->latest('id')
            ->first();

        if (! empty($mpesaPayment?->consumed_by_transaction_id)) {
            $transaction = Transaction::with('contact')
                ->where('business_id', $business_id)
                ->where('type', 'sell')
                ->where('status', 'final')
                ->find($mpesaPayment->consumed_by_transaction_id);
        }

        if (empty($transaction) && ! empty($mpesaPayment?->account_reference)) {
            $accountReference = strtoupper(trim((string) $mpesaPayment->account_reference));
            $transaction = Transaction::with('contact')
                ->where('business_id', $business_id)
                ->where('type', 'sell')
                ->where('status', 'final')
                ->where(function ($query) use ($accountReference) {
                    $query->whereRaw('UPPER(invoice_no) = ?', [$accountReference])
                        ->orWhereRaw('UPPER(ref_no) = ?', [$accountReference]);
                })
                ->latest('id')
                ->first();
        }

        if (empty($transaction)) {
            $transaction = Transaction::with('contact')
                ->where('business_id', $business_id)
                ->where('type', 'sell')
                ->where('status', 'final')
                ->where(function ($query) use ($references) {
                    foreach ($references as $candidate) {
                        $query->orWhereRaw('UPPER(invoice_no) = ?', [$candidate])
                            ->orWhereRaw('UPPER(ref_no) = ?', [$candidate]);
                    }
                })
                ->latest('id')
                ->first();
        }

        if (empty($transaction)) {
            return null;
        }

        $existingPayment = TransactionPayment::where('transaction_id', $transaction->id)
            ->where(function ($query) use ($references) {
                foreach ($references as $candidate) {
                    $query->orWhere('transaction_no', $candidate)
                        ->orWhere('payment_ref_no', $candidate);
                }
            })
            ->exists();

        if ($existingPayment) {
            return null;
        }

        $due_amount = max(0, round((float) $transaction->final_total - (float) $this->transactionUtil->getTotalPaid($transaction->id), 4));
        if ($due_amount <= 0) {
            return null;
        }

        return [
            'transaction_id' => $transaction->id,
            'invoice_no' => $transaction->invoice_no ?: $transaction->ref_no,
            'contact_name' => optional($transaction->contact)->name ?: optional($transaction->contact)->supplier_business_name,
            'due_amount' => $due_amount,
            'suggested_amount' => min($amount > 0 ? $amount : $due_amount, $due_amount),
        ];
    }

    private function extractReferenceCandidatesFromText(string $text): array
    {
        if ($text === '') {
            return [];
        }

        preg_match_all('/[A-Z0-9\-]{6,25}/', $text, $matches);

        return collect($matches[0] ?? [])
            ->map(function ($value) {
                return strtoupper(trim((string) $value));
            })
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function consumeMpesaPaymentForTransaction(int $transactionId, ?string $reference): void
    {
        if (empty($reference)) {
            return;
        }

        $mpesaPayment = MpesaPayment::where(function ($query) use ($reference) {
            $query->where('mpesa_receipt_number', $reference)
                ->orWhere('checkout_request_id', $reference);
        })
            ->where('transaction_status', 'paid')
            ->latest('id')
            ->first();

        if (! empty($mpesaPayment) && (empty($mpesaPayment->consumed_by_transaction_id) || (int) $mpesaPayment->consumed_by_transaction_id === $transactionId)) {
            $mpesaPayment->update([
                'consumed_by_transaction_id' => $transactionId,
                'consumed_at' => now(),
            ]);
        }
    }

    /**
     * Parse date formats commonly used in bank statements into Y-m-d.
     */
    private function parseBankStatementDate($rawDate)
    {
        if (is_numeric($rawDate) && (float) $rawDate > 20000) {
            try {
                return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($rawDate)->format('Y-m-d');
            } catch (\Throwable $e) {
                // Fall through to string parsing below.
            }
        }

        $value = trim((string) $rawDate);
        if ($value === '') {
            return null;
        }

        $formats = ['Y-m-d', 'd/m/Y', 'm/d/Y', 'd-m-Y', 'm-d-Y', 'd.m.Y'];

        foreach ($formats as $format) {
            try {
                $dt = \Carbon\Carbon::createFromFormat($format, $value);
                if ($dt && $dt->format($format) === $value) {
                    return $dt->format('Y-m-d');
                }
            } catch (\Exception $e) {
                // Try next format.
            }
        }

        try {
            return \Carbon\Carbon::parse($value)->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Parse international numeric formats into decimal amount.
     */
    private function parseBankStatementAmount($rawAmount)
    {
        $value = trim((string) $rawAmount);
        if ($value === '') {
            return null;
        }

        $negative = false;
        if (strpos($value, '(') !== false && strpos($value, ')') !== false) {
            $negative = true;
            $value = str_replace(['(', ')'], '', $value);
        }

        $value = preg_replace('/[^0-9,\.\-]/', '', $value);

        if ($value === '' || $value === '-' || $value === ',' || $value === '.') {
            return null;
        }

        $lastComma = strrpos($value, ',');
        $lastDot = strrpos($value, '.');

        if ($lastComma !== false && $lastDot !== false) {
            if ($lastComma > $lastDot) {
                $value = str_replace('.', '', $value);
                $value = str_replace(',', '.', $value);
            } else {
                $value = str_replace(',', '', $value);
            }
        } elseif ($lastComma !== false && $lastDot === false) {
            $value = str_replace('.', '', $value);
            $value = str_replace(',', '.', $value);
        } else {
            $value = str_replace(',', '', $value);
        }

        if (! is_numeric($value)) {
            return null;
        }

        $amount = (float) $value;
        if ($negative && $amount > 0) {
            $amount = -1 * $amount;
        }

        return round($amount, 4);
    }

    /**
     * Finalize a completed bank reconciliation run.
     */
    public function finalizeBankReconciliation($id)
    {
        if (! auth()->user()->can('account.access')) {
            abort(403, 'Unauthorized action.');
        }

        $run = $this->getBankReconciliationRunOrFail($id);

        if (($run->status ?? 'completed') === 'finalized') {
            return response()->json([
                'success' => true,
                'msg' => __('account.reconciliation_run_already_finalized'),
                'run_id' => (int) $run->id,
                'status' => 'finalized',
            ]);
        }

        if (($run->status ?? 'completed') === 'processing') {
            return response()->json([
                'success' => false,
                'msg' => __('account.reconciliation_run_still_processing'),
            ]);
        }

        DB::table('bank_reconciliation_runs')
            ->where('id', $run->id)
            ->update([
                'status' => 'finalized',
                'finalized_at' => now(),
                'finalized_by' => auth()->id(),
                'updated_at' => now(),
            ]);

        $this->logBankReconciliationAudit(
            $run->id,
            'run_finalized',
            'run',
            $run->id,
            ['status' => $run->status],
            ['status' => 'finalized']
        );

        return response()->json([
            'success' => true,
            'msg' => __('account.reconciliation_finalized_successfully'),
            'run_id' => (int) $run->id,
            'status' => 'finalized',
        ]);
    }

    /**
     * Undo a finalized reconciliation run.
     */
    public function undoBankReconciliation($id)
    {
        if (! auth()->user()->can('account.access')) {
            abort(403, 'Unauthorized action.');
        }

        $run = $this->getBankReconciliationRunOrFail($id);

        if (($run->status ?? 'completed') !== 'finalized') {
            return response()->json([
                'success' => false,
                'msg' => __('account.reconciliation_run_not_finalized'),
            ]);
        }

        DB::table('bank_reconciliation_runs')
            ->where('id', $run->id)
            ->update([
                'status' => 'completed',
                'finalized_at' => null,
                'finalized_by' => null,
                'undone_at' => now(),
                'undone_by' => auth()->id(),
                'updated_at' => now(),
            ]);

        $this->logBankReconciliationAudit(
            $run->id,
            'run_undone',
            'run',
            $run->id,
            ['status' => $run->status],
            ['status' => 'completed']
        );

        return response()->json([
            'success' => true,
            'msg' => __('account.reconciliation_undo_success'),
            'run_id' => (int) $run->id,
            'status' => 'completed',
        ]);
    }

    /**
     * Return reconciliation run details with lines for manual review.
     */
    public function bankReconciliationDetails($id)
    {
        if (! auth()->user()->can('account.access')) {
            abort(403, 'Unauthorized action.');
        }

        $run = $this->getBankReconciliationRunOrFail($id);
        $business_id = session()->get('user.business_id');

        $lines = DB::table('bank_reconciliation_lines')
            ->where('run_id', $run->id)
            ->orderBy('line_no')
            ->get();

        $candidate_ids = $lines
            ->pluck('candidate_payment_ids')
            ->filter()
            ->map(function ($value) {
                return json_decode($value, true);
            })
            ->flatten()
            ->filter()
            ->unique()
            ->values()
            ->all();

        $matched_ids = $lines->pluck('matched_transaction_payment_id')->filter()->unique()->values()->all();
        $all_payment_ids = array_values(array_unique(array_merge($candidate_ids, $matched_ids)));

        $payments = collect();
        if (! empty($all_payment_ids)) {
            $payments = TransactionPayment::with(['transaction'])
                ->where('business_id', $business_id)
                ->whereIn('id', $all_payment_ids)
                ->get()
                ->keyBy('id');
        }

        $line_data = $lines->map(function ($line) use ($payments, $business_id) {
            $candidates = collect(json_decode($line->candidate_payment_ids ?? '[]', true))
                ->map(function ($id) use ($payments) {
                    $payment = $payments->get($id);
                    if (empty($payment)) {
                        return null;
                    }

                    return [
                        'id' => $payment->id,
                        'paid_on' => $payment->paid_on,
                        'amount' => (float) $payment->amount,
                        'payment_ref_no' => $payment->payment_ref_no ?: $payment->transaction_no,
                        'transaction_no' => $payment->transaction_no,
                        'method' => $payment->method,
                        'invoice_no' => optional($payment->transaction)->invoice_no,
                        'transaction_type' => optional($payment->transaction)->type,
                    ];
                })
                ->filter()
                ->values();

            $matched_payment = null;
            if (! empty($line->matched_transaction_payment_id)) {
                $payment = $payments->get($line->matched_transaction_payment_id);
                if (! empty($payment)) {
                    $matched_payment = [
                        'id' => $payment->id,
                        'paid_on' => $payment->paid_on,
                        'amount' => (float) $payment->amount,
                        'payment_ref_no' => $payment->payment_ref_no ?: $payment->transaction_no,
                        'transaction_no' => $payment->transaction_no,
                        'method' => $payment->method,
                        'invoice_no' => optional($payment->transaction)->invoice_no,
                        'transaction_type' => optional($payment->transaction)->type,
                    ];
                }
            }

            $suggested_transaction = null;
            if ($line->status === 'unmatched') {
                $suggested_transaction = $this->findSuggestedSellTransactionForStatementLine($business_id, $line);
            }

            return [
                'id' => (int) $line->id,
                'line_no' => (int) $line->line_no,
                'statement_date' => $line->statement_date,
                'statement_amount' => (float) $line->statement_amount,
                'description' => $line->description,
                'reference' => $line->reference,
                'status' => $line->status,
                'match_type' => $line->match_type,
                'is_duplicate' => (bool) ($line->is_duplicate ?? false),
                'matched_payment' => $matched_payment,
                'candidates' => $candidates,
                'suggested_transaction' => $suggested_transaction,
            ];
        })->values();

        return response()->json([
            'success' => true,
            'run' => $run,
            'lines' => $line_data,
        ]);
    }

    /**
     * Return reconciliation audit logs.
     */
    public function bankReconciliationAuditLogs($id)
    {
        if (! auth()->user()->can('account.access')) {
            abort(403, 'Unauthorized action.');
        }

        $run = $this->getBankReconciliationRunOrFail($id);

        $logs = collect();
        if (DB::getSchemaBuilder()->hasTable('bank_reconciliation_audit_logs')) {
            $logs = DB::table('bank_reconciliation_audit_logs as l')
                ->leftJoin('users as u', 'u.id', '=', 'l.user_id')
                ->where('l.run_id', $run->id)
                ->orderByDesc('l.id')
                ->limit(500)
                ->select(
                    'l.id',
                    'l.action',
                    'l.entity_type',
                    'l.entity_id',
                    'l.before_data',
                    'l.after_data',
                    'l.meta',
                    'l.created_at',
                    'u.username as user_name'
                )
                ->get()
                ->map(function ($row) {
                    return [
                        'id' => (int) $row->id,
                        'action' => (string) $row->action,
                        'entity_type' => (string) ($row->entity_type ?? ''),
                        'entity_id' => $row->entity_id,
                        'before_data' => json_decode($row->before_data ?? 'null', true),
                        'after_data' => json_decode($row->after_data ?? 'null', true),
                        'meta' => json_decode($row->meta ?? 'null', true),
                        'created_at' => $row->created_at,
                        'user_name' => $row->user_name,
                    ];
                })
                ->values();
        }

        return response()->json([
            'success' => true,
            'run_id' => (int) $run->id,
            'logs' => $logs,
        ]);
    }

    /**
     * Manually match a reconciliation line to a specific payment.
     */
    public function manualMatchBankReconciliationLine(Request $request, $runId, $lineId)
    {
        if (! auth()->user()->can('account.access')) {
            abort(403, 'Unauthorized action.');
        }

        $validated = $request->validate([
            'payment_id' => 'required|integer',
        ]);

        $business_id = session()->get('user.business_id');
        $run = $this->getBankReconciliationRunOrFail($runId);

        if (($run->status ?? 'completed') === 'finalized') {
            return response()->json([
                'success' => false,
                'msg' => __('account.reconciliation_run_already_finalized'),
            ]);
        }

        $line = DB::table('bank_reconciliation_lines')
            ->where('id', $lineId)
            ->where('run_id', $run->id)
            ->first();

        if (empty($line)) {
            return response()->json([
                'success' => false,
                'msg' => __('account.reconciliation_line_not_found'),
            ]);
        }

        $payment = TransactionPayment::where('business_id', $business_id)
            ->where('id', $validated['payment_id'])
            ->first();

        if (empty($payment)) {
            return response()->json([
                'success' => false,
                'msg' => __('account.payment_not_found'),
            ]);
        }

        DB::table('bank_reconciliation_lines')
            ->where('id', $line->id)
            ->update([
                'status' => 'matched',
                'match_type' => 'manual',
                'matched_transaction_payment_id' => $payment->id,
                'reviewed_by' => auth()->id(),
                'reviewed_at' => now(),
                'is_duplicate' => false,
                'updated_at' => now(),
            ]);

        $this->logBankReconciliationAudit(
            $run->id,
            'manual_match',
            'line',
            $line->id,
            [
                'status' => $line->status,
                'matched_transaction_payment_id' => $line->matched_transaction_payment_id,
            ],
            [
                'status' => 'matched',
                'matched_transaction_payment_id' => $payment->id,
            ],
            [
                'line_no' => $line->line_no,
            ]
        );

        $summary = $this->recalculateBankReconciliationRun($run->id);

        return response()->json([
            'success' => true,
            'msg' => __('account.reconciliation_manual_match_success'),
            'summary' => $summary,
        ]);
    }

    /**
     * Remove manual/auto match from a reconciliation line.
     */
    public function manualUnmatchBankReconciliationLine($runId, $lineId)
    {
        if (! auth()->user()->can('account.access')) {
            abort(403, 'Unauthorized action.');
        }

        $run = $this->getBankReconciliationRunOrFail($runId);
        if (($run->status ?? 'completed') === 'finalized') {
            return response()->json([
                'success' => false,
                'msg' => __('account.reconciliation_run_already_finalized'),
            ]);
        }

        $line = DB::table('bank_reconciliation_lines')
            ->where('id', $lineId)
            ->where('run_id', $run->id)
            ->first();

        if (empty($line)) {
            return response()->json([
                'success' => false,
                'msg' => __('account.reconciliation_line_not_found'),
            ]);
        }

        DB::table('bank_reconciliation_lines')
            ->where('id', $line->id)
            ->update([
                'status' => 'unmatched',
                'match_type' => 'manual',
                'matched_transaction_payment_id' => null,
                'reviewed_by' => auth()->id(),
                'reviewed_at' => now(),
                'updated_at' => now(),
            ]);

        $this->logBankReconciliationAudit(
            $run->id,
            'manual_unmatch',
            'line',
            $line->id,
            [
                'status' => $line->status,
                'matched_transaction_payment_id' => $line->matched_transaction_payment_id,
            ],
            [
                'status' => 'unmatched',
                'matched_transaction_payment_id' => null,
            ],
            [
                'line_no' => $line->line_no,
            ]
        );

        $summary = $this->recalculateBankReconciliationRun($run->id);

        return response()->json([
            'success' => true,
            'msg' => __('account.reconciliation_manual_unmatch_success'),
            'summary' => $summary,
        ]);
    }

    public function createMissingPaymentFromBankReconciliationLine($runId, $lineId)
    {
        if (! auth()->user()->can('account.access')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = session()->get('user.business_id');
        $run = $this->getBankReconciliationRunOrFail($runId);

        if (($run->status ?? 'completed') === 'finalized') {
            return response()->json([
                'success' => false,
                'msg' => __('account.reconciliation_run_already_finalized'),
            ]);
        }

        $line = DB::table('bank_reconciliation_lines')
            ->where('id', $lineId)
            ->where('run_id', $run->id)
            ->first();

        if (empty($line)) {
            return response()->json([
                'success' => false,
                'msg' => __('account.reconciliation_line_not_found'),
            ]);
        }

        $suggested = $this->findSuggestedSellTransactionForStatementLine($business_id, $line);
        if (empty($suggested['transaction_id'])) {
            return response()->json([
                'success' => false,
                'msg' => __('account.no_matching_sale_for_statement_line'),
            ]);
        }

        DB::beginTransaction();

        try {
            $transaction = Transaction::with(['contact', 'location'])
                ->where('business_id', $business_id)
                ->where('type', 'sell')
                ->where('status', 'final')
                ->lockForUpdate()
                ->find($suggested['transaction_id']);

            if (empty($transaction)) {
                throw new \RuntimeException(__('account.suggested_sale_could_not_be_loaded'));
            }

            $reference = $this->normalizeStatementReference($line->reference ?? '');
            $existingPaymentQuery = TransactionPayment::where('transaction_id', $transaction->id);
            if ($reference !== '') {
                $existingPaymentQuery->where(function ($query) use ($reference) {
                    $query->where('transaction_no', $reference)
                        ->orWhere('payment_ref_no', $reference);
                });
            }

            if ($existingPaymentQuery->exists()) {
                throw new \RuntimeException(__('account.payment_reference_already_exists_on_sale'));
            }

            $total_paid = $this->transactionUtil->getTotalPaid($transaction->id);
            $due_amount = max(0, round((float) $transaction->final_total - (float) $total_paid, 4));
            $statement_amount = round((float) ($line->statement_amount ?? 0), 4);
            $payment_amount = min($statement_amount, $due_amount);

            if ($payment_amount <= 0) {
                throw new \RuntimeException(__('account.no_outstanding_balance_for_statement_payment'));
            }

            $ref_count = $this->transactionUtil->setAndGetReferenceCount('sell_payment', $transaction->business_id);
            $payment_ref_no = $this->transactionUtil->generateReferenceNumber('sell_payment', $ref_count, $transaction->business_id);
            $account_id = ! empty($run->account_id)
                ? (int) $run->account_id
                : TransactionPayment::resolveDefaultAccountId('mpesa', $transaction->location_id, $transaction->business_id, $transaction->type);

            $inputs = [
                'paid_on' => $line->statement_date ? $line->statement_date . ' 00:00:00' : now()->toDateTimeString(),
                'transaction_id' => $transaction->id,
                'amount' => $payment_amount,
                'payment_for' => $transaction->contact_id,
                'method' => 'mpesa',
                'note' => __('account.statement_reconciliation_created_note', [
                    'run_id' => $run->id,
                    'line_no' => $line->line_no ?? $line->id,
                ]),
                'business_id' => $transaction->business_id,
                'payment_ref_no' => $payment_ref_no,
                'created_by' => auth()->id(),
                'account_id' => $account_id,
                'transaction_no' => $reference !== '' ? $reference : null,
                'transaction_type' => $transaction->type,
            ];

            $tp = TransactionPayment::create($inputs);
            event(new \App\Events\TransactionPaymentAdded($tp, $inputs));

            $payment_status = $this->transactionUtil->updatePaymentStatus($transaction->id, $transaction->final_total);
            $transaction->payment_status = $payment_status;
            $transaction->save();

            $this->consumeMpesaPaymentForTransaction($transaction->id, $reference);

            DB::table('bank_reconciliation_lines')
                ->where('id', $line->id)
                ->update([
                    'status' => 'matched',
                    'match_type' => 'manual',
                    'matched_transaction_payment_id' => $tp->id,
                    'reviewed_by' => auth()->id(),
                    'reviewed_at' => now(),
                    'updated_at' => now(),
                ]);

            $this->logBankReconciliationAudit(
                $run->id,
                'create_missing_payment',
                'line',
                $line->id,
                [
                    'status' => $line->status,
                    'matched_transaction_payment_id' => $line->matched_transaction_payment_id,
                ],
                [
                    'status' => 'matched',
                    'matched_transaction_payment_id' => $tp->id,
                ],
                [
                    'line_no' => $line->line_no,
                    'transaction_id' => $transaction->id,
                    'invoice_no' => $transaction->invoice_no,
                ]
            );

            $summary = $this->recalculateBankReconciliationRun($run->id);
            DB::commit();

            return response()->json([
                'success' => true,
                'msg' => 'Missing sales payment created and matched successfully.',
                'summary' => $summary,
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'msg' => $e->getMessage() ?: __('messages.something_went_wrong'),
            ]);
        }
    }

    /**
     * Export an auditor-ready package for a finalized bank reconciliation run.
     */
    public function exportBankReconciliationPackage($id)
    {
        if (! auth()->user()->can('account.access')) {
            abort(403, 'Unauthorized action.');
        }

        $run = $this->getBankReconciliationRunOrFail($id);

        if (($run->status ?? 'completed') !== 'finalized') {
            return redirect()->back()->with('status', [
                'success' => 0,
                'msg' => __('account.finalize_before_exporting_audit_package'),
            ]);
        }

        $lines = DB::table('bank_reconciliation_lines as l')
            ->leftJoin('transaction_payments as tp', 'tp.id', '=', 'l.matched_transaction_payment_id')
            ->leftJoin('transactions as t', 't.id', '=', 'tp.transaction_id')
            ->where('l.run_id', $run->id)
            ->select(
                'l.line_no',
                'l.statement_date',
                'l.statement_amount',
                'l.description',
                'l.reference',
                'l.status',
                'tp.id as payment_id',
                'tp.paid_on',
                'tp.amount as payment_amount',
                'tp.payment_ref_no',
                'tp.method as payment_method',
                't.invoice_no',
                't.ref_no',
                't.type as transaction_type'
            )
            ->orderBy('l.id')
            ->get();

        $tmpDir = storage_path('app/temp');
        if (! is_dir($tmpDir)) {
            mkdir($tmpDir, 0755, true);
        }

        $timestamp = now()->format('Ymd_His');
        $baseName = 'bank_reconciliation_run_' . $run->id . '_' . $timestamp;
        $summaryCsvPath = $tmpDir . '/' . $baseName . '_summary.csv';
        $linesCsvPath = $tmpDir . '/' . $baseName . '_lines.csv';
        $zipPath = $tmpDir . '/' . $baseName . '.zip';

        $summaryHandle = fopen($summaryCsvPath, 'w');
        fputcsv($summaryHandle, [
            'Run ID', 'Business ID', 'User ID', 'Account ID', 'Status', 'Statement File',
            'Start Date', 'End Date', 'Total Lines', 'Matched', 'Ambiguous', 'Unmatched',
            'Invalid', 'Statement Amount', 'Matched Amount', 'Completed At', 'Finalized At', 'Finalized By'
        ]);
        fputcsv($summaryHandle, [
            $run->id,
            $run->business_id,
            $run->user_id,
            $run->account_id,
            $run->status,
            $run->statement_filename,
            $run->start_date,
            $run->end_date,
            $run->total_statement_lines,
            $run->matched_count,
            $run->ambiguous_count,
            $run->unmatched_count,
            $run->invalid_count,
            $run->total_statement_amount,
            $run->total_matched_amount,
            $run->completed_at,
            $run->finalized_at,
            $run->finalized_by,
        ]);
        fclose($summaryHandle);

        $linesHandle = fopen($linesCsvPath, 'w');
        fputcsv($linesHandle, [
            'Line No', 'Statement Date', 'Statement Amount', 'Description', 'Reference', 'Status',
            'Matched Payment ID', 'Payment Date', 'Payment Amount', 'Payment Ref', 'Payment Method',
            'Invoice No', 'Transaction Ref', 'Transaction Type'
        ]);

        foreach ($lines as $line) {
            fputcsv($linesHandle, [
                $line->line_no,
                $line->statement_date,
                $line->statement_amount,
                $line->description,
                $line->reference,
                $line->status,
                $line->payment_id,
                $line->paid_on,
                $line->payment_amount,
                $line->payment_ref_no,
                $line->payment_method,
                $line->invoice_no,
                $line->ref_no,
                $line->transaction_type,
            ]);
        }

        fclose($linesHandle);

        $zip = new \ZipArchive();
        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            @unlink($summaryCsvPath);
            @unlink($linesCsvPath);

            return redirect()->back()->with('status', [
                'success' => 0,
                'msg' => __('account.unable_to_create_audit_package_archive'),
            ]);
        }

        $zip->addFile($summaryCsvPath, basename($summaryCsvPath));
        $zip->addFile($linesCsvPath, basename($linesCsvPath));
        $zip->close();

        @unlink($summaryCsvPath);
        @unlink($linesCsvPath);

        $this->logBankReconciliationAudit(
            $run->id,
            'export_package',
            'run',
            $run->id,
            null,
            [
                'format' => 'zip',
                'filename' => $baseName . '.zip',
            ]
        );

        return response()->download($zipPath, $baseName . '.zip')->deleteFileAfterSend(true);
    }

    /**
     * Export finalized reconciliation run as PDF.
     */
    public function exportBankReconciliationPdf($id)
    {
        if (! auth()->user()->can('account.access')) {
            abort(403, 'Unauthorized action.');
        }

        $run = $this->getBankReconciliationRunOrFail($id);
        if (($run->status ?? 'completed') !== 'finalized') {
            return redirect()->back()->with('status', [
                'success' => 0,
                'msg' => __('account.finalize_before_exporting_audit_package'),
            ]);
        }

        $rows = $this->getBankReconciliationExportRows($run);
        $fileName = 'bank_reconciliation_run_' . $run->id . '_' . now()->format('Ymd_His');

        $pdf = Pdf::loadView('account_reports.bank_reconciliation_pdf', [
            'run' => $run,
            'rows' => $rows,
        ])->setPaper('a4', 'landscape');

        $this->logBankReconciliationAudit(
            $run->id,
            'export_pdf',
            'run',
            $run->id,
            null,
            [
                'format' => 'pdf',
                'filename' => $fileName . '.pdf',
            ]
        );

        return $pdf->download($fileName . '.pdf');
    }

    /**
     * Export finalized reconciliation run as Excel file.
     */
    public function exportBankReconciliationExcel($id)
    {
        if (! auth()->user()->can('account.access')) {
            abort(403, 'Unauthorized action.');
        }

        $run = $this->getBankReconciliationRunOrFail($id);
        if (($run->status ?? 'completed') !== 'finalized') {
            return redirect()->back()->with('status', [
                'success' => 0,
                'msg' => __('account.finalize_before_exporting_audit_package'),
            ]);
        }

        if (! class_exists('Maatwebsite\\Excel\\Facades\\Excel')) {
            return redirect()->back()->with('status', [
                'success' => 0,
                'msg' => __('messages.something_went_wrong'),
            ]);
        }

        $rows = $this->getBankReconciliationExportRows($run);
        $fileName = 'bank_reconciliation_run_' . $run->id . '_' . now()->format('Ymd_His') . '.xlsx';

        $this->logBankReconciliationAudit(
            $run->id,
            'export_excel',
            'run',
            $run->id,
            null,
            [
                'format' => 'excel',
                'filename' => $fileName,
            ]
        );

        return \Maatwebsite\Excel\Facades\Excel::download(
            new class($rows) implements \Maatwebsite\Excel\Concerns\FromCollection, \Maatwebsite\Excel\Concerns\WithHeadings {
                protected $rows;

                public function __construct($rows)
                {
                    $this->rows = $rows;
                }

                public function collection()
                {
                    return $this->rows->map(function ($row) {
                        return [
                            $row->line_no,
                            $row->statement_date,
                            $row->statement_amount,
                            $row->description,
                            $row->reference,
                            $row->status,
                            $row->payment_id,
                            $row->paid_on,
                            $row->payment_amount,
                            $row->payment_ref_no,
                            $row->payment_method,
                            $row->invoice_no,
                            $row->ref_no,
                            $row->transaction_type,
                        ];
                    });
                }

                public function headings(): array
                {
                    return [
                        'Line No',
                        'Statement Date',
                        'Statement Amount',
                        'Description',
                        'Reference',
                        'Status',
                        'Matched Payment ID',
                        'Payment Date',
                        'Payment Amount',
                        'Payment Ref',
                        'Payment Method',
                        'Invoice No',
                        'Transaction Ref',
                        'Transaction Type',
                    ];
                }
            },
            $fileName
        );
    }

    /**
     * Fetch reconciliation run scoped to authenticated user's business.
     */
    private function getBankReconciliationRunOrFail($id)
    {
        $business_id = session()->get('user.business_id');

        $run = DB::table('bank_reconciliation_runs')
            ->where('id', $id)
            ->where('business_id', $business_id)
            ->first();

        if (empty($run)) {
            abort(404, __('account.reconciliation_run_not_found'));
        }

        return $run;
    }

    /**
     * Recalculate and persist aggregate totals for a reconciliation run.
     */
    private function recalculateBankReconciliationRun($run_id)
    {
        $run = $this->getBankReconciliationRunOrFail($run_id);

        $totals = DB::table('bank_reconciliation_lines')
            ->where('run_id', $run->id)
            ->selectRaw('COUNT(*) as total_lines')
            ->selectRaw("SUM(CASE WHEN status = 'matched' THEN 1 ELSE 0 END) as matched_count")
            ->selectRaw("SUM(CASE WHEN status = 'ambiguous' THEN 1 ELSE 0 END) as ambiguous_count")
            ->selectRaw("SUM(CASE WHEN status = 'unmatched' THEN 1 ELSE 0 END) as unmatched_count")
            ->selectRaw("SUM(CASE WHEN status = 'invalid' THEN 1 ELSE 0 END) as invalid_count")
            ->selectRaw("SUM(CASE WHEN status = 'matched' THEN statement_amount ELSE 0 END) as total_matched_amount")
            ->selectRaw('SUM(statement_amount) as total_statement_amount')
            ->first();

        $ledger_closing_balance = $this->getBankLedgerClosingBalance(
            $run->business_id,
            $run->account_id,
            $run->end_date
        );

        $variance_amount = ! is_null($run->closing_balance_statement)
            ? round(((float) $run->closing_balance_statement) - $ledger_closing_balance, 4)
            : null;

        DB::table('bank_reconciliation_runs')
            ->where('id', $run->id)
            ->update([
                'total_statement_lines' => (int) ($totals->total_lines ?? 0),
                'total_statement_amount' => (float) ($totals->total_statement_amount ?? 0),
                'matched_count' => (int) ($totals->matched_count ?? 0),
                'ambiguous_count' => (int) ($totals->ambiguous_count ?? 0),
                'unmatched_count' => (int) ($totals->unmatched_count ?? 0),
                'invalid_count' => (int) ($totals->invalid_count ?? 0),
                'total_matched_amount' => (float) ($totals->total_matched_amount ?? 0),
                'ledger_closing_balance' => $ledger_closing_balance,
                'variance_amount' => $variance_amount,
                'updated_at' => now(),
            ]);

        return [
            'run_id' => (int) $run->id,
            'total_statement_lines' => (int) ($totals->total_lines ?? 0),
            'total_statement_amount' => (float) ($totals->total_statement_amount ?? 0),
            'matched_count' => (int) ($totals->matched_count ?? 0),
            'ambiguous_count' => (int) ($totals->ambiguous_count ?? 0),
            'unmatched_count' => (int) ($totals->unmatched_count ?? 0),
            'invalid_count' => (int) ($totals->invalid_count ?? 0),
            'total_matched_amount' => (float) ($totals->total_matched_amount ?? 0),
            'ledger_closing_balance' => $ledger_closing_balance,
            'variance_amount' => $variance_amount,
        ];
    }

    /**
     * Shared export dataset for reconciliation run.
     */
    private function getBankReconciliationExportRows($run)
    {
        return DB::table('bank_reconciliation_lines as l')
            ->leftJoin('transaction_payments as tp', 'tp.id', '=', 'l.matched_transaction_payment_id')
            ->leftJoin('transactions as t', 't.id', '=', 'tp.transaction_id')
            ->where('l.run_id', $run->id)
            ->select(
                'l.line_no',
                'l.statement_date',
                'l.statement_amount',
                'l.description',
                'l.reference',
                'l.status',
                'tp.id as payment_id',
                'tp.paid_on',
                'tp.amount as payment_amount',
                'tp.payment_ref_no',
                'tp.method as payment_method',
                't.invoice_no',
                't.ref_no',
                't.type as transaction_type'
            )
            ->orderBy('l.id')
            ->get();
    }

    /**
     * Persist reconciliation action log entry.
     */
    private function logBankReconciliationAudit($run_id, $action, $entity_type = null, $entity_id = null, $before_data = null, $after_data = null, $meta = null)
    {
        try {
            if (! DB::getSchemaBuilder()->hasTable('bank_reconciliation_audit_logs')) {
                return;
            }

            $run = DB::table('bank_reconciliation_runs')->where('id', $run_id)->first();
            if (empty($run)) {
                return;
            }

            DB::table('bank_reconciliation_audit_logs')->insert([
                'run_id' => (int) $run_id,
                'business_id' => (int) $run->business_id,
                'user_id' => auth()->id(),
                'action' => (string) $action,
                'entity_type' => $entity_type,
                'entity_id' => $entity_id,
                'before_data' => is_null($before_data) ? null : json_encode($before_data),
                'after_data' => is_null($after_data) ? null : json_encode($after_data),
                'meta' => is_null($meta) ? null : json_encode($meta),
                'created_at' => now(),
            ]);
        } catch (\Exception $e) {
            \Log::warning('Reconciliation audit log write failed: ' . $e->getMessage());
        }
    }

    /**
     * Get ledger closing balance for the selected bank account.
     */
    private function getBankLedgerClosingBalance($business_id, $account_id = null, $end_date = null)
    {
        if (empty($account_id)) {
            return 0.0;
        }

        $query = DB::table('account_transactions')
            ->where('business_id', $business_id)
            ->where('account_id', $account_id);

        if (! empty($end_date)) {
            $query->whereDate('operation_date', '<=', $end_date);
        }

        $balance = $query->selectRaw("SUM(CASE WHEN type = 'debit' THEN amount ELSE -amount END) as balance")
            ->value('balance');

        return round((float) ($balance ?? 0), 4);
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
