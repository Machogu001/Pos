@extends('accounting::layouts.app')
@section('title')
    {{ trans_choice('accounting::lang.reconcile', 1) }}
@endsection

@section('content')

    @include('accounting::layouts.nav')
    <!-- Content Header (Page header) -->
    @component('accounting::components.section_header')
        @slot('title')
            {{ trans_choice('accounting::lang.reconcile', 1) }}
        @endslot
        @slot('subtitle')
            {{ trans('accounting::lang.reconcile_subtitle') }}
        @endslot
    @endcomponent

    <!-- Main content -->
    <section class="content no-print" id="vue-app">

        <div class="row">

            <div class="col-md-12">
                <div class="box box-info">
                    <div class="box-header with-border">
                        <h3 class="box-title">Statement matching</h3>
                    </div>
                    <div class="box-body">
                        <p class="text-muted">Upload an M-Pesa or bank statement here, then review the suggested matches before completing reconciliation.</p>
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
                                        {!! Form::label('embedded_statement', 'Bank / M-Pesa statement (.csv, .xlsx):') !!}
                                        {!! Form::file('statement', ['class' => 'form-control', 'accept' => '.csv,.txt,.xlsx,.xls,text/csv']) !!}
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        {!! Form::label('embedded_statement_source', 'Statement source:') !!}
                                        {!! Form::select('statement_source', ['auto' => 'Auto detect', 'bank' => 'Bank', 'mpesa' => 'M-Pesa'], 'auto', ['class' => 'form-control']) !!}
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        {!! Form::label('embedded_amount_tolerance', __('sale.amount') . ' tolerance:') !!}
                                        {!! Form::number('amount_tolerance', 0.01, ['class' => 'form-control', 'step' => '0.0001', 'min' => '0']) !!}
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        {!! Form::label('embedded_date_tolerance_days', __('messages.date') . ' tolerance:') !!}
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
                                        {!! Form::label('embedded_closing_balance_statement', 'Statement closing balance:') !!}
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
                                            <input type="checkbox" name="preview_only" value="1"> Preview only
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12 text-right">
                                    <button type="submit" class="btn btn-primary">Upload and review matches</button>
                                    <a href="{{ url('/account/bank-reconciliation') }}" class="btn btn-default">Open full statement matching page</a>
                                </div>
                            </div>
                        {!! Form::close() !!}
                    </div>
                </div>
            </div>

            @component('accounting::components.box')
                @slot('body')
                    <section class="content">
                        @include('accounting::reconcile.partials.reconcile_form')
                        @include('accounting::reconcile.partials.undo_reconcile_form')
                    </section>
                @endslot
            @endcomponent

        </div>
    </section>
@stop

@section('javascript')
    <script>
        const app = new Vue({
            el: '#vue-app',
            data: {
                ending_balance: "{{ old('ending_balance') }}",
                ending_date: "{{ old('ending_date') }}",
                chart_of_accounts: {!! json_encode($chart_of_accounts) !!},
                chart_of_account_id: parseInt("{{ old('chart_of_account_id') }}"),
            },

            computed: {
                chart_of_account() {
                    return !isNaN(this.chart_of_account_id) ?
                        this.chart_of_accounts.find(account => account.id == this.chart_of_account_id) : {};
                }
            },

            methods: {
                storeUndoReconciliation() {
                    const form = document.getElementById('undo_last_reconcile_form');

                    swal({
                            title: `Undo last reconcilation for ${this.chart_of_account.name}?`,
                            text: 'This action may not be reversible',
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

        $('#embedded_bank_reco_form').on('submit', function(e) {
            e.preventDefault();

            var formData = new FormData(this);
            $.ajax({
                method: 'POST',
                url: $(this).attr('action'),
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
                        window.location.href = "{{ url('/account/bank-reconciliation') }}?run_id=" + runId;
                        return;
                    }

                    toastr.success('Preview generated successfully.');
                },
                error: function() {
                    toastr.error('{{ __('messages.something_went_wrong') }}');
                }
            });
        });
    </script>
@endsection
