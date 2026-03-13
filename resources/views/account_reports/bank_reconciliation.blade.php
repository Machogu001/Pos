@extends('layouts.app')
@section('title', __('account.payment_account_report') . ' - ' . __('account.bank_reconciliation'))

@section('content')

    <!-- Content Header (Page header) -->
    <section class="content-header">
        <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">
            {{ __('account.bank_reconciliation') ?? 'Bank Reconciliation' }}
        </h1>
    </section>

    <!-- Main content -->
    <section class="content no-print">
        <div class="row">
            <div class="col-md-12">
                @component('components.filters', ['title' => __('report.filters')])
                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('account_id', __('account.account') . ':') !!}
                            {!! Form::select('account_id', $accounts, null, ['class' => 'form-control select2', 'style' => 'width:100%']) !!}
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('date_filter', __('report.date_range') . ':') !!}
                            {!! Form::text('date_range', null, [
                                'placeholder' => __('lang_v1.select_a_date_range'),
                                'class' => 'form-control',
                                'id' => 'date_filter',
                                'readonly',
                            ]) !!}
                        </div>
                    </div>
                @endcomponent
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                @component('components.widget')
                    <h3 class="tw-text-lg tw-font-semibold tw-mb-3">{{ __('account.bank_reconciliation') ?? 'Bank Reconciliation' }}</h3>

                    {!! Form::open(['url' => action([\App\Http\Controllers\AccountReportsController::class, 'uploadBankReconciliation']), 'method' => 'post', 'id' => 'bank_reco_form', 'files' => true]) !!}
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    {!! Form::label('statement', __('account.bank_statement') . ' (CSV):') !!}
                                    {!! Form::file('statement', ['class' => 'form-control', 'accept' => '.csv,text/csv']) !!}
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group" style="margin-top:25px;">
                                    <a href="{{ action([\App\Http\Controllers\AccountReportsController::class, 'downloadBankReconciliationTemplate']) }}" class="btn btn-default">
                                        @lang('account.download_bank_statement_template')
                                    </a>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group" style="margin-top:25px;">
                                    <button type="submit" class="btn btn-primary">
                                        @lang('account.reconcile')
                                    </button>
                                </div>
                            </div>
                        </div>
                        <p class="help-block">
                            {{ __('account.bank_reconciliation_help') ?? 'Upload a CSV file with columns: Date, Amount, Description (optional), Reference (optional). The system will try to match each line with payments by amount and nearby dates.' }}
                        </p>
                    {!! Form::close() !!}

                    <hr>

                    <div id="reco_summary" class="tw-mb-4"></div>

                    <div id="reco_results">
                        <h4>{{ __('account.matched_transactions') ?? 'Matched Transactions' }}</h4>
                        <div class="table-responsive tw-mb-4">
                            <table class="table table-bordered table-striped" id="reco_matched_table">
                                <thead>
                                    <tr>
                                        <th>@lang('messages.date')</th>
                                        <th>@lang('sale.amount')</th>
                                        <th>@lang('lang_v1.description')</th>
                                        <th>@lang('account.payment_ref_no')</th>
                                        <th>@lang('account.invoice_ref_no')</th>
                                        <th>@lang('lang_v1.payment_type')</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>

                        <h4>{{ __('account.ambiguous_matches') ?? 'Ambiguous Matches' }}</h4>
                        <div class="table-responsive tw-mb-4">
                            <table class="table table-bordered table-striped" id="reco_ambiguous_table">
                                <thead>
                                    <tr>
                                        <th>@lang('messages.date')</th>
                                        <th>@lang('sale.amount')</th>
                                        <th>@lang('lang_v1.description')</th>
                                        <th>@lang('account.payment_ref_no')</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>

                        <h4>{{ __('account.unmatched_statement_lines') ?? 'Unmatched Statement Lines' }}</h4>
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped" id="reco_unmatched_table">
                                <thead>
                                    <tr>
                                        <th>@lang('messages.date')</th>
                                        <th>@lang('sale.amount')</th>
                                        <th>@lang('lang_v1.description')</th>
                                        <th>@lang('lang_v1.reference')</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                @endcomponent
            </div>
        </div>
    </section>
    <!-- /.content -->

