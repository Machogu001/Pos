@extends('layouts.app')
@section('title', __('account.finance_dashboard'))

@section('content')
<section class="content-header">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">@lang('account.finance_dashboard')</h1>
    <div class="no-print" style="margin-top: 10px;">
        <button type="button" class="tw-dw-btn tw-dw-btn-primary tw-text-white" onclick="window.print();">
            <i class="fa fa-print"></i> @lang('messages.print')
        </button>
    </div>
</section>


@push('styles')
<style>
    .finance-dashboard .small-box {
        min-height: 145px;
        overflow: hidden;
    }

    .finance-dashboard .small-box .inner {
        padding-right: 88px;
    }

    .finance-dashboard .small-box h3 {
        font-size: clamp(15px, 1.2vw, 24px);
        line-height: 1;
        margin-bottom: 4px;
        word-break: break-all;
        white-space: normal;
        overflow-wrap: anywhere;
    }

    .finance-dashboard .small-box p {
        font-size: clamp(10px, 0.95vw, 14px);
        line-height: 1.25;
        min-height: 2.4em;
        overflow-wrap: anywhere;
    }

    .finance-dashboard .small-box .icon {
        font-size: 44px;
        top: 18px;
        right: 16px;
    }

    .finance-dashboard .small-box .small-box-footer {
        white-space: normal;
        line-height: 1.35;
        padding: 10px 12px;
    }

    .finance-dashboard .summary-alert {
        display: flex;
        align-items: flex-start;
        gap: 8px;
        flex-wrap: wrap;
        min-height: 72px;
        margin-bottom: 0;
    }

    .finance-dashboard .summary-alert strong {
        flex: 0 0 auto;
    }

    .finance-dashboard .report-title {
        word-break: break-word;
        line-height: 1.25;
    }

    .finance-dashboard .action-list .btn {
        white-space: normal;
        text-align: left;
        padding-top: 10px;
        padding-bottom: 10px;
    }

    .finance-dashboard-print {
        display: block;
    }

    @media print {
        html, body {
            height: auto !important;
            overflow: visible !important;
            background: #fff !important;
        }

        main {
            display: block !important;
            height: auto !important;
        }

        #scrollable-container {
            height: auto !important;
            overflow: visible !important;
        }

        .no-print {
            display: none !important;
        }

        .finance-dashboard-print {
            display: block !important;
        }

        .finance-dashboard .small-box,
        .finance-dashboard .widget,
        .finance-dashboard .box,
        .finance-dashboard .card {
            break-inside: avoid;
            page-break-inside: avoid;
        }
    }

    @media (max-width: 1450px) and (min-width: 992px) {
        .finance-dashboard .small-box {
            min-height: 155px;
        }

        .finance-dashboard .small-box .inner {
            padding-right: 72px;
        }

        .finance-dashboard .small-box h3 {
            font-size: 15px;
            line-height: 1;
            margin-bottom: 3px;
        }

        .finance-dashboard .small-box p {
            font-size: 10px;
            line-height: 1.2;
            min-height: 2.6em;
        }

        .finance-dashboard .small-box .icon {
            font-size: 34px;
            top: 16px;
            right: 12px;
        }

        .finance-dashboard .small-box .small-box-footer {
            padding: 9px 10px;
            font-size: 12px;
        }

        .finance-dashboard .summary-alert {
            min-height: 80px;
        }
    }

    @media (max-width: 767px) {
        .finance-dashboard .small-box {
            min-height: 132px;
        }

        .finance-dashboard .small-box .inner {
            padding-right: 76px;
            padding-top: 10px;
            padding-bottom: 10px;
        }

        .finance-dashboard .small-box .icon {
            font-size: 36px;
            top: 14px;
            right: 12px;
        }

        .finance-dashboard .summary-alert {
            min-height: auto;
        }

        .finance-dashboard .action-list .btn {
            width: 100%;
        }
    }
