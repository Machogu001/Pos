@extends('layouts.app')
@section('title', __('account.chart_of_accounts'))

@section('content')

<section class="content-header">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">@lang('account.chart_of_accounts')</h1>
</section>

<section class="content no-print">
    <div class="row">
        <div class="col-md-12">
            @component('components.filters', ['title' => __('report.filters')])
                <div class="col-md-4">
                    <div class="form-group">
                        {!! Form::label('account_status', __('business.is_active') . ' / ' . __('account.closed') . ':') !!}
                        {!! Form::select('account_status', ['active' => __('business.is_active'), 'closed' => __('account.closed')], 'active', ['class' => 'form-control select2', 'style' => 'width:100%']) !!}
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group" style="margin-top:25px;">
                        <a href="{{ action([\App\Http\Controllers\AccountController::class, 'index']) }}" class="btn btn-default">
                            <i class="fa fa-cogs"></i> @lang('account.list_accounts')
                        </a>
                    </div>
                </div>
            @endcomponent
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            @component('components.widget')
                <p class="text-muted" style="margin-bottom: 15px;">
                    {{ __('account.chart_of_accounts_help') }}
                </p>

                <div class="table-responsive">
                    <table class="table table-bordered table-striped" id="chart_of_accounts_table">
                        <thead>
                            <tr>
                                <th>@lang('lang_v1.name')</th>
                                <th>@lang('lang_v1.account_type')</th>
                                <th>@lang('lang_v1.account_sub_type')</th>
                                <th>@lang('account.account_number')</th>
                                <th>@lang('brand.note')</th>
                                <th>@lang('lang_v1.balance')</th>
                                <th>@lang('lang_v1.added_by')</th>
                                <th>@lang('messages.action')</th>
                            </tr>
                        </thead>
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
        chart_of_accounts_table = $('#chart_of_accounts_table').DataTable({
            processing: true,
            serverSide: true,
            fixedHeader: false,
            ajax: {
                url: "{{ action([\App\Http\Controllers\AccountReportsController::class, 'chartOfAccounts']) }}",
                data: function(d) {
                    d.account_status = $('#account_status').val();
                }
            },
            columns: [
                { data: 'name', name: 'accounts.name' },
                { data: 'account_type', name: 'ats.name' },
                { data: 'parent_account_type_name', name: 'pat.name' },
                { data: 'account_number', name: 'accounts.account_number' },
                { data: 'note', name: 'accounts.note' },
                { data: 'balance', name: 'balance', searchable: false },
                { data: 'added_by', name: 'added_by' },
                { data: 'action', name: 'action', orderable: false, searchable: false },
            ],
            fnDrawCallback: function() {
                __currency_convert_recursively($('#chart_of_accounts_table'));
            }
        });

        $('#account_status').change(function() {
            chart_of_accounts_table.ajax.reload();
        });
    });
</script>
@endsection
