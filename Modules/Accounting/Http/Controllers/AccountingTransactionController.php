<?php

namespace Modules\Accounting\Http\Controllers;

use App\Account;
use App\Business;
use App\TransactionPayment;
use Modules\Accounting\Services\FlashService;
use Modules\Accounting\Entities\PaymentDetail;
use Modules\Accounting\Entities\Transaction;
use Modules\Accounting\Utils\ExpenseUtil;
use Modules\Accounting\Utils\PurchaseUtil;
use Modules\Accounting\Utils\SellUtil;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Accounting\Entities\ChartOfAccount;
use Modules\Accounting\Entities\JournalEntry;

class AccountingTransactionController extends Controller
{
    protected $sellUtil;
    protected $expenseUtil;
    protected $purchaseUtil;

    public function __construct(SellUtil $sellUtil, ExpenseUtil $expenseUtil, PurchaseUtil $purchaseUtil)
    {
        $this->sellUtil = $sellUtil;
        $this->expenseUtil = $expenseUtil;
        $this->purchaseUtil = $purchaseUtil;
    }

    public function sales()
    {
        switch (request()->type) {
            case 'payment':
                if (request()->ajax()) {
                    return $this->sellUtil->getTransactionsDataTableSafe();
                }
                $data_array = $this->sellUtil->getData();
                $data_array['chart_of_accounts'] = ChartOfAccount::where('active', 1)->orderBy('gl_code')->get();
                return view('accounting::transactions.sales.payment')->with($data_array);

            case 'invoice':
                if (request()->ajax()) {
                    return $this->sellUtil->getTransactionsDataTableSafe();
                }
                $data_array = $this->sellUtil->getData();
                $data_array['chart_of_accounts'] = ChartOfAccount::where('active', 1)->orderBy('gl_code')->get();
                return view('accounting::transactions.sales.invoice')->with($data_array);

            default:
                abort(404);
                break;
        }
    }

    public function expenses()
    {
        if (!auth()->user()->can('all_expense.access') && !auth()->user()->can('view_own_expense')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            return $this->expenseUtil->getTransactionsDataTable();
        }

        $data_array = $this->expenseUtil->getData();
        $data_array['chart_of_accounts'] = ChartOfAccount::where('active', 1)->orderBy('gl_code')->get();

        return view('accounting::transactions.expenses')->with($data_array);
    }

    public function purchases()
    {
        switch (request()->type) {
            case 'purchase_order':
                if (!auth()->user()->can('purchase_order.view_all') && !auth()->user()->can('purchase_order.view_own')) {
                    abort(403, 'Unauthorized action.');
                }

                if (request()->ajax()) {
                    return $this->purchaseUtil->getPurchaseOrderTransactionsDataTable();
                }

                $data_array = $this->purchaseUtil->getPurchaseOrdersData();
                $data_array['chart_of_accounts'] = ChartOfAccount::where('active', 1)->orderBy('gl_code')->get();
                return view('accounting::transactions.purchases.purchase_order')->with($data_array);

            case 'purchase_payment':
                if (!auth()->user()->can('purchase.view') && !auth()->user()->can('purchase.create') && !auth()->user()->can('view_own_purchase')) {
                    abort(403, 'Unauthorized action.');
                }
                if (request()->ajax()) {
                    return $this->purchaseUtil->getPurchaseTransactionsDataTable();
                }
                $data_array = $this->purchaseUtil->getPurchaseData();
                $data_array['chart_of_accounts'] = ChartOfAccount::where('active', 1)
                    ->whereIn('account_type', ['asset', 'expense'])
                    ->orderBy('gl_code')
                    ->get();
                return view('accounting::transactions.purchases.purchase_payment')->with($data_array);

            default:
                abort(404);
                break;
        }
    }

