<?php

namespace Modules\Accounting\Http\Controllers;

use App\Account;
use App\Charts\CommonChart;
use App\Utils\TransactionUtil;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\Accounting\Entities\AccountType;
use Modules\Accounting\Entities\ChartOfAccount;
use Modules\Accounting\Entities\JournalEntry;

class DashboardController extends Controller
{
    protected $transactionUtil;

    public function __construct(TransactionUtil $transactionUtil)
    {
        $this->transactionUtil = $transactionUtil;
    }

    /**
     * Display a listing of the resource.
     * @return Response
     */
    public function index()
    {
        $business_id = $this->resolveBusinessId();
        $chart_of_accounts = ChartOfAccount::with('parent')
            ->forBusiness()
            ->with('currency')
            ->get();

        $account_types = AccountType::getTypes()->pluck('id');
        $account_type_balances = $this->getAccountTypeBalances($business_id, $account_types);
        $total_account_balance = collect($account_type_balances)->sum();

        $expense_chart = $this->expense_chart($business_id);
        $current_financial_year_chart = $this->current_financial_year_chart();
        $last_30_days_financial_year_chart = $this->last_30_days_financial_year_chart();


        $balance_summary_chart = (object)[
            'labels' => json_encode(
                $account_types->map(function ($account_type) {
                    return trans_choice('accounting::general.' . $account_type, 1);
                }),
            ),
            'values' => $account_types->map(function ($account_type) use ($account_type_balances) {
                return $account_type_balances[$account_type] ?? 0;
            })
        ];

        return view('accounting::dashboard.index', compact('account_types', 'chart_of_accounts', 'account_type_balances', 'total_account_balance', 'expense_chart', 'current_financial_year_chart', 'last_30_days_financial_year_chart', 'balance_summary_chart'));
    }


    public function expense_chart(?int $businessId = null)
    {
        $businessId = $businessId ?: $this->resolveBusinessId();
        $expenses = $this->transactionUtil->getExpenseReport($businessId);

        $values = [];
        $labels = [];
        foreach ($expenses as $expense) {
            $values[] = (float) ChartOfAccount::forBusiness()->where('account_type', 'expense')->get()->sum('current_balance');
            $labels[] = !empty($expense->category) ? $expense->category : __('report.others');
        }

        $chart = new CommonChart;
        $chart->labels($labels)
            ->title(__('report.expense_report'))
            ->dataset(__('report.total_expense'), 'column', $values);

        return $chart;
    }

    public function current_financial_year_chart()
    {
        $values = [];
        $labels = [];

        $values[] = (float) JournalEntry::forBusiness()->where('date', '>=', financial_year_start_date())->get()->sum('amount');
        $labels[] = '';

        $title = trans('accounting::lang.current') . ' ' . trans_choice('accounting::general.financial_year', 1);

        $chart = new CommonChart;
        $chart->labels($labels)
            ->title($title)
            ->dataset($title, 'column', $values);

        return $chart;
    }

    public function last_30_days_financial_year_chart()
    {
        $values = [];
        $labels = [];

        $values[] = (float) JournalEntry::forBusiness()->where('date', '>=', thirty_days_ago())->get()->sum('amount');
        $labels[] = '';

        $title = trans('accounting::lang.last') . ' 30 ' . trans_choice('accounting::lang.day', 2);

        $chart = new CommonChart;
        $chart->labels($labels)
            ->title($title)
            ->dataset($title, 'column', $values);

        return $chart;
    }

    /**
     * Retrieves totals for the main admin dashboard
     *
     * @return \Illuminate\Http\Response
     */
    public function get_totals()
    {
        if (request()->ajax()) {
            $business_id = $this->resolveBusinessId();
            $account_types = AccountType::getTypes()->pluck('id');
            $account_type_balances = $this->getAccountTypeBalances($business_id, $account_types);

            // Will be needed in filtering  
            // $start = request()->start;
            // $end = request()->end;
            // $location_id = request()->location_id;
            // $business_id = request()->session()->get('user.business_id');

            $output = [];

            $output['no_journal_entries'] = JournalEntry::forBusiness()->count();
            $output['no_charts_of_account'] = ChartOfAccount::forBusiness()->get()->count('id');
            $output['all_transactions'] = collect($account_type_balances)->sum();

            return $output;
        }
    }

    protected function resolveBusinessId(): ?int
    {
        $businessId = session('business.id')
            ?? session('user.business_id')
            ?? optional(auth()->user())->business_id;

        return ! empty($businessId) ? (int) $businessId : null;
    }

    protected function getAccountTypeBalances($business_id, $account_types)
    {
        $type_balances = collect($account_types)->mapWithKeys(function ($type) {
            return [$type => 0.0];
        })->toArray();

        $account_rows = Account::leftJoin('account_transactions as AT', function ($join) {
                $join->on('AT.account_id', '=', 'accounts.id')
                    ->whereNull('AT.deleted_at');
            })
            ->leftJoin('account_types as ats', 'accounts.account_type_id', '=', 'ats.id')
            ->leftJoin('account_types as pat', 'ats.parent_account_type_id', '=', 'pat.id')
            ->where('accounts.business_id', $business_id)
            ->select([
                DB::raw('COALESCE(pat.name, ats.name) as account_type_name'),
                DB::raw(Account::typeAwareBalanceExpression('COALESCE(pat.name, ats.name)', 'AT.type', 'AT.amount', 'AT.sub_type').' as balance'),
            ])
            ->groupBy('accounts.id')
            ->get();

        foreach ($account_rows as $row) {
            $major_type = Account::majorTypeFromLabel($row->account_type_name);
            if ($major_type === 'cost') {
                $major_type = 'expense';
            }

            if (! array_key_exists($major_type, $type_balances)) {
                continue;
            }

            $type_balances[$major_type] += Account::getDisplayBalance($row->balance);
        }

        return $type_balances;
    }
}
