<?php

namespace Modules\Accounting\Http\Controllers;

use App\Account;
use App\AccountTransaction;
use Modules\Accounting\Entities\BusinessLocation;
use Modules\Accounting\Entities\Currency;
use Modules\Accounting\Services\ApiService;
use Modules\Accounting\Entities\PaymentDetail;
use App\Utils\Util;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Accounting\Exports\AccountingExport;
use PDF;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\ManageUserController;
use Modules\Accounting\Entities\ChartOfAccount;
use Modules\Accounting\Entities\JournalEntry;
use Modules\Accounting\Entities\PaymentType;
use Modules\Accounting\Entities\Transfer;
use Modules\Accounting\Services\FlashService;
use Yajra\DataTables\Facades\DataTables;

class AccountingController extends Controller
{
    private $commonUtil;

    private const TRANSFER_ACCOUNT_TYPES = ['asset', 'liability', 'equity'];

    public function __construct(Util $commonUtil)
    {
        $this->commonUtil = $commonUtil;
    }

    public function trial_balance(Request $request)
    {
        $start_date = $request->start_date;
        $end_date = $request->end_date;
        $location_id = $request->location_id;
        $data = [];
        $business_locations = BusinessLocation::getDropdownCollection($this->resolveBusinessId());
        if (!empty($start_date)) {
            $data = DB::table("chart_of_accounts")->join("journal_entries", "journal_entries.chart_of_account_id", "chart_of_accounts.id")->join("business_locations", "journal_entries.location_id", "business_locations.id")->when($start_date, function ($query) use ($start_date, $end_date) {
                $query->whereBetween('journal_entries.date', [$start_date, $end_date]);
            })->when($location_id, function ($query) use ($location_id) {
                $query->where('journal_entries.location_id', $location_id);
            })->where('chart_of_accounts.active', 1)->selectRaw("chart_of_accounts.name,chart_of_accounts.gl_code,chart_of_accounts.account_type,business_locations.name business_location,SUM(journal_entries.debit) debit,SUM(journal_entries.credit) credit")->groupBy("chart_of_accounts.id")->get();
            //check if we should download
            if ($request->download) {
                if ($request->type == 'pdf') {
                    $pdf = PDF::loadView('accounting::report.trial_balance_pdf', compact(
                        'start_date',
                        'end_date',
                        'location_id',
                        'data',
                        'business_locations'
                    ));
                    return $pdf->download(trans_choice('accounting::general.trial_balance', 1) . '(' . $start_date . ' to ' . $end_date . ').pdf');
                }
                $view = view(
                    'accounting::report.trial_balance_pdf',
                    compact(
                        'start_date',
                        'end_date',
                        'location_id',
                        'data',
                        'business_locations'
                    )
                );
                if ($request->type == 'excel_2007') {
                    return Excel::download(new AccountingExport($view), trans_choice('accounting::general.trial_balance', 1) . '(' . $start_date . ' to ' . $end_date . ').xlsx');
                }
                if ($request->type == 'excel') {
                    return Excel::download(new AccountingExport($view), trans_choice('accounting::general.trial_balance', 1) . '(' . $start_date . ' to ' . $end_date . ').xls');
                }
                if ($request->type == 'csv') {
                    return Excel::download(new AccountingExport($view), trans_choice('accounting::general.trial_balance', 1) . '(' . $start_date . ' to ' . $end_date . ').csv');
                }
            }
        }
        return view(
            'accounting::report.trial_balance',
            compact(
                'start_date',
                'end_date',
                'location_id',
                'data',
                'business_locations',
            )
        );
    }

