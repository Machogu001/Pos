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

                    <div id="reco_actions" class="tw-mb-4" style="display:none;">
                        <button type="button" id="finalize_reco_btn" class="btn btn-success">
                            {{ __('lang_v1.finalize') ?? 'Finalize Reconciliation' }}
                        </button>
                        <a href="#" id="export_reco_btn" class="btn btn-default" style="display:none;">
                            {{ __('lang_v1.download') ?? 'Download' }} Audit Package
                        </a>
                    </div>

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
                                        <th>{{ __('lang_v1.reference') }}</th>
                                        <th>{{ __('lang_v1.details') }}</th>
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

                        <h4>{{ __('lang_v1.invalid') ?? 'Invalid Statement Lines' }}</h4>
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped" id="reco_invalid_table">
                                <thead>
                                    <tr>
                                        <th>{{ __('lang_v1.sr_no') }}</th>
                                        <th>@lang('messages.date')</th>
                                        <th>@lang('sale.amount')</th>
                                        <th>@lang('lang_v1.description')</th>
                                        <th>@lang('lang_v1.reference')</th>
                                        <th>{{ __('lang_v1.reason') ?? 'Reason' }}</th>
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
            var currentRunId = null;
            var currentRunStatus = null;

            function escapeHtml(value) {
                return $('<div/>').text(value == null ? '' : value).html();
            }

            function setRunActions(runId, status) {
                currentRunId = runId || null;
                currentRunStatus = status || null;

                if (!currentRunId) {
                    $('#reco_actions').hide();
                    $('#export_reco_btn').hide().attr('href', '#');
                    $('#finalize_reco_btn').prop('disabled', true);
                    return;
                }

                $('#reco_actions').show();

                var exportUrl = "{{ url('account/bank-reconciliation') }}/" + currentRunId + "/export";
                $('#export_reco_btn').attr('href', exportUrl);

                if (currentRunStatus === 'finalized') {
                    $('#finalize_reco_btn').prop('disabled', true).text("{{ __('lang_v1.finalized') ?? 'Finalized' }}");
                    $('#export_reco_btn').show();
                } else {
                    $('#finalize_reco_btn').prop('disabled', false).text("{{ __('lang_v1.finalize') ?? 'Finalize Reconciliation' }}");
                    $('#export_reco_btn').hide();
                }
            }

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
                        var run_id = summary.run_id ? ('<p><strong>{{ __('lang_v1.id') ?? 'ID' }}:</strong> ' + escapeHtml(summary.run_id) + '</p>') : '';
                        var run_status = summary.status ? ('<p><strong>{{ __('lang_v1.status') ?? 'Status' }}:</strong> ' + escapeHtml(summary.status) + '</p>') : '';
                        var html = '<p><strong>{{ __('account.total_statement_lines') ?? 'Statement lines' }}:</strong> ' + (summary.total_statement_lines || 0) + '</p>';
                        html = run_id + run_status + html;
                        html += '<p><strong>{{ __('account.total_statement_amount') ?? 'Statement amount' }}:</strong> ' + __currency_trans_from_en(summary.total_statement_amount || 0, true) + '</p>';
                        html += '<p><strong>{{ __('account.matched') ?? 'Matched' }}:</strong> ' + (summary.matched_count || 0) + ' (' + __currency_trans_from_en(summary.total_matched_amount || 0, true) + ')</p>';
                        html += '<p><strong>{{ __('account.ambiguous') ?? 'Ambiguous' }}:</strong> ' + (summary.ambiguous_count || 0) + '</p>';
                        html += '<p><strong>{{ __('account.unmatched') ?? 'Unmatched' }}:</strong> ' + (summary.unmatched_count || 0) + '</p>';
                        html += '<p><strong>{{ __('lang_v1.invalid') ?? 'Invalid' }}:</strong> ' + (summary.invalid_count || 0) + '</p>';
                        $('#reco_summary').html(html);
                        setRunActions(summary.run_id || null, summary.status || 'completed');

                        var matched_rows = '';
                        (result.matched || []).forEach(function(item) {
                            var s = item.statement || {};
                            var p = item.payment || {};
                            matched_rows += '<tr>' +
                                '<td>' + escapeHtml(s.date || '') + '</td>' +
                                '<td>' + __currency_trans_from_en(s.amount || 0, true) + '</td>' +
                                '<td>' + escapeHtml(s.description || '') + '</td>' +
                                '<td>' + escapeHtml(p.payment_ref_no || '') + '</td>' +
                                '<td>' + escapeHtml(p.invoice_no || '') + '</td>' +
                                '<td>' + escapeHtml(p.method || p.transaction_type || '') + '</td>' +
                                '</tr>';
                        });
                        $('#reco_matched_table tbody').html(matched_rows);

                        var ambiguous_rows = '';
                        (result.ambiguous || []).forEach(function(item) {
                            var s = item.statement || {};
                            var candidates = item.candidates || [];
                            var candidateRefs = candidates.map(function(c) {
                                return c.payment_ref_no || c.invoice_no || ('#' + c.id);
                            }).join(', ');
                            ambiguous_rows += '<tr>' +
                                '<td>' + escapeHtml(s.date || '') + '</td>' +
                                '<td>' + __currency_trans_from_en(s.amount || 0, true) + '</td>' +
                                '<td>' + escapeHtml(s.description || '') + '</td>' +
                                '<td>' + escapeHtml(s.reference || '') + '</td>' +
                                '<td>' + escapeHtml(candidates.length + ' candidates: ' + candidateRefs) + '</td>' +
                                '</tr>';
                        });
                        $('#reco_ambiguous_table tbody').html(ambiguous_rows);

                        var unmatched_rows = '';
                        (result.unmatched || []).forEach(function(s) {
                            unmatched_rows += '<tr>' +
                                '<td>' + escapeHtml(s.date || '') + '</td>' +
                                '<td>' + __currency_trans_from_en(s.amount || 0, true) + '</td>' +
                                '<td>' + escapeHtml(s.description || '') + '</td>' +
                                '<td>' + escapeHtml(s.reference || '') + '</td>' +
                                '</tr>';
                        });
                        $('#reco_unmatched_table tbody').html(unmatched_rows);

                        var invalid_rows = '';
                        (result.invalid || []).forEach(function(s) {
                            invalid_rows += '<tr>' +
                                '<td>' + escapeHtml(s.line || '') + '</td>' +
                                '<td>' + escapeHtml(s.date || '') + '</td>' +
                                '<td>' + escapeHtml(s.amount || '') + '</td>' +
                                '<td>' + escapeHtml(s.description || '') + '</td>' +
                                '<td>' + escapeHtml(s.reference || '') + '</td>' +
                                '<td>' + escapeHtml(s.reason || '') + '</td>' +
                                '</tr>';
                        });
                        $('#reco_invalid_table tbody').html(invalid_rows);
                    },
                    error: function() {
                        toastr.error('{{ __('messages.something_went_wrong') }}');
                    }
                });
            });

            $('#finalize_reco_btn').on('click', function() {
                if (!currentRunId) {
                    toastr.error("{{ __('account.no_reconciliation_run_selected') }}");
                    return;
                }

                $.ajax({
                    method: 'POST',
                    url: "{{ url('account/bank-reconciliation') }}/" + currentRunId + "/finalize",
                    dataType: 'json',
                    success: function(result) {
                        if (!result.success) {
                            toastr.error(result.msg || '{{ __('messages.something_went_wrong') }}');
                            return;
                        }

                        toastr.success(result.msg || "{{ __('account.reconciliation_finalized_successfully') }}");
                        setRunActions(result.run_id || currentRunId, result.status || 'finalized');

                        var currentHtml = $('#reco_summary').html();
                        if (currentHtml && currentHtml.indexOf('{{ __('lang_v1.status') ?? 'Status' }}') !== -1) {
                            $('#reco_summary').html(currentHtml.replace(/<strong>{{ __('lang_v1.status') ?? 'Status' }}:<\/strong>\s*[^<]*/i, '<strong>{{ __('lang_v1.status') ?? 'Status' }}:</strong> finalized'));
                        }
                    },
                    error: function() {
                        toastr.error('{{ __('messages.something_went_wrong') }}');
                    }
                });
            });
        });
    </script>
@endsection
