@extends('layouts.app')
@section('title', __('report.stock_valuation_report'))

@section('css')
<style>
    .stock-valuation-wrap {
        scroll-margin-top: 90px;
    }

    .stock-valuation-card {
        border: 1px solid #d6ebe6;
        border-radius: 8px;
        background-color: #f6fbfa;
        padding: 16px;
        min-height: 104px;
    }

    .stock-valuation-label {
        color: #4a5568;
        font-weight: 600;
        margin-bottom: 8px;
    }

    .stock-valuation-value {
        margin: 0;
        font-size: 26px;
        font-weight: 700;
        color: #1a202c;
        line-height: 1.15;
        word-break: break-word;
    }
</style>
@endsection

@section('content')
<section class="content-header">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">{{ __('report.stock_valuation_report') }}</h1>
</section>

<section class="content">
    <div class="row">
        <div class="col-md-12">
            @component('components.filters', ['title' => __('report.filters')])
                {!! Form::open(['url' => action([\App\Http\Controllers\ReportController::class, 'getStockValuationReport']), 'method' => 'get', 'id' => 'stock_valuation_filter_form' ]) !!}
                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('valuation_location_id',  __('purchase.business_location') . ':') !!}
                            {!! Form::select('location_id', $business_locations, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'id' => 'valuation_location_id']); !!}
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('valuation_category_id', __('category.category') . ':') !!}
                            {!! Form::select('category', $categories, null, ['placeholder' => __('messages.all'), 'class' => 'form-control select2', 'style' => 'width:100%', 'id' => 'valuation_category_id']); !!}
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('valuation_sub_category_id', __('product.sub_category') . ':') !!}
                            {!! Form::select('sub_category', [], null, ['placeholder' => __('messages.all'), 'class' => 'form-control select2', 'style' => 'width:100%', 'id' => 'valuation_sub_category_id']); !!}
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('valuation_brand_id', __('product.brand') . ':') !!}
                            {!! Form::select('brand', $brands, null, ['placeholder' => __('messages.all'), 'class' => 'form-control select2', 'style' => 'width:100%', 'id' => 'valuation_brand_id']); !!}
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('valuation_unit_id', __('product.unit') . ':') !!}
                            {!! Form::select('unit', $units, null, ['placeholder' => __('messages.all'), 'class' => 'form-control select2', 'style' => 'width:100%', 'id' => 'valuation_unit_id']); !!}
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('valuation_product_id', __('business.product') . ':') !!}
                            {!! Form::select('product_id', [], null, ['placeholder' => __('messages.all'), 'class' => 'form-control', 'style' => 'width:100%', 'id' => 'valuation_product_id']); !!}
                        </div>
                    </div>
                {!! Form::close() !!}
            @endcomponent
        </div>
    </div>

    <div class="row stock-valuation-wrap" id="stock_valuation_summary">
        <div class="col-md-3 col-sm-6 col-xs-12">
            <div class="stock-valuation-card">
                <p class="stock-valuation-label">@lang('report.closing_stock') (@lang('lang_v1.by_purchase_price'))</p>
                <h3 id="stock_valuation_closing_stock_by_pp" class="stock-valuation-value"></h3>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 col-xs-12">
            <div class="stock-valuation-card">
                <p class="stock-valuation-label">@lang('report.closing_stock') (@lang('lang_v1.by_sale_price'))</p>
                <h3 id="stock_valuation_closing_stock_by_sp" class="stock-valuation-value"></h3>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 col-xs-12">
            <div class="stock-valuation-card">
                <p class="stock-valuation-label">@lang('lang_v1.potential_profit')</p>
                <h3 id="stock_valuation_potential_profit" class="stock-valuation-value"></h3>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 col-xs-12">
            <div class="stock-valuation-card">
                <p class="stock-valuation-label">@lang('lang_v1.profit_margin')</p>
                <h3 id="stock_valuation_profit_margin" class="stock-valuation-value"></h3>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            @component('components.widget', [
                'class' => 'box-solid',
                'title' => __('report.stock_valuation_breakdown') !== 'report.stock_valuation_breakdown' ? __('report.stock_valuation_breakdown') : 'Stock Valuation Breakdown',
                'help_text' => __('report.stock_valuation_default_sort') !== 'report.stock_valuation_default_sort' ? __('report.stock_valuation_default_sort') : 'Default order: highest stock value by purchase price first. You can sort by any column.'
            ])
                <div class="table-responsive">
                    <table class="table table-bordered table-striped" id="stock_valuation_table" style="width: 100%;">
                        <thead>
                            <tr>
                                <th>SKU</th>
                                <th>@lang('business.product')</th>
                                <th>@lang('lang_v1.variation')</th>
                                <th>@lang('product.category')</th>
                                <th>@lang('sale.location')</th>
                                <th>@lang('report.current_stock')</th>
                                <th>@lang('lang_v1.total_stock_price') <br><small>(@lang('lang_v1.by_purchase_price'))</small></th>
                                <th>@lang('lang_v1.total_stock_price') <br><small>(@lang('lang_v1.by_sale_price'))</small></th>
                                <th>@lang('lang_v1.potential_profit')</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td colspan="9" class="text-center text-muted">Loading valuation breakdown...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            @endcomponent
        </div>
    </div>
