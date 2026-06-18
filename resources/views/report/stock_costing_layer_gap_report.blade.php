@extends('layouts.app')
@section('title', __('report.stock_costing_layer_gap_report'))

@section('content')
<section class="content-header">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">{{ __('report.stock_costing_layer_gap_report') }}</h1>
    <p class="help-block">{{ __('report.stock_costing_layer_gap_report_msg') }}</p>
</section>

<section class="content">
    <div class="row">
        <div class="col-md-12">
            @component('components.filters', ['title' => __('report.filters')])
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            {!! Form::label('layer_gap_location_id', __('purchase.business_location') . ':') !!}
                            {!! Form::select('location_id', $business_locations, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'id' => 'layer_gap_location_id']) !!}
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            {!! Form::label('layer_gap_product_id', __('business.product') . ':') !!}
                            {!! Form::select('product_id', [], null, ['class' => 'form-control', 'style' => 'width:100%', 'id' => 'layer_gap_product_id', 'placeholder' => __('messages.all')]) !!}
                        </div>
                    </div>
                </div>
            @endcomponent
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            @component('components.widget', ['class' => 'box-solid'])
                <div class="table-responsive">
                    <table class="table table-bordered table-striped" id="stock_costing_layer_gap_table" style="width: 100%;">
                        <thead>
                            <tr>
                                <th>SKU</th>
                                <th>{{ __('business.product') }}</th>
                                <th>{{ __('sale.location') }}</th>
                                <th>{{ __('report.current_stock') }}</th>
                                <th>{{ __('lang_v1.total_stock_price') }} Layer Qty</th>
                                <th>Gap Qty</th>
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
    $(document).ready(function () {
        $('#layer_gap_product_id').select2({
            ajax: {
                url: '/products/list-no-variation',
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    return { term: params.term };
                },
                processResults: function (data) {
                    return { results: data };
                }
            },
            minimumInputLength: 1,
            allowClear: true,
            placeholder: '{{ __('messages.all') }}',
            escapeMarkup: function (markup) {
                return markup;
            }
        });

        var table = $('#stock_costing_layer_gap_table').DataTable({
            processing: true,
            serverSide: true,
            order: [[5, 'desc']],
            ajax: {
                url: '{{ action([\App\Http\Controllers\ReportController::class, 'getStockCostingLayerGapReport']) }}',
                data: function (d) {
                    d.location_id = $('#layer_gap_location_id').val();
                    d.product_id = $('#layer_gap_product_id').val();
                }
            },
            columns: [
                { data: 'sku', name: 'sku' },
                { data: 'product_name', name: 'product_name' },
                { data: 'location_name', name: 'location_name' },
                { data: 'qty_available', name: 'qty_available', searchable: false },
                { data: 'layer_available', name: 'layer_available', searchable: false },
                { data: 'layer_gap', name: 'layer_gap', searchable: false }
            ]
        });

        $('#layer_gap_location_id, #layer_gap_product_id').on('change', function () {
            table.ajax.reload();
        });
    });
</script>
@endsection
