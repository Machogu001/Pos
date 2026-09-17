@extends('layouts.app')
@section('title', __('report.stock_report'))

@section('css')
<style>
    .stock-report-alert-banner {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        flex-wrap: wrap;
    }

    .stock-report-alert-banner__message {
        flex: 1 1 280px;
    }

    .stock-report-alert-banner__clear {
        flex: 0 0 auto;
        white-space: nowrap;
    }

    .stock-valuation-summary {
        scroll-margin-top: 90px;
        transition: box-shadow 0.35s ease, border-color 0.35s ease, background-color 0.35s ease;
    }

    #stock_report_table tbody tr.notification-stock-hit {
        background-color: #fff6da;
        box-shadow: inset 4px 0 0 #d9a100;
    }

    #stock_report_table tbody tr.notification-stock-hit td {
        background-color: transparent;
    }

    .stock-valuation-summary.is-highlighted {
        border: 1px solid #2c7a7b;
        background-color: #f1fbfa;
        box-shadow: 0 0 0 3px rgba(44, 122, 123, 0.14);
        animation: valuationPulse 1.4s ease-in-out 1;
    }

    @keyframes valuationPulse {
        0% { box-shadow: 0 0 0 0 rgba(44, 122, 123, 0.36); }
        100% { box-shadow: 0 0 0 12px rgba(44, 122, 123, 0); }
    }
</style>
@endsection

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">{{ __('report.stock_report')}}</h1>
</section>

<!-- Main content -->
<section class="content">
    @if(request()->filled('product_id') || request()->filled('variation_id'))
    <div class="row">
        <div class="col-md-12">
            <div class="alert alert-info stock-report-alert-banner">
                <span class="stock-report-alert-banner__message">
                    {{ __('lang_v1.notifications') }}: {{ __('report.stock_report') }} filtered to the selected alert item.
                    @if(request()->filled('alert_stock_label'))
                        <br>
                        <small>
                            Alert snapshot: {{ request('alert_stock_label') }} at {{ request('alerted_at') ?: now()->toDateTimeString() }}. Current stock on this page may be higher if the item was restocked after the alert was created.
                        </small>
                    @endif
                    @if(!empty($notification_stock_context))
                        <br>
                        <small>
                            Live stock: {{ $notification_stock_context['current_stock_label'] }}.
                            Alert quantity: {{ $notification_stock_context['alert_quantity_label'] }}.
                            {{ $notification_stock_context['status_text'] }}
                            @if(!empty($notification_stock_context['stock_updated_at']))
                                Last stock movement: {{ $notification_stock_context['stock_updated_at'] }}.
                            @endif
                        </small>
                    @endif
                </span>
                <a href="{{ action([\App\Http\Controllers\ReportController::class, 'getStockReport']) }}" class="btn btn-sm btn-primary stock-report-alert-banner__clear">{{ __('messages.clear') }}</a>
            </div>
        </div>
    </div>
    @endif
    <div class="row">
        <div class="col-md-12">
            @component('components.filters', ['title' => __('report.filters')])
              {!! Form::open(['url' => action([\App\Http\Controllers\ReportController::class, 'getStockReport']), 'method' => 'get', 'id' => 'stock_report_filter_form' ]) !!}
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('location_id',  __('purchase.business_location') . ':') !!}
                        {!! Form::select('location_id', $business_locations, null, ['class' => 'form-control select2', 'style' => 'width:100%']); !!}
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('category_id', __('category.category') . ':') !!}
                        {!! Form::select('category', $categories, null, ['placeholder' => __('messages.all'), 'class' => 'form-control select2', 'style' => 'width:100%', 'id' => 'category_id']); !!}
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('sub_category_id', __('product.sub_category') . ':') !!}
                        {!! Form::select('sub_category', array(), null, ['placeholder' => __('messages.all'), 'class' => 'form-control select2', 'style' => 'width:100%', 'id' => 'sub_category_id']); !!}
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('brand', __('product.brand') . ':') !!}
                        {!! Form::select('brand', $brands, null, ['placeholder' => __('messages.all'), 'class' => 'form-control select2', 'style' => 'width:100%']); !!}
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('unit',__('product.unit') . ':') !!}
                        {!! Form::select('unit', $units, null, ['placeholder' => __('messages.all'), 'class' => 'form-control select2', 'style' => 'width:100%']); !!}
                    </div>
                </div>
                @if($show_manufacturing_data)
                    <div class="col-md-3">
                        <div class="form-group">
                            <br>
                            <div class="checkbox">
                                <label>
                                  {!! Form::checkbox('only_mfg', 1, false, 
                                  [ 'class' => 'input-icheck', 'id' => 'only_mfg_products']); !!} {{ __('manufacturing::lang.only_mfg_products') }}
                                </label>
                            </div>
                        </div>
                    </div>
                @endif
                {!! Form::close() !!}
            @endcomponent
        </div>
    </div>
    @can('view_product_stock_value')
    <div class="row">
        <div class="col-md-12">
            <div id="stock_valuation_summary" class="stock-valuation-summary">
            @component('components.widget', ['class' => 'box-solid'])
            <table class="table no-border">
                <tr>
                    <td>@lang('report.closing_stock') (@lang('lang_v1.by_purchase_price'))</td>
                    <td>@lang('report.closing_stock') (@lang('lang_v1.by_sale_price'))</td>
                    <td>@lang('lang_v1.potential_profit')</td>
                    <td>@lang('lang_v1.profit_margin')</td>
                </tr>
                <tr>
                    <td><h3 id="closing_stock_by_pp" class="mb-0 mt-0"></h3></td>
                    <td><h3 id="closing_stock_by_sp" class="mb-0 mt-0"></h3></td>
                    <td><h3 id="potential_profit" class="mb-0 mt-0"></h3></td>
                    <td><h3 id="profit_margin" class="mb-0 mt-0"></h3></td>
                </tr>
            </table>
            @endcomponent
            </div>
        </div>
    </div>
    @endcan
    <div class="row">
        <div class="col-md-12">
            @component('components.widget', ['class' => 'box-solid'])
                @include('report.partials.stock_report_table')
            @endcomponent
        </div>
    </div>
</section>
<!-- /.content -->

@endsection

@section('javascript')
    <script src="{{ asset('js/report.js?v=' . $asset_v) }}"></script>
    <script>
        $(document).ready(function() {
            var query = new URLSearchParams(window.location.search || '');
            var shouldFocusStockValue = query.get('view') === 'stock_value';
            var $valuationSummary = $('#stock_valuation_summary');

            if (!shouldFocusStockValue || !$valuationSummary.length) {
                return;
            }

            // Let initial widgets render before scrolling and highlighting.
            setTimeout(function() {
                var top = $valuationSummary.offset().top - 80;
                $('html, body').animate({ scrollTop: top }, 450);

                $valuationSummary.addClass('is-highlighted');
                setTimeout(function() {
                    $valuationSummary.removeClass('is-highlighted');
                }, 2600);
            }, 220);
        });
    </script>
@endsection