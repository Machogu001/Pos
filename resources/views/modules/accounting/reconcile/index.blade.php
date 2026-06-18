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
@endsection
