<?php

namespace App;

use App\Utils\Util;
use DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Account extends Model
{
    use SoftDeletes;

    protected $guarded = ['id'];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'account_details' => 'array',
    ];

    public static function forDropdown($business_id, $prepend_none, $closed = false, $show_balance = false)
    {
        $query = Account::where('accounts.business_id', $business_id);

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

        $can_access_account = auth()->user()->can('account.access');
        if ($can_access_account && $show_balance) {
            $balance_expression = self::typeAwareBalanceExpression(
                'COALESCE(pat.name, ats.name)',
                'AT.type',
                'AT.amount',
                'AT.sub_type'
            );

            $query->leftJoin('account_transactions as AT', function ($join) {
                $join->on('AT.account_id', '=', 'accounts.id')
                    ->whereNull('AT.deleted_at');
            })
                ->leftJoin('account_types as ats', 'accounts.account_type_id', '=', 'ats.id')
                ->leftJoin('account_types as pat', 'ats.parent_account_type_id', '=', 'pat.id');

            $query->select('accounts.name',
                    'accounts.id',
                    DB::raw($balance_expression.' as balance')
                )
                ->groupBy('accounts.id');
        }

        if (! $closed) {
            $query->where('accounts.is_closed', 0);
        }

        $accounts = $query->get();

        $dropdown = [];
        if ($prepend_none) {
            $dropdown[''] = __('lang_v1.none');
        }

        $commonUtil = new Util;
        foreach ($accounts as $account) {
            $name = $account->name;

            if ($can_access_account && $show_balance) {
                $name .= ' ('.__('lang_v1.balance').': '.$commonUtil->num_f($account->balance).')';
            }

            $dropdown[$account->id] = $name;
        }

        return $dropdown;
    }

    /**
     * Scope a query to only include not closed accounts.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeNotClosed($query)
    {
        return $query->where('accounts.is_closed', 0);
    }

    /**
     * Scope a query to only include non capital accounts.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    // public function scopeNotCapital($query)
    // {
    //     return $query->where(function ($q) {
    //         $q->where('account_type', '!=', 'capital');
    //         $q->orWhereNull('account_type');
    //     });
    // }

    public static function accountTypes()
    {
        return [
            '' => __('account.not_applicable'),
            'saving_current' => __('account.saving_current'),
            'capital' => __('account.capital'),
        ];
    }

    public function account_type()
    {
        return $this->belongsTo(\App\AccountType::class, 'account_type_id');
    }

    public function scopeNotExpense($query)
    {
        return $query->whereDoesntHave('account_type', function ($accountTypeQuery) {
            $accountTypeQuery->where('name', 'Expenses')
                ->orWhereHas('parent_account', function ($parentQuery) {
                    $parentQuery->where('name', 'Expenses');
                });
        });
    }

    public function scopeTransferEligible($query)
    {
        return $query->whereDoesntHave('account_type', function ($accountTypeQuery) {
            $accountTypeQuery->where(function ($query) {
                $query->whereRaw('LOWER(name) LIKE ?', ['%expense%'])
                    ->orWhereRaw('LOWER(name) LIKE ?', ['%income%'])
                    ->orWhereRaw('LOWER(name) LIKE ?', ['%revenue%'])
                    ->orWhereRaw('LOWER(name) LIKE ?', ['%cost%']);
            })->orWhereHas('parent_account', function ($parentQuery) {
                $parentQuery->where(function ($query) {
                    $query->whereRaw('LOWER(name) LIKE ?', ['%expense%'])
                        ->orWhereRaw('LOWER(name) LIKE ?', ['%income%'])
                        ->orWhereRaw('LOWER(name) LIKE ?', ['%revenue%'])
                        ->orWhereRaw('LOWER(name) LIKE ?', ['%cost%']);
                });
            });
        });
    }

    public function isExpenseAccount()
    {
        $accountType = $this->relationLoaded('account_type')
            ? $this->account_type
            : $this->account_type()->with('parent_account')->first();

        if (empty($accountType)) {
            return false;
        }

        return $accountType->name === 'Expenses'
            || (! empty($accountType->parent_account) && $accountType->parent_account->name === 'Expenses');
    }

    public function isProfitAndLossAccount()
    {
        $accountType = $this->relationLoaded('account_type')
            ? $this->account_type
            : $this->account_type()->with('parent_account')->first();

        if (empty($accountType)) {
            return false;
        }

        $typeLabel = ! empty($accountType->parent_account)
            ? $accountType->parent_account->name
            : $accountType->name;

        return in_array(self::majorTypeFromLabel($typeLabel), ['income', 'expense', 'cost'], true);
    }

    public function isDebitNormalAccount()
    {
        $accountType = $this->relationLoaded('account_type')
            ? $this->account_type
            : $this->account_type()->with('parent_account')->first();

        if (empty($accountType)) {
            return false;
        }

        $typeLabel = ! empty($accountType->parent_account)
            ? $accountType->parent_account->name
            : $accountType->name;

        return self::isDebitNormalTypeLabel($typeLabel);
    }

    public static function isDebitNormalTypeLabel($accountTypeLabel)
    {
        return in_array(self::majorTypeFromLabel($accountTypeLabel), ['asset', 'expense', 'cost'], true);
    }

    public static function majorTypeFromLabel($accountTypeLabel)
    {
        $typeName = strtolower(trim((string) $accountTypeLabel));

        if ($typeName === '') {
            return 'unknown';
        }

        if (str_contains($typeName, 'asset')) {
            return 'asset';
        }

        if (str_contains($typeName, 'liabilit')) {
            return 'liability';
        }

        if (str_contains($typeName, 'equity') || str_contains($typeName, 'capital')) {
            return 'equity';
        }

        if (str_contains($typeName, 'income') || str_contains($typeName, 'revenue')) {
            return 'income';
        }

        if (str_contains($typeName, 'cost of goods') || $typeName === 'cogs' || str_contains($typeName, 'cost')) {
            return 'cost';
        }

        if (str_contains($typeName, 'expense')) {
            return 'expense';
        }

        return 'unknown';
    }

    public static function isBalanceSheetTypeLabel($accountTypeLabel)
    {
        return in_array(self::majorTypeFromLabel($accountTypeLabel), ['asset', 'liability', 'equity'], true);
    }

    public static function getBalanceSide($accountTypeLabel, $balance)
    {
        $balance = (float) $balance;
        if (abs($balance) < 0.00001) {
            return '';
        }

        $isDebitNormal = self::isDebitNormalTypeLabel($accountTypeLabel);

        if ($balance > 0) {
            return $isDebitNormal ? 'Dr' : 'Cr';
        }

        return $isDebitNormal ? 'Cr' : 'Dr';
    }

    public static function getDisplayBalance($balance)
    {
        return abs((float) $balance);
    }

    public static function expenseBalanceExpression($typeColumn = 'account_transactions.type', $amountColumn = 'account_transactions.amount', $subTypeColumn = 'account_transactions.sub_type')
    {
        return "COALESCE(SUM(CASE WHEN {$subTypeColumn} IN ('fund_transfer', 'deposit') THEN 0 WHEN {$typeColumn}='debit' THEN {$amountColumn} WHEN {$typeColumn}='credit' THEN -1 * {$amountColumn} ELSE 0 END), 0)";
    }

    public static function debitNormalAccountCondition($accountTypeColumn)
    {
        return "{$accountTypeColumn} LIKE '%asset%' OR {$accountTypeColumn} LIKE '%expense%' OR {$accountTypeColumn} LIKE '%cost%'";
    }

    public static function profitAndLossAccountCondition($accountTypeColumn)
    {
        return "{$accountTypeColumn} LIKE '%expense%' OR {$accountTypeColumn} LIKE '%income%' OR {$accountTypeColumn} LIKE '%revenue%' OR {$accountTypeColumn} LIKE '%cost%'";
    }

    public static function typeAwareBalanceExpression($accountTypeColumn, $typeColumn = 'AT.type', $amountColumn = 'AT.amount', $subTypeColumn = 'AT.sub_type')
    {
        $debit_normal_condition = self::debitNormalAccountCondition("LOWER({$accountTypeColumn})");
        $profit_and_loss_condition = self::profitAndLossAccountCondition("LOWER({$accountTypeColumn})");

        return "COALESCE(SUM(CASE WHEN ({$profit_and_loss_condition}) AND {$subTypeColumn} IN ('fund_transfer', 'deposit') THEN 0 WHEN ({$debit_normal_condition}) AND {$typeColumn}='debit' THEN {$amountColumn} WHEN ({$debit_normal_condition}) AND {$typeColumn}='credit' THEN -1 * {$amountColumn} WHEN {$typeColumn}='credit' THEN {$amountColumn} ELSE -1 * {$amountColumn} END), 0)";
    }
}
