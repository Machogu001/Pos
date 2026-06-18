@extends('layouts.app')
@section('title', __('account.payment_account_report') . ' - ' . __('account.bank_reconciliation'))

@section('content')

    @include('accounting::layouts.nav')

    @component('accounting::components.section_header')
        @slot('title')
            {{ __('account.bank_reconciliation') }}
        @endslot
        @slot('subtitle')
            {{ __('account.bank_reconciliation_subtitle') }}
        @endslot
    @endcomponent

    <style>
        .reco-shell {
            display: grid;
            gap: 18px;
        }

        .reco-page-title {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 12px;
        }

        .reco-upload-card {
            border: 1px solid #dbe4ee;
            border-radius: 14px;
            padding: 22px;
            background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
            margin-bottom: 18px;
            box-shadow: 0 10px 28px rgba(15, 23, 42, 0.05);
        }

        .reco-upload-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 16px;
            margin-bottom: 18px;
        }

        .reco-upload-header-copy {
            max-width: 760px;
        }

        .reco-upload-header-copy h3 {
            margin: 0 0 6px;
            font-size: 22px;
            font-weight: 700;
            color: #0f172a;
            letter-spacing: -0.02em;
        }

        .reco-upload-header-copy p {
            margin: 0;
            max-width: 62ch;
            line-height: 1.6;
        }

        .reco-upload-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 120px;
            padding: 9px 12px;
            border-radius: 999px;
            background: #e0f2fe;
            color: #075985;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .reco-form-grid {
            display: grid;
            grid-template-columns: repeat(12, minmax(0, 1fr));
            gap: 14px 16px;
            align-items: end;
        }

        .reco-form-span-5 { grid-column: span 5; }
        .reco-form-span-3 { grid-column: span 3; }
        .reco-form-span-2 { grid-column: span 2; }
        .reco-form-span-1 { grid-column: span 1; }
        .reco-form-span-4 { grid-column: span 4; }

        .reco-field {
            display: flex;
            flex-direction: column;
            gap: 6px;
            min-height: 100%;
        }

        .reco-field label,
        .reco-check-field label {
            margin-bottom: 0;
            font-size: 12px;
            font-weight: 700;
            color: #334155;
            letter-spacing: 0.02em;
        }

        .reco-field .form-control,
        .reco-check-field {
            min-height: 42px;
        }

        .reco-field .form-control {
            border-radius: 10px;
            border-color: #d7dee8;
            box-shadow: none;
        }

        .reco-field .form-control:focus {
            border-color: #0ea5e9;
            box-shadow: 0 0 0 3px rgba(14, 165, 233, 0.12);
        }

        .reco-check-field {
            display: flex;
            align-items: center;
            padding: 0 12px;
            border: 1px dashed #cbd5e1;
            border-radius: 10px;
            background: #fff;
        }

        .reco-check-field input {
            margin-right: 8px;
        }

        .reco-check-field label {
            display: flex;
            align-items: center;
            gap: 8px;
            width: 100%;
            cursor: pointer;
        }

        .reco-upload-actions {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 10px;
            min-height: 42px;
        }

        .reco-upload-actions .btn {
            min-width: 170px;
            border-radius: 10px;
        }

        .reco-hint {
            border-left: 3px solid #0ea5e9;
            padding: 12px 14px;
            background: #f0f9ff;
            border-radius: 10px;
            color: #0f172a;
            margin-top: 18px;
            line-height: 1.6;
        }

        .reco-summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 12px;
            margin-bottom: 14px;
        }

        .reco-metric {
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 14px;
            background: #fff;
            box-shadow: 0 8px 20px rgba(15, 23, 42, 0.04);
        }

        .reco-metric-label {
            font-size: 12px;
            color: #64748b;
            margin-bottom: 2px;
        }

        .reco-metric-value {
            font-size: 16px;
            font-weight: 600;
            color: #0f172a;
        }

        .reco-section {
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            background: #fff;
            margin-bottom: 16px;
            overflow: hidden;
            box-shadow: 0 8px 24px rgba(15, 23, 42, 0.04);
        }

        .reco-section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 8px;
            padding: 14px 16px;
            border-bottom: 1px solid #f1f5f9;
            background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
        }

        .reco-section-header h4 {
            margin: 0;
            font-size: 15px;
            font-weight: 600;
        }

        .reco-section .table {
            margin-bottom: 0;
        }

        .reco-section .table-responsive {
            padding: 0;
        }

        #reco_results table {
            width: 100%;
            table-layout: fixed;
            border-collapse: collapse;
        }

        #reco_results table th,
        #reco_results table td {
            vertical-align: middle;
            text-align: left;
            word-break: break-word;
            padding: 10px 8px;
        }

        #reco_results table tbody tr:nth-child(even) {
            background: #fcfdff;
        }

        #reco_results table tbody tr:hover {
            background: #f8fbff;
        }

        #reco_actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            padding: 14px;
            border: 1px solid #dbe4ee;
            border-radius: 12px;
            background: #fff;
            box-shadow: 0 8px 24px rgba(15, 23, 42, 0.04);
        }

        #reco_actions .btn {
            border-radius: 10px;
        }

        .reco-section .label {
            border-radius: 999px;
            padding: 0.45em 0.8em;
            font-size: 11px;
            font-weight: 700;
        }

        #reco_results table th {
            background: #f8fafc;
            font-size: 12px;
            letter-spacing: 0.2px;
            text-transform: uppercase;
            color: #475569;
            font-weight: 600;
        }

        #reco_results table td {
            color: #0f172a;
            font-size: 13px;
        }

        #reco_results col.col-date { width: 140px; }
        #reco_results col.col-amount { width: 130px; }
        #reco_results col.col-desc { width: 28%; }
        #reco_results col.col-ref { width: 180px; }
        #reco_results col.col-details { width: 30%; }
        #reco_results col.col-payment-ref { width: 170px; }
        #reco_results col.col-invoice-ref { width: 190px; }
        #reco_results col.col-payment-type { width: 170px; }
        #reco_results col.col-serial { width: 70px; }
        #reco_results col.col-reason { width: 26%; }

        #reco_matched_table th:nth-child(1),
        #reco_matched_table td:nth-child(1) { width: 13%; }
        #reco_matched_table th:nth-child(2),
        #reco_matched_table td:nth-child(2) { width: 12%; }
        #reco_matched_table th:nth-child(3),
        #reco_matched_table td:nth-child(3) { width: 24%; }
        #reco_matched_table th:nth-child(4),
        #reco_matched_table td:nth-child(4) { width: 16%; }
        #reco_matched_table th:nth-child(5),
        #reco_matched_table td:nth-child(5) { width: 17%; }
        #reco_matched_table th:nth-child(6),
        #reco_matched_table td:nth-child(6) { width: 18%; }

        #reco_ambiguous_table th:nth-child(1),
        #reco_ambiguous_table td:nth-child(1) { width: 14%; }
        #reco_ambiguous_table th:nth-child(2),
        #reco_ambiguous_table td:nth-child(2) { width: 12%; }
        #reco_ambiguous_table th:nth-child(3),
        #reco_ambiguous_table td:nth-child(3) { width: 30%; }
        #reco_ambiguous_table th:nth-child(4),
        #reco_ambiguous_table td:nth-child(4) { width: 16%; }
        #reco_ambiguous_table th:nth-child(5),
        #reco_ambiguous_table td:nth-child(5) { width: 28%; }

        #reco_unmatched_table th:nth-child(1),
        #reco_unmatched_table td:nth-child(1) { width: 16%; }
        #reco_unmatched_table th:nth-child(2),
        #reco_unmatched_table td:nth-child(2) { width: 14%; }
        #reco_unmatched_table th:nth-child(3),
        #reco_unmatched_table td:nth-child(3) { width: 42%; }
        #reco_unmatched_table th:nth-child(4),
        #reco_unmatched_table td:nth-child(4) { width: 28%; }

        #reco_invalid_table th:nth-child(1),
        #reco_invalid_table td:nth-child(1) { width: 8%; }
        #reco_invalid_table th:nth-child(2),
        #reco_invalid_table td:nth-child(2) { width: 12%; }
        #reco_invalid_table th:nth-child(3),
        #reco_invalid_table td:nth-child(3) { width: 12%; }
        #reco_invalid_table th:nth-child(4),
        #reco_invalid_table td:nth-child(4) { width: 28%; }
        #reco_invalid_table th:nth-child(5),
        #reco_invalid_table td:nth-child(5) { width: 18%; }
        #reco_invalid_table th:nth-child(6),
        #reco_invalid_table td:nth-child(6) { width: 22%; }

        #reco_matched_table th:nth-child(2),
        #reco_matched_table td:nth-child(2),
        #reco_ambiguous_table th:nth-child(2),
        #reco_ambiguous_table td:nth-child(2),
        #reco_unmatched_table th:nth-child(2),
        #reco_unmatched_table td:nth-child(2),
        #reco_invalid_table th:nth-child(3),
        #reco_invalid_table td:nth-child(3) {
            text-align: right;
            white-space: nowrap;
            font-variant-numeric: tabular-nums;
        }

        #reco_matched_table th:nth-child(1),
        #reco_matched_table td:nth-child(1),
        #reco_ambiguous_table th:nth-child(1),
        #reco_ambiguous_table td:nth-child(1),
        #reco_unmatched_table th:nth-child(1),
        #reco_unmatched_table td:nth-child(1),
        #reco_invalid_table th:nth-child(2),
        #reco_invalid_table td:nth-child(2) {
            white-space: nowrap;
        }

        #reco_invalid_table th:nth-child(1),
        #reco_invalid_table td:nth-child(1) {
            text-align: center;
        }

        @media (max-width: 1200px) {
            #reco_results .table-responsive {
                overflow-x: auto;
            }

            #reco_results table {
                min-width: 980px;
            }
        }

        @media (max-width: 991px) {
            .reco-upload-header {
                flex-direction: column;
            }

            .reco-form-span-5,
            .reco-form-span-4,
            .reco-form-span-3,
            .reco-form-span-2,
            .reco-form-span-1 {
                grid-column: span 6;
            }
        }

        @media (max-width: 767px) {
            .reco-upload-card {
                padding: 16px;
            }

            .reco-form-grid {
                grid-template-columns: repeat(1, minmax(0, 1fr));
            }

            .reco-form-span-5,
            .reco-form-span-4,
            .reco-form-span-3,
            .reco-form-span-2,
            .reco-form-span-1 {
                grid-column: span 1;
            }

            .reco-upload-actions .btn,
            #reco_actions .btn {
                width: 100%;
            }

            .reco-section-header {
                align-items: flex-start;
                flex-direction: column;
            }
        }
    </style>

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
                    <div class="reco-shell">
                    <div class="reco-upload-card">
                        <div class="reco-upload-header">
                            <div class="reco-upload-header-copy">
                                <h3>{{ __('account.bank_reconciliation') }}</h3>
                                <p class="text-muted">{{ __('account.statement_matching_help_full') }}</p>
                            </div>
                            <div class="reco-upload-badge">{{ __('account.reconcile') }}</div>
                        </div>

                        {!! Form::open(['url' => action([\App\Http\Controllers\AccountReportsController::class, 'uploadBankReconciliation']), 'method' => 'post', 'id' => 'bank_reco_form', 'files' => true]) !!}
                            <div class="reco-form-grid">
                                <div class="reco-form-span-5">
                                    <div class="form-group reco-field">
                                        {!! Form::label('statement', __('account.statement_file') . ':') !!}
                                        {!! Form::file('statement', ['class' => 'form-control', 'accept' => '.csv,.txt,.xlsx,.xls,text/csv']) !!}
                                    </div>
                                </div>
                                <div class="reco-form-span-2">
                                    <div class="form-group reco-field">
                                        {!! Form::label('statement_source', __('account.statement_source') . ':') !!}
                                        {!! Form::select('statement_source', ['auto' => __('account.statement_source_auto'), 'bank' => __('account.statement_source_bank'), 'mpesa' => __('account.statement_source_mpesa')], 'auto', ['class' => 'form-control']) !!}
                                    </div>
                                </div>
                                <div class="reco-form-span-2">
                                    <div class="form-group reco-field">
                                        {!! Form::label('amount_tolerance', __('account.amount_tolerance') . ':') !!}
                                        {!! Form::number('amount_tolerance', 0.01, ['class' => 'form-control', 'step' => '0.0001', 'min' => '0']) !!}
                                    </div>
                                </div>
                                <div class="reco-form-span-1">
                                    <div class="form-group reco-field">
                                        {!! Form::label('date_tolerance_days', __('account.date_tolerance_days') . ':') !!}
                                        {!! Form::number('date_tolerance_days', 3, ['class' => 'form-control', 'step' => '1', 'min' => '0', 'max' => '30']) !!}
                                    </div>
                                </div>
                                <div class="reco-form-span-2">
                                    <div class="form-group reco-check-field">
                                        <label>
                                            <input type="checkbox" id="preview_only" name="preview_only" value="1"> {{ __('account.preview_only') }}
                                        </label>
                                    </div>
                                </div>
                                <div class="reco-form-span-3">
                                    <div class="form-group reco-field">
                                        {!! Form::label('opening_balance', __('account.opening_balance') . ':') !!}
                                        {!! Form::number('opening_balance', null, ['class' => 'form-control', 'step' => '0.0001']) !!}
                                    </div>
                                </div>
                                <div class="reco-form-span-3">
                                    <div class="form-group reco-field">
                                        {!! Form::label('closing_balance_statement', __('account.statement_closing_balance') . ':') !!}
                                        {!! Form::number('closing_balance_statement', null, ['class' => 'form-control', 'step' => '0.0001']) !!}
                                    </div>
                                </div>
                                <div class="reco-form-span-3">
                                    <div class="form-group reco-field">
                                        {!! Form::label('reconciliation_notes', __('account.reconciliation_note') . ':') !!}
                                        {!! Form::text('reconciliation_notes', null, ['class' => 'form-control', 'maxlength' => 2000]) !!}
                                    </div>
                                </div>
                                <div class="reco-form-span-3">
                                    <div class="form-group reco-upload-actions">
                                        <a href="{{ action([\App\Http\Controllers\AccountReportsController::class, 'downloadBankReconciliationTemplate']) }}" class="btn btn-default">
                                            {{ __('account.download_statement_template') }}
                                        </a>
                                        <button type="submit" class="btn btn-primary">
                                            @lang('account.reconcile')
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="reco-hint">
                                {{ __('account.bank_reconciliation_help') }}
                            </div>
                        {!! Form::close() !!}
                    </div>

                    <div id="reco_actions" class="tw-mb-4" style="display:none;">
                        <button type="button" id="finalize_reco_btn" class="btn btn-success">
                            {{ __('lang_v1.finalize') }}
                        </button>
                        <button type="button" id="undo_reco_btn" class="btn btn-warning tw-ml-2" style="display:none;">
                            {{ __('account.undo_reconciliation') }}
                        </button>
                        <a href="#" id="export_reco_btn" class="btn btn-default" style="display:none;">
                            {{ __('lang_v1.download') }} {{ __('account.audit_package') }}
                        </a>
                        <a href="#" id="export_reco_pdf_btn" class="btn btn-default" style="display:none;">
                            {{ __('lang_v1.download') }} PDF
                        </a>
                        <a href="#" id="export_reco_excel_btn" class="btn btn-default" style="display:none;">
                            {{ __('lang_v1.download') }} Excel
                        </a>
                    </div>

                    <div id="reco_summary" class="tw-mb-4"></div>

                    <div id="reco_results">
                        <div class="reco-section">
                            <div class="reco-section-header">
                                <h4>{{ __('account.matched_transactions') }}</h4>
                                <span class="label label-success" id="matched_count_badge">0</span>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped" id="reco_matched_table">
                                    <colgroup>
                                        <col class="col-date">
                                        <col class="col-amount">
                                        <col class="col-desc">
                                        <col class="col-payment-ref">
                                        <col class="col-invoice-ref">
                                        <col class="col-payment-type">
                                    </colgroup>
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
                        </div>

                        <div class="reco-section">
                            <div class="reco-section-header">
                                <h4>{{ __('account.ambiguous_matches') }}</h4>
                                <span class="label label-warning" id="ambiguous_count_badge">0</span>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped" id="reco_ambiguous_table">
                                    <colgroup>
                                        <col class="col-date">
                                        <col class="col-amount">
                                        <col class="col-desc">
                                        <col class="col-ref">
                                        <col class="col-details">
                                    </colgroup>
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
                        </div>

                        <div class="reco-section">
                            <div class="reco-section-header">
                                <h4>{{ __('account.unmatched_statement_lines') }}</h4>
                                <span class="label label-default" id="unmatched_count_badge">0</span>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped" id="reco_unmatched_table">
                                    <colgroup>
                                        <col class="col-date">
                                        <col class="col-amount">
                                        <col class="col-desc">
                                        <col class="col-ref">
                                    </colgroup>
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

                        <div class="reco-section">
                            <div class="reco-section-header">
                                <h4>{{ __('lang_v1.invalid') }}</h4>
                                <span class="label label-danger" id="invalid_count_badge">0</span>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped" id="reco_invalid_table">
                                    <colgroup>
                                        <col class="col-serial">
                                        <col class="col-date">
                                        <col class="col-amount">
                                        <col class="col-desc">
                                        <col class="col-ref">
                                        <col class="col-reason">
                                    </colgroup>
                                    <thead>
                                        <tr>
                                            <th>{{ __('lang_v1.sr_no') }}</th>
                                            <th>@lang('messages.date')</th>
                                            <th>@lang('sale.amount')</th>
                                            <th>@lang('lang_v1.description')</th>
                                            <th>@lang('lang_v1.reference')</th>
                                            <th>{{ __('lang_v1.reason') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                        </div>

                        <div class="reco-section">
                            <div class="reco-section-header">
                                <h4>{{ __('account.audit_log') }}</h4>
                                <span class="label label-primary" id="audit_log_count_badge">0</span>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped" id="reco_audit_log_table">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>{{ __('messages.date') }}</th>
                                            <th>{{ __('messages.action') }}</th>
                                            <th>{{ __('report.user') }}</th>
                                            <th>{{ __('lang_v1.details') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                        </div>
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
                    $('#export_reco_pdf_btn').hide().attr('href', '#');
                    $('#export_reco_excel_btn').hide().attr('href', '#');
                    $('#undo_reco_btn').hide();
                    $('#finalize_reco_btn').prop('disabled', true);
                    return;
                }

                $('#reco_actions').show();

                var exportUrl = "{{ url('account/bank-reconciliation') }}/" + currentRunId + "/export";
                var exportPdfUrl = "{{ url('account/bank-reconciliation') }}/" + currentRunId + "/export/pdf";
                var exportExcelUrl = "{{ url('account/bank-reconciliation') }}/" + currentRunId + "/export/excel";
                $('#export_reco_btn').attr('href', exportUrl);
                $('#export_reco_pdf_btn').attr('href', exportPdfUrl);
                $('#export_reco_excel_btn').attr('href', exportExcelUrl);

                if (currentRunStatus === 'finalized') {
                    $('#finalize_reco_btn').prop('disabled', true).text("{{ __('lang_v1.finalized') }}");
                    $('#export_reco_btn').show();
                    $('#export_reco_pdf_btn').show();
                    $('#export_reco_excel_btn').show();
                    $('#undo_reco_btn').show();
                } else {
                    $('#finalize_reco_btn').prop('disabled', false).text("{{ __('lang_v1.finalize') }}");
                    $('#export_reco_btn').hide();
                    $('#export_reco_pdf_btn').hide();
                    $('#export_reco_excel_btn').hide();
                    $('#undo_reco_btn').hide();
                }
            }

            function renderAuditLogs(logs) {
                var rows = '';
                (logs || []).forEach(function(log, index) {
                    var afterData = log.after_data ? JSON.stringify(log.after_data) : '';
                    rows += '<tr>' +
                        '<td>' + (index + 1) + '</td>' +
                        '<td>' + escapeHtml(log.created_at || '') + '</td>' +
                        '<td>' + escapeHtml(log.action || '') + '</td>' +
                        '<td>' + escapeHtml(log.user_name || '') + '</td>' +
                        '<td>' + escapeHtml(afterData) + '</td>' +
                        '</tr>';
                });

                $('#reco_audit_log_table tbody').html(rows);
                $('#audit_log_count_badge').text((logs || []).length);
            }

            function loadAuditLogs(runId) {
                if (!runId) {
                    renderAuditLogs([]);
                    return;
                }

                $.ajax({
                    method: 'GET',
                    url: "{{ url('account/bank-reconciliation') }}/" + runId + "/audit-logs",
                    dataType: 'json',
                    success: function(result) {
                        if (!result.success) {
                            return;
                        }

                        renderAuditLogs(result.logs || []);
                    }
                });
            }

            function renderSummary(summary) {
                summary = summary || {};
                var run_id = summary.run_id ? '<div class="reco-metric"><div class="reco-metric-label">{{ __('lang_v1.id') }}</div><div class="reco-metric-value">' + escapeHtml(summary.run_id) + '</div></div>' : '';
                var run_status = summary.status ? '<div class="reco-metric"><div class="reco-metric-label">{{ __('lang_v1.status') }}</div><div class="reco-metric-value">' + escapeHtml(summary.status) + '</div></div>' : '';
                var html = '<div class="reco-summary-grid">';
                html += run_id;
                html += run_status;
                html += '<div class="reco-metric"><div class="reco-metric-label">{{ __('account.total_statement_lines') }}</div><div class="reco-metric-value">' + (summary.total_statement_lines || 0) + '</div></div>';
                html += '<div class="reco-metric"><div class="reco-metric-label">{{ __('account.total_statement_amount') }}</div><div class="reco-metric-value">' + __currency_trans_from_en(summary.total_statement_amount || 0, true) + '</div></div>';
                html += '<div class="reco-metric"><div class="reco-metric-label">{{ __('account.matched') }}</div><div class="reco-metric-value">' + (summary.matched_count || 0) + ' (' + __currency_trans_from_en(summary.total_matched_amount || 0, true) + ')</div></div>';
                html += '<div class="reco-metric"><div class="reco-metric-label">{{ __('account.ambiguous') }}</div><div class="reco-metric-value">' + (summary.ambiguous_count || 0) + '</div></div>';
                html += '<div class="reco-metric"><div class="reco-metric-label">{{ __('account.unmatched') }}</div><div class="reco-metric-value">' + (summary.unmatched_count || 0) + '</div></div>';
                html += '<div class="reco-metric"><div class="reco-metric-label">{{ __('lang_v1.invalid') }}</div><div class="reco-metric-value">' + (summary.invalid_count || 0) + '</div></div>';
                html += '<div class="reco-metric"><div class="reco-metric-label">{{ __('account.opening_balance') }}</div><div class="reco-metric-value">' + __currency_trans_from_en(summary.opening_balance || 0, true) + '</div></div>';
                html += '<div class="reco-metric"><div class="reco-metric-label">{{ __('account.statement_closing') }}</div><div class="reco-metric-value">' + __currency_trans_from_en(summary.closing_balance_statement || 0, true) + '</div></div>';
                html += '<div class="reco-metric"><div class="reco-metric-label">{{ __('account.ledger_closing') }}</div><div class="reco-metric-value">' + __currency_trans_from_en(summary.ledger_closing_balance || 0, true) + '</div></div>';
                html += '<div class="reco-metric"><div class="reco-metric-label">{{ __('account.variance') }}</div><div class="reco-metric-value">' + __currency_trans_from_en(summary.variance_amount || 0, true) + '</div></div>';
                html += '<div class="reco-metric"><div class="reco-metric-label">{{ __('account.tolerance') }}</div><div class="reco-metric-value">{{ __('account.amount_short') }}: ' + escapeHtml(summary.amount_tolerance || 0) + ' | {{ __('messages.date') }}: ' + escapeHtml(summary.date_tolerance_days || 0) + '</div></div>';
                html += '</div>';
                $('#reco_summary').html(html);

                $('#matched_count_badge').text((summary.matched_count || 0));
                $('#ambiguous_count_badge').text((summary.ambiguous_count || 0));
                $('#unmatched_count_badge').text((summary.unmatched_count || 0));
                $('#invalid_count_badge').text((summary.invalid_count || 0));
            }

            function showStatementUploadSuccessToast() {
                toastr.success(@json(__('account.statement_uploaded_successfully')));
            }

            function renderLines(lines) {
                var matched_rows = '';
                var ambiguous_rows = '';
                var unmatched_rows = '';
                var invalid_rows = '';

                (lines || []).forEach(function(line) {
                    if (line.status === 'matched') {
                        var p = line.matched_payment || {};
                        matched_rows += '<tr>' +
                            '<td>' + escapeHtml(line.statement_date || '') + '</td>' +
                            '<td>' + __currency_trans_from_en(line.statement_amount || 0, true) + '</td>' +
                            '<td>' + escapeHtml(line.description || '') + '</td>' +
                            '<td>' + escapeHtml(p.payment_ref_no || '') + '</td>' +
                            '<td>' + escapeHtml(p.invoice_no || '') + '</td>' +
                            '<td>' +
                                '<div>' + escapeHtml(p.method || p.transaction_type || '') + '</div>' +
                                '<button type="button" class="btn btn-xs btn-danger tw-mt-1 unmatch-line-btn" data-line-id="' + line.id + '">Unmatch</button>' +
                            '</td>' +
                            '</tr>';
                    } else if (line.status === 'ambiguous') {
                        var options = '<option value="">{{ __('account.select_payment') }}</option>';
                        (line.candidates || []).forEach(function(c) {
                            var label = (c.payment_ref_no || c.invoice_no || ('#' + c.id)) + ' | ' + (c.paid_on || '') + ' | ' + __currency_trans_from_en(c.amount || 0, true);
                            options += '<option value="' + c.id + '">' + escapeHtml(label) + '</option>';
                        });

                        ambiguous_rows += '<tr>' +
                            '<td>' + escapeHtml(line.statement_date || '') + '</td>' +
                            '<td>' + __currency_trans_from_en(line.statement_amount || 0, true) + '</td>' +
                            '<td>' + escapeHtml(line.description || '') + '</td>' +
                            '<td>' + escapeHtml(line.reference || '') + '</td>' +
                            '<td>' +
                                '<select class="form-control input-sm candidate-select" data-line-id="' + line.id + '">' + options + '</select>' +
                                '<button type="button" class="btn btn-xs btn-primary tw-mt-1 match-line-btn" data-line-id="' + line.id + '">{{ __('account.manual_match') }}</button>' +
                            '</td>' +
                            '</tr>';
                    } else if (line.status === 'unmatched') {
                        var suggestion = line.suggested_transaction || null;
                        var actionHtml = '';
                        if (suggestion && suggestion.transaction_id) {
                            actionHtml = '<div class="tw-mt-1 text-muted">{{ __('account.suggested_sale') }}: ' + escapeHtml(suggestion.invoice_no || ('#' + suggestion.transaction_id)) + ' | {{ __('account.due') }}: ' + __currency_trans_from_en(suggestion.due_amount || 0, true) + '</div>' +
                                '<button type="button" class="btn btn-xs btn-success tw-mt-1 create-payment-btn" data-line-id="' + line.id + '">{{ __('account.create_missing_mpesa_payment') }}</button>';
                        }

                        unmatched_rows += '<tr>' +
                            '<td>' + escapeHtml(line.statement_date || '') + '</td>' +
                            '<td>' + __currency_trans_from_en(line.statement_amount || 0, true) + '</td>' +
                            '<td>' + escapeHtml(line.description || '') + '</td>' +
                            '<td>' + escapeHtml(line.reference || '') + actionHtml + '</td>' +
                            '</tr>';
                    } else if (line.status === 'invalid') {
                        invalid_rows += '<tr>' +
                            '<td>' + escapeHtml(line.line_no || '') + '</td>' +
                            '<td>' + escapeHtml(line.statement_date || '') + '</td>' +
                            '<td>' + __currency_trans_from_en(line.statement_amount || 0, true) + '</td>' +
                            '<td>' + escapeHtml(line.description || '') + '</td>' +
                            '<td>' + escapeHtml(line.reference || '') + '</td>' +
                            '<td>' + (line.is_duplicate ? '{{ __('account.duplicate_statement_line') }}' : '') + '</td>' +
                            '</tr>';
                    }
                });

                $('#reco_matched_table tbody').html(matched_rows);
                $('#reco_ambiguous_table tbody').html(ambiguous_rows);
                $('#reco_unmatched_table tbody').html(unmatched_rows);
                $('#reco_invalid_table tbody').html(invalid_rows);
            }

            function loadRunDetails(runId) {
                if (!runId) {
                    return;
                }

                $.ajax({
                    method: 'GET',
                    url: "{{ url('account/bank-reconciliation') }}/" + runId + "/details",
                    dataType: 'json',
                    success: function(result) {
                        if (!result.success) {
                            return;
                        }

                        var run = result.run || {};
                        renderSummary({
                            run_id: run.id,
                            status: run.status,
                            total_statement_lines: run.total_statement_lines,
                            total_statement_amount: run.total_statement_amount,
                            matched_count: run.matched_count,
                            ambiguous_count: run.ambiguous_count,
                            unmatched_count: run.unmatched_count,
                            invalid_count: run.invalid_count,
                            total_matched_amount: run.total_matched_amount,
                            opening_balance: run.opening_balance,
                            closing_balance_statement: run.closing_balance_statement,
                            ledger_closing_balance: run.ledger_closing_balance,
                            variance_amount: run.variance_amount,
                            amount_tolerance: run.amount_tolerance,
                            date_tolerance_days: run.date_tolerance_days
                        });
                        renderLines(result.lines || []);
                        setRunActions(run.id, run.status || 'completed');
                        loadAuditLogs(run.id);
                    }
                });
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

                        var summary = result.summary || {};
                        showStatementUploadSuccessToast();
                        renderSummary(summary);
                        setRunActions(summary.run_id || null, summary.status || 'completed');
                        loadRunDetails(summary.run_id || null);
                    },
                    error: function(xhr) {
                        toastr.error(extractAjaxErrorMessage(xhr));
                    }
                });
            });

            var urlParams = new URLSearchParams(window.location.search);
            var initialRunId = urlParams.get('run_id');
            var uploadedFromRedirect = urlParams.get('uploaded');

            if (uploadedFromRedirect === '1') {
                showStatementUploadSuccessToast();
            }

            if (initialRunId) {
                loadRunDetails(initialRunId);
            }

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
                        loadRunDetails(result.run_id || currentRunId);
                    },
                    error: function() {
                        toastr.error('{{ __('messages.something_went_wrong') }}');
                    }
                });
            });

            $('#undo_reco_btn').on('click', function() {
                if (!currentRunId) {
                    toastr.error("{{ __('account.no_reconciliation_run_selected') }}");
                    return;
                }

                $.ajax({
                    method: 'POST',
                    url: "{{ url('account/bank-reconciliation') }}/" + currentRunId + "/undo",
                    dataType: 'json',
                    success: function(result) {
                        if (!result.success) {
                            toastr.error(result.msg || '{{ __('messages.something_went_wrong') }}');
                            return;
                        }

                        toastr.success(result.msg || @json(__('account.reconciliation_undo_success')));
                        loadRunDetails(result.run_id || currentRunId);
                    },
                    error: function() {
                        toastr.error('{{ __('messages.something_went_wrong') }}');
                    }
                });
            });

            $(document).on('click', '.match-line-btn', function() {
                if (!currentRunId || currentRunStatus === 'finalized') {
                    return;
                }

                var lineId = $(this).data('line-id');
                var paymentId = $('.candidate-select[data-line-id="' + lineId + '"]').val();
                if (!paymentId) {
                    toastr.error(@json(__('account.select_payment_first')));
                    return;
                }

                $.ajax({
                    method: 'POST',
                    url: "{{ url('account/bank-reconciliation') }}/" + currentRunId + "/lines/" + lineId + "/manual-match",
                    data: {
                        payment_id: paymentId
                    },
                    dataType: 'json',
                    success: function(result) {
                        if (!result.success) {
                            toastr.error(result.msg || '{{ __('messages.something_went_wrong') }}');
                            return;
                        }

                        toastr.success(result.msg || @json(__('account.line_matched')));
                        loadRunDetails(currentRunId);
                    },
                    error: function() {
                        toastr.error('{{ __('messages.something_went_wrong') }}');
                    }
                });
            });

            $(document).on('click', '.unmatch-line-btn', function() {
                if (!currentRunId || currentRunStatus === 'finalized') {
                    return;
                }

                var lineId = $(this).data('line-id');
                $.ajax({
                    method: 'POST',
                    url: "{{ url('account/bank-reconciliation') }}/" + currentRunId + "/lines/" + lineId + "/manual-unmatch",
                    dataType: 'json',
                    success: function(result) {
                        if (!result.success) {
                            toastr.error(result.msg || '{{ __('messages.something_went_wrong') }}');
                            return;
                        }

                        toastr.success(result.msg || @json(__('account.line_unmatched')));
                        loadRunDetails(currentRunId);
                    },
                    error: function() {
                        toastr.error('{{ __('messages.something_went_wrong') }}');
                    }
                });
            });

            $(document).on('click', '.create-payment-btn', function() {
                if (!currentRunId || currentRunStatus === 'finalized') {
                    return;
                }

                var lineId = $(this).data('line-id');
                $.ajax({
                    method: 'POST',
                    url: "{{ url('account/bank-reconciliation') }}/" + currentRunId + "/lines/" + lineId + "/create-payment",
                    dataType: 'json',
                    success: function(result) {
                        if (!result.success) {
                            toastr.error(result.msg || '{{ __('messages.something_went_wrong') }}');
                            return;
                        }

                        toastr.success(result.msg || @json(__('account.payment_created_successfully')));
                        loadRunDetails(currentRunId);
                    },
                    error: function() {
                        toastr.error('{{ __('messages.something_went_wrong') }}');
                    }
                });
            });
        });
    </script>
@endsection