    public function map_to_chart_of_account(Request $request)
    {
        $request->validate([
            'map_type' => ['required'],
            'mapping_for' => ['required'],
            'chart_of_account_id' => ['required'],
            'transaction_id' => ['required'],
            'notes' => ['nullable'],
        ]);

        try {
            DB::beginTransaction();

            $business = Business::where('id', session('business.id'))->select('id', 'currency_id', 'common_settings')->firstOrFail();
            $transaction = Transaction::where('id', $request->transaction_id)->select('id', 'business_id', 'location_id', 'type', 'final_total', 'journal_entry_id')->firstOrFail();
            $transaction_number = get_uniqid();
            $transaction_type = "map_{$request->mapping_for}_transaction_to_journal_entry";

            if ($request->mapping_for === 'purchase_payment') {
                $journalEntry = $this->storePurchasePaymentJournalEntries($request, $transaction, $business, $transaction_number, $transaction_type);

                Transaction::findOrFail($request->transaction_id)->update(['journal_entry_id' => $journalEntry->id]);

                activity()
                    ->on($journalEntry)
                    ->withProperties(['id' => $journalEntry->id])
                    ->log("Map {$request->mapping_for} transaction to journal entry");

                DB::commit();

                (new FlashService())->onSave();
                return back();
            }

            if ($request->mapping_for === 'payment') {
                $journalEntry = $this->storeSalesPaymentJournalEntries($request, $transaction, $business, $transaction_number, $transaction_type);

                Transaction::findOrFail($request->transaction_id)->update(['journal_entry_id' => $journalEntry->id]);

                activity()
                    ->on($journalEntry)
                    ->withProperties(['id' => $journalEntry->id])
                    ->log("Map {$request->mapping_for} transaction to journal entry");

                DB::commit();

                (new FlashService())->onSave();
                return back();
            }

            $payment_detail = new PaymentDetail();
            $payment_detail->created_by_id = Auth::id();
            $payment_detail->payment_type_id = 1; //cash
            $payment_detail->transaction_type = $transaction_type;
            $payment_detail->cheque_number = null;
            $payment_detail->receipt = null;
            $payment_detail->account_number = null;
            $payment_detail->bank_name = null;
            $payment_detail->routing_code = null;
            $payment_detail->save();

            $today = date('Y-m-d');
            $journal_entry = new JournalEntry();
            $journal_entry->created_by_id = Auth::id();
            $journal_entry->transaction_number = $transaction_number;
            $journal_entry->payment_detail_id = $payment_detail->id;
            $journal_entry->location_id = $transaction->location_id;
            $journal_entry->currency_id = $business->currency_id;
            $journal_entry->chart_of_account_id = $request->chart_of_account_id;
            $journal_entry->transaction_type = $transaction_type;
            $journal_entry->date = $today;
            $date = explode('-', $today);
            $journal_entry->month = $date[1];
            $journal_entry->year = $date[0];
            $request->map_type == 'credit' ?
                $journal_entry->credit = $transaction->final_total :
                $journal_entry->debit = $transaction->final_total;
            $journal_entry->manual_entry = 0;
            $journal_entry->notes = $request->notes;
            $journal_entry->save();

            Transaction::findOrFail($request->transaction_id)->update(['journal_entry_id' => $journal_entry->id]);

            activity()
                ->on($journal_entry)
                ->withProperties(['id' => $journal_entry->id])
                ->log("Map {$request->mapping_for} transaction to journal entry");

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return (new  FlashService())->onException($e)->redirectBackWithInput();
        }

        (new FlashService())->onSave();
        return back();
    }