    //income statement
    public function income_statement(Request $request)
    {
        $start_date = $request->start_date;
        $end_date = $request->end_date;
        $location_id = $request->location_id;
        $data = [];
        $business_locations = BusinessLocation::getDropdownCollection($this->resolveBusinessId());
        if (!empty($start_date)) {
            $data = DB::table("chart_of_accounts")->join("journal_entries", "journal_entries.chart_of_account_id", "chart_of_accounts.id")->join("business_locations", "journal_entries.location_id", "business_locations.id")->when($start_date, function ($query) use ($start_date, $end_date) {
                $query->whereBetween('journal_entries.date', [$start_date, $end_date]);
            })->when($location_id, function ($query) use ($location_id) {
                $query->where('journal_entries.location_id', $location_id);
            })->where('chart_of_accounts.active', 1)->whereIn('chart_of_accounts.account_type', ['income', 'expense'])->selectRaw("chart_of_accounts.name,chart_of_accounts.gl_code,chart_of_accounts.account_type,business_locations.name business_location,SUM(journal_entries.debit) debit,SUM(journal_entries.credit) credit")->groupBy("chart_of_accounts.id")->orderBy('account_type')->get();
            //check if we should download
            if ($request->download) {
                if ($request->type == 'pdf') {
                    $pdf = PDF::loadView('accounting::report.income_statement_pdf', compact(
                        'start_date',
                        'end_date',
                        'location_id',
                        'data',
                        'business_locations'
                    ));
                    return $pdf->download(trans_choice('accounting::general.income_statement', 1) . '(' . $start_date . ' to ' . $end_date . ').pdf');
                }
                $view = view(
                    'accounting::report.income_statement_pdf',
                    compact(
                        'start_date',
                        'end_date',
                        'location_id',
                        'data',
                        'business_locations'
                    )
                );
                if ($request->type == 'excel_2007') {
                    return Excel::download(new AccountingExport($view), trans_choice('accounting::general.income_statement', 1) . '(' . $start_date . ' to ' . $end_date . ').xlsx');
                }
                if ($request->type == 'excel') {
                    return Excel::download(new AccountingExport($view), trans_choice('accounting::general.income_statement', 1) . '(' . $start_date . ' to ' . $end_date . ').xls');
                }
                if ($request->type == 'csv') {
                    return Excel::download(new AccountingExport($view), trans_choice('accounting::general.income_statement', 1) . '(' . $start_date . ' to ' . $end_date . ').csv');
                }
            }
        }
        return view(
            'accounting::report.income_statement',
            compact(
                'start_date',
                'end_date',
                'location_id',
                'data',
                'business_locations',
            )
        );
    }

    //balance sheet
    public function balance_sheet(Request $request)
    {
        $end_date = $request->end_date;
        $location_id = $request->location_id;
        $data = [];
        $business_locations = BusinessLocation::getDropdownCollection($this->resolveBusinessId());
        if (!empty($end_date)) {
            $data = DB::table("chart_of_accounts")->leftJoin('journal_entries', function ($join) use ($end_date) {
                $join->on('journal_entries.chart_of_account_id', '=', 'chart_of_accounts.id')
                    ->when($end_date, function ($query) use ($end_date) {
                        $query->where('journal_entries.date', '<=', $end_date);
                    });
            })->leftJoin('business_locations', function ($join) use ($location_id) {
                $join->on('journal_entries.location_id', '=', 'business_locations.id')
                    ->when($location_id, function ($query) use ($location_id) {
                        $query->where('journal_entries.location_id', $location_id);
                    });
            })->where('chart_of_accounts.active', 1)->whereIn('chart_of_accounts.account_type', ['asset', 'equity', 'liability'])->selectRaw("chart_of_accounts.name,chart_of_accounts.gl_code,chart_of_accounts.account_type,business_locations.name business_location,SUM(journal_entries.debit) debit,SUM(journal_entries.credit) credit")->groupBy("chart_of_accounts.id")->orderBy('account_type')->get();
            //check if we should download
            if ($request->download) {
                if ($request->type == 'pdf') {
                    $pdf = PDF::loadView('accounting::report.balance_sheet_pdf', compact('end_date', 'location_id', 'data', 'business_locations'));
                    return $pdf->download(trans_choice('accounting::general.balance_sheet', 1) . '(' . $end_date . ').pdf');
                }
                $view = view(
                    'accounting::report.balance_sheet_pdf',
                    compact('end_date', 'location_id', 'data', 'business_locations')
                );
                if ($request->type == 'excel_2007') {
                    return Excel::download(new AccountingExport($view), trans_choice('accounting::general.balance_sheet', 1) . '(' . $end_date . ').xlsx');
                }
                if ($request->type == 'excel') {
                    return Excel::download(new AccountingExport($view), trans_choice('accounting::general.balance_sheet', 1) . '(' . $end_date . ').xls');
                }
                if ($request->type == 'csv') {
                    return Excel::download(new AccountingExport($view), trans_choice('accounting::general.balance_sheet', 1) . '(' . $end_date . ').csv');
                }
            }
        }
        return view(
            'accounting::report.balance_sheet',
            compact('end_date', 'location_id', 'data', 'business_locations')
        );
    }

