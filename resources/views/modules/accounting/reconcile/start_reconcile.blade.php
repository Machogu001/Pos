@extends('accounting::layouts.app')
@section('title')
    {{ trans_choice('accounting::lang.reconcile', 1) }}
@endsection

@section('css')
    <link rel="stylesheet" href="{{ Module::asset('accounting:css/plugins/money-fields.css') }}">
@endsection

@section('content')

    @include('accounting::layouts.nav')
    <!-- Content Header (Page header) -->
    @component('accounting::components.section_header')
        @slot('title')
            {{ trans_choice('accounting::lang.reconcile', 1) }} {{ $chart_of_account->name }}
        @endslot
    @endcomponent

    <!-- Main content -->
    <section class="content no-print" id="vue-app">

        <form method="post" id="store_reconcile_form" action="{{ url('accounting/reconcile/store') }}">
            @csrf
            <input type="hidden" name="ending_balance" id="ending_balance" v-model="ending_balance">
            <input type="hidden" name="difference" id="difference" v-model="difference">
            <input type="hidden" name="chart_of_account_id" id="chart_of_account_id" v-model="chart_of_account_id">
        </form>

        <div class="row">
            @component('accounting::components.box')
                @slot('body')
                    @include('accounting::reconcile.partials.reconcile_form')
                    @include('accounting::reconcile.partials.undo_reconcile_form')
                @endslot
            @endcomponent
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="box box-info">
                    <div class="box-header with-border">
                        <h3 class="box-title">{{ __('account.statement_matching') }}</h3>
                    </div>
                    <div class="box-body">
                        <p class="text-muted">{{ __('account.statement_matching_help') }}</p>
                        {!! Form::open(['url' => action([\App\Http\Controllers\AccountReportsController::class, 'uploadBankReconciliation']), 'method' => 'post', 'id' => 'embedded_bank_reco_form', 'files' => true]) !!}
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        {!! Form::label('embedded_account_id', __('account.account') . ':') !!}
                                        {!! Form::select('account_id', $bank_reconciliation_accounts, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('messages.please_select')]) !!}
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        {!! Form::label('embedded_statement', __('account.statement_file') . ':') !!}
                                        {!! Form::file('statement', ['class' => 'form-control', 'accept' => '.csv,.txt,.xlsx,.xls,text/csv']) !!}
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        {!! Form::label('embedded_statement_source', __('account.statement_source') . ':') !!}
                                        {!! Form::select('statement_source', ['auto' => __('account.statement_source_auto'), 'bank' => __('account.statement_source_bank'), 'mpesa' => __('account.statement_source_mpesa')], 'auto', ['class' => 'form-control']) !!}
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        {!! Form::label('embedded_amount_tolerance', __('account.amount_tolerance') . ':') !!}
                                        {!! Form::number('amount_tolerance', 0.01, ['class' => 'form-control', 'step' => '0.0001', 'min' => '0']) !!}
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        {!! Form::label('embedded_date_tolerance_days', __('account.date_tolerance') . ':') !!}
                                        {!! Form::number('date_tolerance_days', 3, ['class' => 'form-control', 'step' => '1', 'min' => '0', 'max' => '30']) !!}
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        {!! Form::label('embedded_opening_balance', __('account.opening_balance') . ':') !!}
                                        {!! Form::number('opening_balance', null, ['class' => 'form-control', 'step' => '0.0001']) !!}
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        {!! Form::label('embedded_closing_balance_statement', __('account.statement_closing_balance') . ':') !!}
                                        {!! Form::number('closing_balance_statement', null, ['class' => 'form-control', 'step' => '0.0001']) !!}
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        {!! Form::label('embedded_reconciliation_notes', __('account.reconciliation_note') . ':') !!}
                                        {!! Form::text('reconciliation_notes', null, ['class' => 'form-control', 'maxlength' => 2000]) !!}
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="checkbox" style="margin-top:32px;">
                                        <label>
                                            <input type="checkbox" name="preview_only" value="1"> {{ __('account.preview_only') }}
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12 text-right">
                                    <a href="{{ action([\App\Http\Controllers\AccountReportsController::class, 'downloadBankReconciliationTemplate']) }}" class="btn btn-default">
                                        {{ __('account.download_statement_template') }}
                                    </a>
                                    <button type="submit" class="btn btn-primary">{{ __('account.upload_and_review_matches') }}</button>
                                    <a href="{{ url('/account/bank-reconciliation') }}" class="btn btn-default">{{ __('account.open_full_statement_matching_page') }}</a>
                                </div>
                            </div>
                        {!! Form::close() !!}
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            @component('accounting::components.box')
                @slot('header')
                    <div class="box-tools">
                        <a href="{{ url('accounting/reconcile') }}" class="btn btn-danger confirm">
                            {{ trans('accounting::lang.cancel') }}
                        </a>

                        <a href="#" @click="storeReconciliation" class="btn btn-primary">
                            {{ trans('accounting::general.finish_now') }}
                        </a>
                    </div>
                @endslot

                @slot('body')
                    <div class="integrations-banking-reconcile-ui">
                        <div class="reconcile-stage table-responsive">
                            <table class="stage-expanded-section">
                                <tbody>
                                    <tr>
                                        <td>
                                            <table class="equation-top">
                                                <tbody>
                                                    <tr>
                                                        <td>
                                                            <div class="automationID-StatementEndingBalance">
                                                                <div class="money-field">
                                                                    <div class="inlineBlock">
                                                                        <div class="ha-numeral medium">{{ $currency_code }}
                                                                            {{ number_format($ending_balance) }}</div>
                                                                        <div class="ha-numeral description">
                                                                            <span>{{ strtoupper(trans('accounting::general.statement_ending_balance')) }}</span>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </td>
                                                        <td class="operator">-</td>
                                                        <td colspan="2">
                                                            <div class="automationID-ClearedBalance">
                                                                <div class="money-field">
                                                                    <div class="inlineBlock">
                                                                        <div class="ha-numeral medium" id="cleared_balance_amount">
                                                                            {{ $currency_code }}
                                                                            {{ number_format($chart_of_account->current_balance) }}</div>
                                                                        <div class="ha-numeral description">
                                                                            <span>{{ strtoupper(trans('accounting::general.cleared_balance')) }}</span>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </td>
                                                        <td class="spacer-large"></td>
                                                    </tr>
                                                    <tr class="brace">
                                                        <td colspan="3" class="brace-head"></td>
                                                        <td colspan="2"></td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                            <table class="equation-bottom">
                                                <tbody>
                                                    <tr class="brace">
                                                        <td></td>
                                                        <td colspan="5" class="brace-arms"></td>
                                                        <td></td>
                                                    </tr>
                                                    <tr>
                                                        <td class="spacer-large"></td>
                                                        <td>
                                                            <div class="automationID-BeginningBalance">
                                                                <div class="money-field">
                                                                    <div class="inlineBlock">
                                                                        <div class="ha-numeral" id="beginning_balance_amount">
                                                                            {{ $currency_code }}
                                                                            {{ number_format($opening_balance) }}</div>
                                                                        <div class="ha-numeral description">
                                                                            <span>{{ strtoupper(trans('accounting::general.beginning_balance')) }}</span>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </td>
                                                        <td class="operator">
                                                            <div>-</div>
                                                        </td>
                                                        <td>
                                                            <div class="automationID-Payments">
                                                                <div class="money-field">
                                                                    <div class="inlineBlock">
                                                                        <div class="ha-numeral" id="payment_amount">{{ $currency_code }}
                                                                            {{ number_format($total_debit) }}</div>
                                                                        <div class="ha-numeral description">
                                                                            <span id="count_payments">{{ $currency_code }} {{ $no_debit }}
                                                                                {{ strtoupper(trans_choice('accounting::lang.payment', 2)) }}</span>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </td>
                                                        <td class="operator">
                                                            <div>+</div>
                                                        </td>
                                                        <td>
                                                            <div class="automationID-Deposits">
                                                                <div class="money-field">
                                                                    <div class="inlineBlock">
                                                                        <div class="ha-numeral" id="deposit_amount">{{ $currency_code }}
                                                                            {{ number_format($total_credit) }}</div>
                                                                        <div class="ha-numeral description">
                                                                            <span id="count_deposit">{{ $currency_code }} {{ $no_credit }}
                                                                                {{ strtoupper(trans_choice('accounting::lang.deposit', 2)) }}</span>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </td>
                                                        <td class="spacer"></td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </td>
                                        <td class="difference">
                                            <div class="automationID-Difference">
                                                <div class="difference-icon"><i class="hidden-xs ha-round-badge ha-warn"></i></div>
                                                <div class="money-field">
                                                    <div class="inlineBlock">
                                                        <div class="ha-numeral medium" id="difference_amount">{{ $currency_code }}
                                                            {{ number_format($difference) }} </div>
                                                        <div class="ha-numeral description">
                                                            <span
                                                                class="tool-tipped">{{ strtoupper(trans_choice('accounting::general.difference', 1)) }}</span>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="tooltip-empty-prevsib"></div>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <section class="content">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="card">
                                    <div class="card-body">
                                        <table id="journal_entries_table" class="table table-bordered table-hover">
                                            <thead>
                                                <tr>
                                                    <th>{{ trans_choice('accounting::lang.date', 1) }}</th>
                                                    <th>{{ trans_choice('accounting::general.gl_code', 1) }}</th>
                                                    <th style="text-align:right">{{ trans_choice('accounting::lang.description', 1) }}</th>
                                                    <th style="text-align:right">{{ trans_choice('accounting::general.debit', 1) }}</th>
                                                    <th style="text-align:right">{{ trans_choice('accounting::general.credit', 1) }}</th>
                                                    <th style="text-align:right">{{ trans_choice('accounting::general.balance', 1) }}</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @php
                                                    //group the results
                                                    $total_debit = 0;
                                                    $total_credit = 0;
                                                @endphp
                                                @foreach ($chart_of_account->journal_entries_not_reversed as $key)
                                                    @php
                                                        //group the results
                                                        $total_debit = $total_debit + $key->debit;
                                                        $total_credit = $total_credit + $key->credit;
                                                    @endphp
                                                    <tr>
                                                        <td>{{ $key->date }}</td>
                                                        <td>{{ $chart_of_account->gl_code }}</td>
                                                        <td>{{ $key->notes }}</td>
                                                        <td style="text-align:right">
                                                            {{ number_format($key->debit, 2) }}
                                                        </td>
                                                        <td style="text-align:right">
                                                            {{ number_format($key->credit, 2) }}
                                                        </td>
                                                        <td style="text-align:right">
                                                            {{ number_format($total_credit - $total_debit, 2) }}
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                            <tfoot>
                                                <tr>
                                                    <td colspan="3"><b>{{ trans_choice('accounting::lang.total', 1) }} ({{ $currency_code }})</b>
                                                    </td>
                                                    <td style="text-align:right"><b>{{ number_format($total_debit, 2) }}</b></td>
                                                    <td style="text-align:right"><b>{{ number_format($total_credit, 2) }}</b></td>
                                                    <td style="text-align:right"><b>{{ number_format($total_credit - $total_debit, 2) }}</b></td>
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
    </section>
