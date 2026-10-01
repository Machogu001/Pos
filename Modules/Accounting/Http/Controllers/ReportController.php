<?php

namespace Modules\Accounting\Http\Controllers;

use App\Business;
use Modules\Accounting\Entities\BusinessLocation;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Accounting\Exports\AccountingExport;
use PDF;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\Accounting\Services\AccountingReportService;
use Modules\Accounting\Entities\AccountSubtype;
use Modules\Accounting\Entities\ChartOfAccount;
use Modules\Accounting\Entities\Transaction;
use Modules\Accounting\Services\BudgetService;

class ReportController extends Controller
{
    private $default_start_date;
    private $default_end_date;

    public function __construct()
    {
        $this->default_start_date = date('Y') . '-' . '01-01';
        $this->default_end_date = date('Y-m-d');
    }

    public function index()
    {
        $reports = (new AccountingReportService())->getReports();

        return view('accounting::report.index', compact('reports'));
    }

    public function trial_balance(Request $request)
    {
        $start_date = $request->start_date ?: $this->default_start_date;
        $end_date = $request->end_date ?: $this->default_end_date;
        $location_id = $request->location_id;
        $business_id = $this->resolveBusinessId();
        $data = [];
        $currency_code = currency_code();
        $business_locations = BusinessLocation::getDropdownCollection($business_id);
        if (empty($location_id) && $business_locations->isNotEmpty()) {
            $location_id = $business_locations->first()->id;
        }
        $account_types = ChartOfAccount::getAccountTypes();
        $data = $this->buildJournalStatementRows($business_id, $start_date, $end_date, $location_id);

        $compact_data = compact(
            'start_date',
            'end_date',
            'location_id',
            'data',
            'business_locations',
            'currency_code',
            'account_types'
        );


        if (!empty($start_date)) {
            //check if we should download
            if ($request->download) {
                $view = view('accounting::report.trial_balance_pdf', $compact_data);
                $file_name = trans_choice('accounting::general.trial_balance', 1) . '(' . $start_date . ' to ' . $end_date . ')';

                if ($request->type == 'pdf') {
                    $pdf = PDF::loadView(theme_view_file('accounting::report.trial_balance_pdf'), $compact_data);
                    return $pdf->download("$file_name.pdf");
                } elseif ($request->type == 'excel_2007') {
                    return Excel::download(new AccountingExport($view), "$file_name.xlsx");
                } elseif ($request->type == 'excel') {
                    return Excel::download(new AccountingExport($view), "$file_name.xls");
                } elseif ($request->type == 'csv') {
                    return Excel::download(new AccountingExport($view), "$file_name.csv");
                }
            }
        }

        return view('accounting::report.trial_balance', $compact_data);
    }

