@extends('layouts.app')
@section('title', __('account.journal_entry'))

@section('content')
<section class="content-header">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">@lang('account.journal_entry')</h1>
</section>

<section class="content no-print">
    <div class="row">
        <div class="col-md-12">
            @component('components.widget')
                <p class="text-muted">{{ __('account.journal_entry_help') }}</p>

                {!! Form::open(['url' => action([\App\Http\Controllers\AccountReportsController::class, 'storeJournalEntry']), 'method' => 'post', 'id' => 'journal_entry_form']) !!}
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                {!! Form::label('debit_account_id', __('account.debit') . ':*') !!}
                                {!! Form::select('debit_account_id', $accounts, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'required']) !!}
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                {!! Form::label('credit_account_id', __('account.credit') . ':*') !!}
                                {!! Form::select('credit_account_id', $accounts, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'required']) !!}
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                {!! Form::label('amount', __('sale.amount') . ':*') !!}
                                {!! Form::text('amount', null, ['class' => 'form-control input_number', 'required']) !!}
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                {!! Form::label('operation_date', __('messages.date') . ':*') !!}
                                {!! Form::text('operation_date', @format_date('now'), ['class' => 'form-control', 'id' => 'journal_operation_date', 'readonly', 'required']) !!}
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group" style="margin-top:25px;">
                                <button type="submit" class="btn btn-primary btn-block">@lang('messages.save')</button>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                {!! Form::label('note', __('brand.note')) !!}
                                {!! Form::textarea('note', null, ['class' => 'form-control', 'rows' => 3]) !!}
                            </div>
                        </div>
                    </div>
                {!! Form::close() !!}
            @endcomponent
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            @component('components.widget')
                <div class="table-responsive">
                    <table class="table table-bordered table-striped" id="journal_entry_table">
                        <thead>
                            <tr>
                                <th>@lang('messages.date')</th>
                                <th>@lang('account.account')</th>
                                <th>@lang('account.transaction_type')</th>
                                <th>@lang('account.debit')</th>
                                <th>@lang('account.credit')</th>
                                <th>@lang('lang_v1.description')</th>
                                <th>@lang('lang_v1.added_by')</th>
                                <th>@lang('messages.action')</th>
                            </tr>
                        </thead>
                        <tfoot>
                            <tr class="bg-gray font-17 text-center">
                                <td colspan="3"><strong>@lang('sale.total'):</strong></td>
                                <td class="footer_total_debit"></td>
                                <td class="footer_total_credit"></td>
                                <td colspan="3"></td>
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
        $('#journal_operation_date').datepicker({
            autoclose: true,
            format: datepicker_date_format
        });

        journal_entry_table = $('#journal_entry_table').DataTable({
            processing: true,
            serverSide: true,
            fixedHeader: false,
            ajax: {
                url: "{{ action([\App\Http\Controllers\AccountReportsController::class, 'journalEntry']) }}",
                data: function(d) {
                    if ($('#journal_operation_date').val()) {
                        d.start_date = $('#journal_operation_date').val();
                        d.end_date = $('#journal_operation_date').val();
                    }
                }
            },
            columns: [
                { data: 'operation_date', name: 'account_transactions.operation_date' },
                { data: 'account', name: 'A.name' },
                { data: 'sub_type', name: 'account_transactions.sub_type' },
                { data: 'debit', name: 'account_transactions.amount', searchable: false },
                { data: 'credit', name: 'account_transactions.amount', searchable: false },
                { data: 'note', name: 'account_transactions.note' },
                { data: 'added_by', name: 'added_by' },
                { data: 'action', name: 'action', orderable: false, searchable: false },
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
                __currency_convert_recursively($('#journal_entry_table'));
            }
        });

        $('#journal_entry_form').on('submit', function(e) {
            e.preventDefault();
            $.ajax({
                method: 'POST',
                url: $(this).attr('action'),
                data: $(this).serialize(),
                dataType: 'json',
                success: function(result) {
                    if (result.success) {
                        toastr.success(result.msg || '{{ __('messages.success') }}');
                        journal_entry_table.ajax.reload();
                        $('#journal_entry_form')[0].reset();
                    } else {
                        toastr.error(result.msg || '{{ __('messages.something_went_wrong') }}');
                    }
                },
                error: function() {
                    toastr.error('{{ __('messages.something_went_wrong') }}');
                }
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
                        if (result.success) {
                            toastr.success(result.msg);
                            journal_entry_table.ajax.reload();
                        } else {
                            toastr.error(result.msg);
                        }
                    }
                });
            });
        });
    });
</script>
@endsection