@stop

@section('javascript')
    <script>
        function showReconcileValidationToast(message) {
            if (typeof window.showToast === 'function') {
                window.showToast('error', message);
                return;
            }

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'error',
                    title: message,
                    showConfirmButton: false,
                    timer: 4000,
                    timerProgressBar: true
                });
                return;
            }

            if (typeof toastr !== 'undefined') {
                toastr.error(message);
                return;
            }

            alert(message);
        }

        function validateVisibleEndingBalance() {
            var endingBalanceInput = document.querySelector('#start_reconcile_form input[name="ending_balance"]');
            var endingBalance = endingBalanceInput ? endingBalanceInput.value.trim() : '';

            if (endingBalance !== '') {
                return true;
            }

            showReconcileValidationToast(@json(__('accounting::general.ending_balance_field_required')));

            if (endingBalanceInput) {
                endingBalanceInput.focus();
            }

            return false;
        }

        function extractAjaxErrorMessage(xhr) {
            if (!xhr) {
                return '{{ __('messages.something_went_wrong') }}';
            }

            if (xhr.responseJSON) {
                if (xhr.responseJSON.errors) {
                    var firstErrorGroup = Object.values(xhr.responseJSON.errors)[0];
                    if (Array.isArray(firstErrorGroup) && firstErrorGroup.length) {
                        return firstErrorGroup[0];
                    }
                }

                if (xhr.responseJSON.msg) {
                    return xhr.responseJSON.msg;
                }

                if (xhr.responseJSON.message) {
                    return xhr.responseJSON.message;
                }
            }

            return '{{ __('messages.something_went_wrong') }}';
        }

        const app = new Vue({
            el: '#vue-app',
            data: {
                opening_balance: "{{ $opening_balance }}",
                ending_balance: "{{ $ending_balance }}",
                ending_date: "{{ $ending_date }}",
                difference: "{{ $difference }}",
                chart_of_account_id: parseInt("{{ $chart_of_account_id }}"),
                chart_of_accounts: {!! json_encode($chart_of_accounts) !!},
                chart_of_account: {!! $chart_of_account !!},
            },

            methods: {
                storeReconciliation() {
                    const form = document.getElementById('store_reconcile_form');

                    if (!validateVisibleEndingBalance()) {
                        return;
                    }

                    if (this.difference != 0) {
                        swal({
                                title: @json(__('accounting::general.hold_on')),
                                text: @json(__('accounting::general.difference_not_zero_proceed')),
                                icon: "warning",
                                buttons: true,
                                dangerMode: true,
                            })
                            .then((clickedOk) => {
                                if (clickedOk) {
                                    form.submit();
                                }
                            });
                    } else {
                        form.submit();
                    }
                },

                storeUndoReconciliation() {
                    const form = document.getElementById('undo_last_reconcile_form');

                    swal({
                            title: `Undo last reconcilation for ${this.chart_of_account.name}?`,
                            text: @json(__('accounting::general.action_may_not_be_reversible')),
                            icon: "warning",
                            buttons: true,
                            dangerMode: true,
                        })
                        .then((clickedOk) => {
                            if (clickedOk) {
                                form.submit();
                            }
                        });
                }
            }
        });

        $('#start_reconcile_form').on('submit', function(e) {
            if (!validateVisibleEndingBalance()) {
                e.preventDefault();
            }
        });

        $('#embedded_bank_reco_form').on('submit', function(e) {
            e.preventDefault();

            var formData = new FormData(this);
            $.ajax({
                method: 'POST',
                url: $(this).attr('action'),
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function(result) {
                    if (!result.success) {
                        toastr.error(result.msg || '{{ __('messages.something_went_wrong') }}');
                        return;
                    }

                    var runId = (result.summary || {}).run_id;
                    if (runId) {
                        window.location.href = "{{ url('/account/bank-reconciliation') }}?uploaded=1&run_id=" + runId;
                        return;
                    }

                    toastr.success(@json(__('account.statement_uploaded_successfully')));
                },
                error: function(xhr) {
                    toastr.error(extractAjaxErrorMessage(xhr));
                }
            });
        });

        @if ($errors->has('ending_balance'))
            showReconcileValidationToast(@json($errors->first('ending_balance')));
        @endif
    </script>
    <script>
        $('#journal_entries_table').DataTable({
            'ordering': false
        });
    </script>
@endsection