</section>
@endsection

@section('javascript')
<script src="{{ asset('js/report.js?v=' . $asset_v) }}"></script>
<script>
    $(document).ready(function() {
        var stockValuationTable;

        $('#valuation_product_id').select2({
            ajax: {
                url: '/products/list-no-variation',
                dataType: 'json',
                delay: 250,
                data: function(params) {
                    return {
                        term: params.term
                    };
                },
                processResults: function(data) {
                    return {
                        results: data
                    };
                }
            },
            minimumInputLength: 1,
            allowClear: true,
            placeholder: '{{ __('messages.all') }}',
            escapeMarkup: function(markup) {
                return markup;
            }
        });

        function currentFilters() {
            return {
                location_id: $('#valuation_location_id').val(),
                category_id: $('#valuation_category_id').val(),
                sub_category_id: $('#valuation_sub_category_id').val(),
                brand_id: $('#valuation_brand_id').val(),
                unit_id: $('#valuation_unit_id').val(),
                product_id: $('#valuation_product_id').val()
            };
        }

        function refreshStockValue() {
            var loader = __fa_awesome();
            $('#stock_valuation_closing_stock_by_pp').html(loader);
            $('#stock_valuation_closing_stock_by_sp').html(loader);
            $('#stock_valuation_potential_profit').html(loader);
            $('#stock_valuation_profit_margin').html(loader);

            $.ajax({
                url: '{{ action([\App\Http\Controllers\ReportController::class, 'getStockValue']) }}',
                data: currentFilters(),
                success: function(data) {
                    $('#stock_valuation_closing_stock_by_pp').text(__currency_trans_from_en(data.closing_stock_by_pp));
                    $('#stock_valuation_closing_stock_by_sp').text(__currency_trans_from_en(data.closing_stock_by_sp));
                    $('#stock_valuation_potential_profit').text(__currency_trans_from_en(data.potential_profit));
                    $('#stock_valuation_profit_margin').text(__currency_trans_from_en(data.profit_margin, false));
                }
            });
        }

        function refreshBreakdownTable() {
            if (stockValuationTable) {
                stockValuationTable.ajax.reload();
            }
        }

        stockValuationTable = $('#stock_valuation_table').DataTable({
            processing: true,
            serverSide: true,
            fixedHeader: false,
            order: [[6, 'desc']],
            ajax: {
                url: '{{ action([\App\Http\Controllers\ReportController::class, 'getStockValuationReport']) }}',
                data: function(d) {
                    $.extend(d, currentFilters());
                }
            },
            columns: [
                { data: 'sku', name: 'variations.sub_sku' },
                { data: 'product', name: 'p.name' },
                { data: 'variation', name: 'variations.name' },
                { data: 'category_name', name: 'c.name' },
                { data: 'location_name', name: 'l.name' },
                { data: 'stock', name: 'stock', searchable: false },
                { data: 'stock_price', name: 'stock_price', searchable: false },
                { data: 'stock_value_by_sale_price', name: 'stock_value_by_sale_price', searchable: false },
                { data: 'potential_profit', name: 'potential_profit', searchable: false }
            ],
            drawCallback: function() {
                __currency_convert_recursively($('#stock_valuation_table'));
            }
        });

        function loadSubCategories(categoryId, selectedId) {
            var $subCategory = $('#valuation_sub_category_id');
            $subCategory.html('<option value="">{{ __('messages.all') }}</option>').trigger('change');

            if (!categoryId) {
                return;
            }

            $.ajax({
                method: 'POST',
                url: '/products/get_sub_categories',
                dataType: 'html',
                data: { cat_id: categoryId },
                success: function(result) {
                    if (result) {
                        $subCategory.html(result);
                    }

                    if (selectedId) {
                        $subCategory.val(selectedId);
                    }

                    $subCategory.trigger('change');
                }
            });
        }

        $('#valuation_category_id').on('change', function() {
            loadSubCategories($(this).val(), null);
            refreshStockValue();
            refreshBreakdownTable();
        });

        $('#valuation_location_id, #valuation_sub_category_id, #valuation_brand_id, #valuation_unit_id').on('change', function() {
            refreshStockValue();
            refreshBreakdownTable();
        });

        $('#valuation_product_id').on('change', function() {
            refreshStockValue();
            refreshBreakdownTable();
        });

        refreshStockValue();
    });
</script>
@endsection