    public function transfers()
    {
        if (!auth()->user()->can('account.access')) {
            abort(403, 'Unauthorized action.');
        }

        $transfers = Transfer::with('transfer_from')
            ->with('transfer_to')
            ->with('transfer_by')
            ->forBusiness()
            ->get();

        $business_id = session()->get('user.business_id');

        if (request()->ajax()) {
            return DataTables::of($transfers)
                ->editColumn('journal_entry', function ($row) {
                    $journal_transaction_number = $row->journal_transaction_number;
                    return '<a href="/accounting/journal_entry/' . $journal_transaction_number . '/show"> ' . $journal_transaction_number . ' </a>&nbsp;';
                })
                ->editColumn('transfer_from', function ($row) {
                    $transfer_from = $row->transfer_from->name;
                    $transfer_from_id = $row->transfer_from_id;
                    return '<a href="/accounting/chart_of_account/' . $transfer_from_id . '/show"> ' . $transfer_from . ' </a>&nbsp;';
                })
                ->editColumn('transfer_to', function ($row) {
                    $transfer_to = $row->transfer_to->name;
                    $transfer_to_id = $row->transfer_to_id;
                    return '<a href="/accounting/chart_of_account/' . $transfer_to_id . '/show"> ' . $transfer_to . ' </a>&nbsp;';
                })
                ->editColumn('transfer_by', function ($row) {
                    $transfer_by = $row->transfer_by->user_full_name;
                    $transfer_by_id = $row->transfer_by_id;
                    return '<a href="' . action([ManageUserController::class, 'show'], [$transfer_by_id]) . '"> ' . $transfer_by . ' </a>&nbsp;';
                })
                ->editColumn('arrow_right', function () {
                    return '<i class="fa fa-arrow-right" aria-hidden="true"></i>';
                })
                ->rawColumns(['journal_entry', 'transfer_from', 'transfer_to', 'transfer_by', 'arrow_right'])
                ->make(true);
        }

        return view('accounting::transfers.index')->with(compact('business_id'));
    }

    public function create_transfer()
    {
        $businessId = $this->resolveBusinessId();
        $chart_of_accounts = ChartOfAccount::query()
            ->where('business_id', $businessId)
            ->where('active', 1)
            ->whereIn('account_type', self::TRANSFER_ACCOUNT_TYPES)
            ->orderBy('gl_code')
            ->get();
        $currencies = Currency::all();
        $payment_types = PaymentType::getTypesCollection();

        // Always load active locations directly for transfer UI defaults.
        $business_locations = \App\BusinessLocation::query()
            ->where('business_id', $businessId)
            ->where('is_active', 1)
            ->orderBy('name')
            ->select([
                'id',
                DB::raw("IF(location_id IS NULL OR location_id='', name, CONCAT(name, ' (', location_id, ')')) AS name"),
            ])
            ->get();

        Log::info('AccountingController::create_transfer options loaded', [
            'business_id' => $businessId,
            'locations_count' => $business_locations->count(),
            'chart_of_accounts_count' => $chart_of_accounts->count(),
        ]);

        return view('accounting::transfers.create', compact('chart_of_accounts', 'currencies', 'payment_types', 'business_locations'));
    }

    private function resolveBusinessId(): ?int
    {
        $businessId = session('business.id')
            ?? session('user.business_id')
            ?? optional(auth()->user())->business_id;

        return ! empty($businessId) ? (int) $businessId : null;
    }

