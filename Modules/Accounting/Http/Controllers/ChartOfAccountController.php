<?php

namespace Modules\Accounting\Http\Controllers;

use App\Account;
use Modules\Accounting\Entities\PaymentType;
use Modules\Accounting\Entities\Currency;
use Modules\Accounting\Services\FlashService;
use Modules\Accounting\Entities\PaymentDetail;
use App\Utils\Util;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Modules\Accounting\Entities\ChartOfAccount;
use Yajra\DataTables\Facades\DataTables;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Accounting\Entities\AccountDetailType;
use Modules\Accounting\Entities\AccountSubtype;
use Modules\Accounting\Entities\AccountType;
use Modules\Accounting\Entities\JournalEntry;
use Modules\Accounting\Exports\AccountingTableExport;
use PDF;

class ChartOfAccountController extends Controller
{
    private $commonUtil;

    public function __construct(Util $commonUtil)
    {
        $this->commonUtil = $commonUtil;
    }

    /**
     * Display a listing of the resource.
     * @return Response
     */
    public function index(Request $request)
    {
        $data = $this->get_account_chart_data($request);
        return view('accounting::chart_of_account.index', compact('data'));
    }

    private function get_account_chart_data(Request $request)
    {
        $business_id = $this->resolveBusinessId();
        $perPage = $request->per_page ?: 20;
        $orderBy = $request->order_by;
        $orderByDir = $request->order_by_dir;
        $search = $request->s;

        $ledger_balances = $this->getLedgerBalancesByGlCode($business_id);

        $paginator = ChartOfAccount::with('parent')
            ->with('account_detail_type')
            ->with('account_detail_type')
            ->with('currency')
            ->forBusiness()
            ->when($orderBy, function (Builder $query) use ($orderBy, $orderByDir) {
                $query->orderBy($orderBy, $orderByDir);
            })->when($search, function (Builder $query) use ($search) {
                $query->where('name', 'like', "%$search%");
                $query->orWhere('gl_code', 'like', "%$search%");
            })
            ->paginate($perPage)
            ->appends($request->input());

        $paginator->getCollection()->transform(function ($chart_of_account) use ($ledger_balances) {
            $gl_code = (string) $chart_of_account->gl_code;
            $chart_of_account->ledger_current_balance = $ledger_balances[$gl_code] ?? $chart_of_account->current_balance;

            return $chart_of_account;
        });

        return $paginator;
    }

    /**Export the account data to file*/
    public function export(Request $request)
    {
        $fileName = trans_choice('accounting::general.chart_of_accounts_sheet', 1) . " (" . date('d-m-Y') . ")";

        switch ($request->type) {
            case 'csv':
                return Excel::download(new AccountingTableExport, $fileName . '.csv');
                break;

            case 'excel':
                return Excel::download(new AccountingTableExport, $fileName . '.xlsx');
                break;

            case 'excel_2007':
                return Excel::download(new AccountingTableExport, $fileName . '.xls');
                break;

            case 'pdf':
                $data = $this->get_account_chart_data($request);
                $pdf = PDF::loadView(theme_view_file('accounting::chart_of_account.accounts_chart_pdf'), compact('data'));
                return $pdf->download($fileName . '.pdf');
                break;

            default:
                return back()->with('error', 'An unsupported format was chosen');
                break;
        }
    }

    public function get_chart_of_accounts()
    {
        $query = ChartOfAccount::forBusiness($this->resolveBusinessId())->where('active', 1)->orderBy('gl_code');
        return DataTables::of($query)->editColumn('user', function ($data) {
            return $data->first_name . ' ' . $data->last_name;
        })->editColumn('action', function ($data) {
            $action = '<div class="btn-group"><button type="button" class="btn btn-info btn-xs dropdown-toggle" data-toggle="dropdown" aria-expanded="true"><i class="fa fa-navicon"></i></button> <ul class="dropdown-menu dropdown-menu-right" role="menu">';
            $action .= '<li><a href="' . url('accounting/chart_of_account/' . $data->id . '/show') . '" class="">' . trans_choice('accounting::lang.detail', 2) . '</a></li>';
            if (Auth::user()->hasPermissionTo('accounting.chart_of_accounts.edit')) {
                $action .= '<li><a href="' . url('accounting/chart_of_account/' . $data->id . '/edit') . '" class="">' . trans_choice('accounting::lang.edit', 2) . '</a></li>';
            }
            if (Auth::user()->hasPermissionTo('accounting.chart_of_accounts.destroy')) {
                $action .= '<li><a href="' . url('accounting/chart_of_account/' . $data->id . '/destroy') . '" class="confirm">' . trans_choice('accounting::lang.delete', 2) . '</a></li>';
            }
            $action .= "</ul></li></div>";
            return $action;
        })->editColumn('id', function ($data) {
            return '<a href="' . url('accounting/chart_of_account/' . $data->id . '/show') . '">' . $data->id . '</a>';
        })->editColumn('name', function ($data) {
            return '<a href="' . url('accounting/chart_of_account/' . $data->id . '/show') . '">' . $data->name . ' ' . $data->last_name . '</a>';
        })->editColumn('account_type', function ($data) {
            if ($data->account_type == "asset") {
                return trans_choice('accounting::general.asset', 1);
            }
            if ($data->account_type == "expense") {
                return trans_choice('accounting::general.expense', 1);
            }
            if ($data->account_type == "equity") {
                return trans_choice('accounting::general.equity', 1);
            }
            if ($data->account_type == "liability") {
                return trans_choice('accounting::general.liability', 1);
            }
            if ($data->account_type == "income") {
                return trans_choice('accounting::general.income', 1);
            }
        })->editColumn('allow_manual', function ($data) {
            if ($data->allow_manual == "1") {
                return trans_choice('accounting::lang.yes', 1);
            }
            if ($data->account_type == "0") {
                return trans_choice('accounting::lang.no', 1);
            }
        })->editColumn('active', function ($data) {
            if ($data->active == "1") {
                return trans_choice('accounting::lang.yes', 1);
            }
            if ($data->active == "0") {
                return trans_choice('accounting::lang.no', 1);
            }
        })->rawColumns(['id', 'name', 'action'])->make(true);
    }

