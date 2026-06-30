@php
	$receipt_payment_rows = !empty($receipt_details->payments) ? $receipt_details->payments : [];
	$receipt_has_payment_box = !empty($receipt_payment_rows);
	$receipt_primary_payment = $receipt_has_payment_box ? $receipt_payment_rows[0] : [];
	$receipt_primary_method = !empty($receipt_primary_payment['method']) ? $receipt_primary_payment['method'] : '';
	$receipt_primary_method_normalized = strtolower($receipt_primary_method);
	$receipt_primary_is_mpesa = strpos($receipt_primary_method_normalized, 'mpesa') !== false;
	$receipt_is_paid = isset($receipt_details->total_due)
		? empty($receipt_details->total_due)
		: !empty($receipt_details->total_paid);
	$receipt_primary_method_label = $receipt_primary_is_mpesa ? 'M-Pesa' : $receipt_primary_method;
	$receipt_primary_method_ref = '';
	if (!empty($receipt_primary_payment['method']) && stripos($receipt_primary_payment['method'], 'MPESA:') !== false) {
		$parts = explode(', MPESA:', $receipt_primary_payment['method'], 2);
		$receipt_primary_method_label = trim($parts[0]);
		$receipt_primary_method_ref = isset($parts[1]) ? trim($parts[1]) : '';
	}
	$receipt_primary_transaction_code = $receipt_primary_is_mpesa
		? $receipt_primary_method_ref
		: $receipt_primary_method_label;
	$receipt_primary_paid_datetime = !empty($receipt_primary_payment['date_time'])
		? $receipt_primary_payment['date_time']
		: (!empty($receipt_primary_payment['date']) ? $receipt_primary_payment['date'] : '');
@endphp