    //cash flow statement
    public function cash_flow(Request $request)
    {
        $start_date = $request->start_date ?: $this->default_start_date;
        $end_date = $request->end_date ?: $this->default_end_date;
        $location_id = $request->location_id;
        $business_id = $this->resolveBusinessId();
        $data = [];
        $business_locations = BusinessLocation::getDropdownCollection($business_id);
        if (empty($location_id) && $business_locations->isNotEmpty()) {
            $location_id = $business_locations->first()->id;
        }
        $currency_code = currency_code();
        $account_types = ChartOfAccount::getAccountTypes();
        $data = $this->buildJournalStatementRows($business_id, null, $end_date, $location_id)
            ->sortBy('account_type')
            ->values();

        $compact_data = compact('start_date', 'end_date', 'location_id', 'data', 'business_locations', 'currency_code', 'account_types');

        //check if we should download
        if ($request->download) {
            $view = view(
                'accounting::report.cash_flow_pdf',
                $compact_data
            );

            if ($request->type == 'pdf') {
                $pdf = PDF::loadView(theme_view_file('accounting::report.cash_flow_pdf'), $compact_data);
                return $pdf->download(trans_choice('accounting::general.cash_flow', 1) . '.pdf');
            } elseif ($request->type == 'excel_2007') {
                return Excel::download(new AccountingExport($view), trans_choice('accounting::general.cash_flow', 1) . '.xlsx');
            } elseif ($request->type == 'excel') {
                return Excel::download(new AccountingExport($view), trans_choice('accounting::general.cash_flow', 1) . '.xls');
            } elseif ($request->type == 'csv') {
                return Excel::download(new AccountingExport($view), trans_choice('accounting::general.cash_flow', 1) . '.csv');
            }
        }

        return view(
            'accounting::report.cash_flow',
            $compact_data
        );
    }
    //income statement
    public function profit_and_loss(Request $request)
    {
        $start_date = $request->start_date ?: $this->default_start_date;
        $end_date = $request->end_date ?: $this->default_end_date;
        $location_id = $request->location_id;
        $account_types = ['income', 'expense'];
        $business_id = $this->resolveBusinessId();
        $business_locations = BusinessLocation::getDropdownCollection($business_id);
        if (empty($location_id) && $business_locations->isNotEmpty()) {
            $location_id = $business_locations->first()->id;
        }
        $data = $this->buildJournalStatementRows($business_id, $start_date, $end_date, $location_id, $account_types)
            ->sortBy('account_type')
            ->values();
        $currency_code = currency_code();
        $compact_data = compact(
            'start_date',
            'end_date',
            'location_id',
            'data',
            'business_locations',
            'currency_code',
            'account_types'
        );

        if (!empty($start_date)) {
            //check if we should download
            if ($request->download) {
                $view = view('accounting::report.profit_and_loss_pdf', $compact_data);
                if ($request->type == 'pdf') {
                    $pdf = PDF::loadView(theme_view_file('accounting::report.profit_and_loss_pdf'), $compact_data);
                    return $pdf->download(trans_choice('accounting::report.profit_and_loss', 1) . '(' . $start_date . ' to ' . $end_date . ').pdf');
                } elseif ($request->type == 'excel_2007') {
                    return Excel::download(new AccountingExport($view), trans_choice('accounting::report.profit_and_loss', 1) . '(' . $start_date . ' to ' . $end_date . ').xlsx');
                } elseif ($request->type == 'excel') {
                    return Excel::download(new AccountingExport($view), trans_choice('accounting::report.profit_and_loss', 1) . '(' . $start_date . ' to ' . $end_date . ').xls');
                } elseif ($request->type == 'csv') {
                    return Excel::download(new AccountingExport($view), trans_choice('accounting::report.profit_and_loss', 1) . '(' . $start_date . ' to ' . $end_date . ').csv');
                }
            }
        }
        return view('accounting::report.profit_and_loss', $compact_data);
    }

    //balance sheet
    public function balance_sheet(Request $request)
    {
        $end_date = $request->end_date ?: $this->default_end_date;
        $location_id = $request->location_id;
        $business_id = $this->resolveBusinessId();
        $data = [];
        $business_locations = BusinessLocation::getDropdownCollection($business_id);
        if (empty($location_id) && $business_locations->isNotEmpty()) {
            $location_id = $business_locations->first()->id;
        }
        $currency_code = currency_code();
        $account_types = ['asset', 'equity', 'liability'];

        if (!empty($end_date)) {
            $data = $this->buildJournalStatementRows($business_id, null, $end_date, $location_id, ['asset', 'equity', 'liability'])
                ->map(function ($row) {
                    $balance = in_array($row->account_type, ['asset'], true)
                        ? ((float) $row->debit - (float) $row->credit)
                        : ((float) $row->credit - (float) $row->debit);

                    $row->balance = $balance;

                    return $row;
                })
                ->sortBy('account_type')
                ->values();

            $compact_data = compact('end_date', 'location_id', 'data', 'business_locations', 'currency_code', 'account_types');

            //check if we should download
            if ($request->download) {
                $view = view('accounting::report.balance_sheet_pdf', $compact_data);
                if ($request->type == 'pdf') {
                    $pdf = PDF::loadView(theme_view_file('accounting::report.balance_sheet_pdf'), $compact_data);
                    return $pdf->download(trans_choice('accounting::general.balance_sheet', 1) . '(' . $end_date . ').pdf');
                } elseif ($request->type == 'excel_2007') {
                    return Excel::download(new AccountingExport($view), trans_choice('accounting::general.balance_sheet', 1) . '(' . $end_date . ').xlsx');
                } elseif ($request->type == 'excel') {
                    return Excel::download(new AccountingExport($view), trans_choice('accounting::general.balance_sheet', 1) . '(' . $end_date . ').xls');
                } elseif ($request->type == 'csv') {
                    return Excel::download(new AccountingExport($view), trans_choice('accounting::general.balance_sheet', 1) . '(' . $end_date . ').csv');
                }
            }
        }

        return view('accounting::report.balance_sheet', $compact_data);
    }