    private function storePurchasePaymentJournalEntries(Request $request, Transaction $transaction, Business $business, string $transactionNumber, string $transactionType): JournalEntry
    {
        $debitAccount = ChartOfAccount::where('id', $request->chart_of_account_id)
            ->where('active', 1)
            ->firstOrFail();

        if (! in_array($debitAccount->account_type, ['asset', 'expense'], true)) {
            throw new \InvalidArgumentException('Purchase payment mapping must target an asset or expense account, such as Inventory or a purchase expense account.');
        }

        $payments = TransactionPayment::query()
            ->where('transaction_id', $transaction->id)
            ->whereNull('parent_id')
            ->where('is_return', 0)
            ->where('amount', '>', 0)
            ->get(['id', 'account_id', 'amount', 'method']);

        if ($payments->isEmpty()) {
            throw new \InvalidArgumentException('No purchase payment lines were found for this transaction.');
        }

        $paymentAccounts = Account::query()
            ->whereIn('id', $payments->pluck('account_id')->filter()->unique()->values()->all())
            ->get()
            ->keyBy('id');

        $creditChartAccounts = [];
        foreach ($payments as $payment) {
            $legacyAccount = ! empty($payment->account_id) ? $paymentAccounts->get($payment->account_id) : null;
            $creditChartAccount = $this->resolvePurchasePaymentCreditAccount($business, $transaction, $legacyAccount, $payment->method);

            if (empty($creditChartAccounts[$creditChartAccount->id])) {
                $creditChartAccounts[$creditChartAccount->id] = [
                    'chart_account' => $creditChartAccount,
                    'amount' => 0.0,
                ];
            }

            $creditChartAccounts[$creditChartAccount->id]['amount'] = round(
                $creditChartAccounts[$creditChartAccount->id]['amount'] + (float) $payment->amount,
                4
            );
        }

        $paymentDetail = new PaymentDetail();
        $paymentDetail->created_by_id = Auth::id();
        $paymentDetail->payment_type_id = 1;
        $paymentDetail->transaction_type = $transactionType;
        $paymentDetail->save();

        $today = date('Y-m-d');
        $date = explode('-', $today);
        $totalPaid = round((float) $payments->sum('amount'), 4);

        $debitEntry = new JournalEntry();
        $debitEntry->created_by_id = Auth::id();
        $debitEntry->transaction_number = $transactionNumber;
        $debitEntry->payment_detail_id = $paymentDetail->id;
        $debitEntry->location_id = $transaction->location_id;
        $debitEntry->currency_id = $business->currency_id;
        $debitEntry->chart_of_account_id = $debitAccount->id;
        $debitEntry->transaction_type = $transactionType;
        $debitEntry->date = $today;
        $debitEntry->month = $date[1];
        $debitEntry->year = $date[0];
        $debitEntry->debit = $totalPaid;
        $debitEntry->manual_entry = 0;
        $debitEntry->notes = $request->notes;
        $debitEntry->save();

        foreach ($creditChartAccounts as $creditRow) {
            $creditEntry = new JournalEntry();
            $creditEntry->created_by_id = Auth::id();
            $creditEntry->transaction_number = $transactionNumber;
            $creditEntry->payment_detail_id = $paymentDetail->id;
            $creditEntry->location_id = $transaction->location_id;
            $creditEntry->currency_id = $business->currency_id;
            $creditEntry->chart_of_account_id = $creditRow['chart_account']->id;
            $creditEntry->transaction_type = $transactionType;
            $creditEntry->date = $today;
            $creditEntry->month = $date[1];
            $creditEntry->year = $date[0];
            $creditEntry->credit = $creditRow['amount'];
            $creditEntry->manual_entry = 0;
            $creditEntry->notes = $request->notes;
            $creditEntry->save();
        }

        return $debitEntry;
    }