<style type="text/css">
	.receipt-wrap {
		color: #000 !important;
		font-family: Arial, Helvetica, sans-serif;
		max-width: 100%;
		overflow-x: hidden;
	}
	.receipt-wrap * {
		box-sizing: border-box;
	}
	.receipt-wrap .row {
		margin-left: 0 !important;
		margin-right: 0 !important;
	}
	.receipt-wrap [class*="col-xs-"] {
		padding-left: 4px;
		padding-right: 4px;
	}
	.receipt-top {
		text-align: center;
	}
	.receipt-top .brand-logo {
		max-height: 64px;
		width: auto;
		margin: 0 auto 8px;
		display: block;
	}
	.receipt-top .brand-name {
		font-size: 22px;
		font-weight: 800;
		letter-spacing: 1px;
		line-height: 1;
		margin: 0;
	}
	.receipt-top .brand-subname {
		font-size: 15px;
		font-weight: 700;
		letter-spacing: 4px;
		margin: 4px 0 8px;
	}
	.receipt-divider {
		border-top: 2px dashed #222;
		margin: 8px 0 10px;
	}
	.receipt-heading-ribbon {
		display: inline-block;
		background: #111;
		color: #fff;
		padding: 6px 14px;
		font-size: 18px;
		font-weight: 800;
		letter-spacing: 1px;
		line-height: 1;
		margin: 4px 0 8px;
		position: relative;
	}
	.receipt-heading-ribbon:before,
	.receipt-heading-ribbon:after {
		content: '';
		position: absolute;
		top: 0;
		width: 0;
		height: 0;
		border-top: 21px solid transparent;
		border-bottom: 21px solid transparent;
	}
	.receipt-heading-ribbon:before {
		left: -16px;
		border-right: 16px solid #111;
	}
	.receipt-heading-ribbon:after {
		right: -16px;
		border-left: 16px solid #111;
	}
	.receipt-meta-row {
		border-top: 2px solid #222;
		border-bottom: 2px solid #222;
		padding: 10px 0 8px;
		margin: 8px 0 10px;
	}
	.receipt-meta-title {
		font-size: 15px;
		font-weight: 800;
		margin-bottom: 4px;
	}
	.receipt-meta-value {
		font-size: 13px;
		margin-bottom: 4px;
		word-break: break-word;
	}
	.receipt-payment-box {
		border: 2px solid #222;
		border-radius: 10px;
		padding: 5px 7px;
		margin: 8px 0 8px;
		width: 100%;
		max-width: 420px;
		margin-left: auto;
		margin-right: auto;
	}
	.receipt-payment-left {
		text-align: center;
		border-right: 2px solid #222;
		min-height: 42px;
		padding-right: 5px;
		display: flex;
		flex-direction: column;
		justify-content: center;
		gap: 2px;
	}
	.receipt-payment-left img {
		max-width: 44px;
		height: auto;
	}
	.receipt-payment-left .pay-label {
		font-weight: 700;
		font-size: 10px;
		margin-bottom: 2px;
		word-break: break-word;
	}
	.receipt-payment-right {
		padding-left: 6px;
		overflow: hidden;
	}
	.receipt-payment-method {
		font-size: 12px;
		font-weight: 700;
		margin: 0;
		line-height: 1.2;
		word-break: break-word;
	}
	.receipt-payment-detail {
		display: grid;
		grid-template-columns: 108px minmax(0, 1fr);
		align-items: center;
		gap: 6px;
		line-height: 1.2;
		margin-bottom: 2px;
		font-size: 11px;
	}
	.receipt-payment-detail:last-child {
		margin-bottom: 0;
	}
	.receipt-payment-detail .detail-label {
		font-weight: 700;
		white-space: nowrap;
		text-align: left;
	}
	.receipt-payment-detail .detail-value {
		font-weight: 600;
		word-break: break-word;
		text-align: left;
	}
	.receipt-payment-amount {
		display: inline;
		background: transparent;
		color: inherit;
		padding: 0;
		border-radius: 0;
		font-size: 11px;
		font-weight: 600;
		line-height: 1.2;
		white-space: nowrap;
	}
	.receipt-payment-date {
		font-size: 11px;
		font-weight: 600;
	}
	@media (max-width: 420px) {
		.receipt-payment-box {
			max-width: 100%;
		}
		.receipt-payment-detail {
			font-size: 10px;
			gap: 4px;
			grid-template-columns: 90px minmax(0, 1fr);
		}
	}
	.receipt-summary table.table-slim > tbody > tr > th,
	.receipt-summary table.table-slim > tbody > tr > td {
		border-top: none !important;
		padding-top: 2px;
		padding-bottom: 2px;
		font-size: 12px;
		white-space: nowrap;
	}
	.receipt-totals {
		width: 280px;
		max-width: 100%;
		margin: 0;
	}
	.receipt-totals td,
	.receipt-totals th {
		padding: 2px 0;
		font-size: 12px;
	}
	.receipt-totals .label-cell {
		text-align: right;
		padding-right: 8px;
		white-space: nowrap;
	}
	.receipt-totals .value-cell {
		text-align: right;
		white-space: nowrap;
	}
	.receipt-totals .grand-total-row td {
		border-top: 2px dashed #222;
		padding-top: 7px;
		font-size: 16px;
		font-weight: 800;
	}
	.receipt-summary .total-row th,
	.receipt-summary .total-row td {
		font-size: 12px;
		font-weight: 700;
	}
	.receipt-summary .table-slim {
		width: 100%;
	}
	.receipt-line-items {
		width: 100%;
		table-layout: fixed;
		border-collapse: collapse;
	}
	.receipt-line-items > thead > tr > th,
	.receipt-line-items > tbody > tr > td {
		padding: 6px 6px;
		vertical-align: top;
	}
	.receipt-line-items .col-product {
		width: 35%;
		word-break: break-word;
	}
	.receipt-line-items .col-qty {
		width: 9%;
	}
	.receipt-line-items .col-bonus {
		width: 8%;
	}
	.receipt-line-items .col-lot {
		width: 10%;
	}
	.receipt-line-items .col-expiry {
		width: 9%;
	}
	.receipt-line-items .col-unit-price {
		width: 9%;
	}
	.receipt-line-items .col-tax {
		width: 8%;
	}
	.receipt-line-items .col-discount {
		width: 6%;
	}
	.receipt-line-items .col-subtotal {
		width: 6%;
	}
	.receipt-line-items .col-qty,
	.receipt-line-items .col-bonus,
	.receipt-line-items .col-lot,
	.receipt-line-items .col-expiry,
	.receipt-line-items .col-unit-price,
	.receipt-line-items .col-tax,
	.receipt-line-items .col-discount,
	.receipt-line-items .col-subtotal {
		white-space: nowrap;
		font-variant-numeric: tabular-nums;
	}
	@media (max-width: 1024px) {
		.receipt-line-items {
			font-size: 11px;
		}
		.receipt-line-items > thead > tr > th,
		.receipt-line-items > tbody > tr > td {
			padding: 5px 4px;
		}
	}
	.receipt-summary {
		padding-top: 0;
	}
	.receipt-totals-wrap {
		display: flex;
		justify-content: flex-end;
		width: 100%;
		padding-right: 0;
		margin-top: -4px;
	}
	.receipt-total-line {
		clear: both;
		display: block;
		width: 100%;
		border-top: 2px dashed #222;
		margin: 12px 0 10px;
	}
	.receipt-totals {
		margin-bottom: 2px;
	}
	.receipt-footer {
		text-align: center;
		font-weight: 700;
	}
	.receipt-footer small {
		font-weight: 400;
	}
