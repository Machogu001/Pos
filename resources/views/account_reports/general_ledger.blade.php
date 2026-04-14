@extends('layouts.app')
@section('title', __('account.general_ledger'))

@section('content')
<section class="content-header">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">@lang('account.general_ledger')</h1>
</section>

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
                        {!! Form::label('account_status', __('business.is_active') . ' / ' . __('account.closed') . ':') !!}
                        {!! Form::select('account_status', ['all' => __('lang_v1.all'), 'active' => __('business.is_active'), 'closed' => __('account.closed')], 'all', ['class' => 'form-control select2', 'style' => 'width:100%']) !!}
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('transaction_type', __('account.transaction_type') . ':') !!}
                        {!! Form::select('transaction_type', ['' => __('messages.all'), 'debit' => __('account.debit'), 'credit' => __('account.credit')], null, ['class' => 'form-control select2', 'style' => 'width:100%']) !!}
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('date_range', __('report.date_range') . ':') !!}
                        {!! Form::text('date_range', null, ['class' => 'form-control', 'readonly', 'placeholder' => __('lang_v1.select_a_date_range')]) !!}
                    </div>
                </div>
            @endcomponent
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            @component('components.widget')
                <p class="text-muted">{{ __('account.general_ledger_help') }}</p>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped" id="general_ledger_table">
                        <thead>
                            <tr>
                                <th>@lang('messages.date')</th>
                                <th>@lang('account.account')</th>
                                <th>@lang('account.transaction_type')</th>
                                <th>@lang('lang_v1.payment_method')</th>
                                <th>@lang('lang_v1.payment_details')</th>
                                <th>@lang('lang_v1.description')</th>
                                <th>@lang('account.debit')</th>
                                <th>@lang('account.credit')</th>
                                <th>@lang('lang_v1.added_by')</th>
                                <th>@lang('messages.action')</th>
                            </tr>
                        </thead>
                        <tfoot>
                            <tr class="bg-gray font-17 text-center">
                                <td colspan="6"><strong>@lang('sale.total'):</strong></td>
                                <td class="footer_total_debit"></td>
                                <td class="footer_total_credit"></td>
                                <td colspan="2"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            @endcomponent
        </div>
    </div>
</section>
@endsection

@section('javascript')
<script>
    $(document).ready(function() {
        if ($('#date_range').length == 1) {
            $('#date_range').daterangepicker(dateRangeSettings, function(start, end) {
                $('#date_range').val(start.format(moment_date_format) + ' ~ ' + end.format(moment_date_format));
                general_ledger_table.ajax.reload();
            });

            $('#date_range').on('cancel.daterangepicker', function() {
                $(this).val('');
                general_ledger_table.ajax.reload();
            });
        }

        general_ledger_table = $('#general_ledger_table').DataTable({
            processing: true,
            serverSide: true,
            fixedHeader: false,
            ajax: {
                url: "{{ action([\App\Http\Controllers\AccountReportsController::class, 'generalLedger']) }}",
                data: function(d) {
                    d.account_id = $('#account_id').val();
                    d.account_status = $('#account_status').val();
                    d.transaction_type = $('#transaction_type').val();
                    if ($('#date_range').val()) {
                        d.start_date = $('#date_range').data('daterangepicker').startDate.format('YYYY-MM-DD');
                        d.end_date = $('#date_range').data('daterangepicker').endDate.format('YYYY-MM-DD');
                    }
                }
            },
            columns: [
                { data: 'operation_date', name: 'account_transactions.operation_date' },
                { data: 'account', name: 'A.name' },
                { data: 'sub_type', name: 'account_transactions.sub_type' },
                { data: 'payment_method', name: 'tp.method' },
                { data: 'payment_details', name: 'payment_details', searchable: false, orderable: false },
                { data: 'note', name: 'account_transactions.note' },
                { data: 'debit', name: 'account_transactions.amount', searchable: false },
                { data: 'credit', name: 'account_transactions.amount', searchable: false },
                { data: 'added_by', name: 'added_by' },
                { data: 'action', name: 'action', searchable: false, orderable: false },
            ],
            footerCallback: function(row, data) {
                var total_debit = 0;
                var total_credit = 0;

                for (var i in data) {
                    total_debit += $(data[i].debit).data('orig-value') ? parseFloat($(data[i].debit).data('orig-value')) : 0;
                    total_credit += $(data[i].credit).data('orig-value') ? parseFloat($(data[i].credit).data('orig-value')) : 0;
                }

                $('.footer_total_debit').html(__currency_trans_from_en(total_debit, true));
                $('.footer_total_credit').html(__currency_trans_from_en(total_credit, true));
            },
            fnDrawCallback: function() {
                __currency_convert_recursively($('#general_ledger_table'));
            }
        });

        $('#account_id, #account_status, #transaction_type').change(function() {
            general_ledger_table.ajax.reload();
        });
    });

    $(document).on('click', '.delete_account_transaction', function(e) {
        e.preventDefault();
        swal({
            title: LANG.sure,
            icon: 'warning',
            buttons: true,
            dangerMode: true,
        }).then((willDelete) => {
            if (!willDelete) return;

            $.ajax({
                url: $(this).data('href'),
                dataType: 'json',
                success: function(result) {
                    if (result.success === true) {
                        toastr.success(result.msg);
                        general_ledger_table.ajax.reload();
                    } else {
                        toastr.error(result.msg);
                    }
                }
            });
        });
    });
</script>
@endsection