</style>
@endpush
<section class="content finance-dashboard-print">
    <div class="row">
        <div class="col-md-12">
            @component('components.widget')
                <p class="text-muted">{{ __('account.finance_dashboard_help') }}</p>
                <div class="row">
                    <div class="col-md-3 col-sm-6">
                        <div class="small-box bg-aqua">
                            <div class="inner">
                                <h3>{{ number_format($total_accounts) }}</h3>
                                <p>@lang('account.accounts')</p>
                            </div>
                            <div class="icon"><i class="fa fa-book"></i></div>
                            <a href="{{ action([\App\Http\Controllers\AccountController::class, 'index']) }}" class="small-box-footer">
                                Open <i class="fa fa-arrow-circle-right"></i>
                            </a>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <div class="small-box bg-green">
                            <div class="inner">
                                <h3>{{ number_format($active_accounts) }}</h3>
                                <p>@lang('business.is_active')</p>
                            </div>
                            <div class="icon"><i class="fa fa-check-circle"></i></div>
                            <a href="{{ action([\App\Http\Controllers\AccountReportsController::class, 'chartOfAccounts']) }}" class="small-box-footer">
                                @lang('account.chart_of_accounts') <i class="fa fa-arrow-circle-right"></i>
                            </a>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <div class="small-box bg-red">
                            <div class="inner">
                                <h3>{{ number_format($closed_accounts) }}</h3>
                                <p>@lang('account.closed')</p>
                            </div>
                            <div class="icon"><i class="fa fa-lock"></i></div>
                            <a href="{{ action([\App\Http\Controllers\AccountReportsController::class, 'balanceSheet']) }}" class="small-box-footer">
                                @lang('account.balance_sheet') <i class="fa fa-arrow-circle-right"></i>
                            </a>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <div class="small-box bg-yellow">
                            <div class="inner">
                                <h3>
                                    @format_currency(abs($retained_earnings))
                                    @if (abs($retained_earnings) > 0.00001)
                                        <small style="color: rgba(0, 0, 0, 0.65); font-weight: 700;">{{ $retained_earnings >= 0 ? 'Cr' : 'Dr' }}</small>
                                    @endif
                                </h3>
                                <p>@lang('account.retained_earnings')</p>
                            </div>
                            <div class="icon"><i class="fa fa-balance-scale"></i></div>
                            <a href="{{ action([\App\Http\Controllers\AccountReportsController::class, 'balanceSheet']) }}" class="small-box-footer">
                                @lang('account.balance_sheet') <i class="fa fa-arrow-circle-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
                <div class="row" style="margin-top: 15px;">
                    <div class="col-md-4 col-sm-6">
                        @if ($not_linked_payments > 0)
                            <div class="alert alert-warning">
                                <strong>{{ number_format($not_linked_payments) }}</strong> {{ __('account.payments_not_linked_with_account', ['payments' => number_format($not_linked_payments)]) }}
                            </div>
                        @else
                            <div class="alert alert-success">
                                <strong>0</strong> {{ __('account.all_payments_linked_with_accounts') }}
                            </div>
                        @endif
                    </div>
                    <div class="col-md-4 col-sm-6">
                        <div class="alert alert-info">
                            <strong>{{ number_format($backfill_missing_count) }}</strong> {{ __('account.backfill_default_accounts') }}
                        </div>
                    </div>
                    <div class="col-md-4 col-sm-6">
                        <div class="alert alert-success">
                            <strong>{{ __('account.chart_of_accounts') }}</strong> {{ __('account.chart_of_accounts_help') }}
                        </div>
                    </div>
                </div>
            @endcomponent
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            @component('components.widget')
                <h4 class="tw-font-semibold tw-mb-3">{{ __('report.profit_loss') }} - {{ $fy['start'] }} {{ __('lang_v1.to') }} {{ $fy['end'] }}</h4>
                <div class="row">
                    <div class="col-md-3 col-sm-6">
                        <div class="small-box bg-teal">
                            <div class="inner">
                                <h3>@format_currency($profit_loss['gross_profit'])</h3>
                                <p>@lang('lang_v1.gross_profit')</p>
                            </div>
                            <div class="icon"><i class="fa fa-line-chart"></i></div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <div class="small-box bg-purple">
                            <div class="inner">
                                <h3>@format_currency($profit_loss['net_profit'])</h3>
                                <p>@lang('report.net_profit')</p>
                            </div>
                            <div class="icon"><i class="fa fa-area-chart"></i></div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <div class="small-box" style="background: linear-gradient(135deg, #0f172a 0%, #2563eb 100%); color: #fff;">
                            <div class="inner" style="padding-right: 90px;">
                                <h3 style="font-weight: 800; letter-spacing: .2px; color: #fff;">@format_currency($profit_loss['total_sell'])</h3>
                                <p style="font-weight: 600; color: #fff;">@lang('report.total_sell')</p>
                            </div>
                            <div class="icon" style="color: rgba(255, 255, 255, 0.18); font-size: 44px; top: 22px; right: 18px;"><i class="fa fa-shopping-cart"></i></div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <div class="small-box bg-orange">
                            <div class="inner">
                                <h3>@format_currency($profit_loss['total_expense'])</h3>
                                <p>@lang('report.total_expense')</p>
                            </div>
                            <div class="icon"><i class="fa fa-money"></i></div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <div class="small-box" style="background: linear-gradient(135deg, #7c2d12 0%, #ea580c 100%); color: #fff;">
                            <div class="inner">
                                <h3 style="color: #fff;">@format_currency($profit_loss['cogs'])</h3>
                                <p style="color: #fff;">@lang('lang_v1.cogs')</p>
                            </div>
                            <div class="icon" style="color: rgba(255,255,255,.18);"><i class="fa fa-cubes"></i></div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <div class="small-box" style="background: linear-gradient(135deg, #14532d 0%, #16a34a 100%); color: #fff;">
                            <div class="inner">
                                <h3 style="color: #fff;">@format_currency($profit_loss['total_adjustment_effect'])</h3>
                                <p style="color: #fff;">Stock Adjustment Effect</p>
                            </div>
                            <div class="icon" style="color: rgba(255,255,255,.18);"><i class="fa fa-exchange"></i></div>
                        </div>
                    </div>
                </div>
            @endcomponent
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            @component('components.widget')
                <h4 class="tw-font-semibold tw-mb-3">@lang('account.finance_dashboard')</h4>
                <div class="table-responsive">
                    <table class="table table-striped table-bordered">
                        <thead>
                            <tr>
                                <th>@lang('lang_v1.name')</th>
                                <th>@lang('lang_v1.account_type')</th>
                                <th>@lang('account.account_number')</th>
                                <th>@lang('lang_v1.balance')</th>
                                <th>@lang('messages.action')</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($recent_accounts as $account)
                                <tr>
                                    <td>
                                        {{ $account->name }}
                                        @if ($account->is_closed)
                                            <span class="label label-danger" style="margin-left: 6px;">@lang('account.closed')</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if (!empty($account->parent_account_type_name))
                                            {{ $account->parent_account_type_name }} -
                                        @endif
                                        {{ $account->account_type_name }}
                                    </td>
                                    <td>{{ $account->account_number }}</td>
                                    <td>
                                        @format_currency($account->display_balance)
                                        @if (!empty($account->balance_side))
                                            <small class="text-muted">{{ $account->balance_side }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ action([\App\Http\Controllers\AccountController::class, 'show'], [$account->id]) }}" class="tw-dw-btn tw-dw-btn-xs tw-dw-btn-outline tw-dw-btn-warning">
                                            <i class="fa fa-book"></i> @lang('account.account_book')
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endcomponent
        </div>

        <div class="col-md-4">
            @component('components.widget')
                <h4 class="tw-font-semibold tw-mb-3">@lang('messages.actions')</h4>
                <div class="tw-flex tw-flex-col tw-gap-2">
                    <a class="btn btn-primary btn-block" href="{{ action([\App\Http\Controllers\AccountController::class, 'index']) }}">@lang('account.list_accounts')</a>
                    <a class="btn btn-default btn-block" href="{{ action([\App\Http\Controllers\AccountReportsController::class, 'chartOfAccounts']) }}">@lang('account.chart_of_accounts')</a>
                    <a class="btn btn-default btn-block" href="{{ action([\App\Http\Controllers\AccountReportsController::class, 'generalLedger']) }}">@lang('account.general_ledger')</a>
                    <a class="btn btn-default btn-block" href="{{ action([\App\Http\Controllers\AccountReportsController::class, 'journalEntry']) }}">@lang('account.journal_entry')</a>
                    <a class="btn btn-default btn-block" href="{{ action([\App\Http\Controllers\AccountReportsController::class, 'balanceSheet']) }}">@lang('account.balance_sheet')</a>
                    <a class="btn btn-default btn-block" href="{{ action([\App\Http\Controllers\AccountReportsController::class, 'trialBalance']) }}">@lang('account.trial_balance')</a>
                    <a class="btn btn-default btn-block" href="{{ action([\App\Http\Controllers\ReportController::class, 'getProfitLoss']) }}">@lang('report.profit_loss')</a>
                    <a class="btn btn-default btn-block" href="{{ action([\App\Http\Controllers\AccountController::class, 'cashFlow']) }}">@lang('lang_v1.cash_flow')</a>
                    <a class="btn btn-default btn-block" href="{{ action([\App\Http\Controllers\AccountReportsController::class, 'paymentAccountReport']) }}">@lang('account.chart_of_accounts_report')</a>
                    <a class="btn btn-default btn-block" href="{{ action([\App\Http\Controllers\AccountReportsController::class, 'showBankReconciliation']) }}">@lang('account.bank_reconciliation')</a>
                </div>
            @endcomponent
        </div>
    </div>
</section>
@endsection