</style>

<div class="receipt-wrap">
<!-- business information here -->


<div class="row receipt-top">
		<!-- Logo -->
		@if(empty($receipt_details->letter_head))
			@if(!empty($receipt_details->logo))
				<img src="{{$receipt_details->logo}}" class="img img-responsive center-block brand-logo">
			@endif

			<!-- Header text -->
			@if(!empty($receipt_details->header_text))
				<div class="col-xs-12">
					{!! $receipt_details->header_text !!}
				</div>
			@endif

			<!-- business information here -->
			<div class="col-xs-12 text-center">
				<h2 class="text-center brand-name">
					<!-- Shop & Location Name  -->
					@if(!empty($receipt_details->display_name))
						{{$receipt_details->display_name}}
					@endif
				</h2>
				<div class="brand-subname">@lang('business.business')</div>

				<!-- Address -->
				<div>
				@if(!empty($receipt_details->address))
						<small class="text-center">
						{!! $receipt_details->address !!}
						</small>
				@endif
				@if(!empty($receipt_details->contact))
					<br/>{!! $receipt_details->contact !!}
				@endif	
				@if(!empty($receipt_details->contact) && !empty($receipt_details->website))
					 | 
				@endif
				@if(!empty($receipt_details->website))
					{{ $receipt_details->website }}
				@endif
				@if(!empty($receipt_details->location_custom_fields))
					<br>{{ $receipt_details->location_custom_fields }}
				@endif
				</div>
				<div class="receipt-divider"></div>
				@if(!empty($receipt_details->sub_heading_line1))
					{{ $receipt_details->sub_heading_line1 }}
				@endif
				@if(!empty($receipt_details->sub_heading_line2))
					<br>{{ $receipt_details->sub_heading_line2 }}
				@endif
				@if(!empty($receipt_details->sub_heading_line3))
					<br>{{ $receipt_details->sub_heading_line3 }}
				@endif
				@if(!empty($receipt_details->sub_heading_line4))
					<br>{{ $receipt_details->sub_heading_line4 }}
				@endif		
				@if(!empty($receipt_details->sub_heading_line5))
					<br>{{ $receipt_details->sub_heading_line5 }}
				@endif
				</p>
				<p>
				@if(!empty($receipt_details->tax_info1))
					<b>{{ $receipt_details->tax_label1 }}</b> {{ $receipt_details->tax_info1 }}
				@endif

				@if(!empty($receipt_details->tax_info2))
					<b>{{ $receipt_details->tax_label2 }}</b> {{ $receipt_details->tax_info2 }}
				@endif
			@endif


			<!-- Title of receipt -->
			@if(!empty($receipt_details->invoice_heading))
				<div class="text-center">
					<div class="receipt-heading-ribbon">
					{!! $receipt_details->invoice_heading !!}
					</div>
				</div>
			@endif
		</div>
		@if(!empty($receipt_details->letter_head))
			<div class="col-xs-12 text-center">
				<img style="width: 100%;margin-bottom: 10px;" src="{{$receipt_details->letter_head}}">
			</div>
		@endif
	<div class="col-xs-12 receipt-meta-row">
		<div class="row">
			<div class="col-xs-6 text-left">
				<div class="receipt-meta-title">@lang('contact.customer')</div>
				<div class="receipt-meta-value">{{ $receipt_details->customer_name ?? __('contact.customer') }}</div>
				<div class="receipt-meta-value"><strong>@lang('contact.mobile'):</strong> {{ $receipt_details->customer_mobile ?? '—' }}</div>
			</div>
			<div class="col-xs-6 text-right">
				<div class="receipt-meta-title">@lang('receipt.date')</div>
				<div class="receipt-meta-value">{{ $receipt_details->invoice_date }}</div>
				<div class="receipt-meta-value"><strong>@lang('sale.invoice_no')</strong> {{ $receipt_details->invoice_no }}</div>
			</div>
		</div>
	</div>
