<?php

namespace App\Listeners;

use App\Account;
use App\AccountTransaction;
use App\AccountType;
use App\Business;
use App\Events\StockAdjustmentCreatedOrModified;
use App\Transaction;
use App\TransactionPayment;
use App\Utils\ModuleUtil;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class SyncStockAdjustmentAccountTransaction
{
    protected $moduleUtil;

    protected $subTypes = [
        'stock_adjustment_inventory',
        'stock_adjustment_inventory_adjustment',
        'stock_adjustment_inventory_gain',
        'stock_adjustment_inventory_loss',
        'stock_adjustment_inventory_opening_equity',
    ];

    public function __construct(ModuleUtil $moduleUtil)
    {
        $this->moduleUtil = $moduleUtil;
    }

    public function handle(StockAdjustmentCreatedOrModified $event)
    {
        $transaction = $event->stockAdjustment;

        if (
            empty($transaction)
            || $transaction->type !== 'stock_adjustment'
            || ! $this->moduleUtil->isModuleEnabled('account', $transaction->business_id)
        ) {
            return true;
        }

        $accountTransactionQuery = AccountTransaction::where('transaction_id', $transaction->id)
            ->whereNull('transaction_payment_id')
            ->whereIn('reff_no', $this->subTypes);

        if ($event->action === 'deleted') {
            $accountTransactionQuery->delete();

            return true;
        }

        $transaction = Transaction::with('stock_adjustment_lines')->find($transaction->id) ?: $transaction->loadMissing('stock_adjustment_lines');

        $inventoryAccountId = TransactionPayment::resolveDefaultAccountMapping('inventory', $transaction->business_id);
        $amount = round($this->getAdjustmentAmount($transaction), 4);

        if (empty($inventoryAccountId) || abs($amount) < 0.00001) {
            $accountTransactionQuery->delete();

            return true;
        }

        $operationDate = $transaction->transaction_date;
        $createdBy = $transaction->created_by;
        $note = $transaction->ref_no ?: $transaction->additional_notes;
        $entryAmount = abs($amount);

        $this->syncEntry(
            $transaction,
            'stock_adjustment_inventory',
            $inventoryAccountId,
            $entryAmount,
            $amount > 0 ? 'debit' : 'credit',
            $operationDate,
            $createdBy,
            $note
        );

        $shouldUseOpeningStockEquity = $this->shouldPostStocktakeToOpeningEquity($transaction);

        if ($shouldUseOpeningStockEquity) {
            $openingStockEquityAccountId = $this->ensureOpeningStockEquityAccountId($transaction);

            if (empty($openingStockEquityAccountId)) {
                $accountTransactionQuery->delete();

                return true;
            }

            $this->syncEntry(
                $transaction,
                'stock_adjustment_inventory_opening_equity',
                $openingStockEquityAccountId,
                $entryAmount,
                $amount > 0 ? 'credit' : 'debit',
                $operationDate,
                $createdBy,
                $note
            );

            $this->deleteEntry($transaction, 'stock_adjustment_inventory_gain');
            $this->deleteEntry($transaction, 'stock_adjustment_inventory_loss');
            $this->deleteEntry($transaction, 'stock_adjustment_inventory_adjustment');

            return true;
        }

        $this->deleteEntry($transaction, 'stock_adjustment_inventory_opening_equity');

        if ($amount > 0) {
            $gainAccountId = $this->ensureInventoryGainAccountId($transaction);

            if (empty($gainAccountId)) {
                $accountTransactionQuery->delete();

                return true;
            }

            $this->syncEntry(
                $transaction,
                'stock_adjustment_inventory_gain',
                $gainAccountId,
                $entryAmount,
                'credit',
                $operationDate,
                $createdBy,
                $note
            );

            $this->deleteEntry($transaction, 'stock_adjustment_inventory_loss');
            $this->deleteEntry($transaction, 'stock_adjustment_inventory_adjustment');
        } else {
            $lossAccountId = $this->ensureInventoryLossAccountId($transaction);

            if (empty($lossAccountId)) {
                $accountTransactionQuery->delete();

                return true;
            }

            $this->syncEntry(
                $transaction,
                'stock_adjustment_inventory_loss',
                $lossAccountId,
                $entryAmount,
                'debit',
                $operationDate,
                $createdBy,
                $note
            );

            $this->deleteEntry($transaction, 'stock_adjustment_inventory_gain');
            $this->deleteEntry($transaction, 'stock_adjustment_inventory_adjustment');
        }

        return true;
    }

    protected function getAdjustmentAmount($transaction)
    {
        return (float) DB::table('stock_adjustment_lines as sal')
            ->leftJoin('variations as v', 'sal.variation_id', '=', 'v.id')
            ->where('sal.transaction_id', $transaction->id)
            ->sum(DB::raw('sal.quantity * COALESCE(NULLIF(v.dpp_inc_tax, 0), NULLIF(v.default_purchase_price, 0), 0)'));
    }

    protected function ensureInventoryGainAccountId($transaction)
    {
        $businessId = $transaction->business_id;
        $mappedAccountId = TransactionPayment::resolveDefaultAccountMapping('inventory_gain', $businessId);

        if (! empty($mappedAccountId) && Account::where('business_id', $businessId)->where('id', $mappedAccountId)->exists()) {
            return (int) $mappedAccountId;
        }

        $account = Account::where('business_id', $businessId)
            ->where('name', 'Inventory Gain')
            ->first();

        if (empty($account)) {
            $account = Account::create([
                'business_id' => $businessId,
                'name' => 'Inventory Gain',
                'account_number' => $this->nextAccountNumber($businessId),
                'account_type_id' => $this->resolveIncomeAccountTypeId($businessId),
                'created_by' => $transaction->created_by,
            ]);
        }

        $business = Business::find($businessId);
        $commonSettings = $business->common_settings ?: [];
        $typeMappings = ! empty($commonSettings['default_account_mappings'])
            ? $commonSettings['default_account_mappings']
            : [];

        if (($typeMappings['inventory_gain'] ?? null) != $account->id) {
            $typeMappings['inventory_gain'] = $account->id;
            $commonSettings['default_account_mappings'] = $typeMappings;
            $business->common_settings = $commonSettings;
            $business->save();
        }

        return (int) $account->id;
    }

    protected function ensureInventoryLossAccountId($transaction)
    {
        $businessId = $transaction->business_id;
        $mappedAccountId = TransactionPayment::resolveDefaultAccountMapping('inventory_loss', $businessId);

        if (! empty($mappedAccountId) && Account::where('business_id', $businessId)->where('id', $mappedAccountId)->exists()) {
            return (int) $mappedAccountId;
        }

        $account = Account::where('business_id', $businessId)
            ->where('name', 'Inventory Loss')
            ->first();

        if (empty($account)) {
            $legacyAdjustmentAccount = Account::where('business_id', $businessId)
                ->where('name', 'Inventory Adjustment')
                ->where('account_type_id', $this->resolveExpenseAccountTypeId($businessId))
                ->first();

            if (! empty($legacyAdjustmentAccount)) {
                $legacyAdjustmentAccount->name = 'Inventory Loss';
                $legacyAdjustmentAccount->save();
                $account = $legacyAdjustmentAccount;
            }
        }

        if (empty($account)) {
            $account = Account::create([
                'business_id' => $businessId,
                'name' => 'Inventory Loss',
                'account_number' => $this->nextAccountNumber($businessId),
                'account_type_id' => $this->resolveExpenseAccountTypeId($businessId),
                'created_by' => $transaction->created_by,
            ]);
        }

        $business = Business::find($businessId);
        $commonSettings = $business->common_settings ?: [];
        $typeMappings = ! empty($commonSettings['default_account_mappings'])
            ? $commonSettings['default_account_mappings']
            : [];

        if (($typeMappings['inventory_loss'] ?? null) != $account->id) {
            $typeMappings['inventory_loss'] = $account->id;

            if (($typeMappings['inventory_adjustment'] ?? null) == $account->id) {
                unset($typeMappings['inventory_adjustment']);
            }

            $commonSettings['default_account_mappings'] = $typeMappings;
            $business->common_settings = $commonSettings;
            $business->save();
        }

        return (int) $account->id;
    }

    protected function shouldPostStocktakeToOpeningEquity($transaction)
    {
        if (empty($transaction->is_stocktake) || empty($transaction->business_id) || empty($transaction->transaction_date)) {
            return false;
        }

        $business = Business::select('id', 'common_settings')->find($transaction->business_id);
        $cutoffDate = $business->common_settings['stocktake_opening_balance_cutoff_date'] ?? null;

        if (empty($cutoffDate)) {
            return false;
        }

        try {
            return Carbon::parse($transaction->transaction_date)->startOfDay()->lte(Carbon::parse($cutoffDate)->startOfDay());
        } catch (\Throwable $exception) {
            return false;
        }
    }

    protected function ensureOpeningStockEquityAccountId($transaction)
    {
        $businessId = $transaction->business_id;
        $mappedAccountId = TransactionPayment::resolveDefaultAccountMapping('opening_stock_equity', $businessId);

        if (! empty($mappedAccountId) && Account::where('business_id', $businessId)->where('id', $mappedAccountId)->exists()) {
            return (int) $mappedAccountId;
        }

        $account = Account::where('business_id', $businessId)
            ->where('name', 'Opening Stock Equity')
            ->first();

        if (empty($account)) {
            $account = Account::create([
                'business_id' => $businessId,
                'name' => 'Opening Stock Equity',
                'account_number' => $this->nextAccountNumber($businessId, 3100),
                'account_type_id' => $this->resolveEquityAccountTypeId($businessId),
                'created_by' => $transaction->created_by,
            ]);
        }

        $business = Business::find($businessId);
        $commonSettings = $business->common_settings ?: [];
        $typeMappings = ! empty($commonSettings['default_account_mappings'])
            ? $commonSettings['default_account_mappings']
            : [];

        if (($typeMappings['opening_stock_equity'] ?? null) != $account->id) {
            $typeMappings['opening_stock_equity'] = $account->id;
            $commonSettings['default_account_mappings'] = $typeMappings;
            $business->common_settings = $commonSettings;
            $business->save();
        }

        return (int) $account->id;
    }

    protected function resolveExpenseAccountTypeId($businessId)
    {
        $accountType = AccountType::where('business_id', $businessId)
            ->where(function ($query) {
                $query->whereRaw('LOWER(name) LIKE ?', ['%expense%'])
                    ->orWhereHas('parent_account', function ($parentQuery) {
                        $parentQuery->whereRaw('LOWER(name) LIKE ?', ['%expense%']);
                    });
            })
            ->orderByRaw('CASE WHEN parent_account_type_id IS NULL THEN 0 ELSE 1 END')
            ->orderBy('id')
            ->first();

        return optional($accountType)->id;
    }

    protected function resolveIncomeAccountTypeId($businessId)
    {
        $accountType = AccountType::where('business_id', $businessId)
            ->where(function ($query) {
                $query->whereRaw('LOWER(name) LIKE ?', ['%income%'])
                    ->orWhereRaw('LOWER(name) LIKE ?', ['%revenue%'])
                    ->orWhereHas('parent_account', function ($parentQuery) {
                        $parentQuery->whereRaw('LOWER(name) LIKE ?', ['%income%'])
                            ->orWhereRaw('LOWER(name) LIKE ?', ['%revenue%']);
                    });
            })
            ->orderByRaw('CASE WHEN parent_account_type_id IS NULL THEN 0 ELSE 1 END')
            ->orderBy('id')
            ->first();

        return optional($accountType)->id;
    }

    protected function resolveEquityAccountTypeId($businessId)
    {
        $accountType = AccountType::where('business_id', $businessId)
            ->where(function ($query) {
                $query->whereRaw('LOWER(name) LIKE ?', ['%equity%'])
                    ->orWhereRaw('LOWER(name) LIKE ?', ['%capital%'])
                    ->orWhereHas('parent_account', function ($parentQuery) {
                        $parentQuery->whereRaw('LOWER(name) LIKE ?', ['%equity%'])
                            ->orWhereRaw('LOWER(name) LIKE ?', ['%capital%']);
                    });
            })
            ->orderByRaw('CASE WHEN parent_account_type_id IS NULL THEN 0 ELSE 1 END')
            ->orderBy('id')
            ->first();

        return optional($accountType)->id;
    }

    protected function nextAccountNumber($businessId, $candidate = 5300)
    {
        while (Account::where('business_id', $businessId)->where('account_number', (string) $candidate)->exists()) {
            $candidate++;
        }

        return (string) $candidate;
    }

    protected function syncEntry($transaction, $subType, $accountId, $amount, $type, $operationDate, $createdBy, $note)
    {
        $accountTransaction = AccountTransaction::where('transaction_id', $transaction->id)
            ->whereNull('transaction_payment_id')
            ->where('reff_no', $subType)
            ->first();

        $amount = round((float) $amount, 4);

        if (empty($accountId) || $amount <= 0) {
            if (! empty($accountTransaction)) {
                $accountTransaction->delete();
            }

            return null;
        }

        $data = [
            'amount' => $amount,
            'account_id' => $accountId,
            'type' => $type,
            'reff_no' => $subType,
            'operation_date' => $operationDate,
            'created_by' => $createdBy,
            'transaction_id' => $transaction->id,
            'note' => $note,
        ];

        if (! empty($accountTransaction)) {
            $accountTransaction->amount = $data['amount'];
            $accountTransaction->account_id = $data['account_id'];
            $accountTransaction->type = $data['type'];
            $accountTransaction->reff_no = $data['reff_no'];
            $accountTransaction->operation_date = $data['operation_date'];
            $accountTransaction->created_by = $data['created_by'];
            $accountTransaction->note = $data['note'];
            $accountTransaction->save();

            return $accountTransaction;
        }

        return AccountTransaction::createAccountTransaction($data);
    }

    protected function deleteEntry($transaction, $subType)
    {
        AccountTransaction::where('transaction_id', $transaction->id)
            ->whereNull('transaction_payment_id')
            ->where('reff_no', $subType)
            ->delete();
    }
}