@extends('layouts.app')
@section('title', __('sale.sell_details'))

@section('content')
<style>
    .sell-fullpage-compact .box-body {
        font-size: 13px;
    }
    .sell-fullpage-compact h4 {
        font-size: 17px;
        margin-top: 8px;
        margin-bottom: 8px;
    }
    .sell-fullpage-compact .table > thead > tr > th,
    .sell-fullpage-compact .table > tbody > tr > th,
    .sell-fullpage-compact .table > tfoot > tr > th,
    .sell-fullpage-compact .table > thead > tr > td,
    .sell-fullpage-compact .table > tbody > tr > td,
    .sell-fullpage-compact .table > tfoot > tr > td {
        padding: 6px;
        font-size: 12px;
    }
</style>
<section class="content-header">
    <div class="tw-mb-5 tw-rounded-xl tw-bg-gradient-to-r tw-from-purple-700 tw-to-purple-900 tw-text-white tw-p-6 tw-shadow-lg">
        <div class="tw-flex tw-items-center tw-justify-between">
            <div>
                <h1 class="tw-text-3xl tw-font-bold tw-mb-2">
                    <i class="fas fa-file-invoice tw-mr-3"></i>
                    @if($sell->type == 'sales_order') @lang('restaurant.order_details') @else @lang('sale.sell_details') @endif
                </h1>
                <p class="tw-text-purple-100 tw-text-lg">
                    <strong>@if($sell->type == 'sales_order') @lang('restaurant.order_no') @else @lang('sale.invoice_no') @endif:</strong> {{ $sell->invoice_no }}
                </p>
            </div>
            <div class="tw-flex tw-gap-3">
                @if($sell->type != 'sales_order')
                <a href="#" class="print-invoice tw-dw-btn tw-dw-btn-success tw-text-white" data-href="{{route('sell.printInvoice', [$sell->id])}}?package_slip=true">
                    <i class="fas fa-file-alt" aria-hidden="true"></i> @lang("lang_v1.packing_slip")
                </a>
                @endif
                @can('print_invoice')
                <a href="#" class="print-invoice tw-dw-btn tw-dw-btn-primary tw-text-white" data-href="{{route('sell.printInvoice', [$sell->id])}}">
                    <i class="fa fa-print" aria-hidden="true"></i> @lang("lang_v1.print_invoice")
                </a>
                @endcan
                @if(config('constants.enable_download_pdf'))
                <a href="{{route('sell.downloadPdf', [$sell->id])}}" class="tw-dw-btn tw-dw-btn-danger tw-text-white" target="_blank">
                    <i class="fas fa-file-pdf" aria-hidden="true"></i> @lang("lang_v1.download_pdf")
                </a>
                @endif
                <a href="{{url('/admin/subscriptions')}}" class="tw-dw-btn tw-dw-btn-neutral tw-text-white">
                    <i class="fas fa-arrow-left"></i> @lang('messages.back')
                </a>
            </div>
        </div>
    </div>
</section>