</div>

<div class="row" style="color: #000000 !important;">
	@includeIf('sale_pos.receipts.partial.common_repair_invoice')
</div>

<div class="row" style="color: #000000 !important;">
	<div class="col-xs-12">
		<br/>
		<table class="table table-slim receipt-line-items">
			<thead>
				<tr>
					<th class="col-product">{{$receipt_details->table_product_label}}</th>
					<th class="text-right col-qty">{{$receipt_details->table_qty_label}}</th>
					<th class="text-right col-bonus">Bonus</th>
					<th class="text-right col-lot">Batch No.</th>
					<th class="text-center col-expiry">{{ $receipt_details->product_expiry_label ?? __('lang_v1.expiry') }}</th>
					<th class="text-right col-unit-price">{{$receipt_details->table_unit_price_label}}</th>
					<th class="text-right col-tax">{{ $receipt_details->line_tax_label ?? __('sale.tax') }}</th>
					<th class="text-right col-discount">Disc</th>
					<th class="text-right col-subtotal">{{$receipt_details->table_subtotal_label}}</th>
				</tr>
			</thead>
			<tbody>
				@forelse($receipt_details->lines as $line)
					<tr>
						<td class="col-product">
							@if(!empty($line['image']))
								<img src="{{$line['image']}}" alt="{{ __('ui.image') }}" width="50" style="float: left; margin-right: 8px;">
							@endif
                            {{$line['name']}} {{$line['product_variation']}} {{$line['variation']}} 
                            @if(!empty($line['sub_sku'])), {{$line['sub_sku']}} @endif @if(!empty($line['brand'])), {{$line['brand']}} @endif @if(!empty($line['cat_code'])), {{$line['cat_code']}}@endif
                            @if(!empty($line['product_custom_fields'])), {{$line['product_custom_fields']}} @endif
                            @if(!empty($line['product_description']))
                            	<small>
                            		{!!$line['product_description']!!}
                            	</small>
                            @endif 
                            @if(!empty($line['sell_line_note']))
                            <br>
                            <small>
                            	{!!$line['sell_line_note']!!}
                            </small>
                            @endif 

                            @if(!empty($line['warranty_name'])) <br><small>{{$line['warranty_name']}} </small>@endif @if(!empty($line['warranty_exp_date'])) <small>- {{@format_date($line['warranty_exp_date'])}} </small>@endif
                            @if(!empty($line['warranty_description'])) <small> {{$line['warranty_description'] ?? ''}}</small>@endif

                            @if($receipt_details->show_base_unit_details && $line['quantity'] && $line['base_unit_multiplier'] !== 1)
                            <br><small>
                            	1 {{$line['units']}} = {{$line['base_unit_multiplier']}} {{$line['base_unit_name']}} <br>
                            	{{$line['base_unit_price']}} x {{$line['orig_quantity']}} = {{$line['line_total']}}
                            </small>
                            @endif
                        </td>
						<td class="text-right col-qty">
							{{$line['quantity']}} {{$line['units']}} 

							@if($receipt_details->show_base_unit_details && $line['quantity'] && $line['base_unit_multiplier'] !== 1)
                            <br><small>
                            	{{$line['quantity']}} x {{$line['base_unit_multiplier']}} = {{$line['orig_quantity']}} {{$line['base_unit_name']}}
                            </small>
                            @endif
						</td>
						<td class="text-right col-bonus">{{ !empty($line['bonus_quantity']) ? $line['bonus_quantity'] : '-' }}</td>
						<td class="text-right col-lot">{{ !empty($line['lot_number']) ? $line['lot_number'] : '-' }}</td>
						<td class="text-center col-expiry">{{ !empty($line['product_expiry']) ? $line['product_expiry'] : '-' }}</td>
						<td class="text-right col-unit-price">{{$line['unit_price_before_discount']}}</td>
						<td class="text-right col-tax">{{ $line['tax'] ?? '0.00' }}{{ !empty($line['tax_name']) ? ' '.$line['tax_name'] : '' }}</td>
						<td class="text-right col-discount">
							{{$line['total_line_discount'] ?? '0.00'}}
							@if(!empty($line['line_discount_percent']))
							 	({{$line['line_discount_percent']}}%)
							@endif
						</td>
						<td class="text-right col-subtotal">{{$line['line_total']}}</td>
					</tr>
					@if(!empty($line['modifiers']))
						@foreach($line['modifiers'] as $modifier)
							<tr>
								<td class="col-product">
		                            {{$modifier['name']}} {{$modifier['variation']}} 
		                            @if(!empty($modifier['sub_sku'])), {{$modifier['sub_sku']}} @endif @if(!empty($modifier['cat_code'])), {{$modifier['cat_code']}}@endif
		                            @if(!empty($modifier['sell_line_note']))({!!$modifier['sell_line_note']!!}) @endif 
		                        </td>
								<td class="text-right col-qty">{{$modifier['quantity']}} {{$modifier['units']}} </td>
								<td class="text-right col-bonus">-</td>
								<td class="text-right col-lot">-</td>
								<td class="text-center col-expiry">-</td>
								<td class="text-right col-unit-price">{{$modifier['unit_price_inc_tax']}}</td>
								<td class="text-right col-tax">0.00</td>
								<td class="text-right col-discount">0.00</td>
								<td class="text-right col-subtotal">{{$modifier['line_total']}}</td>
							</tr>
						@endforeach
					@endif
				@empty
					<tr>
						<td colspan="8">&nbsp;</td>
					</tr>
				@endforelse
			</tbody>
		</table>
	</div>