    public function store_transfer(Request $request)
    {
        $request->validate([
            'location_id' => ['required'],
            'currency_id' => ['required'],
            'amount' => ['required'],
            'debit' => ['required'],
            'credit' => ['required'],
            'date' => ['required', 'date']
        ]);

        try {
            DB::beginTransaction();

            $amount = $this->commonUtil->num_uf($request->input('amount'));

            $this->assertDistinctTransferAccounts((int) $request->debit, (int) $request->credit);

            $businessId = $this->resolveBusinessId();
            $fromChartAccount = ChartOfAccount::query()
                ->where('business_id', $businessId)
                ->findOrFail($request->debit);
            $toChartAccount = ChartOfAccount::query()
                ->where('business_id', $businessId)
                ->findOrFail($request->credit);

            $this->assertBalanceSheetTransferAccounts($fromChartAccount, $toChartAccount);

            $transaction_number = $this->store_transfer_journal_entry($request, $amount);

            $transfer = Transfer::create([
                'journal_transaction_number' => $transaction_number,
                'transfer_from_id' => $request->debit,
                'transfer_to_id' => $request->credit,
                'transfer_by_id' => Auth::id(),
                'amount' => $amount,
            ]);

            activity()
                ->on($transfer)
                ->withProperties(['id' => $transfer->id])
                ->log('Create Transfer');

            $this->syncTransferAccountTransactions($request, $transaction_number, $amount, $fromChartAccount, $toChartAccount);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();

            if ($e instanceof \InvalidArgumentException) {
                Log::warning($e->getMessage(), ['exception' => $e]);

                return response()->json([
                    'success' => false,
                    'msg' => $e->getMessage(),
                ], 422);
            }

            return (new ApiService())->onException($e);
        }

        return (new ApiService())->onSave();
    }

    private function store_transfer_journal_entry(Request $request, float $amount)
    {
        $payment_detail = new PaymentDetail();
        $payment_detail->created_by_id = Auth::id();
        $payment_detail->payment_type_id = $request->payment_type_id;
        $payment_detail->transaction_type = 'journal_transfer_entry';
        $payment_detail->cheque_number = $request->cheque_number;
        $payment_detail->receipt = $request->receipt;
        $payment_detail->account_number = $request->account_number;
        $payment_detail->bank_name = $request->bank_name;
        $payment_detail->routing_code = $request->routing_code;
        $payment_detail->save();

        $transaction_number = get_uniqid();

        //debit account
        $journal_entry = new JournalEntry();
        $journal_entry->created_by_id = Auth::id();
        $journal_entry->payment_detail_id = $payment_detail->id;
        $journal_entry->transaction_number = $transaction_number;
        $journal_entry->location_id = $request->location_id;
        $journal_entry->currency_id = $request->currency_id;
        $journal_entry->chart_of_account_id = $request->debit;
        $journal_entry->transaction_type = 'transfer_entry';
        $journal_entry->date = $request->date;
        $date = explode('-', $request->date);
        $journal_entry->month = $date[1];
        $journal_entry->year = $date[0];
        $journal_entry->debit = $amount;
        $journal_entry->reference = $request->reference;
        $journal_entry->manual_entry = 0;
        $journal_entry->notes = $request->notes;
        $journal_entry->save();
        //credit account
        $journal_entry = new JournalEntry();
        $journal_entry->created_by_id = Auth::id();
        $journal_entry->transaction_number = $transaction_number;
        $journal_entry->payment_detail_id = $payment_detail->id;
        $journal_entry->location_id = $request->location_id;
        $journal_entry->currency_id = $request->currency_id;
        $journal_entry->chart_of_account_id = $request->credit;
        $journal_entry->transaction_type = 'transfer_entry';
        $journal_entry->date = $request->date;
        $date = explode('-', $request->date);
        $journal_entry->month = $date[1];
        $journal_entry->year = $date[0];
        $journal_entry->credit = $amount;
        $journal_entry->reference = $request->reference;
        $journal_entry->manual_entry = 0;
        $journal_entry->notes = $request->notes;
        $journal_entry->save();

        activity()
            ->on($journal_entry)
            ->withProperties(['id' => $journal_entry->id])
            ->log('Create Journal Entry for Transfer');

        return $transaction_number;
    }