    /**
     * Show the form for creating a new resource.
     * @return Response
     */
    public function create()
    {
        $payment_types = PaymentType::getTypesCollection();
        $currencies = Currency::orderBy('currency')->get();
        $account_types = AccountType::getTypes();
        $account_subtypes = AccountSubtype::forBusiness()->active()->get();
        $account_detail_types = AccountDetailType::forBusiness()->active()->get();
        $chart_of_accounts = ChartOfAccount::forBusiness()->where('active', 1)->orderBy('gl_code')->get();

        return view('accounting::chart_of_account.create', compact('payment_types', 'currencies', 'account_types', 'account_subtypes', 'account_detail_types', 'chart_of_accounts'));
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required'],
            'gl_code' => ['required', 'numeric', 'unique:chart_of_accounts,gl_code'],
            'currency_id' => ['required'],
            'opening_balance' => ['sometimes', 'required', 'numeric'],
            'payment_type_id' => ['sometimes', 'required'],
            'account_subtype_id' => ['required'],
            'detail_type_id' => ['required'],
        ]);

        try {

            DB::transaction(function () use ($request) {
                $chart_of_account = new ChartOfAccount();
                $chart_of_account->name = $request->name;
                $chart_of_account->parent_id = $request->parent_id;
                $chart_of_account->gl_code = $request->gl_code;
                $chart_of_account->account_type = $request->account_type;
                $chart_of_account->allow_manual = $request->allow_manual;
                $chart_of_account->active = $request->active;
                $chart_of_account->notes = $request->notes;
                $chart_of_account->business_id = session('business.id');
                $chart_of_account->opening_balance = $request->opening_balance ?: 0;
                $chart_of_account->currency_id = $request->currency_id;
                $chart_of_account->payment_type_id = $request->payment_type_id ?: 1;
                $chart_of_account->account_subtype_id = $request->account_subtype_id;
                $chart_of_account->detail_type_id = $request->detail_type_id;
                $chart_of_account->save();

                if ($request->has('opening_balance')) {
                    $this->store_opening_balance_transaction($request, $chart_of_account->id);
                }

                activity()->on($chart_of_account)
                    ->withProperties(['id' => $chart_of_account->id])
                    ->log('Create Chart Of Account');
            });
        } catch (\Exception $e) {
            throw $e;
            return (new FlashService())->onException($e)->redirectBackWithInput();
        }

        (new FlashService())->onSave();
        return redirect('accounting/chart_of_account');
    }

    private function store_opening_balance_transaction(Request $request, $id)
    {
        $transaction_number = get_uniqid();

        $payment_detail = new PaymentDetail();
        $payment_detail->created_by_id = Auth::id();
        $payment_detail->payment_type_id = $request->payment_type_id;
        $payment_detail->transaction_type = 'journal_opening_balance_entry';
        $payment_detail->cheque_number = $request->cheque_number;
        $payment_detail->receipt = $request->receipt;
        $payment_detail->account_number = $request->account_number;
        $payment_detail->bank_name = $request->bank_name;
        $payment_detail->routing_code = $request->routing_code;
        $payment_detail->save();

        //credit account
        $journal_entry = new JournalEntry();
        $journal_entry->created_by_id = Auth::id();
        $journal_entry->transaction_number = $transaction_number;
        $journal_entry->payment_detail_id = $payment_detail->id;
        $journal_entry->location_id = null;
        $journal_entry->currency_id = $request->currency_id;
        $journal_entry->chart_of_account_id = $id;
        $journal_entry->transaction_type = 'opening_balance_entry';
        $journal_entry->date = $request->date;
        $date = explode('-', $request->date);
        $journal_entry->year = $date[0];
        $journal_entry->month = $date[1];
        $journal_entry->credit = $request->opening_balance;
        $journal_entry->reference = $request->reference;
        $journal_entry->manual_entry = 0;
        $journal_entry->notes = $request->notes;
        $journal_entry->save();
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Response
     */
    public function show($id)
    {
        $business_id = $this->resolveBusinessId();
        $chart_of_account = ChartOfAccount::with('journal_entries')->forBusiness($business_id)->findOrFail($id);
        $ledger_balances = $this->getLedgerBalancesByGlCode($business_id);
        $chart_of_account->ledger_current_balance = $ledger_balances[(string) $chart_of_account->gl_code] ?? $chart_of_account->current_balance;

        return view('accounting::chart_of_account.show', compact('chart_of_account'));
    }

    private function getLedgerBalancesByGlCode($business_id)
    {
        $rows = Account::leftJoin('account_transactions as AT', function ($join) {
                $join->on('AT.account_id', '=', 'accounts.id')
                    ->whereNull('AT.deleted_at');
            })
            ->leftJoin('account_types as ats', 'accounts.account_type_id', '=', 'ats.id')
            ->leftJoin('account_types as pat', 'ats.parent_account_type_id', '=', 'pat.id')
            ->where('accounts.business_id', $business_id)
            ->select([
                'accounts.account_number',
                DB::raw(Account::typeAwareBalanceExpression('COALESCE(pat.name, ats.name)', 'AT.type', 'AT.amount', 'AT.sub_type').' as balance'),
            ])
            ->groupBy('accounts.id')
            ->get();

        $balances = [];
        foreach ($rows as $row) {
            $account_number = trim((string) $row->account_number);
            if ($account_number === '') {
                continue;
            }

            if (! isset($balances[$account_number])) {
                $balances[$account_number] = 0;
            }

            $balances[$account_number] += Account::getDisplayBalance($row->balance);
        }

        return $balances;
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Response
     */
    public function edit($id)
    {
        $business_id = $this->resolveBusinessId();
        $chart_of_account = ChartOfAccount::forBusiness($business_id)->where('active', 1)->findOrFail($id);
        $payment_types = PaymentType::getTypesCollection();
        $currencies = Currency::orderBy('currency')->get();
        $account_types = AccountType::getTypes();
        $account_subtypes = AccountSubtype::forBusiness()->active()->get();
        $account_detail_types = AccountDetailType::forBusiness()->active()->get();
        $chart_of_accounts = ChartOfAccount::forBusiness($business_id)->where('active', 1)->where('id', '!=', $id)->orderBy('gl_code')->get();

        return view('accounting::chart_of_account.edit', compact('chart_of_account', 'payment_types', 'currencies', 'account_types', 'account_subtypes', 'account_detail_types', 'chart_of_accounts'));
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Response
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => ['required'],
            'gl_code' => ['required', 'numeric', Rule::unique('chart_of_accounts', 'gl_code')->ignore($id)],
            'currency_id' => ['required'],
            'opening_balance' => ['sometimes', 'required', 'numeric'],
            'payment_type_id' => ['sometimes', 'required'],
            'account_subtype_id' => ['required'],
            'detail_type_id' => ['required'],
        ]);

        $chart_of_account = ChartOfAccount::findOrFail($id);
        $chart_of_account->name = $request->name;
        $chart_of_account->parent_id = $request->parent_id;
        $chart_of_account->gl_code = $request->gl_code;
        $chart_of_account->account_type = $request->account_type;
        $chart_of_account->allow_manual = $request->allow_manual;
        $chart_of_account->active = $request->active;
        $chart_of_account->notes = $request->notes;
        $chart_of_account->business_id = $this->resolveBusinessId();
        $chart_of_account->opening_balance = $request->opening_balance ?: 0;
        $chart_of_account->currency_id = $request->currency_id;
        $chart_of_account->payment_type_id = $request->payment_type_id ?: 1;
        $chart_of_account->account_subtype_id = $request->account_subtype_id;
        $chart_of_account->detail_type_id = $request->detail_type_id;
        $chart_of_account->save();
        activity()->on($chart_of_account)
            ->withProperties(['id' => $chart_of_account->id])
            ->log('Update Chart Of Account');
        (new FlashService())->onSave();
        return redirect('accounting/chart_of_account');
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Response
     */
    public function destroy($id)
    {
        $chart_of_account = ChartOfAccount::forBusiness($this->resolveBusinessId())->findOrFail($id);
        $chart_of_account->delete();
        activity()->on($chart_of_account)
            ->withProperties(['id' => $chart_of_account->id])
            ->log('Delete Chart Of Account');
        (new FlashService())->onDelete();
        return redirect()->back();
    }

    private function resolveBusinessId(): ?int
    {
        $business_id = session('business.id') ?? session('user.business_id') ?? optional(auth()->user())->business_id;

        return ! empty($business_id) ? (int) $business_id : null;
    }
}