    private function storeSalesPaymentJournalEntries(Request $request, Transaction $transaction, Business $business, string $transactionNumber, string $transactionType): JournalEntry
    {
        $creditAccount = ChartOfAccount::where('id', $request->chart_of_account_id)
            ->where('active', 1)
            ->firstOrFail();

        $payments = TransactionPayment::query()
            ->where('transaction_id', $transaction->id)
            ->whereNull('parent_id')
            ->where('is_return', 0)
            ->where('amount', '>', 0)
            ->get(['id', 'account_id', 'amount', 'method']);

        if ($payments->isEmpty()) {
            throw new \InvalidArgumentException('No sales payment lines were found for this transaction.');
        }

        $paymentAccounts = Account::query()
            ->whereIn('id', $payments->pluck('account_id')->filter()->unique()->values()->all())
            ->get()
            ->keyBy('id');

        $debitChartAccounts = [];
        foreach ($payments as $payment) {
            $legacyAccount = ! empty($payment->account_id) ? $paymentAccounts->get($payment->account_id) : null;
            $debitChartAccount = $this->resolvePurchasePaymentCreditAccount($business, $transaction, $legacyAccount, $payment->method);

            if (empty($debitChartAccounts[$debitChartAccount->id])) {
                $debitChartAccounts[$debitChartAccount->id] = [
                    'chart_account' => $debitChartAccount,
                    'amount' => 0.0,
                ];
            }

            $debitChartAccounts[$debitChartAccount->id]['amount'] = round(
                $debitChartAccounts[$debitChartAccount->id]['amount'] + (float) $payment->amount,
                4
            );
        }

        $paymentDetail = new PaymentDetail();
        $paymentDetail->created_by_id = Auth::id();
        $paymentDetail->payment_type_id = 1;
        $paymentDetail->transaction_type = $transactionType;
        $paymentDetail->save();

        $today = date('Y-m-d');
        $date = explode('-', $today);
        $totalPaid = round((float) $payments->sum('amount'), 4);

        foreach ($debitChartAccounts as $debitRow) {
            $debitEntry = new JournalEntry();
            $debitEntry->created_by_id = Auth::id();
            $debitEntry->transaction_number = $transactionNumber;
            $debitEntry->payment_detail_id = $paymentDetail->id;
            $debitEntry->location_id = $transaction->location_id;
            $debitEntry->currency_id = $business->currency_id;
            $debitEntry->chart_of_account_id = $debitRow['chart_account']->id;
            $debitEntry->transaction_type = $transactionType;
            $debitEntry->date = $today;
            $debitEntry->month = $date[1];
            $debitEntry->year = $date[0];
            $debitEntry->debit = $debitRow['amount'];
            $debitEntry->manual_entry = 0;
            $debitEntry->notes = $request->notes;
            $debitEntry->save();
        }

        $creditEntry = new JournalEntry();
        $creditEntry->created_by_id = Auth::id();
        $creditEntry->transaction_number = $transactionNumber;
        $creditEntry->payment_detail_id = $paymentDetail->id;
        $creditEntry->location_id = $transaction->location_id;
        $creditEntry->currency_id = $business->currency_id;
        $creditEntry->chart_of_account_id = $creditAccount->id;
        $creditEntry->transaction_type = $transactionType;
        $creditEntry->date = $today;
        $creditEntry->month = $date[1];
        $creditEntry->year = $date[0];
        $creditEntry->credit = $totalPaid;
        $creditEntry->manual_entry = 0;
        $creditEntry->notes = $request->notes;
        $creditEntry->save();

        return $creditEntry;
    }

    private function resolvePurchasePaymentCreditAccount(Business $business, Transaction $transaction, ?Account $legacyAccount, ?string $paymentMethod): ChartOfAccount
    {
        $resolvedPaymentAccountId = TransactionPayment::resolveDefaultAccountId(
            $paymentMethod,
            $transaction->location_id,
            $transaction->business_id,
            $transaction->type
        );

        if (! empty($resolvedPaymentAccountId)) {
            $resolvedPaymentAccount = Account::query()->find($resolvedPaymentAccountId);
            $resolvedChartAccount = $this->findActiveChartAccountForLegacyAccount($transaction->business_id, $resolvedPaymentAccount);

            if (! empty($resolvedChartAccount) && $resolvedChartAccount->account_type === 'asset') {
                return $resolvedChartAccount;
            }
        }

        $creditChartAccount = $this->findActiveChartAccountForLegacyAccount($transaction->business_id, $legacyAccount);

        if (! empty($creditChartAccount) && $creditChartAccount->account_type === 'asset') {
            return $creditChartAccount;
        }

        $defaultPaymentAccountId = (int) data_get($business->common_settings, 'default_account_mappings.payment');
        if ($defaultPaymentAccountId <= 0) {
            throw new \InvalidArgumentException('No valid asset payment account is configured for purchase payment mapping.');
        }

        $defaultPaymentAccount = Account::query()->find($defaultPaymentAccountId);
        $fallbackChartAccount = $this->findActiveChartAccountForLegacyAccount($transaction->business_id, $defaultPaymentAccount);

        if (empty($fallbackChartAccount) || $fallbackChartAccount->account_type !== 'asset') {
            throw new \InvalidArgumentException('The configured default payment chart account must be an asset account.');
        }

        return $fallbackChartAccount;
    }

    private function findActiveChartAccountForLegacyAccount(int $businessId, ?Account $legacyAccount): ?ChartOfAccount
    {
        if (empty($legacyAccount) || empty($legacyAccount->account_number)) {
            return null;
        }

        return ChartOfAccount::query()
            ->where('business_id', $businessId)
            ->where('gl_code', $legacyAccount->account_number)
            ->where('active', 1)
            ->first();
    }
}
