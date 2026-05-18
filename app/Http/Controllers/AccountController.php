<?php

namespace App\Http\Controllers;

use App\Account;
use App\AccountTransaction;
use App\AccountType;
use App\BusinessLocation;
use App\Media;
use App\TransactionPayment;
use App\Utils\ModuleUtil;
use App\Utils\Util;
use DB;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Yajra\DataTables\Facades\DataTables;

class AccountController extends Controller
{
    protected $commonUtil;

    protected $moduleUtil;

    /**
     * Constructor
     *
     * @param  Util  $commonUtil
     * @return void
     */
    public function __construct(Util $commonUtil, ModuleUtil $moduleUtil)
    {
        $this->commonUtil = $commonUtil;
        $this->moduleUtil = $moduleUtil;
    }

    /**
     * Backfill missing account transactions for existing payments using
     * default account mappings. Admin-only helper, triggered from UI.
     */
    public function backfillDefaultAccounts()
    {
        if (! auth()->user()->can('account.access')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            \Artisan::call('accounting:backfill-default-accounts');
            $message = __('messages.success');
            $type = 'success';
        } catch (\Throwable $e) {
            \Log::error('Backfill default accounts failed: '.$e->getMessage());
            $message = __('messages.something_went_wrong');
            $type = 'danger';
        }

        if (request()->ajax()) {
            return response()->json(['status' => $type, 'msg' => $message]);
        }

        return redirect()->back()->with($type, $message);
    }

    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index()
    {
        if (! auth()->user()->can('account.access')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = session()->get('user.business_id');
        if (request()->ajax()) {
            $accounts = Account::leftjoin('account_transactions as AT', function ($join) {
                    $join->on('AT.account_id', '=', 'accounts.id')
                        ->whereNull('AT.deleted_at');
                })
            ->leftjoin(
                'account_types as ats',
                'accounts.account_type_id',
                '=',
                'ats.id'
            )
            ->leftjoin(
                'account_types as pat',
                'ats.parent_account_type_id',
                '=',
                'pat.id'
            )
            ->leftJoin('users AS u', 'accounts.created_by', '=', 'u.id')
                                ->where('accounts.business_id', $business_id)
                                ->select(['accounts.name', 'accounts.account_number', 'accounts.note', 'accounts.id', 'accounts.account_type_id',
                                    'ats.name as account_type_name',
                                    'pat.name as parent_account_type_name',
                                    'accounts.account_details',
                                    'is_closed', DB::raw(\App\Account::typeAwareBalanceExpression('COALESCE(pat.name, ats.name)', 'AT.type', 'AT.amount', 'AT.sub_type').' as balance'),
                                    DB::raw("CONCAT(COALESCE(u.surname, ''),' ',COALESCE(u.first_name, ''),' ',COALESCE(u.last_name,'')) as added_by"),
                                ]);

            //check account permissions basaed on location
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

            if (! $this->moduleUtil->is_admin(auth()->user(), $business_id) && $permitted_locations != 'all') {
                $accounts->whereIn('accounts.id', $account_ids);
            }

            $is_closed = request()->input('account_status') == 'closed' ? 1 : 0;
            $accounts->where('is_closed', $is_closed)
                ->groupBy('accounts.id')
                ->orderByRaw(\App\AccountType::majorTypeOrderCase('COALESCE(pat.name, ats.name)'))
                ->orderByRaw("CASE WHEN accounts.account_number REGEXP '^[0-9]+$' THEN CAST(accounts.account_number AS UNSIGNED) ELSE 99999999 END")
                ->orderBy('accounts.account_number')
                ->orderBy('accounts.name');

            return DataTables::of($accounts)
                            ->addColumn('action', function ($row) use ($is_closed) {
                                $action = '<button data-href="'.action([\App\Http\Controllers\AccountController::class, 'edit'], [$row->id]).'" data-container=".account_model" class="tw-dw-btn tw-dw-btn-xs tw-dw-btn-outline tw-dw-btn-primary btn-modal"><i class="glyphicon glyphicon-edit"></i> '.__('messages.edit').'</button>';
                                $action .= ' <a href="'.action([\App\Http\Controllers\AccountController::class, 'show'], [$row->id]).'" class="tw-dw-btn tw-dw-btn-outline tw-dw-btn-xs tw-dw-btn-warning btn-xs"><i class="fa fa-book"></i> '.__('account.account_book').'</a>';

                                if ($is_closed == 0) {
                                    $action .= ' <button data-href="'.action([\App\Http\Controllers\AccountController::class, 'getFundTransfer'], [$row->id]).'" class="tw-dw-btn tw-dw-btn-xs tw-dw-btn-outline tw-dw-btn-info btn-modal" data-container=".view_modal"><i class="fa fa-exchange"></i> '.__('account.fund_transfer').'</button>';
                                    $action .= ' <button data-href="'.action([\App\Http\Controllers\AccountController::class, 'getDeposit'], [$row->id]).'" class="tw-dw-btn tw-dw-btn-outline tw-dw-btn-xs tw-dw-btn-success btn-modal" data-container=".view_modal"><i class="fas fa-money-bill-alt"></i> '.__('account.deposit').'</button>';

                                    $action .= ' <button data-url="'.action([\App\Http\Controllers\AccountController::class, 'close'], [$row->id]).'" class="tw-dw-btn tw-dw-btn-outline tw-dw-btn-xs tw-dw-btn-error close_account"><i class="fa fa-power-off"></i> '.__('messages.close').'</button>';
                                } else {
                                    $action .= ' <button data-url="'.action([\App\Http\Controllers\AccountController::class, 'activate'], [$row->id]).'" class="tw-dw-btn tw-dw-btn-outline tw-dw-btn-xs tw-dw-btn-success activate_account"><i class="fa fa-power-off"></i> '.__('messages.activate').'</button>';
                                }

                                return $action;
                            })
                            ->editColumn('name', function ($row) {
                                if ($row->is_closed == 1) {
                                    return $row->name.' <small class="label pull-right bg-red no-print">'.__('account.closed').'</small><span class="print_section">('.__('account.closed').')</span>';
                                } else {
                                    return $row->name;
                                }
                            })
                            ->addColumn('debit_balance', function ($row) {
                                $accountTypeLabel = ! empty($row->parent_account_type_name) ? $row->parent_account_type_name : $row->account_type_name;
                                $displayBalance = \App\Account::getDisplayBalance($row->balance);
                                $balanceSide = \App\Account::getBalanceSide($accountTypeLabel, $row->balance);

                                $amount = $balanceSide === 'Dr' ? $displayBalance : 0;

                                return '<span class="debit_balance" data-orig-value="'.$amount.'">'.($amount > 0 ? $this->commonUtil->num_f($amount, true) : '').'</span>';
                            })
                            ->addColumn('credit_balance', function ($row) {
                                $accountTypeLabel = ! empty($row->parent_account_type_name) ? $row->parent_account_type_name : $row->account_type_name;
                                $displayBalance = \App\Account::getDisplayBalance($row->balance);
                                $balanceSide = \App\Account::getBalanceSide($accountTypeLabel, $row->balance);

                                $amount = $balanceSide === 'Cr' ? $displayBalance : 0;

                                return '<span class="credit_balance" data-orig-value="'.$amount.'">'.($amount > 0 ? $this->commonUtil->num_f($amount, true) : '').'</span>';
                            })
                            ->editColumn('account_type', function ($row) {
                                $account_type = '';
                                if (! empty($row->account_type->parent_account)) {
                                    $account_type .= $row->account_type->parent_account->name.' - ';
                                }
                                if (! empty($row->account_type)) {
                                    $account_type .= $row->account_type->name;
                                }

                                return $account_type;
                            })
                            ->editColumn('parent_account_type_name', function ($row) {
                                $parent_account_type_name = empty($row->parent_account_type_name) ? $row->account_type_name : $row->parent_account_type_name;

                                return $parent_account_type_name;
                            })
                            ->editColumn('account_type_name', function ($row) {
                                $account_type_name = empty($row->parent_account_type_name) ? '' : $row->account_type_name;

                                return $account_type_name;
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
                            ->removeColumn('id')
                            ->removeColumn('is_closed')
                            ->rawColumns(['action', 'debit_balance', 'credit_balance', 'name', 'account_details'])
                            ->make(true);
        }

        $not_linked_payments = TransactionPayment::leftjoin(
            'transactions as T',
            'transaction_payments.transaction_id',
            '=',
            'T.id'
        )
                                    ->whereNull('transaction_payments.parent_id')
                                    ->where('method', '!=', 'advance')
                                    ->where('transaction_payments.business_id', $business_id)
                                    ->whereNull('account_id')
                                    ->count();

        // Payments that have an account_id but no corresponding AccountTransaction yet
        $backfill_missing_count = TransactionPayment::whereNotNull('account_id')
                                ->whereNull('parent_id')
                                ->where('method', '!=', 'advance')
                                ->where('business_id', $business_id)
                                ->whereDoesntHave('account_transactions')
                                ->count();

        // $capital_account_count = Account::where('business_id', $business_id)
        //                             ->NotClosed()
        //                             ->where('account_type', 'capital')
        //                             ->count();

        $account_types = AccountType::where('business_id', $business_id)
                         ->whereNull('parent_account_type_id')
                         ->orderedForChart()
                         ->with(['sub_types'])
                         ->get();

        return view('account.index')
            ->with(compact('not_linked_payments', 'account_types', 'backfill_missing_count'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create()
    {
        if (! request()->ajax()) {
            abort(404);
        }

        if (! auth()->user()->can('account.access')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = session()->get('user.business_id');
        $account_types = AccountType::where('business_id', $business_id)
                         ->whereNull('parent_account_type_id')
                         ->orderedForChart()
                         ->with(['sub_types'])
                         ->get();

        return view('account.create')
                ->with(compact('account_types'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  Request  $request
     * @return Response
     */
    public function store(Request $request)
    {
        if (! auth()->user()->can('account.access')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            try {
                $input = $request->only(['name', 'account_number', 'note', 'account_type_id', 'account_details']);
                $business_id = $request->session()->get('user.business_id');
                $user_id = $request->session()->get('user.id');
                $input['business_id'] = $business_id;
                $input['created_by'] = $user_id;

                $account = Account::create($input);

                //Opening Balance
                $opening_bal = $request->input('opening_balance');

                if (! empty($opening_bal)) {
                    $account->loadMissing('account_type.parent_account');

                    $ob_transaction_data = [
                        'amount' => $this->commonUtil->num_uf($opening_bal),
                        'account_id' => $account->id,
                        'type' => $account->isDebitNormalAccount() ? 'debit' : 'credit',
                        'sub_type' => 'opening_balance',
                        'operation_date' => \Carbon::now(),
                        'created_by' => $user_id,
                    ];

                    AccountTransaction::createAccountTransaction($ob_transaction_data);
                }

                $output = ['success' => true,
                    'msg' => __('account.account_created_success'),
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

    /**
     * Show the specified resource.
     *
     * @return Response
     */
    public function show($id)
    {
        if (! auth()->user()->can('account.access')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        $account = Account::where('business_id', $business_id)
                        ->with(['account_type', 'account_type.parent_account'])
                        ->findOrFail($id);
        $accountTypeLabel = ! empty($account->account_type->parent_account)
            ? $account->account_type->parent_account->name
            : optional($account->account_type)->name;
        $quotedAccountTypeLabel = DB::getPdo()->quote((string) $accountTypeLabel);
        $runningBalanceExpression = \App\Account::typeAwareBalanceExpression(
            $quotedAccountTypeLabel,
            'account_transactions.type',
            'account_transactions.amount',
            'account_transactions.sub_type'
        );
        $currentRunningBalanceExpression = \App\Account::typeAwareBalanceExpression(
            $quotedAccountTypeLabel,
            'type',
            'amount',
            'sub_type'
        );

        if (request()->ajax()) {
            $start_date = request()->input('start_date');
            $end_date = request()->input('end_date');

            $bal_before_start_date = 0;
            if (! empty($start_date)) {
                $before_bal_query = AccountTransaction::join(
                    'accounts as A',
                    'account_transactions.account_id',
                    '=',
                    'A.id'
                )
                        ->where('A.business_id', $business_id)
                        ->where('A.id', $id)
                        ->select([
                            DB::raw($runningBalanceExpression.' as prev_bal'), ])
                        ->where('account_transactions.operation_date', '<', $start_date)
                        ->whereNull('account_transactions.deleted_at');
                if (! empty(request()->input('type'))) {
                    $before_bal_query->where('account_transactions.type', request()->input('type'));
                }

                $bal_before_start_date = (float) optional($before_bal_query->first())->prev_bal;
            }

            $accounts = AccountTransaction::join(
                'accounts as A',
                'account_transactions.account_id',
                '=',
                'A.id'
            )
            ->leftJoin('transaction_payments AS tp', 'account_transactions.transaction_payment_id', '=', 'tp.id')
            ->leftJoin('contacts AS c', 'tp.payment_for', '=', 'c.id')
            ->leftJoin('users AS u', 'account_transactions.created_by', '=', 'u.id')
            ->leftjoin(
                    'transaction_payments as child_payments',
                    'tp.id',
                    '=',
                    'child_payments.parent_id'
                )
            ->leftjoin(
                'transactions as child_sells',
                'child_sells.id',
                '=',
                'child_payments.transaction_id'
            )
            ->with(['transaction', 'transaction.contact', 'transfer_transaction', 'transaction.transaction_for'])
                            ->where('A.business_id', $business_id)
                            ->where('A.id', $id)
                            ->with(['transaction', 'transaction.contact', 'transfer_transaction', 'media', 'transfer_transaction.media'])
                            ->select(['account_transactions.type', 'account_transactions.amount', 'operation_date',
                                'account_transactions.sub_type', 'transfer_transaction_id',
                                'A.id as account_id',
                                'account_transactions.transaction_id',
                                'account_transactions.id',
                                'account_transactions.note',
                                'tp.is_advance',
                                'tp.is_return',
                                'tp.payment_ref_no',
                                'tp.method',
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
                                DB::raw("CONCAT(COALESCE(u.surname, ''),' ',COALESCE(u.first_name, ''),' ',COALESCE(u.last_name,'')) as added_by"),
                                'c.name as payment_for_contact',
                                'c.type as payment_for_type',
                                'c.supplier_business_name as payment_for_business_name',
                                DB::raw('SUM(child_payments.amount) total_recovered'),
                                DB::raw('GROUP_CONCAT(child_sells.invoice_no) as child_sells'),
                            ])
                             ->groupBy('account_transactions.id')
                             //->orderBy('account_transactions.id', 'asc')
                             ->orderBy('account_transactions.operation_date', 'asc');

            if (! empty(request()->input('type'))) {
                $accounts->where('account_transactions.type', request()->input('type'));
            }

            if (! empty($start_date) && ! empty($end_date)) {
                $accounts->whereDate('operation_date', '>=', $start_date)
                        ->whereDate('operation_date', '<=', $end_date);
            }

            $payment_types = $this->commonUtil->payment_types(null, true, $business_id);

            return DataTables::of($accounts)
                        ->editColumn('method', function ($row) use ($payment_types) {
                            if (! empty($row->method) && isset($payment_types[$row->method])) {
                                return $payment_types[$row->method];
                            } else {
                                return '';
                            }
                        })
                        ->addColumn('payment_details', function ($row) {
                            $arr = [];
                            if (! empty($row->transaction_no)) {
                                $arr[] = '<b>'.__('lang_v1.transaction_no').'</b>: '.$row->transaction_no;
                            }

                            if ($row->method == 'card' && ! empty($row->card_transaction_number)) {
                                $arr[] = '<b>'.__('lang_v1.card_transaction_no').'</b>: '.$row->card_transaction_number;
                            }

                            if ($row->method == 'card' && ! empty($row->card_number)) {
                                $arr[] = '<b>'.__('lang_v1.card_no').'</b>: '.$row->card_number;
                            }
                            if ($row->method == 'card' && ! empty($row->card_type)) {
                                $arr[] = '<b>'.__('lang_v1.card_type').'</b>: '.$row->card_type;
                            }
                            if ($row->method == 'card' && ! empty($row->card_holder_name)) {
                                $arr[] = '<b>'.__('lang_v1.card_holder_name').'</b>: '.$row->card_holder_name;
                            }
                            if ($row->method == 'card' && ! empty($row->card_month)) {
                                $arr[] = '<b>'.__('lang_v1.month').'</b>: '.$row->card_month;
                            }
                            if ($row->method == 'card' && ! empty($row->card_year)) {
                                $arr[] = '<b>'.__('lang_v1.year').'</b>: '.$row->card_year;
                            }
                            if ($row->method == 'card' && ! empty($row->card_security)) {
                                $arr[] = '<b>'.__('lang_v1.security_code').'</b>: '.$row->card_security;
                            }
                            if (! empty($row->cheque_number)) {
                                $arr[] = '<b>'.__('lang_v1.cheque_no').'</b>: '.$row->cheque_number;
                            }
                            if (! empty($row->bank_account_number)) {
                                $arr[] = '<b>'.__('lang_v1.card_no').'</b>: '.$row->bank_account_number;
                            }

                            return implode(', ', $arr);
                        })
                            ->addColumn('debit', function ($row) {
                                if ($row->type == 'debit') {
                                    return '<span class="debit" data-orig-value="'.$row->amount.'">'.$this->commonUtil->num_f($row->amount, true).'</span>';
                                }

                                return '';
                            })
                            ->addColumn('credit', function ($row) {
                                if ($row->type == 'credit') {
                                    return '<span class="credit"  data-orig-value="'.$row->amount.'">'.$this->commonUtil->num_f($row->amount, true).'</span>';
                                }

                                return '';
                            })
                            ->addColumn('balance', function ($row) use ($bal_before_start_date, $start_date, $currentRunningBalanceExpression, $accountTypeLabel) {
                                //TODO:: Need to fix same balance showing for transactions having same operation date
                                $currentBalanceQuery = AccountTransaction::where('account_id', $row->account_id)
                                                ->where('operation_date', '<=', $row->operation_date)
                                                ->whereNull('deleted_at');

                                if (! empty($start_date)) {
                                    $currentBalanceQuery->where('operation_date', '>=', $start_date);
                                }

                                $current_bal = (float) optional($currentBalanceQuery
                                                ->select(DB::raw($currentRunningBalanceExpression.' as balance'))
                                                ->first())->balance;
                                $bal = $bal_before_start_date + $current_bal;
                                $displayBalance = \App\Account::getDisplayBalance($bal);
                                $balanceSide = \App\Account::getBalanceSide($accountTypeLabel, $bal);

                                return '<span class="balance" data-orig-value="'.$bal.'" data-display-value="'.$displayBalance.'" data-balance-side="'.$balanceSide.'">'.$this->commonUtil->num_f($displayBalance, true).(! empty($balanceSide) ? ' <small class="text-muted">'.$balanceSide.'</small>' : '').'</span>';
                            })
                            ->editColumn('operation_date', function ($row) {
                                return $this->commonUtil->format_date($row->operation_date, true);
                            })
                            ->editColumn('sub_type', function ($row) {
                                return $this->__getPaymentDetails($row);
                            })
                            ->editColumn('note', function ($row) {
                                $systemNotes = [
                                    'sell_payment_account',
                                    'sell_payment_receivable',
                                    'purchase_payment_account',
                                    'purchase_payment_payable',
                                ];

                                return in_array($row->note, $systemNotes) ? '' : $row->note;
                            })
                            ->editColumn('action', function ($row) {
                                $action = '';
                                if (auth()->user()->can('delete_account_transaction')) {
                                    if ($row->sub_type == 'fund_transfer' || $row->sub_type == 'deposit') {
                                        $action .= '<button type="button" class="tw-dw-btn tw-dw-btn-xs tw-dw-btn-outline  tw-dw-btn-error delete_account_transaction" data-href="'.action([\App\Http\Controllers\AccountController::class, 'destroyAccountTransaction'], [$row->id]).'"><i class="fa fa-trash"></i> '.__('messages.delete').'</button>';
                                    }
                                }
                                if (auth()->user()->can('edit_account_transaction')) {
                                    if ($row->sub_type == 'fund_transfer' || $row->sub_type == 'deposit' || $row->sub_type == 'opening_balance') {
                                        $action .= ' <button type="button" class="tw-dw-btn tw-dw-btn-xs tw-dw-btn-outline  tw-dw-btn-primary btn-modal" data-container="#edit_account_transaction" data-href="'.action([\App\Http\Controllers\AccountController::class, 'editAccountTransaction'], [$row->id]).'"><i class="fa fa-edit"></i> '.__('messages.edit').'</button>';
                                    }
                                }

                                if (! empty($row->media->first()) || (! empty($row->transfer_transaction && ! empty($row->transfer_transaction->media->first())))) {
                                    $display_url = ! empty($row->media->first()) ? $row->media->first()->display_url : $row->transfer_transaction->media->first()->display_url;

                                    $display_name = ! empty($row->media->first()) ? $row->media->first()->display_name : $row->transfer_transaction->media->first()->display_name;

                                    $action .= '&nbsp; <a class="tw-dw-btn tw-dw-btn-xs tw-dw-btn-outline  tw-dw-btn-accent" href="'.$display_url.'" download="'.$display_name.'"><i class="fa fa-download"></i> '.__('purchase.download_document').'</a>';
                                }

                                return $action;
                            })
                            ->filterColumn('added_by', function ($query, $keyword) {
                                $query->whereRaw("CONCAT(COALESCE(u.surname, ''), ' ', COALESCE(u.first_name, ''), ' ', COALESCE(u.last_name, '')) like ?", ["%{$keyword}%"]);
                            })
                            ->removeColumn('id')
                            ->removeColumn('is_closed')
                            ->rawColumns(['credit', 'debit', 'balance', 'sub_type', 'action', 'payment_details'])
                            ->make(true);
        }
        return view('account.show')
                ->with(compact('account'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return Response
     */
    public function edit($id)
    {
        if (! auth()->user()->can('account.access')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            $business_id = request()->session()->get('user.business_id');
            $account = Account::where('business_id', $business_id)
                                ->find($id);

            $account_types = AccountType::where('business_id', $business_id)
                                     ->whereNull('parent_account_type_id')
                                     ->orderedForChart()
                                     ->with(['sub_types'])
                                     ->get();

            return view('account.edit')
                ->with(compact('account', 'account_types'));
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  Request  $request
     * @return Response
     */
    public function update(Request $request, $id)
    {
        if (! auth()->user()->can('account.access')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            try {
                $input = $request->only(['name', 'account_number', 'note', 'account_type_id', 'account_details']);

                $business_id = request()->session()->get('user.business_id');
                $account = Account::where('business_id', $business_id)
                            ->findOrFail($id);
                $account->name = $input['name'];
                $account->account_number = $input['account_number'];
                $account->note = $input['note'];
                $account->account_type_id = $input['account_type_id'];
                $account->account_details = $input['account_details'];
                $account->save();

                $output = ['success' => true,
                    'msg' => __('account.account_updated_success'),
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

    /**
     * Remove the specified resource from storage.
     *
     * @return Response
     */
    public function destroyAccountTransaction($id)
    {
        if (! auth()->user()->can('delete_account_transaction')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            try {
                $business_id = request()->session()->get('user.business_id');

                $account_transaction = AccountTransaction::findOrFail($id);

                if (in_array($account_transaction->sub_type, ['fund_transfer', 'deposit', 'journal_entry'])) {
                    //Delete transfer transaction for fund transfer
                    if (! empty($account_transaction->transfer_transaction_id)) {
                        $transfer_transaction = AccountTransaction::findOrFail($account_transaction->transfer_transaction_id);
                        $transfer_transaction->delete();
                    }
                    $account_transaction->delete();
                }

                $output = ['success' => true,
                    'msg' => __('lang_v1.deleted_success'),
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

    /**
     * Closes the specified account.
     *
     * @return Response
     */
    public function close($id)
    {
        if (! auth()->user()->can('account.access')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            try {
                $business_id = session()->get('user.business_id');

                $account = Account::where('business_id', $business_id)
                                                    ->findOrFail($id);
                $account->is_closed = 1;
                $account->save();

                $output = ['success' => true,
                    'msg' => __('account.account_closed_success'),
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

    /**
     * Shows form to transfer fund.
     *
     * @param  int  $id
     * @return Response
     */
    public function getFundTransfer($id)
    {
        if (! auth()->user()->can('account.access')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            $business_id = session()->get('user.business_id');

            $from_account = Account::where('business_id', $business_id)
                            ->NotClosed()
                            ->TransferEligible()
                            ->with(['account_type.parent_account'])
                            ->find($id);

            $to_accounts = Account::where('business_id', $business_id)
                            ->NotClosed()
                            ->TransferEligible()
                            ->pluck('name', 'id');

            return view('account.transfer')
                ->with(compact('from_account', 'to_accounts'));
        }
    }

    /**
     * Transfers fund from one account to another.
     *
     * @return Response
     */
    public function postFundTransfer(Request $request)
    {
        if (! auth()->user()->can('account.access')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = session()->get('user.business_id');

            $amount = $this->commonUtil->num_uf($request->input('amount'));
            $from = $request->input('from_account');
            $to = $request->input('to_account');
            $note = $request->input('note');
            if (! empty($amount)) {
                $from_account = Account::where('business_id', $business_id)
                    ->with(['account_type.parent_account'])
                    ->findOrFail($from);
                $to_account = Account::where('business_id', $business_id)
                    ->with(['account_type.parent_account'])
                    ->findOrFail($to);

                if ($from_account->isProfitAndLossAccount() || $to_account->isProfitAndLossAccount()) {
                    throw new \Exception(__('messages.something_went_wrong'));
                }

                $debit_data = [
                    'amount' => $amount,
                    'account_id' => $from,
                    'type' => 'debit',
                    'sub_type' => 'fund_transfer',
                    'created_by' => session()->get('user.id'),
                    'note' => $note,
                    'transfer_account_id' => $to,
                    'operation_date' => $this->commonUtil->uf_date($request->input('operation_date'), true),
                ];

                DB::beginTransaction();
                $debit = AccountTransaction::createAccountTransaction($debit_data);

                $credit_data = [
                    'amount' => $amount,
                    'account_id' => $to,
                    'type' => 'credit',
                    'sub_type' => 'fund_transfer',
                    'created_by' => session()->get('user.id'),
                    'note' => $note,
                    'transfer_account_id' => $from,
                    'transfer_transaction_id' => $debit->id,
                    'operation_date' => $this->commonUtil->uf_date($request->input('operation_date'), true),
                ];

                $credit = AccountTransaction::createAccountTransaction($credit_data);

                $debit->transfer_transaction_id = $credit->id;
                $debit->save();

                Media::uploadMedia($business_id, $debit, $request, 'document');

                DB::commit();
            }

            $output = ['success' => true,
                'msg' => __('account.fund_transfered_success'),
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

            $output = ['success' => false,
                'msg' => __('messages.something_went_wrong'),
            ];
        }

        return redirect()->action([\App\Http\Controllers\AccountController::class, 'index'])->with('status', $output);
    }

    /**
     * Shows deposit form.
     *
     * @param  int  $id
     * @return Response
     */
    public function getDeposit($id)
    {
        if (! auth()->user()->can('account.access')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            $business_id = session()->get('user.business_id');

            $account = Account::where('business_id', $business_id)
                            ->NotClosed()
                            ->TransferEligible()
                            ->with(['account_type.parent_account'])
                            ->find($id);

            $from_accounts = Account::where('business_id', $business_id)
                            ->NotClosed()
                            ->TransferEligible()
                            ->pluck('name', 'id');

            return view('account.deposit')
                ->with(compact('account', 'account', 'from_accounts'));
        }
    }

    /**
     * Deposits amount.
     *
     * @param  Request  $request
     * @return json
     */
    public function postDeposit(Request $request)
    {
        if (! auth()->user()->can('account.access')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = session()->get('user.business_id');

            $amount = $this->commonUtil->num_uf($request->input('amount'));
            $account_id = $request->input('account_id');
            $note = $request->input('note');

            $account = Account::where('business_id', $business_id)
                            ->with(['account_type.parent_account'])
                            ->findOrFail($account_id);

            if ($account->isProfitAndLossAccount()) {
                throw new \Exception(__('messages.something_went_wrong'));
            }

            if (! empty($amount)) {
                $credit_data = [
                    'amount' => $amount,
                    'account_id' => $account_id,
                    'type' => 'credit',
                    'sub_type' => 'deposit',
                    'operation_date' => $this->commonUtil->uf_date($request->input('operation_date'), true),
                    'created_by' => session()->get('user.id'),
                    'note' => $note,
                ];
                $credit = AccountTransaction::createAccountTransaction($credit_data);

                $from_account = $request->input('from_account');
                if (! empty($from_account)) {
                    $from_account_model = Account::where('business_id', $business_id)
                        ->with(['account_type.parent_account'])
                        ->findOrFail($from_account);

                    if ($from_account_model->isProfitAndLossAccount()) {
                        throw new \Exception(__('messages.something_went_wrong'));
                    }

                    $debit_data = $credit_data;
                    $debit_data['type'] = 'debit';
                    $debit_data['account_id'] = $from_account;
                    $debit_data['transfer_transaction_id'] = $credit->id;

                    $debit = AccountTransaction::createAccountTransaction($debit_data);

                    $credit->transfer_transaction_id = $debit->id;

                    $credit->save();
                }
            }

            $output = ['success' => true,
                'msg' => __('account.deposited_successfully'),
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

            $output = ['success' => false,
                'msg' => __('messages.something_went_wrong'),
            ];
        }

        return $output;
    }

    /**
     * Calculates account current balance.
     *
     * @param  int  $id
     * @return json
     */
    public function getAccountBalance($id)
    {
        if (! auth()->user()->can('account.access')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = session()->get('user.business_id');
        $account = Account::leftjoin(
            'account_transactions as AT',
            'AT.account_id',
            '=',
            'accounts.id'
        )
            ->whereNull('AT.deleted_at')
            ->where('accounts.business_id', $business_id)
            ->where('accounts.id', $id)
            ->leftJoin('account_types as ats', 'accounts.account_type_id', '=', 'ats.id')
            ->leftJoin('account_types as pat', 'ats.parent_account_type_id', '=', 'pat.id')
            ->select('accounts.*', 'ats.name as account_type_name', 'pat.name as parent_account_type_name', DB::raw(\App\Account::typeAwareBalanceExpression('COALESCE(pat.name, ats.name)', 'AT.type', 'AT.amount', 'AT.sub_type').' as balance'))
            ->first();

        if (! empty($account)) {
            $accountTypeLabel = ! empty($account->parent_account_type_name) ? $account->parent_account_type_name : $account->account_type_name;
            $account->display_balance = \App\Account::getDisplayBalance($account->balance);
            $account->balance_side = \App\Account::getBalanceSide($accountTypeLabel, $account->balance);
        }

        return $account;
    }

    /**
     * Show the specified resource.
     *
     * @return Response
     */
    public function cashFlow()
    {
        if (! auth()->user()->can('account.access')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        if (request()->ajax()) {
            $accounts = AccountTransaction::join(
                'accounts as A',
                'account_transactions.account_id',
                '=',
                'A.id'
                )
                ->leftJoin('account_types as ats', 'A.account_type_id', '=', 'ats.id')
                ->leftJoin('account_types as pat', 'ats.parent_account_type_id', '=', 'pat.id')
                ->leftjoin(
                    'transaction_payments as TP',
                    'account_transactions.transaction_payment_id',
                    '=',
                    'TP.id'
                )
                ->leftjoin(
                    'transaction_payments as child_payments',
                    'TP.id',
                    '=',
                    'child_payments.parent_id'
                )
                ->leftjoin(
                    'transactions as child_sells',
                    'child_sells.id',
                    '=',
                    'child_payments.transaction_id'
                )
                ->leftJoin('users AS u', 'account_transactions.created_by', '=', 'u.id')
                ->leftJoin('contacts AS c', 'TP.payment_for', '=', 'c.id')
                ->where('A.business_id', $business_id)
                ->with(['transaction', 'transaction.contact', 'transfer_transaction', 'transaction.transaction_for'])
                ->select(['account_transactions.type', 'account_transactions.amount', 'operation_date',
                    'account_transactions.sub_type', 'transfer_transaction_id',
                    'account_transactions.transaction_id',
                    'account_transactions.id',
                    'A.name as account_name',
                    'TP.payment_ref_no as payment_ref_no',
                    'TP.is_return',
                    'TP.is_advance',
                    'TP.method',
                    'TP.transaction_no',
                    'TP.card_transaction_number',
                    'TP.card_number',
                    'TP.card_type',
                    'TP.card_holder_name',
                    'TP.card_month',
                    'TP.card_year',
                    'TP.card_security',
                    'TP.cheque_number',
                    'TP.bank_account_number',
                    'account_transactions.account_id',
                    DB::raw("CONCAT(COALESCE(u.surname, ''),' ',COALESCE(u.first_name, ''),' ',COALESCE(u.last_name,'')) as added_by"),
                    'c.name as payment_for_contact',
                    'c.type as payment_for_type',
                    'c.supplier_business_name as payment_for_business_name',
                    DB::raw('SUM(child_payments.amount) total_recovered'),
                    DB::raw("GROUP_CONCAT(child_sells.invoice_no SEPARATOR ', ') as child_sells"),
                ])
                 ->groupBy('account_transactions.id')
                 ->orderBy('account_transactions.operation_date', 'asc');
            if (! empty(request()->input('type'))) {
                $accounts->where('account_transactions.type', request()->input('type'));
            }

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
                $accounts->whereIn('A.id', $account_ids);
            }

            $location_id = request()->input('location_id');
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

                    $accounts->whereIn('A.id', $account_ids);
                }
            }

            if (! empty(request()->input('account_id'))) {
                $accounts->where('A.id', request()->input('account_id'));
            }

            $start_date = request()->input('start_date');
            $end_date = request()->input('end_date');

            if (! empty($start_date) && ! empty($end_date)) {
                $accounts->whereBetween(DB::raw('date(operation_date)'), [$start_date, $end_date]);
            }

            if (request()->has('only_payment_recovered')) {
                //payment date is today and transaction date is less than today
                $accounts->leftJoin('transactions AS t', 'TP.transaction_id', '=', 't.id')
                    ->whereDate('operation_date', '=', \Carbon::now()->format('Y-m-d'))
                    ->where(function ($q) {
                        $q->whereDate('t.transaction_date', '<',
                        \Carbon::now()->format('Y-m-d'))
                        ->orWhere('TP.is_advance', 1);
                    });
            }

            $payment_types = $this->commonUtil->payment_types(null, true, $business_id);

            return DataTables::of($accounts)
                ->editColumn('method', function ($row) use ($payment_types) {
                    if (! empty($row->method) && isset($payment_types[$row->method])) {
                        return $payment_types[$row->method];
                    } else {
                        return '';
                    }
                })
                ->addColumn('payment_details', function ($row) {
                    $arr = [];
                    if (! empty($row->transaction_no)) {
                        $arr[] = '<b>'.__('lang_v1.transaction_no').'</b>: '.$row->transaction_no;
                    }

                    if ($row->method == 'card' && ! empty($row->card_transaction_number)) {
                        $arr[] = '<b>'.__('lang_v1.card_transaction_no').'</b>: '.$row->card_transaction_number;
                    }

                    if ($row->method == 'card' && ! empty($row->card_number)) {
                        $arr[] = '<b>'.__('lang_v1.card_no').'</b>: '.$row->card_number;
                    }
                    if ($row->method == 'card' && ! empty($row->card_type)) {
                        $arr[] = '<b>'.__('lang_v1.card_type').'</b>: '.$row->card_type;
                    }
                    if ($row->method == 'card' && ! empty($row->card_holder_name)) {
                        $arr[] = '<b>'.__('lang_v1.card_holder_name').'</b>: '.$row->card_holder_name;
                    }
                    if ($row->method == 'card' && ! empty($row->card_month)) {
                        $arr[] = '<b>'.__('lang_v1.month').'</b>: '.$row->card_month;
                    }
                    if ($row->method == 'card' && ! empty($row->card_year)) {
                        $arr[] = '<b>'.__('lang_v1.year').'</b>: '.$row->card_year;
                    }
                    if ($row->method == 'card' && ! empty($row->card_security)) {
                        $arr[] = '<b>'.__('lang_v1.security_code').'</b>: '.$row->card_security;
                    }
                    if (! empty($row->cheque_number)) {
                        $arr[] = '<b>'.__('lang_v1.cheque_no').'</b>: '.$row->cheque_number;
                    }
                    if (! empty($row->bank_account_number)) {
                        $arr[] = '<b>'.__('lang_v1.card_no').'</b>: '.$row->bank_account_number;
                    }

                    return implode(', ', $arr);
                })
                ->addColumn('debit', '@if($type == "debit")<span class="debit" data-orig-value="{{$amount}}">@format_currency($amount)</span>@endif')
                ->addColumn('credit', '@if($type == "credit")<span class="debit" data-orig-value="{{$amount}}">@format_currency($amount)</span>@endif')
                ->addColumn('balance', function ($row) {
                    $balance = AccountTransaction::join('accounts as A', 'account_transactions.account_id', '=', 'A.id')
                                    ->leftJoin('account_types as ats', 'A.account_type_id', '=', 'ats.id')
                                    ->leftJoin('account_types as pat', 'ats.parent_account_type_id', '=', 'pat.id')
                                    ->where('account_transactions.account_id', $row->account_id)
                                    ->where('operation_date', '<=', $row->operation_date)
                                    ->whereNull('account_transactions.deleted_at')
                                    ->select(DB::raw(\App\Account::typeAwareBalanceExpression(
                                        'COALESCE(pat.name, ats.name)',
                                        'account_transactions.type',
                                        'account_transactions.amount',
                                        'account_transactions.sub_type'
                                    ).' as balance'))
                                    ->first()->balance;

                    return '<span class="balance" data-orig-value="'.$balance.'">'.$this->commonUtil->num_f($balance, true).'</span>';
                })
                ->addColumn('total_balance', function ($row) use ($business_id, $account_ids, $permitted_locations) {
                    $query = AccountTransaction::join(
                                        'accounts as A',
                                        'account_transactions.account_id',
                                        '=',
                                        'A.id'
                                    )
                                    ->leftJoin('account_types as ats', 'A.account_type_id', '=', 'ats.id')
                                    ->leftJoin('account_types as pat', 'ats.parent_account_type_id', '=', 'pat.id')
                                    ->where('A.business_id', $business_id)
                                    ->where('operation_date', '<=', $row->operation_date)
                                    ->whereNull('account_transactions.deleted_at')
                                    ->select(DB::raw(\App\Account::typeAwareBalanceExpression(
                                        'COALESCE(pat.name, ats.name)',
                                        'account_transactions.type',
                                        'account_transactions.amount',
                                        'account_transactions.sub_type'
                                    ).' as balance'));

                    if (! empty(request()->input('type'))) {
                        $query->where('type', request()->input('type'));
                    }
                    if ($permitted_locations != 'all' || ! empty(request()->input('location_id'))) {
                        $query->whereIn('A.id', $account_ids);
                    }

                    if (! empty(request()->input('account_id'))) {
                        $query->where('A.id', request()->input('account_id'));
                    }

                    $balance = $query->first()->balance;

                    return '<span class="total_balance" data-orig-value="'.$balance.'">'.$this->commonUtil->num_f($balance, true).'</span>';
                })
                ->editColumn('operation_date', function ($row) {
                    return $this->commonUtil->format_date($row->operation_date, true);
                })
                ->editColumn('sub_type', function ($row) {
                    return $this->__getPaymentDetails($row);
                })
                ->removeColumn('id')
                ->rawColumns(['credit', 'debit', 'balance', 'sub_type', 'total_balance', 'payment_details'])
                ->make(true);
        }
        $accounts = Account::forDropdown($business_id, false);

        $business_locations = BusinessLocation::forDropdown($business_id, true);

        return view('account.cash_flow')
                 ->with(compact('accounts', 'business_locations'));
    }

    public function __getPaymentDetails($row)
    {
        $details = '';
        if (! empty($row->sub_type)) {
            $details = __('account.'.$row->sub_type);
            if (in_array($row->sub_type, ['fund_transfer', 'deposit']) && ! empty($row->transfer_transaction)) {
                if ($row->type == 'credit') {
                    $details .= ' ( '.__('account.from').': '.$row->transfer_transaction->account->name.')';
                } else {
                    $details .= ' ( '.__('account.to').': '.$row->transfer_transaction->account->name.')';
                }
            }
        } else {
            if (! empty($row->transaction->type)) {
                if ($row->transaction->type == 'purchase') {
                    $headline = in_array($row->note, ['purchase_payment_account', 'purchase_payment_payable'])
                        ? __('lang_v1.payment_paid')
                        : __('lang_v1.purchase');
                    $details = $headline.'<br><b>'.__('purchase.supplier').':</b> '.$row->transaction->contact->full_name_with_business.'<br><b>'.
                    __('purchase.ref_no').':</b> <a href="#" data-href="'.action([\App\Http\Controllers\PurchaseController::class, 'show'], [$row->transaction->id]).'" class="btn-modal" data-container=".view_modal">'.$row->transaction->ref_no.'</a>';
                } elseif ($row->transaction->type == 'expense') {
                    $details = __('lang_v1.expense').'<br><b>'.__('purchase.ref_no').':</b>'.$row->transaction->ref_no;
                } elseif ($row->transaction->type == 'sell') {
                    $is_return = $row->is_return == 1 ? ' ('.__('lang_v1.change_return').')' : '';
                    $headline = in_array($row->note, ['sell_payment_account', 'sell_payment_receivable'])
                        ? __('lang_v1.payment_received')
                        : __('sale.sale');
                    $details = $headline.$is_return.'<br><b>'.__('contact.customer').':</b> '.$row->transaction->contact->full_name_with_business.'<br><b>'.
                    __('sale.invoice_no').':</b> <a href="#" data-href="'.action([\App\Http\Controllers\SellController::class, 'show'], [$row->transaction->id]).'" class="btn-modal" data-container=".view_modal">'.$row->transaction->invoice_no.'</a>';
                }
            } else {
                //for contact payment which is not advance
                if ($row->is_advance != 1) {
                    if ($row->payment_for_type == 'supplier') {
                        $details .= '<b>'.__('purchase.supplier').':</b> ';
                    } elseif ($row->payment_for_type == 'customer') {
                        $details .= '<b>'.__('contact.customer').':</b> ';
                    } else {
                        $details .= '<b>'.__('account.payment_for').':</b> ';
                    }

                    if (! empty($row->payment_for_business_name)) {
                        $details .= $row->payment_for_business_name.', ';
                    }
                    if (! empty($row->payment_for_contact)) {
                        $details .= $row->payment_for_contact;
                    }
                }
            }
        }

        if (! empty($row->payment_ref_no)) {
            if (! empty($details)) {
                $details .= '<br/>';
            }

            $details .= '<b>'.__('lang_v1.pay_reference_no').':</b> '.$row->payment_ref_no;
        }
        if (! empty($row->transaction->contact) && $row->transaction->type == 'expense') {
            if (! empty($details)) {
                $details .= '<br/>';
            }

            $details .= '<b>';
            $details .= __('lang_v1.expense_for_contact');
            $details .= ':</b> '.$row->transaction->contact->full_name_with_business;
        }

        if (! empty($row->transaction->transaction_for)) {
            if (! empty($details)) {
                $details .= '<br/>';
            }

            $details .= '<b>'.__('expense.expense_for').':</b> '.$row->transaction->transaction_for->user_full_name;
        }

        if ($row->is_advance == 1) {
            $total_advance = $row->amount - $row->total_recovered;
            $details .= '<br>';

            if ($total_advance > 0) {
                $details .= '<b>'.__('lang_v1.advance_payment').'</b>: '.$this->commonUtil->num_f($total_advance, true).'<br>';
            }

            if (! empty($row->child_sells)) {
                $details .= '<b>'.__('lang_v1.payments_recovered_for').'</b>: '.$row->child_sells.'<br>';
            }

            if ($row->payment_for_type == 'supplier') {
                $details .= '<b>'.__('purchase.supplier').':</b> ';
            } elseif ($row->payment_for_type == 'customer') {
                $details .= '<b>'.__('contact.customer').':</b> ';
            } else {
                $details .= '<b>'.__('account.payment_for').':</b> ';
            }

            if (! empty($row->payment_for_business_name)) {
                $details .= $row->payment_for_business_name.', ';
            }
            if (! empty($row->payment_for_contact)) {
                $details .= $row->payment_for_contact;
            }
        }

        if (! empty($row->added_by)) {
            $details .= '<br><b>'.__('lang_v1.added_by').':</b> '.$row->added_by;
        }

        return $details;
    }

    /**
     * activate the specified account.
     *
     * @return Response
     */
    public function activate($id)
    {
        if (! auth()->user()->can('account.access')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            try {
                $business_id = session()->get('user.business_id');

                $account = Account::where('business_id', $business_id)
                                ->findOrFail($id);

                $account->is_closed = 0;
                $account->save();

                $output = ['success' => true,
                    'msg' => __('lang_v1.success'),
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

    /**
     * Edit the specified resource from storage.
     *
     * @return Response
     */
    public function editAccountTransaction($id)
    {
        if (! request()->ajax()) {
            abort(404);
        }

        if (! auth()->user()->can('edit_account_transaction')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        $account_transaction = AccountTransaction::with(['account', 'transfer_transaction'])->findOrFail($id);

        $accounts = Account::where('business_id', $business_id)
                        ->NotClosed()
                        ->pluck('name', 'id');

        return view('account.edit_account_transaction')
            ->with(compact('accounts', 'account_transaction'));
    }

    public function updateAccountTransaction(Request $request, $id)
    {
        if (! auth()->user()->can('edit_account_transaction')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            DB::beginTransaction();

            $account_transaction = AccountTransaction::with(['transfer_transaction'])->findOrFail($id);

            $amount = $this->commonUtil->num_uf($request->input('amount'));
            $note = $request->input('note');

            $account_transaction->amount = $this->commonUtil->num_uf($request->input('amount'));
            $account_transaction->operation_date = $this->commonUtil->uf_date($request->input('operation_date'), true);
            $account_transaction->note = $request->input('note');

            if($request->input('account_id'))
            {
                $account_transaction->account_id = $request->input('account_id');
            }            

            $account_transaction->loadMissing(['account.account_type.parent_account']);

            if (! empty($account_transaction->account) && $account_transaction->account->isProfitAndLossAccount() && in_array($account_transaction->sub_type, ['fund_transfer', 'deposit'], true)) {
                throw new \Exception(__('messages.something_went_wrong'));
            }

            $account_transaction->save();

            if (! empty($account_transaction->transfer_transaction)) {
                $transfer_transaction = $account_transaction->transfer_transaction;

                $transfer_transaction->amount = $amount;
                $transfer_transaction->operation_date = $account_transaction->operation_date;
                $transfer_transaction->note = $account_transaction->note;

                if ($account_transaction->sub_type == 'deposit') {
                    $transfer_transaction->account_id = $request->input('from_account');
                }
                if ($account_transaction->sub_type == 'fund_transfer') {
                    $transfer_transaction->account_id = $request->input('to_account');
                }

                $transfer_transaction->loadMissing(['account.account_type.parent_account']);

                if (! empty($transfer_transaction->account) && $transfer_transaction->account->isProfitAndLossAccount() && in_array($transfer_transaction->sub_type, ['fund_transfer', 'deposit'], true)) {
                    throw new \Exception(__('messages.something_went_wrong'));
                }

                $transfer_transaction->save();
            }

            DB::commit();

            $output = ['success' => true,
                'msg' => __('lang_v1.success'),
            ];
        } catch (\Exception $e) {
            DB::rollBack();

            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

            $output = ['success' => false,
                'msg' => __('messages.something_went_wrong'),
            ];
        }

        return $output;
    }
}