    private function syncTransferAccountTransactions(Request $request, string $transactionNumber, float $amount, ?ChartOfAccount $fromChartAccount = null, ?ChartOfAccount $toChartAccount = null): void
    {
        $businessId = $this->resolveBusinessId();
        if (empty($businessId)) {
            return;
        }

        $amount = round($amount, 4);
        if ($amount <= 0) {
            return;
        }

        $fromChartAccount = $fromChartAccount ?: ChartOfAccount::query()
            ->where('business_id', $businessId)
            ->find($request->debit);
        $toChartAccount = $toChartAccount ?: ChartOfAccount::query()
            ->where('business_id', $businessId)
            ->find($request->credit);

        if (empty($fromChartAccount) || empty($toChartAccount)) {
            return;
        }

        $fromLegacyAccount = $this->findLegacyAccountForChartAccount($businessId, $fromChartAccount->gl_code);
        $toLegacyAccount = $this->findLegacyAccountForChartAccount($businessId, $toChartAccount->gl_code);

        if (empty($fromLegacyAccount) || empty($toLegacyAccount)) {
            return;
        }

        $operationDate = $request->filled('date')
            ? $this->commonUtil->uf_date($request->input('date'), false)
            : now()->toDateString();
        $createdBy = Auth::id();
        $note = $request->notes;

        $fromData = [
            'amount' => $amount,
            'account_id' => $fromLegacyAccount->id,
            'type' => $this->typeForDecrease($fromLegacyAccount),
            'sub_type' => 'journal_entry',
            'reff_no' => $transactionNumber,
            'operation_date' => $operationDate,
            'created_by' => $createdBy,
            'note' => $note,
        ];
        $fromAccountTransaction = AccountTransaction::createAccountTransaction($fromData);

        $toData = [
            'amount' => $amount,
            'account_id' => $toLegacyAccount->id,
            'type' => $this->typeForIncrease($toLegacyAccount),
            'sub_type' => 'journal_entry',
            'reff_no' => $transactionNumber,
            'operation_date' => $operationDate,
            'created_by' => $createdBy,
            'note' => $note,
            'transfer_transaction_id' => $fromAccountTransaction->id,
        ];
        $toAccountTransaction = AccountTransaction::createAccountTransaction($toData);

        $fromAccountTransaction->transfer_transaction_id = $toAccountTransaction->id;
        $fromAccountTransaction->save();
    }

    private function findLegacyAccountForChartAccount(int $businessId, $glCode): ?Account
    {
        $normalizedGlCode = trim((string) $glCode);
        if ($normalizedGlCode === '') {
            return null;
        }

        return Account::query()
            ->where('business_id', $businessId)
            ->where('account_number', $normalizedGlCode)
            ->with(['account_type.parent_account'])
            ->first();
    }

    private function typeForIncrease(Account $account): string
    {
        return $account->isDebitNormalAccount() ? 'debit' : 'credit';
    }

    private function typeForDecrease(Account $account): string
    {
        return $account->isDebitNormalAccount() ? 'credit' : 'debit';
    }

    private function assertBalanceSheetTransferAccounts(ChartOfAccount $fromChartAccount, ChartOfAccount $toChartAccount): void
    {
        if (! in_array($fromChartAccount->account_type, self::TRANSFER_ACCOUNT_TYPES, true)
            || ! in_array($toChartAccount->account_type, self::TRANSFER_ACCOUNT_TYPES, true)) {
            throw new \InvalidArgumentException('Transfers are only allowed between balance sheet accounts. Use a journal entry for income, expense, or COGS reclassifications.');
        }
    }

    private function assertDistinctTransferAccounts(int $fromChartAccountId, int $toChartAccountId): void
    {
        if ($fromChartAccountId === $toChartAccountId) {
            throw new \InvalidArgumentException('Transfer source and destination accounts must be different.');
        }
    }
}
