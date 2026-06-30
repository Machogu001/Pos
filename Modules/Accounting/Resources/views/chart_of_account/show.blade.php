@extends('accounting::layouts.app')
@section('title')
    {{ $chart_of_account->name }}
@endsection

@section('content')

    @include('accounting::layouts.nav')
    <!-- Content Header (Page header) -->
    @component('accounting::components.section_header')
        @slot('title')
            {{ trans_choice('accounting::general.accounting', 1) }}
        @endslot
        @slot('subtitle')
            {{ trans_choice('accounting::lang.account', 1) }} {{ trans_choice('accounting::lang.detail', 2) }}
        @endslot
    @endcomponent

    <!-- Main content -->
    <section class="content no-print" id="vue-app">
        @can('product.view')
            <div class="row">

                @component('accounting::components.box')
                    @slot('title')
                        {{ $chart_of_account->name }}
                    @endslot

                    @slot('body')
                        <section class="content">
                            <div class="row">

                                <div class="col-md-6">
                                    <div class="row">

                                        <div class="col-lg-6 col-xs-6">
                                            <div class="small-box bg-green">
                                                <div class="inner">
                                                    <h4>
                                                        <strong>
                                                            <span>&nbsp;</span>
                                                            {{ number_format($chart_of_account->opening_balance, 2) }}
                                                        </strong>
                                                    </h4>
                                                    <p>
                                                        {{ trans('account.opening_balance') }}
                                                    </p>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-lg-6 col-xs-6">
                                            <div class="small-box bg-green">
                                                <div class="inner">
                                                    <h4>
                                                        <strong>
                                                            <span>&nbsp;</span>
                                                            {{ number_format((float) ($chart_of_account->ledger_current_balance ?? $chart_of_account->current_balance), 2) }}
                                                        </strong>
                                                    </h4>
                                                    <p>
                                                        {{ trans('accounting::lang.current') }}
                                                        {{ trans('accounting::lang.balance') }}
                                                    </p>
                                                </div>
                                            </div>
                                        </div>

                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="card">
                                        <div class="card-body">
                                            <table class="table">
                                                <tr>
                                                    <td>{{ trans_choice('accounting::lang.currency', 1) }}</td>
                                                    <td>{{ $chart_of_account->currency->name }}</td>
                                                </tr>
                                                <tr>
                                                    <td>{{ trans_choice('accounting::general.gl_code', 2) }}</td>
                                                    <td>{{ $chart_of_account->gl_code }}</td>
                                                </tr>
                                                <tr>
                                                    <td>{{ trans_choice('accounting::lang.parent', 1) }}
                                                        {{ trans_choice('accounting::lang.account', 1) }}</td>
                                                    <td>{{ $chart_of_account->parent->name }}</td>
                                                </tr>
                                                <tr>
                                                    <td>{{ trans_choice('accounting::lang.account', 1) }}
                                                        {{ trans_choice('accounting::lang.type', 1) }}</td>
                                                    <td>
                                                        @if ($chart_of_account->account_type == 'asset')
                                                            {{ trans_choice('accounting::general.asset', 1) }}
                                                        @elseif ($chart_of_account->account_type == 'expense')
                                                            {{ trans_choice('accounting::general.expense', 1) }}
                                                        @elseif ($chart_of_account->account_type == 'equity')
                                                            {{ trans_choice('accounting::general.equity', 1) }}
                                                        @elseif ($chart_of_account->account_type == 'liability')
                                                            {{ trans_choice('accounting::general.liability', 1) }}
                                                        @elseif ($chart_of_account->account_type == 'income')
                                                            {{ trans_choice('accounting::general.income', 1) }}
                                                        @endif
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>{{ trans_choice('accounting::general.manual_entries_allowed', 1) }}</td>
                                                    <td>
                                                        @if ($chart_of_account->allow_manual == 1)
                                                            {{ trans_choice('accounting::lang.yes', 1) }}
                                                        @else
                                                            {{ trans_choice('accounting::lang.no', 1) }}
                                                        @endif
                                                    </td>
                                                </tr>

                                                <tr>
                                                    <td>{{ trans_choice('accounting::lang.active', 1) }} </td>
                                                    <td>
                                                        @if ($chart_of_account->active == 1)
                                                            {{ trans_choice('accounting::lang.yes', 1) }}
                                                        @else
                                                            {{ trans_choice('accounting::lang.no', 1) }}
                                                        @endif
                                                    </td>
                                                </tr>

                                                <tr>
                                                    <td>{{ trans_choice('accounting::lang.note', 2) }} </td>
                                                    <td>{!! $chart_of_account->notes !!}</td>
                                                </tr>
                                            </table>

                                        </div>
                                    </div>
                                    <!-- /.box -->
                                </div>
                            </div>
                        </section>
                    @endslot
                @endcomponent

                @component('accounting::components.box')
                    @slot('title')
                        {{ trans_choice('accounting::general.journal', 1) }} {{ trans_choice('accounting::lang.entry', 2) }}
                    @endslot

                    @slot('body')
                        <section class="content">
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="card">
                                        <div class="card-body">
                                            <table id="journal_entries_table" class="table table-bordered table-hover">
                                                <thead>
                                                    <tr>
                                                        <th>{{ trans_choice('accounting::lang.date', 1) }}</th>
                                                        <th>{{ trans_choice('accounting::lang.reference', 1) }}</th>
                                                        <th>{{ trans_choice('accounting::lang.note', 1) }}</th>
                                                        <th>{{ trans_choice('accounting::general.gl_code', 1) }}</th>
                                                        <th style="text-align:right">{{ trans_choice('accounting::general.debit', 1) }}</th>
                                                        <th style="text-align:right">{{ trans_choice('accounting::general.credit', 1) }}</th>
                                                        <th style="text-align:right">{{ trans_choice('accounting::general.balance', 1) }}</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @php
                                                        $total_debit = 0;
                                                        $total_credit = 0;
                                                        $running_balance = 0;
                                                        $is_debit_normal = in_array($chart_of_account->account_type, ['asset', 'expense'], true);
                                                    @endphp
                                                    @foreach ($account_ledger_entries as $key)
                                                        @php
                                                            $total_debit = $total_debit + $key->debit;
                                                            $total_credit = $total_credit + $key->credit;
                                                            $running_balance += $is_debit_normal
                                                                ? ($key->debit - $key->credit)
                                                                : ($key->credit - $key->debit);
                                                        @endphp
                                                        <tr>
                                                            <td>{{ !empty($key->entry_date) ? \Carbon\Carbon::parse($key->entry_date)->format('d/m/Y H:i') : '' }}</td>
                                                            <td>{{ $key->reference }}</td>
                                                            <td>{{ $key->note }}</td>
                                                            <td>{{ $chart_of_account->gl_code }}</td>
                                                            <td style="text-align:right">
                                                                {{ number_format($key->debit, 2) }}
                                                            </td>
                                                            <td style="text-align:right">
                                                                {{ number_format($key->credit, 2) }}
                                                            </td>
                                                            <td style="text-align:right">
                                                                {{ number_format($running_balance, 2) }}
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                                <tfoot>
                                                    <tr>
                                                        <td colspan="4"><b>{{ trans_choice('accounting::lang.total', 1) }}</b></td>
                                                        <td style="text-align:right"><b>{{ number_format($total_debit, 2) }}</b></td>
                                                        <td style="text-align:right"><b>{{ number_format($total_credit, 2) }}</b></td>
                                                        <td style="text-align:right"><b>{{ number_format($running_balance, 2) }}</b></td>
                                                    </tr>
                                                </tfoot>
                                            </table>
                                        </div>
                                    </div>
                                    <!-- /.box -->
                                </div>
                            </div>
                        </section>
                    @endslot
                @endcomponent

            </div>
        @endcan
    </section>

@stop
@section('javascript')
    <script>
        try {
            localStorage.removeItem('DataTables_journal_entries_table_/accounting/chart_of_account');
            sessionStorage.removeItem('DataTables_journal_entries_table_/accounting/chart_of_account');
        } catch (e) {}

        $('#journal_entries_table').DataTable({
            'ordering': false,
            'destroy': true,
            'stateSave': false
        });
    </script>
@endsection
