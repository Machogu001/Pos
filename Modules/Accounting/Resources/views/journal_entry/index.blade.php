@extends('accounting::layouts.app')
@section('title')
    {{ trans_choice('accounting::general.journal', 1) }} {{ trans_choice('accounting::lang.entry', 2) }}
@endsection
@section('css')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-beta.1/dist/css/select2.min.css" rel="stylesheet" />
@stop

@section('content')

    @include('accounting::layouts.nav')
    <!-- Content Header (Page header) -->
    @component('accounting::components.section_header')
        @slot('title')
            {{ trans_choice('accounting::general.accounting', 1) }}
        @endslot
        @slot('subtitle')
            {{ trans_choice('accounting::general.journal', 1) }} {{ trans_choice('accounting::lang.entry', 2) }}
        @endslot
    @endcomponent

    <!-- Main content -->
    <section class="content no-print" id="vue-app">

        <div class="row">
            <div class="col-md-12">
                @component('accounting::components.filters', ['title' => __('report.filters')])
                    <form method="get" action="{{ Request::url() }}" id="journal-filter-form">
                        <div class="modal-body">

                            <div class="form-group @if (!count($_GET) > 0) d-none @endif">
                                <a class="btn btn-sm btn-success" href="{{ Request::url() }}">
                                    <i class="fa fa-filter"></i>
                                    {{ trans_choice('accounting::lang.clear', 1) }} {{ trans_choice('accounting::lang.filter', 2) }}
                                </a>
                            </div>

                            <div class="form-group">
                                <label for="chart_of_account_id" class="control-label">{{ trans_choice('accounting::general.account', 1) }}</label>
                                <!--<v-select v-model="chart_of_account_id" :options="chart_of_accounts" :reduce="chart_of_accounts => chart_of_accounts.id" label="name"/>-->
                                <select class="form-control select2" name="chart_of_account_id" v-model="chart_of_account_id"
                                    style="width: 100%; height: calc(2.25rem + 2px)">
                                    <option value="">All accounts</option>
                                    <option v-for="account in chart_of_accounts" :value="account.id">@{{ account.name }}</option>
                                </select>
                            </div>

                            <!--<input type="hidden" name="chart_of_account_id" :value="chart_of_account_id">-->

                            <div class="row">
                                <div class="form-group col-md-6">
                                    <label for="start_date" class="control-label">{{ trans_choice('accounting::lang.start_date', 1) }}</label>
                                    <input class="form-control datepicker" v-model="start_date" type="text" name="start_date" id="start_date"
                                        readonly>
                                    @error('start_date')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>

                                <div class="form-group col-md-6">
                                    <label for="end_date" class="control-label">{{ trans_choice('accounting::lang.end_date', 1) }}</label>
                                    <input class="form-control datepicker" v-model="end_date" type="text" name="end_date" id="end_date"
                                        readonly>
                                    @error('end_date')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>


                        </div>
                        <div class="modal-footer">
                            <button type="submit" class="btn btn-primary">{{ trans_choice('accounting::lang.filter', 1) }}</button>
                        </div>
                    </form>
                @endcomponent
            </div>
        </div>

        @can('product.view')
            <div class="row">

                @component('accounting::components.box')
                    @slot('header')
                        <div class="box-tools">
                            @can('accounting.journal_entries.create')
                                <a href="{{ url('accounting/journal_entry/create') }}" class="btn btn-primary">
                                    <i class="fas fa-plus"></i> {{ trans_choice('accounting::lang.add', 1) }}
                                </a>
                            @endcan
                        </div>
                    @endslot

                    @slot('body')
                        <section class="content">
                            @php
                                $currentOrderBy = Request::get('order_by', 'date');
                                $currentOrderDir = strtolower(Request::get('order_by_dir', 'desc')) === 'asc' ? 'asc' : 'desc';
                            @endphp
                            <div class="card">
                                <div class="card-body p-0">
                                    <div class="row" style="margin: 10px 0;">
                                        <div class="col-sm-6">
                                            <form id="per-page-form" method="get" action="{{ Request::url() }}" class="form-inline">
                                                @foreach (request()->except(['per_page', 'page']) as $paramKey => $paramValue)
                                                    @if (is_array($paramValue))
                                                        @foreach ($paramValue as $singleValue)
                                                            <input type="hidden" name="{{ $paramKey }}[]" value="{{ $singleValue }}">
                                                        @endforeach
                                                    @else
                                                        <input type="hidden" name="{{ $paramKey }}" value="{{ $paramValue }}">
                                                    @endif
                                                @endforeach
                                                <label for="per_page" style="margin-right: 8px;">{{ __('Show') }}</label>
                                                <select class="form-control input-sm" name="per_page" id="per_page" style="width: auto; display: inline-block;">
                                                    <option value="10" {{ (int) Request::get('per_page', $perPage ?? 20) === 10 ? 'selected' : '' }}>10</option>
                                                    <option value="20" {{ (int) Request::get('per_page', $perPage ?? 20) === 20 ? 'selected' : '' }}>20</option>
                                                    <option value="50" {{ (int) Request::get('per_page', $perPage ?? 20) === 50 ? 'selected' : '' }}>50</option>
                                                    <option value="100" {{ (int) Request::get('per_page', $perPage ?? 20) === 100 ? 'selected' : '' }}>100</option>
                                                </select>
                                                <span style="margin-left: 8px;">{{ __('entries') }}</span>
                                            </form>
                                        </div>
                                    </div>

                                    <div class="table-responsive">
                                        <table id="data-table" class="table table-striped table-hover">
                                            <thead>
                                                <tr>
                                                    <th>{{ trans_choice('accounting::lang.action', 1) }}</th>
                                                    <th>
                                                        @php $nextDir = ($currentOrderBy === 'id' && $currentOrderDir === 'asc') ? 'desc' : 'asc'; @endphp
                                                        <a href="{{ request()->fullUrlWithQuery(['order_by' => 'id', 'order_by_dir' => $nextDir, 'page' => 1]) }}">
                                                            {{ trans_choice('accounting::lang.entry', 1) }} {{ trans_choice('accounting::lang.id', 1) }}
                                                            @if ($currentOrderBy === 'id')
                                                                <i class="fa fa-sort-{{ $currentOrderDir === 'asc' ? 'asc' : 'desc' }}"></i>
                                                            @else
                                                                <i class="fa fa-sort text-muted"></i>
                                                            @endif
                                                        </a>
                                                    </th>
                                                    <th>
                                                        @php $nextDir = ($currentOrderBy === 'business_location' && $currentOrderDir === 'asc') ? 'desc' : 'asc'; @endphp
                                                        <a href="{{ request()->fullUrlWithQuery(['order_by' => 'business_location', 'order_by_dir' => $nextDir, 'page' => 1]) }}">
                                                            {{ trans_choice('accounting::lang.business_location', 1) }}
                                                            @if ($currentOrderBy === 'business_location')
                                                                <i class="fa fa-sort-{{ $currentOrderDir === 'asc' ? 'asc' : 'desc' }}"></i>
                                                            @else
                                                                <i class="fa fa-sort text-muted"></i>
                                                            @endif
                                                        </a>
                                                    </th>
                                                    <th>
                                                        @php $nextDir = ($currentOrderBy === 'date' && $currentOrderDir === 'asc') ? 'desc' : 'asc'; @endphp
                                                        <a href="{{ request()->fullUrlWithQuery(['order_by' => 'date', 'order_by_dir' => $nextDir, 'page' => 1]) }}">
                                                            {{ trans_choice('accounting::lang.transaction', 1) }}
                                                            {{ trans_choice('accounting::lang.date', 1) }}
                                                            @if ($currentOrderBy === 'date')
                                                                <i class="fa fa-sort-{{ $currentOrderDir === 'asc' ? 'asc' : 'desc' }}"></i>
                                                            @else
                                                                <i class="fa fa-sort text-muted"></i>
                                                            @endif
                                                        </a>
                                                    </th>
                                                    <th>
                                                        @php $nextDir = ($currentOrderBy === 'transaction_number' && $currentOrderDir === 'asc') ? 'desc' : 'asc'; @endphp
                                                        <a href="{{ request()->fullUrlWithQuery(['order_by' => 'transaction_number', 'order_by_dir' => $nextDir, 'page' => 1]) }}">
                                                            {{ trans_choice('accounting::lang.transaction', 1) }}#
                                                            @if ($currentOrderBy === 'transaction_number')
                                                                <i class="fa fa-sort-{{ $currentOrderDir === 'asc' ? 'asc' : 'desc' }}"></i>
                                                            @else
                                                                <i class="fa fa-sort text-muted"></i>
                                                            @endif
                                                        </a>
                                                    </th>
                                                    <th>
                                                        @php $nextDir = ($currentOrderBy === 'account_type' && $currentOrderDir === 'asc') ? 'desc' : 'asc'; @endphp
                                                        <a href="{{ request()->fullUrlWithQuery(['order_by' => 'account_type', 'order_by_dir' => $nextDir, 'page' => 1]) }}">
                                                            {{ trans_choice('accounting::lang.type', 1) }}
                                                            @if ($currentOrderBy === 'account_type')
                                                                <i class="fa fa-sort-{{ $currentOrderDir === 'asc' ? 'asc' : 'desc' }}"></i>
                                                            @else
                                                                <i class="fa fa-sort text-muted"></i>
                                                            @endif
                                                        </a>
                                                    </th>
                                                    <th>
                                                        @php $nextDir = ($currentOrderBy === 'account_name' && $currentOrderDir === 'asc') ? 'desc' : 'asc'; @endphp
                                                        <a href="{{ request()->fullUrlWithQuery(['order_by' => 'account_name', 'order_by_dir' => $nextDir, 'page' => 1]) }}">
                                                            {{ trans_choice('accounting::lang.account', 1) }}
                                                            @if ($currentOrderBy === 'account_name')
                                                                <i class="fa fa-sort-{{ $currentOrderDir === 'asc' ? 'asc' : 'desc' }}"></i>
                                                            @else
                                                                <i class="fa fa-sort text-muted"></i>
                                                            @endif
                                                        </a>
                                                    </th>
                                                    <th>
                                                        @php $nextDir = ($currentOrderBy === 'account_subtype' && $currentOrderDir === 'asc') ? 'desc' : 'asc'; @endphp
                                                        <a href="{{ request()->fullUrlWithQuery(['order_by' => 'account_subtype', 'order_by_dir' => $nextDir, 'page' => 1]) }}">
                                                            {{ trans_choice('accounting::general.account_subtype', 1) }}
                                                            @if ($currentOrderBy === 'account_subtype')
                                                                <i class="fa fa-sort-{{ $currentOrderDir === 'asc' ? 'asc' : 'desc' }}"></i>
                                                            @else
                                                                <i class="fa fa-sort text-muted"></i>
                                                            @endif
                                                        </a>
                                                    </th>
                                                    <th>
                                                        @php $nextDir = ($currentOrderBy === 'account_detail_type' && $currentOrderDir === 'asc') ? 'desc' : 'asc'; @endphp
                                                        <a href="{{ request()->fullUrlWithQuery(['order_by' => 'account_detail_type', 'order_by_dir' => $nextDir, 'page' => 1]) }}">
                                                            {{ trans_choice('accounting::lang.account', 1) }}
                                                            {{ trans_choice('accounting::general.detail_type', 1) }}
                                                            @if ($currentOrderBy === 'account_detail_type')
                                                                <i class="fa fa-sort-{{ $currentOrderDir === 'asc' ? 'asc' : 'desc' }}"></i>
                                                            @else
                                                                <i class="fa fa-sort text-muted"></i>
                                                            @endif
                                                        </a>
                                                    </th>
                                                    <th style="text-align: right">
                                                        @php $nextDir = ($currentOrderBy === 'debit' && $currentOrderDir === 'asc') ? 'desc' : 'asc'; @endphp
                                                        <a href="{{ request()->fullUrlWithQuery(['order_by' => 'debit', 'order_by_dir' => $nextDir, 'page' => 1]) }}" style="white-space: nowrap;">
                                                            {{ trans_choice('accounting::general.debit', 1) }}
                                                            @if ($currentOrderBy === 'debit')
                                                                <i class="fa fa-sort-{{ $currentOrderDir === 'asc' ? 'asc' : 'desc' }}"></i>
                                                            @else
                                                                <i class="fa fa-sort text-muted"></i>
                                                            @endif
                                                        </a>
                                                    </th>
                                                    <th style="text-align: right">
                                                        @php $nextDir = ($currentOrderBy === 'credit' && $currentOrderDir === 'asc') ? 'desc' : 'asc'; @endphp
                                                        <a href="{{ request()->fullUrlWithQuery(['order_by' => 'credit', 'order_by_dir' => $nextDir, 'page' => 1]) }}" style="white-space: nowrap;">
                                                            {{ trans_choice('accounting::general.credit', 1) }}
                                                            @if ($currentOrderBy === 'credit')
                                                                <i class="fa fa-sort-{{ $currentOrderDir === 'asc' ? 'asc' : 'desc' }}"></i>
                                                            @else
                                                                <i class="fa fa-sort text-muted"></i>
                                                            @endif
                                                        </a>
                                                    </th>
                                                    <th>
                                                        @php $nextDir = ($currentOrderBy === 'created_by' && $currentOrderDir === 'asc') ? 'desc' : 'asc'; @endphp
                                                        <a href="{{ request()->fullUrlWithQuery(['order_by' => 'created_by', 'order_by_dir' => $nextDir, 'page' => 1]) }}">
                                                            {{ trans_choice('accounting::lang.created_by', 1) }}
                                                            @if ($currentOrderBy === 'created_by')
                                                                <i class="fa fa-sort-{{ $currentOrderDir === 'asc' ? 'asc' : 'desc' }}"></i>
                                                            @else
                                                                <i class="fa fa-sort text-muted"></i>
                                                            @endif
                                                        </a>
                                                    </th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($data as $key)
                                                    <tr>
                                                        <td>
                                                            <div class="btn-group">
                                                                <button href="#" class="btn btn-info dropdown-toggle btn-xs" data-toggle="dropdown"
                                                                    aria-expanded="false">{{ trans_choice('accounting::lang.action', 1) }}
                                                                    <span class="caret"></span><span class="sr-only"></span>
                                                                </button>
                                                                <div class="dropdown-menu dropdown-menu-left">
                                                                    <a href="{{ url('accounting/journal_entry/' . $key->transaction_number . '/show') }}"
                                                                        class="dropdown-item">
                                                                        <i class="fas fa-eye"></i>
                                                                        <span>{{ trans_choice('accounting::lang.detail', 2) }}</span>
                                                                    </a>
                                                                </div>
                                                            </div>
                                                        </td>

                                                        <td>
                                                            <a href="{{ url('accounting/journal_entry/' . $key->transaction_number . '/show') }}">
                                                                <span>{{ $key->id }}</span> <i class="fas fa-external-link-alt"></i>
                                                            </a>
                                                        </td>
                                                        <td>
                                                            <span>{{ $key->business_location }}</span>
                                                        </td>
                                                        <td>
                                                            <span>{{ $key->date }}</span>
                                                        </td>
                                                        <td>
                                                            <span>{{ $key->transaction_number }}</span>
                                                        </td>
                                                        <td>
                                                            @if ($key->account_type == 'asset')
                                                                <span>{{ trans_choice('accounting::general.asset', 1) }}</span>
                                                            @elseif ($key->account_type == 'expense')
                                                                <span>{{ trans_choice('accounting::general.expense', 1) }}</span>
                                                            @elseif ($key->account_type == 'equity')
                                                                <span>{{ trans_choice('accounting::general.equity', 1) }}</span>
                                                            @elseif ($key->account_type == 'liability')
                                                                <span>{{ trans_choice('accounting::general.liability', 1) }}</span>
                                                            @elseif ($key->account_type == 'income')
                                                                <span>{{ trans_choice('accounting::general.income', 1) }}</span>
                                                            @endif
                                                        </td>
                                                        <td>
                                                            <span>{{ $key->account_name }}</span>
                                                        </td>
                                                        <td>
                                                            <span>{{ $key->account_subtype }}</span>
                                                        </td>
                                                        <td>
                                                            <span>{{ $key->account_detail_type }}</span>
                                                        </td>
                                                        <td style="text-align: right">
                                                            <span>{{ number_format($key->debit) }}</span>
                                                        </td>
                                                        <td style="text-align: right">
                                                            <span>{{ number_format($key->credit) }}</span>
                                                        </td>
                                                        <td>
                                                            <a href="{{ url('users/' . $key->created_by_id) }}">
                                                                <span>{{ $key->created_by }}</span> <i class="fas fa-external-link-alt"></i>
                                                            </a>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>

                                    @if (method_exists($data, 'links'))
                                        <div class="row" style="margin-top: 15px;">
                                            <div class="col-sm-6">
                                                <p class="text-muted" style="margin: 8px 0 0;">
                                                    @if (method_exists($data, 'total'))
                                                        {{ __('Showing') }} {{ $data->firstItem() ?? 0 }} - {{ $data->lastItem() ?? 0 }} {{ __('of') }} {{ $data->total() }}
                                                    @else
                                                        {{ __('Showing') }} {{ $data->firstItem() ?? 0 }} - {{ $data->lastItem() ?? 0 }}
                                                    @endif
                                                </p>
                                            </div>
                                            <div class="col-sm-6 text-right">
                                                <span class="text-muted" style="margin-right: 10px;">
                                                    @if (method_exists($data, 'lastPage'))
                                                        {{ __('Page') }} {{ $data->currentPage() }} {{ __('of') }} {{ $data->lastPage() }}
                                                    @else
                                                        {{ __('Page') }} {{ $data->currentPage() }}
                                                    @endif
                                                </span>
                                                {{ $data->links() }}
                                            </div>
                                        </div>
                                    @endif
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
        var app = new Vue({
            el: "#vue-app",
            data: {
                records: {!! json_encode($data) !!},
                selectAll: false,
                selectedRecords: [],
                chart_of_accounts: {!! json_encode($chart_of_accounts) !!},
                chart_of_account_id: "{{ Request::get('chart_of_account_id') }}",
                start_date: "{{ Request::get('start_date') }}",
                end_date: "{{ Request::get('end_date') }}"
            },
            methods: {
                selectAllRecords() {
                    this.selectedRecords = [];
                    if (this.selectAll) {
                        this.records.data.forEach(item => {
                            this.selectedRecords.push(item.id);
                        });
                    }
                },
            },

            watch: {
                start_date(value) {

                    //If the end date has not been chosen set it to the value of the start_date
                    if (this.end_date == '') {
                        this.end_date = value;
                        return;
                    }

                    const start_date = new Date(value);
                    const end_date = new Date(this.end_date);

                    if (start_date > end_date) {
                        this.end_date = value;
                    }
                },
            }
        })

        $(".select2").select2();

        $('#per_page').on('change', function() {
            $('#per-page-form').trigger('submit');
        });
    </script>
@endsection
