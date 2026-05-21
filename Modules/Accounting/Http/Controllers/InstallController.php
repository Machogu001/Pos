<?php

namespace Modules\Accounting\Http\Controllers;

use App\Account;
use App\AccountTransaction;
use App\Business;
use App\Events\ExpenseCreatedOrModified;
use App\Events\PurchaseCreatedOrModified;
use App\Events\SellCreatedOrModified;
use App\Events\StockAdjustmentCreatedOrModified;
use App\System;
use App\Transaction;
use App\TransactionPayment;
use App\Utils\BusinessUtil;
use Composer\Semver\Comparator;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Modules\Accounting\Entities\AccountDetailType;
use Modules\Accounting\Entities\AccountSubtype;
use Modules\Accounting\Entities\ChartOfAccount;

class InstallController extends Controller
{
    public function __construct()
    {
        $this->module_name = 'accounting';
        $this->appVersion = config('accounting.module_version');
    }

    /**
     * Display a listing of the resource.
     * @return Response
     */

    public function index()
    {
        if (!auth()->user()->can('superadmin')) {
            abort(403, 'Unauthorized action.');
        }

        ini_set('max_execution_time', 0);
        ini_set('memory_limit', '512M');

        $this->installSettings();

        //Check if installed or not.
        $is_installed = System::getProperty($this->module_name . '_version');
        if (!empty($is_installed)) {
            abort(404);
        }

        $action_url = url('accounting/install');

        return view('install.install-module')
            ->with(compact('action_url'));
    }

    /**
     * Initialize all install functions
     */
    private function installSettings()
    {
        config(['app.debug' => true]);
        Artisan::call('config:clear');
    }

    /**
     * Installing Accounting Module
     */
    public function install()
    {
        try {
            $backfillWarning = null;
            DB::beginTransaction();

            $is_installed = System::getProperty($this->module_name . '_version');
            if (!empty($is_installed)) {
                abort(404);
            }

            DB::statement('SET default_storage_engine=INNODB;');
            Artisan::call('module:migrate', ['module' => "Accounting", '--force' => true]);
            $this->publishAssetsNoFail('Accounting');
            System::addProperty($this->module_name . '_version', $this->appVersion);

            DB::commit();

            try {
                $this->backfillAccountingIntegrations();
            } catch (\Throwable $backfillException) {
                \Log::warning('Accounting install backfill warning: '.$backfillException->getMessage());
                $backfillWarning = ' Historical sells, purchases and payments backfill encountered an issue. Please run accounting update again.';
            }
            
            $output = ['success' => 1,
                    'msg' => 'Accounting module installed succesfully'.$backfillWarning
                ];
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());

            $output = [
                'success' => false,
                'msg' => $e->getMessage()
            ];
        }