@endsection

@section('javascript')
    <script type="text/javascript">
        $(document).ready(function() {
            if ($('#date_filter').length == 1) {
                $('#date_filter').daterangepicker(
                    dateRangeSettings,
                    function(start, end) {
                        $('#date_filter').val(start.format(moment_date_format) + ' ~ ' + end.format(moment_date_format));
                    }
                );

                $('#date_filter').on('cancel.daterangepicker', function(ev, picker) {
                    $(this).val('');
                });
            }

            $('#bank_reco_form').on('submit', function(e) {
                e.preventDefault();

                var formData = new FormData(this);

                var account_id = $('#account_id').val();
                if (account_id) {
                    formData.append('account_id', account_id);
                }

                if ($('#date_filter').val()) {
                    var start_date = $('#date_filter').data('daterangepicker').startDate.format('YYYY-MM-DD');
                    var end_date = $('#date_filter').data('daterangepicker').endDate.format('YYYY-MM-DD');
                    formData.append('start_date', start_date);
                    formData.append('end_date', end_date);
                }

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

                        var summary = result.summary || {};
                        var html = '<p><strong>{{ __('account.total_statement_lines') ?? 'Statement lines' }}:</strong> ' + (summary.total_statement_lines || 0) + '</p>';
                        html += '<p><strong>{{ __('account.total_statement_amount') ?? 'Statement amount' }}:</strong> ' + __currency_trans_from_en(summary.total_statement_amount || 0, true) + '</p>';
                        html += '<p><strong>{{ __('account.matched') ?? 'Matched' }}:</strong> ' + (summary.matched_count || 0) + ' (' + __currency_trans_from_en(summary.total_matched_amount || 0, true) + ')</p>';
                        html += '<p><strong>{{ __('account.ambiguous') ?? 'Ambiguous' }}:</strong> ' + (summary.ambiguous_count || 0) + '</p>';
                        html += '<p><strong>{{ __('account.unmatched') ?? 'Unmatched' }}:</strong> ' + (summary.unmatched_count || 0) + '</p>';
                        $('#reco_summary').html(html);

                        var matched_rows = '';
                        (result.matched || []).forEach(function(item) {
                            var s = item.statement || {};
                            var p = item.payment || {};
                            matched_rows += '<tr>' +
                                '<td>' + (s.date || '') + '</td>' +
                                '<td>' + __currency_trans_from_en(s.amount || 0, true) + '</td>' +
                                '<td>' + (s.description || '') + '</td>' +
                                '<td>' + (p.payment_ref_no || '') + '</td>' +
                                '<td>' + (p.transaction_id || '') + '</td>' +
                                '<td>' + (p.transaction_type || '') + '</td>' +
                                '</tr>';
                        });
                        $('#reco_matched_table tbody').html(matched_rows);

                        var ambiguous_rows = '';
                        (result.ambiguous || []).forEach(function(item) {
                            var s = item.statement || {};
                            var first = (item.candidates || [])[0] || {};
                            ambiguous_rows += '<tr>' +
                                '<td>' + (s.date || '') + '</td>' +
                                '<td>' + __currency_trans_from_en(s.amount || 0, true) + '</td>' +
                                '<td>' + (s.description || '') + '</td>' +
                                '<td>' + (first.payment_ref_no || '') + '</td>' +
                                '</tr>';
                        });
                        $('#reco_ambiguous_table tbody').html(ambiguous_rows);

                        var unmatched_rows = '';
                        (result.unmatched || []).forEach(function(s) {
                            unmatched_rows += '<tr>' +
                                '<td>' + (s.date || '') + '</td>' +
                                '<td>' + __currency_trans_from_en(s.amount || 0, true) + '</td>' +
                                '<td>' + (s.description || '') + '</td>' +
                                '<td>' + (s.reference || '') + '</td>' +
                                '</tr>';
                        });
                        $('#reco_unmatched_table tbody').html(unmatched_rows);
                    },
                    error: function() {
                        toastr.error('{{ __('messages.something_went_wrong') }}');
                    }
                });
            });
        });
    </script>
@endsection