</div>

<div class="row receipt-summary">
	<div class="col-md-12"><hr/></div>
	<div class="col-xs-12 receipt-totals-wrap">
		<table class="receipt-totals">
			<tbody>
				@if(!empty($receipt_details->subtotal))
					<tr>
						<td class="label-cell">@lang('receipt.subtotal'):</td>
						<td class="value-cell">{{$receipt_details->subtotal}}</td>
					</tr>
				@endif

				@if(!empty($receipt_details->total_paid))
					<tr>
							<td class="label-cell">@lang('contact.total_paid'):</td>
						<td class="value-cell">{{$receipt_details->total_paid}}</td>
					</tr>
				@endif

				<tr class="grand-total-row">
						<td class="label-cell">@lang('sale.total'):</td>
					<td class="value-cell">{{$receipt_details->total}}</td>
				</tr>
			</tbody>
		</table>
	</div>

    <div class="border-bottom col-md-12">
	    @if(empty($receipt_details->hide_price) && !empty($receipt_details->tax_summary_label) )
	        <!-- tax -->
	        @if(!empty($receipt_details->taxes))
	        	<table class="table table-slim table-bordered">
	        		<tr>
	        			<th colspan="2" class="text-center">{{$receipt_details->tax_summary_label}}</th>
	        		</tr>
	        		@foreach($receipt_details->taxes as $key => $val)
	        			<tr>
	        				<td class="text-center"><b>{{$key}}</b></td>
	        				<td class="text-center">{{$val}}</td>
	        			</tr>
	        		@endforeach
	        	</table>
	        @endif
	    @endif
	</div>

	@if(!empty($receipt_details->additional_notes))
	    <div class="col-xs-12">
	    	<p>{!! nl2br($receipt_details->additional_notes) !!}</p>
	    </div>
    @endif
    