<section class="content sell-fullpage-compact">
    <div class="row">
        <div class="col-md-12">
            <div class="box box-solid">
                <div class="box-body">
                    <div class="row">
                        <div class="col-xs-12">
                            <p class="pull-right"><b>@lang('messages.date'):</b> {{ @format_date($sell->transaction_date) }}</p>
                        </div>
                    </div>
                    
                    <div class="row">
                        @php
                            $custom_labels = json_decode(session('business.custom_labels'), true);
                            $export_custom_fields = [];
                            if (!empty($sell->is_export) && !empty($sell->export_custom_fields_info)) {
                                $export_custom_fields = $sell->export_custom_fields_info;
                            }
                        @endphp
                        <div class="@if(!empty($export_custom_fields)) col-sm-3 @else col-sm-4 @endif">
                            <b>@if($sell->type == 'sales_order') {{ __('restaurant.order_no') }} @else {{ __('sale.invoice_no') }} @endif:</b> #{{ $sell->invoice_no }}<br>
                            <b>{{ __('sale.status') }}:</b> 
                            @if($sell->status == 'draft' && $sell->is_quotation == 1)
                                {{ __('lang_v1.quotation') }}
                            @else
                                {{ $statuses[$sell->status] ?? __('sale.' . $sell->status) }}
                            @endif
                            <br>
                            @if($sell->type != 'sales_order')
                                <b>{{ __('sale.payment_status') }}:</b> @if(!empty($sell->payment_status)){{ __('lang_v1.' . $sell->payment_status) }}
                                @endif
                            @endif
                            @if(!empty($custom_labels['sell']['custom_field_1']))
                                <br><strong>{{$custom_labels['sell']['custom_field_1'] ?? ''}}: </strong> {{$sell->custom_field_1}}
                            @endif
                            @if(!empty($custom_labels['sell']['custom_field_2']))
                                <br><strong>{{$custom_labels['sell']['custom_field_2'] ?? ''}}: </strong> {{$sell->custom_field_2}}
                            @endif
                            @if(!empty($custom_labels['sell']['custom_field_3']))
                                <br><strong>{{$custom_labels['sell']['custom_field_3'] ?? ''}}: </strong> {{$sell->custom_field_3}}
                            @endif
                            @if(!empty($custom_labels['sell']['custom_field_4']))
                                <br><strong>{{$custom_labels['sell']['custom_field_4'] ?? ''}}: </strong> {{$sell->custom_field_4}}
                            @endif

                            @if(!empty($sales_orders))
                                <br><br><strong>@lang('lang_v1.sales_orders'):</strong>
                                <table class="table table-slim no-border">
                                    @foreach($sales_orders as $sales_order)
                                        <tr>
                                            <td>
                                                <a data-href="{{action([\App\Http\Controllers\SalesOrderController::class, 'show'], [$sales_order->id])}}" href="#" class="btn-modal" data-container=".view_modal">
                                                    {{ $sales_order->invoice_no }}
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </table>
                            @endif
                        </div>
                        <div class="@if(!empty($export_custom_fields)) col-sm-3 @else col-sm-4 @endif">
                            <b>{{ __('sale.customer_name') }}:</b> {{ $sell->business->name ?? $sell->contact->name }}<br>
                            @if(!empty($sell->business->owner))
                                <strong>{{ __('business.owner') }}:</strong> {{ $sell->business->owner->first_name }} {{ $sell->business->owner->last_name }}<br>
                            @endif
                            @if(!empty($sell->contact->landmark))
                                <strong>{!! __('business.landmark') !!}:</strong> {{$sell->contact->landmark}}<br>
                            @endif
                            @if(!empty($sell->business->owner->contact_number ?? $sell->contact->mobile))
                                <strong>{!! __('contact.mobile') !!}:</strong> {{$sell->business->owner->contact_number ?? $sell->contact->mobile}}<br>
                            @endif
                            @if(!empty($sell->business->owner->email ?? $sell->contact->email))
                                <strong>{!! __('business.email') !!}:</strong> {{$sell->business->owner->email ?? $sell->contact->email}}<br>
                            @endif
                            @if(!empty($sell->business->tax_number_1 ?? $sell->contact->tax_number))
                                <strong>{!! __('contact.tax_no') !!}:</strong> {{$sell->business->tax_number_1 ?? $sell->contact->tax_number}}<br>
                            @endif
                            @if(!empty($sell->contact->custom_field1))
                                <strong>{{$custom_labels['contact']['custom_field_1'] ?? ''}}:</strong> {{$sell->contact->custom_field1}}<br>
                            @endif
                            @if(!empty($sell->contact->custom_field2))
                                <strong>{{$custom_labels['contact']['custom_field_2'] ?? ''}}:</strong> {{$sell->contact->custom_field2}}<br>
                            @endif
                            @if(!empty($sell->contact->custom_field3))
                                <strong>{{$custom_labels['contact']['custom_field_3'] ?? ''}}:</strong> {{$sell->contact->custom_field3}}<br>
                            @endif
                            @if(!empty($sell->contact->custom_field4))
                                <strong>{{$custom_labels['contact']['custom_field_4'] ?? ''}}:</strong> {{$sell->contact->custom_field4}}<br>
                            @endif

                            @if(!empty($sell->delivery_person_user->first_name))
                                <br><strong>{!! __('lang_v1.delivery_person') !!}:</strong> {{$sell->delivery_person_user->first_name}} {{$sell->delivery_person_user->last_name}}
                            @endif
                        </div>
                        <div class="@if(!empty($export_custom_fields)) col-sm-3 @else col-sm-4 @endif">
                            @if($sell->type != 'sales_order')
                                <b>@lang('purchase.business_location'):</b> {{ $sell->location->name }}<br>
                            @endif

                            @if(!empty($sell->res_table_id))
                                <b>@lang('restaurant.table'):</b> @if(!empty($sell->table)) {{ $sell->table->name }} @endif<br>
                            @endif

                            @if(!empty($sell->service_staff_id))
                                <b>@lang('restaurant.service_staff'):</b> @if(!empty($sell->service_staff)) {{ $sell->service_staff->user_full_name}} @endif<br>
                            @endif

                            @if(!empty($sell->types_of_service_id))
                                <b>@lang('lang_v1.types_of_service'):</b> @if(!empty($sell->types_of_service)) {{ $sell->types_of_service->name }} @endif<br>
                            @endif

                            @if(!empty($sell->additional_notes))
                                <b>@lang('lang_v1.additional_notes'):</b> {{ $sell->additional_notes }}<br>
                            @endif

                            @if(!empty($sell->staff_note))
                                <b>@lang('lang_v1.staff_note'):</b> {{ $sell->staff_note }}<br>
                            @endif

                            @if(!empty($sell->shipping_details))
                                <b>@lang('lang_v1.shipping_details'):</b> {{ $sell->shipping_details }}<br>
                            @endif

                            @if($sell->type != 'sales_order' && !empty($sell->shipping_status))
                                <b>@lang('lang_v1.shipping_status'):</b> 
                                @php
                                    $shipping_status = !empty($shipping_statuses[$sell->shipping_status]) ? $shipping_statuses[$sell->shipping_status] : $sell->shipping_status;

                                    if(!empty($shipping_status_colors[$sell->shipping_status])){
                                        $shipping_status = '<span class="label ' . $shipping_status_colors[$sell->shipping_status] . '" >' . $shipping_status . '</span>';
                                    }
                                @endphp
                                {!! $shipping_status !!}
                            @endif

                            @if(!empty($sell->delivered_to))
                                <br><b>@lang('lang_v1.delivered_to'):</b> {{ $sell->delivered_to }}<br>
                            @endif

                            @if(!empty($sell->shipping_address))
                                <b>@lang('lang_v1.shipping_address'):</b><br>{!! $sell->shipping_address !!}<br>
                            @endif

                            @if(!empty($sell->shipping_charges))
                                <b>@lang('lang_v1.shipping_charges'):</b> {{ @num_format($sell->shipping_charges) }}<br>
                            @endif

                            @if(!empty($sell->packing_charge))
                                <b>@lang('lang_v1.packing_charge'):</b> {{ @num_format($sell->packing_charge) }}<br>
                            @endif
                        </div>
                        @if(!empty($export_custom_fields))
                            <div class="col-sm-3">
                                @foreach($export_custom_fields as $k => $v)
                                    @if(!empty($v))
                                        <strong>{{$k}}:</strong> {{ $v }} <br>
                                    @endif
                                @endforeach
                            </div>
                        @endif
                    </div>
                    <br>
                    <div class="row">
                        <div class="col-sm-12 col-xs-12">
                            <h4>{{ __('sale.products') }}:</h4>
                        </div>
                        <div class="col-sm-12 col-xs-12">
                            <div class="table-responsive">
                                <table class="table bg-gray">
                                    <thead>
                                        <tr class="bg-green">
                                            <th>#</th>
                                            <th>{{ __('sale.product') }}</th>
                                            <th>{{ __('sale.qty') }}</th>
                                            <th>{{ __('sale.unit_price') }}</th>
                                            <th>{{ __('sale.subtotal') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php
                                            $total_before_tax = 0;
                                        @endphp
                                        @foreach($sell->sell_lines as $sell_line)
                                            @if($sell_line->quantity != 0)
                                                <tr>
                                                    <td>{{ $loop->iteration }}</td>
                                                    <td>
                                                        @if($sell_line->product->type == 'variable')
                                                            {{ $sell_line->product->name }} - {{ $sell_line->variations->name }}
                                                        @else
                                                            {{ $sell_line->product->name }}
                                                        @endif
                                                        @if(!empty($sell_line->sell_line_note))
                                                            <br>
                                                            <small>{{ $sell_line->sell_line_note }}</small>
                                                        @endif
                                                        @if($sell_line->modifiers->count() > 0)
                                                            <br>
                                                            <small>
                                                                @foreach($sell_line->modifiers as $modifier)
                                                                    {{ $modifier->product->name }} @if(!$loop->last), @endif
                                                                @endforeach
                                                            </small>
                                                        @endif
                                                        @if(!empty($sell_line->lot_details->lot_number))
                                                            <br><small>@lang('lang_v1.lot'): {{ $sell_line->lot_details->lot_number }}</small>
                                                        @endif
                                                        @if(!empty($sell_line->sub_unit))
                                                            <br><small>({{ $sell_line->sub_unit->name }})</small>
                                                        @endif
                                                        @if($is_warranty_enabled && !empty($sell_line->warranties->count()))
                                                            @php
                                                                $warranty = $sell_line->warranties->first();
                                                            @endphp
                                                            @if(!empty($warranty))
                                                                <br><small><i>@lang('lang_v1.warranty'): {{ $warranty->name }}</i></small>
                                                            @endif
                                                        @endif
                                                        @if(!empty($sell_line->service_staff))
                                                            <br><small>@lang('restaurant.service_staff'): {{ $sell_line->service_staff->user_full_name }}</small>
                                                        @endif
                                                    </td>
                                                    <td>{{ @format_quantity($sell_line->quantity) }}</td>
                                                    <td>
                                                        @php
                                                            $unit_price_inc_tax = $sell_line->unit_price_inc_tax;
                                                            if(!empty($sell_line->sub_unit)) {
                                                                $unit_price_inc_tax = $sell_line->unit_price_inc_tax / $sell_line->sub_unit->base_unit_multiplier;
                                                            }
                                                        @endphp
                                                        <span class="display_currency" data-currency_symbol="true">{{ $unit_price_inc_tax }}</span>

                                                        @if(!empty($sell_line->sell_line_note))
                                                            <br>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        <span class="display_currency" data-currency_symbol="true">{{ $sell_line->quantity * $unit_price_inc_tax }}</span>
                                                    </td>
                                                </tr>
                                                @php
                                                    $total_before_tax += ($sell_line->quantity * $sell_line->unit_price_before_discount);
                                                @endphp
                                            @endif
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-sm-6 col-sm-offset-6 col-xs-12">
                            <table class="table">
                                <tr>
                                    <th>{{ __('sale.total_before_tax') }}: </th>
                                    <td></td>
                                    <td><span class="display_currency" data-currency_symbol="true">{{ $total_before_tax }}</span></td>
                                </tr>
                                @if(!empty($sell->discount_type))
                                    <tr>
                                        <th>{{ __('sale.discount') }}: 
                                        @if($sell->discount_type == 'percentage')
                                            <small>({{ @num_format($sell->discount_amount) }}%)</small>
                                        @endif
                                        </th>
                                        <td></td>
                                        <td>(-) <span class="display_currency" data-currency_symbol="true">{{ $sell->total_before_tax - $total_before_tax }}</span></td>
                                    </tr>
                                @endif

                                @if(!empty($line_taxes))
                                    @foreach($line_taxes as $key => $value)
                                        <tr>
                                            <th>{{$key}}:</th>
                                            <td></td>
                                            <td>(+) <span class="display_currency" data-currency_symbol="true">{{ $value }}</span></td>
                                        </tr>
                                    @endforeach
                                @endif

                                @if(!empty($order_taxes))
                                    @foreach($order_taxes as $k => $v)
                                        <tr>
                                            <th>{{$k}}:</th>
                                            <td></td>
                                            <td>(+) <span class="display_currency" data-currency_symbol="true">{{ $v }}</span></td>
                                        </tr>
                                    @endforeach
                                @endif

                                @if(!empty($sell->packing_charge))
                                    <tr>
                                        <th>{{ __('lang_v1.packing_charge') }}:</th>
                                        <td></td>
                                        <td>(+) <span class="display_currency" data-currency_symbol="true">{{ $sell->packing_charge }}</span></td>
                                    </tr>
                                @endif

                                @if(!empty($sell->shipping_charges))
                                    <tr>
                                        <th>{{ __('lang_v1.shipping_charges') }}:</th>
                                        <td></td>
                                        <td>(+) <span class="display_currency" data-currency_symbol="true">{{ $sell->shipping_charges }}</span></td>
                                    </tr>
                                @endif

                                @if(!empty($sell->round_off_amount))
                                    <tr>
                                        <th>{{ __('lang_v1.round_off') }}:</th>
                                        <td></td>
                                        <td><span class="display_currency" data-currency_symbol="true">{{ $sell->round_off_amount }}</span></td>
                                    </tr>
                                @endif

                                <tr>
                                    <th>{{ __('sale.total') }}: </th>
                                    <td></td>
                                    <td><span class="display_currency" data-currency_symbol="true">{{ $sell->final_total }}</span></td>
                                </tr>

                                @if($sell->type != 'sales_order')
                                    <tr>
                                        <th>{{ __('sale.total_paid') }}:</th>
                                        <td></td>
                                        <td><span class="display_currency" data-currency_symbol="true">{{ $sell->payment_lines->sum('amount') }}</span></td>
                                    </tr>
                                    <tr>
                                        <th>{{ __('sale.total_remaining') }}:</th>
                                        <td></td>
                                        <td><span class="display_currency" data-currency_symbol="true">{{ $sell->final_total - $sell->payment_lines->sum('amount') }}</span></td>
                                    </tr>
                                @endif
                            </table>
                        </div>
                    </div>
                    @if($sell->type != 'sales_order')
                        <div class="row">
                            <div class="col-sm-12">
                                <h4>{{ __('sale.payment_info') }}:</h4>
                            </div>
                            <div class="col-md-6 col-sm-12">
                                <div class="table-responsive">
                                    <table class="table table-sm bg-gray">
                                        <thead>
                                            <tr class="bg-green">
                                                <th>#</th>
                                                <th>{{ __('messages.date') }}</th>
                                                <th>{{ __('purchase.ref_no') }}</th>
                                                <th>{{ __('sale.amount') }}</th>
                                                <th>{{ __('sale.payment_mode') }}</th>
                                                <th>{{ __('sale.payment_note') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @php
                                                $total_paid = 0;
                                            @endphp
                                            @foreach($sell->payment_lines as $payment_line)
                                                @php
                                                    $payment_method = strtolower($payment_line->method ?? '');
                                                    $is_mpesa_payment = strpos($payment_method, 'mpesa') !== false;
                                                    $mpesa_transaction_code = $payment_line->transaction_no ?? null;

                                                    if (empty($mpesa_transaction_code) && !empty($payment_line->method) && stripos($payment_line->method, 'MPESA:') !== false) {
                                                        $parts = explode(', MPESA:', $payment_line->method, 2);
                                                        $mpesa_transaction_code = $parts[1] ?? null;
                                                    }
                                                @endphp
                                                <tr>
                                                    <td>{{ $loop->iteration }}</td>
                                                    <td>{{ @format_date($payment_line->paid_on) }}</td>
                                                    <td>{{ $payment_line->payment_ref_no }}</td>
                                                    <td><span class="display_currency" data-currency_symbol="true">{{ $payment_line->amount }}</span></td>
                                                    <td>
                                                        {{ $payment_types[$payment_line->method] ?? $payment_line->method }}
                                                        @if($is_mpesa_payment && !empty($mpesa_transaction_code))
                                                            <br><small><strong>Transaction code:</strong> {{ $mpesa_transaction_code }}</small>
                                                        @endif
                                                    </td>
                                                    <td>@if(!empty($payment_line->note))
                                                        {{ ucfirst($payment_line->note) }}
                                                    @else
                                                        --
                                                    @endif
                                                    </td>
                                                </tr>
                                                @php
                                                    $total_paid += $payment_line->amount;
                                                @endphp
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    @endif
                    <div class="row">
                        <div class="col-md-12">
                            <strong>{{ __('lang_v1.activities') }}:</strong><br>
                            @includeIf('activity_log.activities', ['activity_type' => 'sell'])
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

@endsection

@section('javascript')
<script type="text/javascript">
    $(document).ready(function(){
        __currency_convert_recursively($('section.content'));
    });
</script>
@endsection
