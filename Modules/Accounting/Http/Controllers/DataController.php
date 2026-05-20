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
            array('value' => 'accounting.chart_of_accounts.index', 'label' => __('accounting::lang.view') . ' ' . __('accounting::lang.view_charts_of_accounts')),
            array('value' => 'accounting.chart_of_accounts.create', 'label' => __('accounting::lang.create') . ' ' . __('accounting::lang.view_charts_of_accounts')),
            array('value' => 'accounting.chart_of_accounts.edit', 'label' => __('accounting::lang.edit') . ' ' . __('accounting::lang.view_charts_of_accounts')),
            array('value' => 'accounting.chart_of_accounts.destroy', 'label' => __('accounting::lang.delete') . ' ' . __('accounting::lang.view_charts_of_accounts')),
            array('value' => 'accounting.journal_entries.index', 'label' => __('accounting::lang.view') . ' ' . __('accounting::lang.journal_of_entries')),
            array('value' => 'accounting.journal_entries.create', 'label' => __('accounting::lang.create') . ' ' . __('accounting::lang.journal_of_entries')),
            array('value' => 'accounting.journal_entries.edit', 'label' => __('accounting::lang.edit') . ' ' . __('accounting::lang.journal_of_entries')),
            array('value' => 'accounting.journal_entries.reverse', 'label' => __('accounting::general.reverse') . ' ' . __('accounting::lang.journal_of_entries')),
            array('value' => 'accounting.reports.balance_sheet', 'label' => __('accounting::lang.view') . ' ' . __('accounting::general.balance_sheet')),
            array('value' => 'accounting.reports.trial_balance', 'label' => __('accounting::lang.view') . ' ' . __('accounting::general.trial_balance')),
            array('value' => 'accounting.reports.income_statement', 'label' => __('accounting::lang.view') . ' ' . __('accounting::general.income_statement')),
            array('value' => 'accounting.reports.ledger', 'label' => __('accounting::lang.view') . ' ' . trans_choice('accounting::general.ledger', 1))
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
                            action([\Modules\Accounting\Http\Controllers\DashboardController::class, 'index']),
                            __('home.dashboard'),
                            ['icon' => '', 'active' => request()->segment(1) == 'accounting' && request()->segment(2) == 'dashboard']
                        );

                        // Bank Reconciliation (legacy – conditional)
                        if (auth()->user()->can('account.access') && in_array('account', session('business.enabled_modules', []))) {
                            $sub->url(
                                action([\App\Http\Controllers\AccountReportsController::class, 'showBankReconciliation']),
                                __('account.bank_reconciliation'),
                                ['icon' => '', 'active' => request()->segment(1) == 'account' && request()->segment(2) == 'bank-reconciliation']
                            );
                        }

                        $sub->url(
                               action([\Modules\Accounting\Http\Controllers\BudgetController::class, 'index']),
                            trans('accounting::general.budgeting'),
                            ['icon' => '', 'active' => request()->segment(1) == 'accounting' && request()->segment(2) == 'budget']
                        );

                        $sub->url(
                            action([\Modules\Accounting\Http\Controllers\ChartOfAccountController::class, 'index']),
                            __('accounting::lang.view_charts_of_accounts'),
                            ['icon' => '', 'active' => request()->segment(1) == 'accounting' && request()->segment(2) == 'chart_of_account']
                        );

                        // GL items (legacy – conditional)
                        if (auth()->user()->can('account.access') && in_array('account', session('business.enabled_modules', []))) {
                            $sub->url(
                                action([\App\Http\Controllers\AccountReportsController::class, 'chartOfAccounts']),
                                __('account.general_ledger') . ' ' . trans_choice('accounting::lang.detail', 2),
                                ['icon' => '', 'active' => request()->segment(1) == 'account' && request()->segment(2) == 'chart-of-accounts']
                            );

                            $sub->url(
                                action([\App\Http\Controllers\AccountController::class, 'index']),
                                __('account.general_ledger') . ' ' . __('accounting::lang.settings'),
                                ['icon' => '', 'active' => request()->segment(1) == 'account' && request()->segment(2) == 'account']
                            );
                        }

                        $sub->url(
                            action([\Modules\Accounting\Http\Controllers\JournalEntryController::class, 'index']),
                            __('accounting::lang.journal_of_entries'),
                            ['icon' => '', 'active' => request()->segment(1) == 'accounting' && request()->segment(2) == 'journal_entry']
                        );

                        $sub->url(
                            action([\Modules\Accounting\Http\Controllers\ReconcileController::class, 'index']),
                            __('accounting::lang.reconcile'),
                            ['icon' => '', 'active' => request()->segment(1) == 'accounting' && request()->segment(2) == 'reconcile']
                        );

                        $sub->url(
                            action([\Modules\Accounting\Http\Controllers\ReportController::class, 'index']),
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