<div class="col-xs-12 receipt-total-line"></div>

</div>
@if($receipt_has_payment_box)
<div class="row receipt-payment-box">
	<div class="col-xs-4 receipt-payment-left">
		<div class="pay-label">
			@if($receipt_primary_is_mpesa)
				{{ __('payment.paid_via') }}
			@else
				{{ __('payment.paid_via') }} {{ $receipt_primary_method_label ?: __('payment.payment') }}
			@endif
		</div>
		@if($receipt_primary_is_mpesa)
			<img src="{{ asset('img/mpesa-logo.svg') }}" alt="M-Pesa">
		@else
			<div style="font-size: 12px; font-weight: 800; line-height: 1.1; padding-top: 4px; word-break: break-word;">
				{{ $receipt_primary_method_label ?: __('payment.payment') }}
			</div>
		@endif
	</div>
	<div class="col-xs-8 receipt-payment-right">
		<div class="receipt-payment-detail">
			<span class="detail-label">@lang('payment.transaction_code'):</span>
			<span class="detail-value receipt-payment-method">{{ !empty($receipt_primary_transaction_code) ? $receipt_primary_transaction_code : __('payment.na') }}</span>
		</div>
		<div class="receipt-payment-detail">
			<span class="detail-label">@lang('payment.amount_paid'):</span>
			<span class="detail-value receipt-payment-amount">{{ !empty($receipt_primary_payment['amount']) ? $receipt_primary_payment['amount'] : __('payment.na') }}</span>
		</div>
		<div class="receipt-payment-detail">
			<span class="detail-label">@lang('payment.paid_on'):</span>
			<span class="detail-value receipt-payment-date">{{ !empty($receipt_primary_paid_datetime) ? $receipt_primary_paid_datetime : __('payment.na') }}</span>
		</div>
	</div>
</div>
@endif

<div class="row" style="color: #000000 !important;">
	@if(!empty($receipt_details->footer_text))
	<div class="@if($receipt_details->show_barcode || $receipt_details->show_qr_code) col-xs-8 @else col-xs-12 @endif">
		{!! $receipt_details->footer_text !!}
	</div>
	@endif
	@if($receipt_details->show_barcode || $receipt_details->show_qr_code)
		<div class="@if(!empty($receipt_details->footer_text)) col-xs-4 @else col-xs-12 @endif text-center">
			@if($receipt_details->show_barcode)
				{{-- Barcode --}}
				<img class="center-block" src="data:image/png;base64,{{DNS1D::getBarcodePNG($receipt_details->invoice_no, 'C128', 2,30,array(39, 48, 54), true)}}">
			@endif
			
			@if($receipt_details->show_qr_code && !empty($receipt_details->qr_code_text))
				<img class="center-block mt-5" src="data:image/png;base64,{{DNS2D::getBarcodePNG($receipt_details->qr_code_text, 'QRCODE', 3, 3, [39, 48, 54])}}">
			@endif
		</div>
	@endif
</div>