        return redirect()
                ->action('\App\Http\Controllers\Install\ModulesController@index')
                ->with('status', $output);
    }

    /**
     * Uninstall
     * @return Response
     */
    public function uninstall()
    {
        if (!auth()->user()->can('superadmin')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            System::removeProperty($this->module_name . '_version');

            $output = ['success' => true,
                            'msg' => __("lang_v1.success")
                        ];
        } catch (\Exception $e) {
            $output = ['success' => false,
                        'msg' => $e->getMessage()
                    ];
        }

        return redirect()->back()->with(['status' => $output]);
    }

    /**
     * update module
     * @return Response
     */
    public function update()
    {
        //Check if accounting_version is same as appVersion then 404
        //If appVersion > accounting_version - run update script.
        //Else there is some problem.
        if (!auth()->user()->can('superadmin')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $backfillWarning = null;
            DB::beginTransaction();
            ini_set('max_execution_time', 0);
            ini_set('memory_limit', '512M');

            $accounting_version = System::getProperty($this->module_name . '_version');

            if (Comparator::greaterThan($this->appVersion, $accounting_version)) {
                ini_set('max_execution_time', 0);
                ini_set('memory_limit', '512M');
                $this->installSettings();
                
                DB::statement('SET default_storage_engine=INNODB;');
                Artisan::call('module:migrate', ['module' => "Accounting", '--force' => true]);
                $this->publishAssetsNoFail('Accounting');
                System::setProperty($this->module_name . '_version', $this->appVersion);
            } else {
                abort(404);
            }

            DB::commit();

            try {
                $this->backfillAccountingIntegrations();
            } catch (\Throwable $backfillException) {
                \Log::warning('Accounting update backfill warning: '.$backfillException->getMessage());
                $backfillWarning = ' Historical sells, purchases and payments backfill encountered an issue. Please run accounting update again.';
            }
            
            $output = ['success' => 1,
                        'msg' => 'Accounting module updated Succesfully to version ' . $this->appVersion . ' !!'.$backfillWarning
                    ];

            return redirect()->back()->with(['status' => $output]);
        } catch (Exception $e) {
            DB::rollBack();
            die($e->getMessage());
        }
    }

    private function publishAssetsNoFail(string $moduleName): void
    {
        $sourcePath = base_path('Modules/'.$moduleName.'/Resources/assets');
        if (! File::isDirectory($sourcePath)) {
            return;
        }

        $targetPath = public_path('modules/'.strtolower($moduleName));
        if (! File::exists($targetPath)) {
            File::ensureDirectoryExists($targetPath);
        }

        if (! is_writable($targetPath)) {
            return;
        }

        foreach (File::allFiles($sourcePath) as $file) {
            $relative = ltrim(str_replace($sourcePath, '', $file->getPathname()), DIRECTORY_SEPARATOR);
            $destination = $targetPath.DIRECTORY_SEPARATOR.$relative;
            File::ensureDirectoryExists(dirname($destination));

            try {
                File::copy($file->getPathname(), $destination);
            } catch (\Throwable $e) {
                // Non-fatal for web installer flow.
            }
        }
    }

    private function backfillAccountingIntegrations(): void
    {
        ini_set('max_execution_time', 0);
        ini_set('memory_limit', '512M');

        $this->provisionAccountingMappingsAndCharts();

        Transaction::where('type', 'sell')
            ->where('status', 'final')
            ->orderBy('id')
            ->chunkById(200, function ($transactions) {
                foreach ($transactions as $transaction) {
                    event(new SellCreatedOrModified($transaction));
                }
            });

        Transaction::where('type', 'purchase')
            ->orderBy('id')
            ->chunkById(200, function ($transactions) {
                foreach ($transactions as $transaction) {
                    event(new PurchaseCreatedOrModified($transaction));
                }
            });

        Transaction::whereIn('type', ['expense', 'expense_refund'])
            ->orderBy('id')
            ->chunkById(200, function ($transactions) {
                foreach ($transactions as $transaction) {
                    event(new ExpenseCreatedOrModified($transaction));
                }
            });

        Transaction::where('type', 'stock_adjustment')
            ->orderBy('id')
            ->chunkById(200, function ($transactions) {
                foreach ($transactions as $transaction) {
                    event(new StockAdjustmentCreatedOrModified($transaction, 'added'));
                }
            });

        TransactionPayment::with('transaction:id,type')
            ->where('method', '!=', 'advance')
            ->orderBy('id')
            ->chunkById(500, function ($payments) {
                foreach ($payments as $payment) {
                    $transactionType = optional($payment->transaction)->type;
                    AccountTransaction::syncPaymentAccountTransactions($payment, $transactionType, $payment->account_id);
                }
            });
    }

    private function provisionAccountingMappingsAndCharts(): void
    {
        $businessUtil = app(BusinessUtil::class);

        Business::select('id', 'owner_id', 'currency_id', 'common_settings')
            ->orderBy('id')
            ->chunkById(50, function ($businesses) use ($businessUtil) {
                foreach ($businesses as $business) {
                    try {
                        $businessUtil->provisionDefaultAccountMappings($business->id, $business->owner_id ?: 1);
                        $this->ensureMappedAccountsExistAsChartAccounts($business);
                    } catch (\Throwable $e) {
                        \Log::warning('Accounting install mapping bootstrap warning for business '.$business->id.': '.$e->getMessage());
                    }
                }
            });
    }

    private function ensureMappedAccountsExistAsChartAccounts(Business $business): void
    {
        $business = Business::select('id', 'currency_id', 'common_settings')->find($business->id);
        if (empty($business)) {
            return;
        }

        $typeMappings = ! empty($business->common_settings['default_account_mappings'])
            && is_array($business->common_settings['default_account_mappings'])
            ? $business->common_settings['default_account_mappings']
            : [];

        if (empty($typeMappings)) {
            return;
        }

        foreach ($typeMappings as $mappingKey => $accountId) {
            if (empty($accountId)) {
                continue;
            }

            $account = Account::with('account_type.parent_account')
                ->where('business_id', $business->id)
                ->find($accountId);

            if (empty($account)) {
                continue;
            }

            $glCode = (int) $account->account_number;
            if ($glCode <= 0) {
                continue;
            }

            $existingChart = ChartOfAccount::where('business_id', $business->id)
                ->where('gl_code', $glCode)
                ->first();

            if (! empty($existingChart)) {
                continue;
            }

            ChartOfAccount::create([
                'business_id' => $business->id,
                'name' => $account->name,
                'gl_code' => $glCode,
                'account_type' => $this->resolveChartAccountType($account),
                'allow_manual' => 1,
                'active' => 1,
                'notes' => 'Auto-generated from '.$mappingKey.' mapping during accounting installation',
                'currency_id' => (int) ($business->currency_id ?: 133),
                'payment_type_id' => 1,
                'account_subtype_id' => $this->resolveChartAccountSubtypeId($business->id, $mappingKey, $account),
                'detail_type_id' => $this->resolveChartAccountDetailTypeId($business->id, $mappingKey, $account),
            ]);
        }
    }

    private function resolveChartAccountType(Account $account): string
    {
        $accountTypeLabel = optional(optional($account->account_type)->parent_account)->name
            ?: optional($account->account_type)->name
            ?: '';

        $majorType = Account::majorTypeFromLabel($accountTypeLabel);

        return [
            'asset' => 'asset',
            'liability' => 'liability',
            'equity' => 'equity',
            'income' => 'income',
            'expense' => 'expense',
            'cost' => 'expense',
        ][$majorType] ?? 'asset';
    }

    private function resolveChartAccountSubtypeId(int $businessId, string $mappingKey, Account $account): ?int
    {
        $chartAccountType = $this->resolveChartAccountType($account);

        $subtypeCandidates = [
            'payment' => ['Cash and Cash Equivalents (CCE)', 'Current Assets'],
            'inventory' => ['Current Assets'],
            'accounts_receivable' => ['Accounts Receivable(A/R)', 'Current Assets'],
            'purchase' => ['Accounts Payable (A/P)', 'Current Liabilities'],
            'purchase_tax' => ['Current Assets'],
            'sales_tax' => ['Current Liabilities'],
            'opening_stock_equity' => ['Owner\'s Equity'],
            'sell' => ['Income'],
            'inventory_gain' => ['Other Income', 'Income'],
            'expense' => ['Expense'],
            'payroll' => ['Expense'],
            'cogs' => ['Cost of Sales', 'Expense'],
            'inventory_loss' => ['Other Expense', 'Expense'],
        ];

        foreach ($subtypeCandidates[$mappingKey] ?? [] as $candidateName) {
            $subtype = AccountSubtype::whereIn('business_id', [0, $businessId])
                ->where('account_type', $chartAccountType)
                ->whereRaw('LOWER(name) = ?', [strtolower($candidateName)])
                ->orderByDesc('business_id')
                ->first();

            if (! empty($subtype)) {
                return (int) $subtype->id;
            }
        }

        $fallbackSubtype = AccountSubtype::whereIn('business_id', [0, $businessId])
            ->where('account_type', $chartAccountType)
            ->orderByDesc('business_id')
            ->orderBy('id')
            ->first();

        return ! empty($fallbackSubtype) ? (int) $fallbackSubtype->id : null;
    }

    private function resolveChartAccountDetailTypeId(int $businessId, string $mappingKey, Account $account): ?int
    {
        $subtypeId = $this->resolveChartAccountSubtypeId($businessId, $mappingKey, $account);
        if (empty($subtypeId)) {
            return null;
        }

        $detailCandidates = [
            'payment' => ['Cash on hand', 'Undeposited funds'],
            'inventory' => ['Inventory'],
            'accounts_receivable' => ['Accounts receivable (A/R)'],
            'purchase' => ['Accounts payable (A/P)'],
            'purchase_tax' => ['Other current assets'],
            'sales_tax' => ['Other current liabilities'],
            'opening_stock_equity' => ['Owner\'s equity'],
            'sell' => ['Sales of Product Income', 'Service/Fee Income', 'Other primary income'],
            'inventory_gain' => ['Other miscellaneous income'],
            'expense' => ['Office/general administrative expenses', 'Other miscellaneous service cost'],
            'payroll' => ['Payroll expenses'],
            'cogs' => ['Cost of labour - COS', 'Supplies and materials - COS'],
            'inventory_loss' => ['Other miscellaneous service cost'],
        ];

        foreach ($detailCandidates[$mappingKey] ?? [] as $candidateName) {
            $detailType = AccountDetailType::whereIn('business_id', [0, $businessId])
                ->where('account_subtype_id', $subtypeId)
                ->whereRaw('LOWER(name) = ?', [strtolower($candidateName)])
                ->orderByDesc('business_id')
                ->first();

            if (! empty($detailType)) {
                return (int) $detailType->id;
            }
        }

        $fallbackDetail = AccountDetailType::whereIn('business_id', [0, $businessId])
            ->where('account_subtype_id', $subtypeId)
            ->orderByDesc('business_id')
            ->orderBy('id')
            ->first();

        return ! empty($fallbackDetail) ? (int) $fallbackDetail->id : null;
    }
}