    private function buildJournalStatementRows($business_id, $start_date = null, $end_date = null, $location_id = null, array $account_types = [])
    {
        $query = DB::table('chart_of_accounts')
            ->leftJoin('journal_entries', function ($join) use ($start_date, $end_date, $location_id) {
                $join->on('journal_entries.chart_of_account_id', '=', 'chart_of_accounts.id')
                    ->where('journal_entries.reversed', 0);

                if (! empty($start_date) && ! empty($end_date)) {
                    $join->whereBetween('journal_entries.date', [$start_date, $end_date]);
                } elseif (! empty($end_date)) {
                    $join->where('journal_entries.date', '<=', $end_date);
                }

                if (! empty($location_id)) {
                    $join->where('journal_entries.location_id', $location_id);
                }
            })
            ->where('chart_of_accounts.active', 1)
            ->where('chart_of_accounts.business_id', $business_id)
            ->when(! empty($account_types), function ($query) use ($account_types) {
                $query->whereIn('chart_of_accounts.account_type', $account_types);
            })
            ->selectRaw(" 
                chart_of_accounts.id,
                chart_of_accounts.name,
                chart_of_accounts.gl_code,
                chart_of_accounts.account_type,
                NULL as business_location,
                COALESCE(SUM(journal_entries.debit), 0) as debit,
                COALESCE(SUM(journal_entries.credit), 0) as credit
            ")
            ->groupBy('chart_of_accounts.id', 'chart_of_accounts.name', 'chart_of_accounts.gl_code', 'chart_of_accounts.account_type');

        return $query->get();
    }

    public function ledger(Request $request)
    {
        $start_date = $request->start_date ?: $this->default_start_date;
        $end_date = $request->end_date ?: $this->default_end_date;
        $location_id = $request->location_id;
        $business_id = $this->resolveBusinessId();
        $data = [];
        $business_locations = BusinessLocation::getDropdownCollection($business_id);
        $currency_code = currency_code();
        $account_types = ChartOfAccount::getAccountTypes();

        $data = DB::table('chart_of_accounts')
            ->leftJoin('accounts', function ($join) use ($business_id) {
                $join->on('accounts.account_number', '=', 'chart_of_accounts.gl_code')
                     ->where('accounts.business_id', $business_id);
            })
            ->leftJoin('account_transactions', function ($join) use ($start_date, $end_date) {
                $join->on('account_transactions.account_id', '=', 'accounts.id')
                     ->whereNull('account_transactions.deleted_at')
                     ->where(DB::raw('DATE(account_transactions.operation_date)'), '>=', $start_date)
                     ->where(DB::raw('DATE(account_transactions.operation_date)'), '<=', $end_date);
            })
            ->leftJoin('account_subtypes', 'account_subtypes.id', '=', 'chart_of_accounts.account_subtype_id')
            ->where('chart_of_accounts.active', 1)
            ->where('chart_of_accounts.business_id', $business_id)
            ->selectRaw("
                chart_of_accounts.name,
                chart_of_accounts.gl_code,
                chart_of_accounts.account_type,
                NULL as business_location,
                COALESCE(SUM(CASE WHEN account_transactions.type='debit' THEN account_transactions.amount ELSE 0 END), 0) as debit,
                COALESCE(SUM(CASE WHEN account_transactions.type='credit' THEN account_transactions.amount ELSE 0 END), 0) as credit,
                account_subtypes.name as account_subtype,
                account_subtypes.id as account_subtype_id
            ")
            ->groupBy('chart_of_accounts.id')
            ->orderBy('account_type')
            ->get();

        $account_subtype_ids = $data->pluck('account_subtype_id')->unique();
        $account_subtypes = AccountSubtype::forBusiness()->whereIn('id', $account_subtype_ids)->active()->get();

        $compact_data = compact('start_date', 'end_date', 'location_id', 'data', 'business_locations', 'currency_code', 'account_types', 'account_subtypes');
        //check if we should download
        if ($request->download) {
            $view = view('accounting::report.ledger_pdf', $compact_data);

            if ($request->type == 'pdf') {
                $pdf = PDF::loadView(theme_view_file('accounting::report.ledger_pdf'), $compact_data);
                return $pdf->download(trans_choice('accounting::general.ledger', 1) . '.pdf');
            } elseif ($request->type == 'excel_2007') {
                return Excel::download(new AccountingExport($view), trans_choice('accounting::general.ledger', 1) . '.xlsx');
            } elseif ($request->type == 'excel') {
                return Excel::download(new AccountingExport($view), trans_choice('accounting::general.ledger', 1) . '.xls');
            } elseif ($request->type == 'csv') {
                return Excel::download(new AccountingExport($view), trans_choice('accounting::general.ledger', 1) . '.csv');
            }
        }
        return view('accounting::report.ledger', $compact_data);
    }

    public function accounts_receivable_ageing_summary(Request $request)
    {
        $start_date = $request->start_date ?: $this->default_start_date;
        $end_date = $request->end_date ?: $this->default_end_date;
        $location_id = $request->location_id;
        $business_id = $this->resolveBusinessId();
        $paymentTotalsSubquery = DB::raw('(' . $this->paymentTotalsSubquerySql() . ') as tp');

        $data = DB::table('transactions')
            ->leftJoin('contacts', 'contacts.id', '=', 'transactions.contact_id')
            ->leftJoin($paymentTotalsSubquery, 'tp.transaction_id', '=', 'transactions.id')
            ->where('transactions.type', 'sell')
            ->where('transactions.payment_status', '!=', 'paid')
            ->where('transactions.business_id', $business_id)
            ->when($start_date, function ($query) use ($start_date, $end_date) {
                $query->whereBetween('transactions.transaction_date', [$start_date, date('Y-m-d', strtotime("$end_date + 1day"))]);
            })
            ->when($location_id, function ($query) use ($location_id) {
                $query->where('transactions.location_id', $location_id);
            })
            ->selectRaw("
                transactions.payment_status,
                transactions.ref_no,
                DATE(transactions.transaction_date) as transaction_date,
                transactions.total_before_tax,
                transactions.tax_amount,
                'Invoice' as transaction_type,
                COALESCE(contacts.name, 'Walk-in Customer') as chart_of_account,
                COALESCE(contacts.id, 0) as chart_of_account_id,
                (transactions.final_total - COALESCE(tp.paid_amount, 0)) as amount_due
            ")
            ->get();

        $chart_of_accounts = $data->pluck('chart_of_account_id', 'chart_of_account')->unique();
        $business_locations = BusinessLocation::getDropdownCollection($business_id);
        $days_past = get_days_past();
        $compact_data = compact('start_date', 'end_date', 'location_id', 'data', 'business_locations', 'chart_of_accounts', 'days_past');

        //check if we should download
        if ($request->download) {
            $view = view('accounting::report.accounts_receivable_ageing_summary_pdf', $compact_data);
            if ($request->type == 'pdf') {
                $pdf = PDF::loadView(theme_view_file('accounting::report.accounts_receivable_ageing_summary_pdf'), $compact_data);
                return $pdf->download(trans_choice('accounting::report.accounts_receivable_ageing_summary', 1) . '.pdf');
            } elseif ($request->type == 'excel_2007') {
                return Excel::download(new AccountingExport($view), trans_choice('accounting::report.accounts_receivable_ageing_summary', 1) . '.xlsx');
            } elseif ($request->type == 'excel') {
                return Excel::download(new AccountingExport($view), trans_choice('accounting::report.accounts_receivable_ageing_summary', 1) . '.xls');
            } elseif ($request->type == 'csv') {
                return Excel::download(new AccountingExport($view), trans_choice('accounting::report.accounts_receivable_ageing_summary', 1) . '.csv');
            }
        }

        return view('accounting::report.accounts_receivable_ageing_summary')->with($compact_data);
    }

    public function accounts_receivable_ageing_detail(Request $request)
    {
        $start_date = $request->start_date ?: $this->default_start_date;
        $end_date = $request->end_date ?: $this->default_end_date;
        $location_id = $request->location_id;
        $business_id = $this->resolveBusinessId();
        $paymentTotalsSubquery = DB::raw('(' . $this->paymentTotalsSubquerySql() . ') as tp');

        $data = DB::table('transactions')
            ->leftJoin('contacts', 'contacts.id', '=', 'transactions.contact_id')
            ->leftJoin($paymentTotalsSubquery, 'tp.transaction_id', '=', 'transactions.id')
            ->where('transactions.type', 'sell')
            ->where('transactions.payment_status', '!=', 'paid')
            ->where('transactions.business_id', $business_id)
            ->when($start_date, function ($query) use ($start_date, $end_date) {
                $query->whereBetween('transactions.transaction_date', [$start_date, $end_date]);
            })
            ->when($location_id, function ($query) use ($location_id) {
                $query->where('transactions.location_id', $location_id);
            })
            ->selectRaw("
                DATE(transactions.transaction_date) as transaction_date,
                transactions.invoice_no,
                transactions.total_before_tax,
                transactions.tax_amount,
                'Invoice' as transaction_type,
                COALESCE(contacts.name, 'Walk-in Customer') as chart_of_account,
                COALESCE(contacts.id, 0) as account_subtype_id,
                COALESCE(contacts.name, 'Walk-in Customer') as account_subtype,
                '' as account_detail_type,
                (transactions.final_total - COALESCE(tp.paid_amount, 0)) as amount_due,
                CASE
                    WHEN DATEDIFF(NOW(), transactions.transaction_date) BETWEEN 1 AND 30 THEN 'one_to_thirty_past_due'
                    WHEN DATEDIFF(NOW(), transactions.transaction_date) BETWEEN 31 AND 60 THEN 'thirty_one_to_sixty_past_due'
                    WHEN DATEDIFF(NOW(), transactions.transaction_date) BETWEEN 61 AND 90 THEN 'sixty_one_to_ninety_past_due'
                    ELSE 'ninety_one_and_over'
                END as days_passed
            ")
            ->get();

        $days_passed_options = Transaction::getDaysPassedOptions();
        $business_locations = BusinessLocation::getDropdownCollection($business_id);
        $currency_code = currency_code();
        $chart_of_accounts = ChartOfAccount::forBusiness()->where('active', 1)->orderBy('gl_code')->get();
        $account_subtypes = collect([]);
        $compact_data = compact('start_date', 'end_date', 'location_id', 'data', 'business_locations', 'currency_code', 'chart_of_accounts', 'account_subtypes', 'days_passed_options');

        //check if we should download
        if ($request->download) {
            $view = view('accounting::report.accounts_receivable_ageing_detail_pdf', $compact_data);

            if ($request->type == 'pdf') {
                $pdf = PDF::loadView(theme_view_file('accounting::report.accounts_receivable_ageing_detail_pdf'), $compact_data);
                return $pdf->download(trans_choice('accounting::report.accounts_receivable_ageing_detail', 1) . '.pdf');
            } elseif ($request->type == 'excel_2007') {
                return Excel::download(new AccountingExport($view), trans_choice('accounting::report.accounts_receivable_ageing_detail', 1) . '.xlsx');
            } elseif ($request->type == 'excel') {
                return Excel::download(new AccountingExport($view), trans_choice('accounting::report.accounts_receivable_ageing_detail', 1) . '.xls');
            } elseif ($request->type == 'csv') {
                return Excel::download(new AccountingExport($view), trans_choice('accounting::report.accounts_receivable_ageing_detail', 1) . '.csv');
            }
        }

        return view('accounting::report.accounts_receivable_ageing_detail')->with($compact_data);
    }

    public function accounts_payable_ageing_summary(Request $request)
    {
        $start_date = $request->start_date ?: $this->default_start_date;
        $end_date = $request->end_date ?: $this->default_end_date;
        $location_id = $request->location_id;
        $business_id = $this->resolveBusinessId();
        $paymentTotalsSubquery = DB::raw('(' . $this->paymentTotalsSubquerySql() . ') as tp');

        $data = DB::table('transactions')
            ->leftJoin('contacts', 'contacts.id', '=', 'transactions.contact_id')
            ->leftJoin($paymentTotalsSubquery, 'tp.transaction_id', '=', 'transactions.id')
            ->where('transactions.type', 'purchase')
            ->where('transactions.payment_status', '!=', 'paid')
            ->where('transactions.business_id', $business_id)
            ->when($start_date, function ($query) use ($start_date, $end_date) {
                $query->whereBetween('transactions.transaction_date', [$start_date, date('Y-m-d', strtotime("$end_date + 1day"))]);
            })
            ->when($location_id, function ($query) use ($location_id) {
                $query->where('transactions.location_id', $location_id);
            })
            ->selectRaw("
                transactions.payment_status,
                transactions.ref_no,
                DATE(transactions.transaction_date) as transaction_date,
                transactions.total_before_tax,
                transactions.tax_amount,
                'Bill' as transaction_type,
                COALESCE(contacts.name, 'Unknown Supplier') as chart_of_account,
                COALESCE(contacts.id, 0) as chart_of_account_id,
                (transactions.final_total - COALESCE(tp.paid_amount, 0)) as amount_due
            ")
            ->get();

        $chart_of_accounts = $data->pluck('chart_of_account_id', 'chart_of_account')->unique();
        $business_locations = BusinessLocation::getDropdownCollection($business_id);
        $days_past = get_days_past();
        $compact_data = compact('start_date', 'end_date', 'location_id', 'data', 'business_locations', 'chart_of_accounts', 'days_past');

        //check if we should download
        if ($request->download) {
            $view = view('accounting::report.accounts_payable_ageing_summary_pdf', $compact_data);
            if ($request->type == 'pdf') {
                $pdf = PDF::loadView(theme_view_file('accounting::report.accounts_payable_ageing_summary_pdf'), $compact_data);
                return $pdf->download(trans_choice('accounting::report.accounts_payable_ageing_summary', 1) . '.pdf');
            } elseif ($request->type == 'excel_2007') {
                return Excel::download(new AccountingExport($view), trans_choice('accounting::report.accounts_payable_ageing_summary', 1) . '.xlsx');
            } elseif ($request->type == 'excel') {
                return Excel::download(new AccountingExport($view), trans_choice('accounting::report.accounts_payable_ageing_summary', 1) . '.xls');
            } elseif ($request->type == 'csv') {
                return Excel::download(new AccountingExport($view), trans_choice('accounting::report.accounts_payable_ageing_summary', 1) . '.csv');
            }
        }

        return view('accounting::report.accounts_payable_ageing_summary')->with($compact_data);
    }

    public function accounts_payable_ageing_detail(Request $request)
    {
        $start_date = $request->start_date ?: $this->default_start_date;
        $end_date = $request->end_date ?: $this->default_end_date;
        $location_id = $request->location_id;
        $business_id = $this->resolveBusinessId();
        $paymentTotalsSubquery = DB::raw('(' . $this->paymentTotalsSubquerySql() . ') as tp');

        $data = DB::table('transactions')
            ->leftJoin('contacts', 'contacts.id', '=', 'transactions.contact_id')
            ->leftJoin($paymentTotalsSubquery, 'tp.transaction_id', '=', 'transactions.id')
            ->where('transactions.type', 'purchase')
            ->where('transactions.payment_status', '!=', 'paid')
            ->where('transactions.business_id', $business_id)
            ->when($start_date, function ($query) use ($start_date, $end_date) {
                $query->whereBetween('transactions.transaction_date', [$start_date, $end_date]);
            })
            ->when($location_id, function ($query) use ($location_id) {
                $query->where('transactions.location_id', $location_id);
            })
            ->selectRaw("
                DATE(transactions.transaction_date) as transaction_date,
                transactions.invoice_no,
                transactions.total_before_tax,
                transactions.tax_amount,
                'Bill' as transaction_type,
                COALESCE(contacts.name, 'Unknown Supplier') as chart_of_account,
                COALESCE(contacts.id, 0) as account_subtype_id,
                COALESCE(contacts.name, 'Unknown Supplier') as account_subtype,
                '' as account_detail_type,
                (transactions.final_total - COALESCE(tp.paid_amount, 0)) as amount_due,
                CASE
                    WHEN DATEDIFF(NOW(), transactions.transaction_date) BETWEEN 1 AND 30 THEN 'one_to_thirty_past_due'
                    WHEN DATEDIFF(NOW(), transactions.transaction_date) BETWEEN 31 AND 60 THEN 'thirty_one_to_sixty_past_due'
                    WHEN DATEDIFF(NOW(), transactions.transaction_date) BETWEEN 61 AND 90 THEN 'sixty_one_to_ninety_past_due'
                    ELSE 'ninety_one_and_over'
                END as days_passed
            ")
            ->get();

        $days_passed_options = Transaction::getDaysPassedOptions();
        $business_locations = BusinessLocation::getDropdownCollection($business_id);
        $currency_code = currency_code();
        $chart_of_accounts = ChartOfAccount::forBusiness()->where('active', 1)->orderBy('gl_code')->get();
        $account_subtypes = collect([]);
        $compact_data = compact('start_date', 'end_date', 'location_id', 'data', 'business_locations', 'currency_code', 'chart_of_accounts', 'account_subtypes', 'days_passed_options');

        //check if we should download
        if ($request->download) {
            $view = view('accounting::report.accounts_payable_ageing_detail_pdf', $compact_data);

            if ($request->type == 'pdf') {
                $pdf = PDF::loadView(theme_view_file('accounting::report.accounts_payable_ageing_detail_pdf'), $compact_data);
                return $pdf->download(trans_choice('accounting::report.accounts_payable_ageing_detail', 1) . '.pdf');
            } elseif ($request->type == 'excel_2007') {
                return Excel::download(new AccountingExport($view), trans_choice('accounting::report.accounts_payable_ageing_detail', 1) . '.xlsx');
            } elseif ($request->type == 'excel') {
                return Excel::download(new AccountingExport($view), trans_choice('accounting::report.accounts_payable_ageing_detail', 1) . '.xls');
            } elseif ($request->type == 'csv') {
                return Excel::download(new AccountingExport($view), trans_choice('accounting::report.accounts_payable_ageing_detail', 1) . '.csv');
            }
        }

        return view('accounting::report.accounts_payable_ageing_detail')->with($compact_data);
    }

    public function budget_overview(Request $request)
    {
        $business = $this->resolveBusinessContext();

        if (! $business) {
            return redirect('/home')->with('status', [
                'success' => 0,
                'msg' => __('accounting::general.accounting_module_not_enabled_for_business'),
            ]);
        }

        $financial_year_start = $business->fy_start_month;
        $account_types = ChartOfAccount::forBusiness()->where('active', 1)->distinct('account_type')->get(['account_type'])->pluck('account_type');
        $financial_year = !empty(request()->year) ? request()->year : BudgetService::getCurrentFinancialYear($financial_year_start);
        $months = (new BudgetService($financial_year_start, $financial_year))->getMonths();
        $calendar_year = get_calendar_year();
        $url = (object) [
            'monthly' => url("report/accounting/budget_overview?view=monthly&year=$financial_year"),
            'quarterly' => url("report/accounting/budget_overview?view=quarterly&year=$financial_year"),
        ];
        $filters = [
            'view' => $request->view,
            'year' => $request->year,
        ];

        $page_title = ucfirst($request->view) . ' ' . trans_choice('accounting::general.budget', 1) . ' - ' . $financial_year . ' ' . trans_choice('accounting::general.financial_year', 1);

        $chart_of_accounts_by_type = collect([]);
        foreach ($account_types as $account_type) {
            $chart_of_account = ChartOfAccount::with('budget')->where('active', 1)->where('account_type', $account_type)->get();
            $chart_of_account->yearly_total = $chart_of_account->pluck('budget.yearly')->sum();
            $chart_of_accounts_by_type->put($account_type, $chart_of_account);
        }

        $chart_of_accounts = ChartOfAccount::forBusiness()->with('budget')->where('active', 1)->get();
        $chart_of_accounts->yearly_total = $chart_of_accounts->pluck('budget.yearly')->sum();
        $compact_data = compact('chart_of_accounts_by_type', 'chart_of_accounts', 'months', 'calendar_year', 'financial_year_start', 'financial_year', 'url', 'account_types', 'filters', 'page_title');

        //check if we should download
        if ($request->download) {
            $view = view('accounting::report.budget.budget_overview_pdf', $compact_data);

            if ($request->type == 'pdf') {
                $pdf = PDF::loadView(theme_view_file('accounting::report.budget.budget_overview_pdf'), $compact_data);
                return $pdf->download($page_title . '.pdf');
            } elseif ($request->type == 'excel_2007') {
                return Excel::download(new AccountingExport($view), $page_title . '.xlsx');
            } elseif ($request->type == 'excel') {
                return Excel::download(new AccountingExport($view), $page_title . '.xls');
            } elseif ($request->type == 'csv') {
                return Excel::download(new AccountingExport($view), $page_title . '.csv');
            }
        }

        return view('accounting::report.budget.budget_overview')->with($compact_data);
    }

    private function resolveBusinessContext(): ?Business
    {
        $business_id = $this->resolveBusinessId();

        if (empty($business_id)) {
            return null;
        }

        return Business::find($business_id);
    }

    public function journal(Request $request)
    {
        $start_date = $request->start_date ?: $this->default_start_date;
        $end_date = $request->end_date ?: $this->default_end_date;
        $location_id = $request->location_id;
        $business_id = $this->resolveBusinessId();
        $data = [];
        $business_locations = BusinessLocation::getDropdownCollection($business_id);
        $currency_code = currency_code();
        $chart_of_account_id = $request->chart_of_account_id;
        $account_types = ChartOfAccount::getAccountTypes();

        $data = JournalEntry::leftJoin("business_locations", "business_locations.id", "journal_entries.location_id")
            ->leftJoin("chart_of_accounts", "chart_of_accounts.id", "journal_entries.chart_of_account_id")
            ->leftJoin("users", "users.id", "journal_entries.created_by_id")
            ->leftJoin("account_subtypes", "account_subtypes.id", "chart_of_accounts.account_subtype_id")
            ->leftJoin("account_detail_types", "account_detail_types.id", "chart_of_accounts.detail_type_id")
            ->when($end_date, function ($query) use ($start_date, $end_date) {
                $query->whereBetween("journal_entries.date", [$start_date, $end_date]);
            })
            ->when($location_id, function ($query) use ($location_id) {
                $query->where("journal_entries.location_id", $location_id);
            })
            ->when($chart_of_account_id, function ($query) use ($chart_of_account_id) {
                $query->where("journal_entries.chart_of_account_id", $chart_of_account_id);
            })
            ->where('business_locations.business_id', $business_id)
            ->selectRaw("journal_entries.id,journal_entries.created_by_id,journal_entries.location_id,journal_entries.date,journal_entries.debit,journal_entries.credit,journal_entries.transaction_number,business_locations.name business_location,chart_of_accounts.account_type,chart_of_accounts.name account_name,concat(users.first_name,' ',users.last_name) created_by, account_subtypes.name account_subtype, account_detail_types.name account_detail_type")
            ->get();

        $compact_data = compact('start_date', 'end_date', 'location_id', 'data', 'business_locations', 'currency_code', 'account_types');

        //check if we should download
        if ($request->download) {
            $view = view('accounting::report.journal_pdf', $compact_data);

            if ($request->type == 'pdf') {
                $pdf = PDF::loadView(theme_view_file('accounting::report.journal_pdf'), $compact_data);
                return $pdf->download(trans_choice('accounting::report.journal', 1) . '.pdf');
            } elseif ($request->type == 'excel_2007') {
                return Excel::download(new AccountingExport($view), trans_choice('accounting::report.journal', 1) . '.xlsx');
            } elseif ($request->type == 'excel') {
                return Excel::download(new AccountingExport($view), trans_choice('accounting::report.journal', 1) . '.xls');
            } elseif ($request->type == 'csv') {
                return Excel::download(new AccountingExport($view), trans_choice('accounting::report.journal', 1) . '.csv');
            }
        }

        return view('accounting::report.journal', $compact_data);
    }

    private function resolveBusinessId(): ?int
    {
        $business_id = session('business.id') ?? session('user.business_id') ?? optional(auth()->user())->business_id;

        return !empty($business_id) ? (int) $business_id : null;
    }

    private function paymentTotalsSubquerySql(): string
    {
        if (Schema::hasColumn('transaction_payments', 'deleted_at')) {
            return 'SELECT transaction_id, SUM(amount) as paid_amount FROM transaction_payments WHERE deleted_at IS NULL GROUP BY transaction_id';
        }

        return 'SELECT transaction_id, SUM(amount) as paid_amount FROM transaction_payments GROUP BY transaction_id';
    }
}
