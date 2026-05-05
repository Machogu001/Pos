<?php

namespace Modules\Accounting\Http\Controllers;

use App\Utils\ModuleUtil;
use Illuminate\Routing\Controller;
use Menu;

class DataController extends Controller
{

    /**
     * Defines user permissions for the module.
     * @return array
     */
    public function user_permissions()
    {
        return array(
            array('value' => 'accounting.chart_of_accounts.index', 'label' => 'View Chart of accounts'),
            array('value' => 'accounting.chart_of_accounts.create', 'label' => 'Create Chart of accounts'),
            array('value' => 'accounting.chart_of_accounts.edit', 'label' => 'Edit Chart of accounts'),
            array('value' => 'accounting.chart_of_accounts.destroy', 'label' => 'Delete Chart of accounts'),
            array('value' => 'accounting.journal_entries.index', 'label' => 'View Journal Entries'),
            array('value' => 'accounting.journal_entries.create', 'label' => 'Create Journal Entries'),
            array('value' => 'accounting.journal_entries.edit', 'label' => 'Edit Journal Entries'),
            array('value' => 'accounting.journal_entries.reverse', 'label' => 'Reverse Journal Entries'),
            array('value' => 'accounting.reports.balance_sheet', 'label' => 'View Balance Sheet'),
            array('value' => 'accounting.reports.trial_balance', 'label' => 'View Trial Balance'),
            array('value' => 'accounting.reports.income_statement', 'label' => 'View Income Statement'),
            array('value' => 'accounting.reports.ledger', 'label' => 'View Ledger')
        );
    }

    public function superadmin_package()
    {
        return [
            [
                'name' => 'accounting_module',
                'label' => __('accounting::lang.accounting'),
                'default' => false
            ]
        ];
    }

    /**
     * Adds Accounting menus
     * @return null
     */
    public function modifyAdminMenu()
    {
        $business_id = session()->get('user.business_id');
        $module_util = new ModuleUtil();
        $module_names = get_module_names();
        $is_accounting_enabled = (bool)$module_util->hasThePermissionInSubscription($business_id, $module_names->accounting);
        $enabled_modules = !empty(session('business.enabled_modules')) ? session('business.enabled_modules') : [];

        $is_admin = (new \App\Utils\Util())->is_admin(auth()->user(), $business_id);
        $user_can_access_accounting = $is_admin || auth()->user()->can('superadmin') ||
            auth()->user()->getAllPermissions()->pluck('name')->filter(fn($p) => str_starts_with($p, 'accounting.'))->isNotEmpty();

        if ($is_accounting_enabled && $user_can_access_accounting) {
            Menu::modify('admin-sidebar-menu', function ($menu) use ($enabled_modules) {
                $menu->dropdown(
                    __('accounting::lang.accounting'),
                    function ($sub) {
                        // Alphabetical order
                        $sub->url(
                            action('\Modules\Accounting\Http\Controllers\DashboardController@index'),
                            __('accounting::lang.accounting'),
                            ['icon' => '', 'active' => request()->segment(1) == 'accounting' && request()->segment(2) == 'dashboard']
                        );

                        // Bank Reconciliation (legacy – conditional)
                        if (auth()->user()->can('account.access') && in_array('account', session('business.enabled_modules', []))) {
                            $sub->url(
                                action([\App\Http\Controllers\AccountReportsController::class, 'showBankReconciliation']),
                                __('account.bank_reconciliation') ?? 'Bank Reconciliation',
                                ['icon' => '', 'active' => request()->segment(1) == 'account' && request()->segment(2) == 'bank-reconciliation']
                            );
                        }

                        $sub->url(
                               action('\Modules\Accounting\Http\Controllers\BudgetController@index'),
                            trans('accounting::general.budgeting'),
                            ['icon' => '', 'active' => request()->segment(1) == 'accounting' && request()->segment(2) == 'budget']
                        );

                        $sub->url(
                            action('\Modules\Accounting\Http\Controllers\ChartOfAccountController@index'),
                            __('accounting::lang.view_charts_of_accounts'),
                            ['icon' => '', 'active' => request()->segment(1) == 'accounting' && request()->segment(2) == 'chart_of_account']
                        );

                        // GL items (legacy – conditional)
                        if (auth()->user()->can('account.access') && in_array('account', session('business.enabled_modules', []))) {
                            $sub->url(
                                action([\App\Http\Controllers\AccountReportsController::class, 'chartOfAccounts']),
                                'GL (General Ledger) Detail',
                                ['icon' => '', 'active' => request()->segment(1) == 'account' && request()->segment(2) == 'chart-of-accounts']
                            );

                            $sub->url(
                                action([\App\Http\Controllers\AccountController::class, 'index']),
                                'GL (General Ledger) Setup',
                                ['icon' => '', 'active' => request()->segment(1) == 'account' && request()->segment(2) == 'account']
                            );
                        }

                        $sub->url(
                            action('\Modules\Accounting\Http\Controllers\JournalEntryController@index'),
                            __('accounting::lang.journal_of_entries'),
                            ['icon' => '', 'active' => request()->segment(1) == 'accounting' && request()->segment(2) == 'journal_entry']
                        );

                        $sub->url(
                            action('\Modules\Accounting\Http\Controllers\ReconcileController@index'),
                            __('accounting::lang.reconcile'),
                            ['icon' => '', 'active' => request()->segment(1) == 'accounting' && request()->segment(2) == 'reconcile']
                        );

                        $sub->url(
                            action('\Modules\Accounting\Http\Controllers\ReportController@index'),
                            __('accounting::lang.reports'),
                            ['icon' => '', 'active' => request()->segment(1) == 'report' && request()->segment(2) == 'accounting']
                        );

                        $sub->url(
                               url('accounting/settings/account_subtypes'),
                            __('accounting::lang.settings'),
                            ['icon' => '', 'active' => request()->segment(1) == 'accounting' && request()->segment(2) == 'settings']
                        );

                        $sub->url(
                               url('accounting/transactions/sales?type=payment'),
                            __('accounting::lang.transactions'),
                            ['icon' => '', 'active' => request()->segment(1) == 'accounting' && request()->segment(2) == 'transactions']
                        );

                        $sub->url(
                               url('accounting/transfers'),
                            trans_choice('accounting::lang.transfer', 2),
                            ['icon' => '', 'active' => request()->segment(1) == 'accounting' && request()->segment(2) == 'transfers']
                        );
                    },
                    [
                        'icon' => 'fa fas fa-book',
                        'id' => 'tour_step14',
                        'active' => request()->segment(1) == 'accounting'
                            || (request()->segment(1) == 'report' && request()->segment(2) == 'accounting')
                            || request()->segment(1) == 'account'
                    ]
                )->order(24);
            });
        }
    }
}
