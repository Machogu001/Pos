@extends('layouts.app')

@php
	if (!empty($status) && $status == 'quotation') {
		$title = __('lang_v1.add_quotation');
	} else if (!empty($status) && $status == 'draft') {
		$title = __('lang_v1.add_draft');
	} else {
		$title = __('sale.add_sale');
	}

	if($sale_type == 'sales_order') {
		$title = __('lang_v1.sales_order');
	}
@endphp

@section('title', $title)

@section('content')
<!-- Content Header (Page header) -->
<section class="content-header @if(!empty($bc_inline_entry)) bc-ui @endif">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">{{$title}}</h1>
</section>
<!-- Main content -->
<section class="content no-print @if(!empty($bc_inline_entry)) bc-ui @endif">
<input type="hidden" id="amount_rounding_method" value="{{$pos_settings['amount_rounding_method'] ?? ''}}">
@if(!empty($pos_settings['allow_overselling']))
	<input type="hidden" id="is_overselling_allowed">
@endif
@if(session('business.enable_rp') == 1)
    <input type="hidden" id="reward_point_enabled">
@endif
@if(count($business_locations) > 0)
<div class="row">
	<div class="col-sm-3">
		<div class="form-group">
			<div class="input-group">
				<span class="input-group-addon">
					<i class="fa fa-map-marker"></i>
				</span>
			{!! Form::select('select_location_id', $business_locations, $default_location->id ?? null, ['class' => 'form-control input-sm',
			'id' => 'select_location_id', 
			'required', 'autofocus'], $bl_attributes); !!}
			<span class="input-group-addon">
					@show_tooltip(__('tooltip.sale_location'))
				</span> 
			</div>
		</div>
	</div>
</div>
@endif

@php
	$custom_labels = json_decode(session('business.custom_labels'), true);
	$common_settings = session()->get('business.common_settings');
@endphp
<input type="hidden" id="item_addition_method" value="{{$business_details->item_addition_method}}">
	{!! Form::open(['url' => action([\App\Http\Controllers\SellPosController::class, 'store']), 'method' => 'post', 'id' => 'add_sell_form', 'files' => true ]) !!}
	 @if(!empty($sale_type))
	 	<input type="hidden" id="sale_type" name="type" value="{{$sale_type}}">
	 @endif
	<div class="row">
		<div class="col-md-12 col-sm-12">
			@if(!empty($bc_inline_entry))
				<div class="bc-fasttab-header bc-fasttab-toggle" id="bc_general_toggle" role="button" tabindex="0">
					<span>General</span>
					<span class="bc-fasttab-state"><i class="fa fa-chevron-up"></i> Show less</span>
				</div>
			@endif
			@component('components.widget', ['class' => 'box-solid', 'id' => !empty($bc_inline_entry) ? 'bc_general_card' : null])
				{!! Form::hidden('location_id', !empty($default_location) ? $default_location->id : null , ['id' => 'location_id', 'data-receipt_printer_type' => !empty($default_location->receipt_printer_type) ? $default_location->receipt_printer_type : 'browser', 'data-default_payment_accounts' => !empty($default_location) ? $default_location->default_payment_accounts : '']); !!}

				@if(!empty($bc_inline_entry))
					{{-- Business Central style General field grid; values follow the selected customer/location --}}
					<div class="bc-general-grid">
						<div class="bc-field">
							<label>Customer No.</label>
							<div class="bc-value" id="bc_info_customer_no">—</div>
						</div>
						<div class="bc-field">
							<label>Sell-to Customer Name</label>
							<div class="bc-value" id="bc_info_customer_name">—</div>
						</div>
						<div class="bc-field">
							<label>Phone No.</label>
							<div class="bc-value" id="bc_info_phone">—</div>
						</div>
						<div class="bc-field">
							<label>Email</label>
							<div class="bc-value" id="bc_info_email">—</div>
						</div>
						<div class="bc-field">
							<label>Address</label>
							<div class="bc-value" id="bc_info_address">—</div>
						</div>
						<div class="bc-field">
							<label>City</label>
							<div class="bc-value" id="bc_info_city">—</div>
						</div>
						<div class="bc-field">
							<label>Document Date</label>
							<div class="bc-value">{{ $default_datetime }}</div>
						</div>
						<div class="bc-field">
							<label>Posting Date</label>
							<div class="bc-value">{{ $default_datetime }}</div>
						</div>
						<div class="bc-field">
							<label>Salesperson Code</label>
							<div class="bc-value">{{ $bc_salesperson_code ?: '—' }}</div>
						</div>
						<div class="bc-field">
							<label>Location Code</label>
							<div class="bc-value" id="bc_info_location_code">{{ $bc_location_codes[$default_location->id ?? 0] ?? '—' }}</div>
						</div>
					</div>
					<div class="clearfix"></div>
				@endif

				@if(!empty($price_groups))
					@if(count($price_groups) > 1)
						<div class="col-sm-4">
							<div class="form-group">
								<div class="input-group">
									<span class="input-group-addon">
										<i class="fas fa-money-bill-alt"></i>
									</span>
									@php
										reset($price_groups);
										$selected_price_group = !empty($default_price_group_id) && array_key_exists($default_price_group_id, $price_groups) ? $default_price_group_id : null;
									@endphp
									{!! Form::hidden('hidden_price_group', key($price_groups), ['id' => 'hidden_price_group']) !!}
									{!! Form::select('price_group', $price_groups, $selected_price_group, ['class' => 'form-control select2', 'id' => 'price_group']); !!}
									<span class="input-group-addon">
										@show_tooltip(__('lang_v1.price_group_help_text'))
									</span> 
								</div>
							</div>
						</div>
						
					@else
						@php
							reset($price_groups);
						@endphp
						{!! Form::hidden('price_group', key($price_groups), ['id' => 'price_group']) !!}
					@endif
				@endif

				{!! Form::hidden('default_price_group', null, ['id' => 'default_price_group']) !!}

				@if(in_array('types_of_service', $enabled_modules) && !empty($types_of_service))
					<div class="col-md-4 col-sm-6">
						<div class="form-group">
							<div class="input-group">
								<span class="input-group-addon">
									<i class="fa fa-external-link-square-alt text-primary service_modal_btn"></i>
								</span>
								{!! Form::select('types_of_service_id', $types_of_service, null, ['class' => 'form-control', 'id' => 'types_of_service_id', 'style' => 'width: 100%;', 'placeholder' => __('lang_v1.select_types_of_service')]); !!}

								{!! Form::hidden('types_of_service_price_group', null, ['id' => 'types_of_service_price_group']) !!}

								<span class="input-group-addon">
									@show_tooltip(__('lang_v1.types_of_service_help'))
								</span> 
							</div>
							<small><p class="help-block hide" id="price_group_text">@lang('lang_v1.price_group'): <span></span></p></small>
						</div>
					</div>
				@endif
				
				@if(in_array('subscription', $enabled_modules))
					<div class="col-md-4 pull-right col-sm-6">
						<div class="checkbox">
							<label>
				              {!! Form::checkbox('is_recurring', 1, false, ['class' => 'input-icheck', 'id' => 'is_recurring']); !!} @lang('lang_v1.subscribe')?
				            </label><button type="button" data-toggle="modal" data-target="#recurringInvoiceModal" class="btn btn-link"><i class="fa fa-external-link"></i></button>@show_tooltip(__('lang_v1.recurring_invoice_help'))
						</div>
					</div>
				@endif
				<div class="clearfix"></div>
				<div class="@if(!empty($commission_agent)) col-sm-3 @else col-sm-4 @endif">
					<div class="form-group">
						{!! Form::label('contact_id', __('contact.customer') . ':*') !!}
						<div class="input-group">
							<span class="input-group-addon">
								<i class="fa fa-user"></i>
							</span>
							{{-- With BC enabled the customer starts blank so the user
							searches and selects the BC-synced customer each time --}}
							<input type="hidden" id="default_customer_id" 
							value="{{ !empty($bc_inline_entry) ? '' : $walk_in_customer['id'] }}" >
							<input type="hidden" id="default_customer_name" 
							value="{{ !empty($bc_inline_entry) ? '' : $walk_in_customer['name'] }}" >
							<input type="hidden" id="default_customer_balance" value="{{ !empty($bc_inline_entry) ? '' : ($walk_in_customer['balance'] ?? '') }}" >
							<input type="hidden" id="default_customer_address" value="{{ !empty($bc_inline_entry) ? '' : ($walk_in_customer['shipping_address'] ?? '') }}" >
							@if(!empty($walk_in_customer['price_calculation_type']) && $walk_in_customer['price_calculation_type'] == 'selling_price_group')
								<input type="hidden" id="default_selling_price_group" 
							value="{{ $walk_in_customer['selling_price_group_id'] ?? ''}}" >
							@endif
							{!! Form::select('contact_id', 
								[], null, ['class' => 'form-control mousetrap', 'id' => 'customer_id', 'placeholder' => __('lang_v1.enter_customer_name_or_phone'), 'required']); !!}
							<span class="input-group-btn">
								<button type="button" class="btn btn-default bg-white btn-flat add_new_customer" data-name=""><i class="fa fa-plus-circle text-primary fa-lg"></i></button>
							</span>
						</div>
						<small class="text-danger hide contact_due_text"><strong>@lang('account.customer_due'):</strong> <span></span></small>
					</div>
					<small>
					<strong>
						@lang('lang_v1.billing_address'):
					</strong>
					<div id="billing_address_div">
						{!! !empty($bc_inline_entry) ? '' : ($walk_in_customer['contact_address'] ?? '') !!}
					</div>
					<br>
					<strong>
						@lang('lang_v1.shipping_address'):
					</strong>
					<div id="shipping_address_div">
						{{$walk_in_customer['supplier_business_name'] ?? ''}},<br>
						{{$walk_in_customer['name'] ?? ''}},<br>
						{{$walk_in_customer['shipping_address'] ?? ''}}
					</div>					
					</small>
				</div>

				<div class="col-md-3">
		          <div class="form-group">
		            <div class="multi-input">
		            @php
						$is_pay_term_required = !empty($pos_settings['is_pay_term_required']);
					@endphp
		              {!! Form::label('pay_term_number', __('contact.pay_term') . ':') !!} @show_tooltip(__('tooltip.pay_term'))
		              <br/>
		              {{-- In BC mode the payment terms come from the BC customer and are not editable --}}
		              {!! Form::number('pay_term_number', $walk_in_customer['pay_term_number'], ['class' => 'form-control width-40 pull-left', 'placeholder' => __('contact.pay_term'), 'required' => $is_pay_term_required, 'readonly' => !empty($bc_inline_entry)]); !!}

		              {!! Form::select(
		                  'pay_term_type',
		                  ['months' => __('lang_v1.months'), 'days' => __('lang_v1.days')],
		                  $walk_in_customer['pay_term_type'],
		                  ['class' => 'form-control width-60 pull-left' . (!empty($bc_inline_entry) ? ' bc-locked-select' : ''), 'placeholder' => __('messages.please_select'), 'required' => $is_pay_term_required, 'tabindex' => !empty($bc_inline_entry) ? '-1' : null]
		              ); !!}
		            </div>
		          </div>
		        </div>

				@if(!empty($commission_agent))
				@php
					$is_commission_agent_required = !empty($pos_settings['is_commission_agent_required']);
				@endphp
				<div class="col-sm-3">
					<div class="form-group">
					{!! Form::label('commission_agent', __('lang_v1.commission_agent') . ':') !!}
					{!! Form::select('commission_agent', 
								$commission_agent, null, ['class' => 'form-control select2', 'id' => 'commission_agent', 'required' => $is_commission_agent_required]); !!}
					</div>
				</div>
				@endif
				<div class="@if(!empty($commission_agent)) col-sm-3 @else col-sm-4 @endif">
					<div class="form-group">
						{!! Form::label('transaction_date', __('sale.sale_date') . ':*') !!}
						<div class="input-group">
							<span class="input-group-addon">
								<i class="fa fa-calendar"></i>
							</span>
							{!! Form::text('transaction_date', $default_datetime, ['class' => 'form-control', 'readonly', 'required']); !!}
						</div>
					</div>
				</div>
				@if(!empty($status))
					<input type="hidden" name="status" id="status" value="{{$status}}">

					@if(in_array($status, ['draft', 'quotation']))
						<input type="hidden" id="disable_qty_alert">
					@endif
				@else
					<div class="@if(!empty($commission_agent)) col-sm-3 @else col-sm-4 @endif">
						<div class="form-group">
							{!! Form::label('status', __('sale.status') . ':*') !!}
							{{-- BC mode defaults to final: only final sales post to Business
							Central; draft/quotation/proforma are saved in POS only --}}
							{!! Form::select('status', $statuses, !empty($bc_inline_entry) ? 'final' : null, ['class' => 'form-control select2', 'placeholder' => __('messages.please_select'), 'required']); !!}
						</div>
					</div>
				@endif
				@if($sale_type != 'sales_order')
					<div class="col-sm-3">
						<div class="form-group">
							{!! Form::label('invoice_scheme_id', __('invoice.invoice_scheme') . ':') !!}
							{!! Form::select('invoice_scheme_id', $invoice_schemes, $default_invoice_schemes->id, ['class' => 'form-control select2', 'placeholder' => __('messages.please_select')]); !!}
						</div>
					</div>
				@endif
					@can('edit_invoice_number')
					{{-- Hidden in BC mode: the invoice number is always auto-generated --}}
					<div class="col-sm-3 @if(!empty($bc_inline_entry)) hide @endif">
						<div class="form-group">
							{!! Form::label('invoice_no', $sale_type == 'sales_order' ? __('restaurant.order_no') : __('sale.invoice_no') . ':') !!}
							{!! Form::text('invoice_no', null, ['class' => 'form-control', 'placeholder' => $sale_type == 'sales_order' ? __('restaurant.order_no') : __('sale.invoice_no')]); !!}
							<p class="help-block">@lang('lang_v1.keep_blank_to_autogenerate')</p>
						</div>
					</div>
					@endcan
				
				@php
			        $custom_field_1_label = !empty($custom_labels['sell']['custom_field_1']) ? $custom_labels['sell']['custom_field_1'] : '';

			        $is_custom_field_1_required = !empty($custom_labels['sell']['is_custom_field_1_required']) && $custom_labels['sell']['is_custom_field_1_required'] == 1 ? true : false;

			        $custom_field_2_label = !empty($custom_labels['sell']['custom_field_2']) ? $custom_labels['sell']['custom_field_2'] : '';

			        $is_custom_field_2_required = !empty($custom_labels['sell']['is_custom_field_2_required']) && $custom_labels['sell']['is_custom_field_2_required'] == 1 ? true : false;

			        $custom_field_3_label = !empty($custom_labels['sell']['custom_field_3']) ? $custom_labels['sell']['custom_field_3'] : '';

			        $is_custom_field_3_required = !empty($custom_labels['sell']['is_custom_field_3_required']) && $custom_labels['sell']['is_custom_field_3_required'] == 1 ? true : false;

			        $custom_field_4_label = !empty($custom_labels['sell']['custom_field_4']) ? $custom_labels['sell']['custom_field_4'] : '';

			        $is_custom_field_4_required = !empty($custom_labels['sell']['is_custom_field_4_required']) && $custom_labels['sell']['is_custom_field_4_required'] == 1 ? true : false;
		        @endphp
		        @if(!empty($custom_field_1_label))
		        	@php
		        		$label_1 = $custom_field_1_label . ':';
		        		if($is_custom_field_1_required) {
		        			$label_1 .= '*';
		        		}
		        	@endphp

		        	<div class="col-md-4">
				        <div class="form-group">
				            {!! Form::label('custom_field_1', $label_1 ) !!}
				            {!! Form::text('custom_field_1', null, ['class' => 'form-control','placeholder' => $custom_field_1_label, 'required' => $is_custom_field_1_required]); !!}
				        </div>
				    </div>
		        @endif
		        @if(!empty($custom_field_2_label))
		        	@php
		        		$label_2 = $custom_field_2_label . ':';
		        		if($is_custom_field_2_required) {
		        			$label_2 .= '*';
		        		}
		        	@endphp

		        	<div class="col-md-4">
				        <div class="form-group">
				            {!! Form::label('custom_field_2', $label_2 ) !!}
				            {!! Form::text('custom_field_2', null, ['class' => 'form-control','placeholder' => $custom_field_2_label, 'required' => $is_custom_field_2_required]); !!}
				        </div>
				    </div>
		        @endif
		        @if(!empty($custom_field_3_label))
		        	@php
		        		$label_3 = $custom_field_3_label . ':';
		        		if($is_custom_field_3_required) {
		        			$label_3 .= '*';
		        		}
		        	@endphp

		        	<div class="col-md-4">
				        <div class="form-group">
				            {!! Form::label('custom_field_3', $label_3 ) !!}
				            {!! Form::text('custom_field_3', null, ['class' => 'form-control','placeholder' => $custom_field_3_label, 'required' => $is_custom_field_3_required]); !!}
				        </div>
				    </div>
		        @endif
		        @if(!empty($custom_field_4_label))
		        	@php
		        		$label_4 = $custom_field_4_label . ':';
		        		if($is_custom_field_4_required) {
		        			$label_4 .= '*';
		        		}
		        	@endphp

		        	<div class="col-md-4">
				        <div class="form-group">
				            {!! Form::label('custom_field_4', $label_4 ) !!}
				            {!! Form::text('custom_field_4', null, ['class' => 'form-control','placeholder' => $custom_field_4_label, 'required' => $is_custom_field_4_required]); !!}
				        </div>
				    </div>
		        @endif
		        {{-- Hidden in BC mode: documents live on the BC posted invoice --}}
		        <div class="col-sm-3 @if(!empty($bc_inline_entry)) hide @endif">
	                <div class="form-group">
	                    {!! Form::label('upload_document', __('purchase.attach_document') . ':') !!}
	                    {!! Form::file('sell_document', ['id' => 'upload_document', 'accept' => implode(',', array_keys(config('constants.document_upload_mimes_types')))]); !!}
	                    <p class="help-block">
	                    	@lang('purchase.max_file_size', ['size' => (config('constants.document_size_limit') / 1000000)])
	                    	@includeIf('components.document_help_text')
	                    </p>
	                </div>
	            </div>
		        <div class="clearfix"></div>

		        @if((!empty($pos_settings['enable_sales_order']) && $sale_type != 'sales_order') || $is_order_request_enabled)
					<div class="col-sm-3">
						<div class="form-group">
							{!! Form::label('sales_order_ids', __('lang_v1.sales_order').':') !!}
							{!! Form::select('sales_order_ids[]', [], null, ['class' => 'form-control select2', 'multiple', 'id' => 'sales_order_ids', 'style' => 'width: 100%;']); !!}
						</div>
					</div>
					<div class="clearfix"></div>
				@endif
				<!-- Call restaurant module if defined -->
		        @if(in_array('tables' ,$enabled_modules) || in_array('service_staff' ,$enabled_modules))
		        	<span id="restaurant_module_span">
		        	</span>
		        @endif
			@endcomponent

			@if(!empty($bc_inline_entry))
				<div class="bc-fasttab-header">Lines</div>
			@endif
			@component('components.widget', ['class' => 'box-solid'])
				<div class="col-sm-10 col-sm-offset-1">
					<div class="form-group">
						<div class="input-group">
							<div class="input-group-btn">
								<button type="button" class="btn btn-default bg-white btn-flat" data-toggle="modal" data-target="#configure_search_modal" title="{{__('lang_v1.configure_product_search')}}"><i class="fas fa-search-plus"></i></button>
							</div>
							{!! Form::text('search_product', null, ['class' => 'form-control mousetrap', 'id' => 'search_product', 'placeholder' => __('lang_v1.search_product_placeholder'),
							'disabled' => is_null($default_location)? true : false,
							'autofocus' => is_null($default_location)? false : true,
							]); !!}
							<span class="input-group-btn">
								<button type="button" class="btn btn-default bg-white btn-flat pos_add_quick_product" data-href="{{action([\App\Http\Controllers\ProductController::class, 'quickAdd'])}}" data-container=".quick_add_product_modal"><i class="fa fa-plus-circle text-primary fa-lg"></i></button>
							</span>
						</div>
					</div>
				</div>

				<div class="row col-sm-12 pos_product_div" style="min-height: 0">

					<input type="hidden" name="sell_price_tax" id="sell_price_tax" value="{{$business_details->sell_price_tax}}">

					<!-- Keeps count of product rows -->
					<input type="hidden" id="product_row_count" 
						value="0">
					@php
						$hide_tax = '';
						if( session()->get('business.enable_inline_tax') == 0){
							$hide_tax = 'hide';
						}
					@endphp
					<div class="table-responsive">
					<table class="table table-condensed table-bordered table-striped table-responsive" id="pos_table">
						<thead>
							@if(!empty($bc_inline_entry))
								{{-- Business Central sales line column layout --}}
								<tr>
									<th class="text-center">No.</th>
									<th class="text-center">Description</th>
									<th class="text-center">Unit of Measure</th>
									<th class="text-center">Quantity</th>
									@if(!empty($pos_settings['inline_service_staff']))
										<th class="text-center">
											@lang('restaurant.service_staff')
										</th>
									@endif
									<th class="text-center @if(!auth()->user()->can('edit_product_price_from_sale_screen')) hide @endif">Unit Price</th>
									<th class="text-center">Line Discount</th>
									<th class="text-center {{$hide_tax}}">
										@lang('sale.tax')
									</th>
									<th class="text-center {{$hide_tax}}">
										@lang('sale.price_inc_tax')
									</th>
									@if(!empty($common_settings['enable_product_warranty']))
										<th>@lang('lang_v1.warranty')</th>
									@endif
									<th class="text-center">Line Amount</th>
									<th class="text-center"><i class="fas fa-times" aria-hidden="true"></i></th>
								</tr>
							@else
								<tr>
									<th class="text-center" scope="col">
										@lang('sale.product')
									</th>
									<th class="text-center">
										@lang('sale.qty')
									</th>
									@if(!empty($pos_settings['inline_service_staff']))
										<th class="text-center">
											@lang('restaurant.service_staff')
										</th>
									@endif
									<th class="@if(!auth()->user()->can('edit_product_price_from_sale_screen')) hide @endif">
										@lang('sale.unit_price')
									</th>
									<th class="@if(!auth()->user()->can('edit_product_discount_from_sale_screen')) hide @endif">
										@lang('receipt.discount')
									</th>
									<th class="text-center {{$hide_tax}}">
										@lang('sale.tax')
									</th>
									<th class="text-center {{$hide_tax}}">
										@lang('sale.price_inc_tax')
									</th>
									@if(!empty($common_settings['enable_product_warranty']))
										<th>@lang('lang_v1.warranty')</th>
									@endif
									<th class="text-center">
										@lang('sale.subtotal')
									</th>
									<th class="text-center"><i class="fas fa-times" aria-hidden="true"></i></th>
								</tr>
							@endif
							@if(!empty($bc_inline_entry))
								@php
									// Columns after qty: unit price, discount, tax, price inc. tax, line amount, remove (+ optional ones)
									$bc_entry_colspan = 6;
									if (!empty($pos_settings['inline_service_staff'])) {
										$bc_entry_colspan++;
									}
									if (!empty($common_settings['enable_product_warranty'])) {
										$bc_entry_colspan++;
									}
								@endphp
								<tr id="bc_inline_entry_row">
									{{-- Spans the No. and Description columns --}}
									<td class="pos-product-cell" colspan="2">
										<input type="text" id="bc_inline_product_search" class="form-control"
											placeholder="{{ __('lang_v1.search_product_placeholder') }}"
											autocomplete="off"
											@if(is_null($default_location)) disabled @endif>
									</td>
									<td class="pos-uom-cell">
										<input type="text" class="form-control" disabled tabindex="-1" placeholder="UOM">
									</td>
									<td class="pos-qty-cell">
										<input type="text" class="form-control" disabled tabindex="-1" placeholder="@lang('sale.qty')">
									</td>
									<td colspan="{{ $bc_entry_colspan }}" class="text-muted" style="vertical-align: middle; font-weight: normal;">
										<small><i class="fa fa-level-down" aria-hidden="true"></i> @lang('sale.product') &rarr; @lang('sale.qty') &rarr; <i class="fa fa-plus" aria-hidden="true"></i></small>
									</td>
								</tr>
							@endif
						</thead>
						<tbody></tbody>
					</table>
					</div>
					{{-- Hidden in BC mode: totals are managed/synced via Business Central --}}
					<div class="table-responsive @if(!empty($bc_inline_entry)) hide @endif">
					<table class="table table-condensed table-bordered table-striped">
						<tr>
							<td>
								<div class="pull-right">
								<b>@lang('sale.item'):</b> 
								<span class="total_quantity">0</span>
								&nbsp;&nbsp;&nbsp;&nbsp;
								<b>@lang('sale.total'): </b>
									<span class="price_total">0</span>
								</div>
							</td>
						</tr>
					</table>
					</div>
					@if(!empty($bc_inline_entry))
						{{-- Business Central style invoice totals --}}
						<div class="row">
							<div class="col-md-4 col-md-offset-8 col-sm-6 col-sm-offset-6">
								<div class="bc-totals-panel">
									<div class="bc-total-row">
										<span class="bc-total-label">Subtotal Excl. VAT</span>
										<span id="bc_subtotal_excl">0.00</span>
									</div>
									<div class="bc-total-row">
										<span class="bc-total-label">Inv. Discount Amount Excl. VAT</span>
										<span id="bc_inv_discount">0.00</span>
									</div>
									<div class="bc-total-row">
										<span class="bc-total-label">Invoice Discount %</span>
										<span id="bc_invoice_discount_pct">0</span>
									</div>
									<div class="bc-total-row">
										<span class="bc-total-label">Total Excl. VAT</span>
										<span id="bc_total_excl">0.00</span>
									</div>
									<div class="bc-total-row">
										<span class="bc-total-label">Total VAT</span>
										<span id="bc_total_vat">0.00</span>
									</div>
									<div class="bc-total-row bc-total-grand">
										<span class="bc-total-label">Total Incl. VAT</span>
										<span id="bc_total_incl">0.00</span>
									</div>
								</div>
							</div>
						</div>
					@endif
				</div>
			@endcomponent
			{{-- Discount, order tax and sell note: hidden in BC mode, defaults still submitted --}}
			@component('components.widget', ['class' => 'box-solid' . (!empty($bc_inline_entry) ? ' hide' : '')])
				<div class="col-md-4  @if($sale_type == 'sales_order') hide @endif">
			        <div class="form-group">
			            {!! Form::label('discount_type', __('sale.discount_type') . ':*' ) !!}
			            <div class="input-group">
			                <span class="input-group-addon">
			                    <i class="fa fa-info"></i>
			                </span>
			                {!! Form::select('discount_type', ['fixed' => __('lang_v1.fixed'), 'percentage' => __('lang_v1.percentage')], 'percentage' , ['class' => 'form-control','placeholder' => __('messages.please_select'), 'required', 'data-default' => 'percentage']); !!}
			            </div>
			        </div>
			    </div>
			    @php
			    	$max_discount = !is_null(auth()->user()->max_sales_discount_percent) ? auth()->user()->max_sales_discount_percent : '';

			    	//if sale discount is more than user max discount change it to max discount
			    	$sales_discount = $business_details->default_sales_discount;
			    	if($max_discount != '' && $sales_discount > $max_discount) $sales_discount = $max_discount;

			    	$default_sales_tax = $business_details->default_sales_tax;

			    	if($sale_type == 'sales_order') {
			    		$sales_discount = 0;
			    		$default_sales_tax = null;
			    	}
			    @endphp
			    <div class="col-md-4 @if($sale_type == 'sales_order') hide @endif">
			        <div class="form-group">
			            {!! Form::label('discount_amount', __('sale.discount_amount') . ':*' ) !!}
			            <div class="input-group">
			                <span class="input-group-addon">
			                    <i class="fa fa-info"></i>
			                </span>
			                {!! Form::text('discount_amount', @num_format($sales_discount), ['class' => 'form-control input_number', 'data-default' => $sales_discount, 'data-max-discount' => $max_discount, 'data-max-discount-error_msg' => __('lang_v1.max_discount_error_msg', ['discount' => $max_discount != '' ? @num_format($max_discount) : '']) ]); !!}
			            </div>
			        </div>
			    </div>
			    <div class="col-md-4 @if($sale_type == 'sales_order') hide @endif"><br>
			    	<b>@lang( 'sale.discount_amount' ):</b>(-) 
					<span class="display_currency" id="total_discount">0</span>
			    </div>
			    <div class="clearfix"></div>
			    <div class="col-md-12 well well-sm bg-light-gray @if(session('business.enable_rp') != 1 || $sale_type == 'sales_order') hide @endif">
			    	<input type="hidden" name="rp_redeemed" id="rp_redeemed" value="0">
			    	<input type="hidden" name="rp_redeemed_amount" id="rp_redeemed_amount" value="0">
			    	<div class="col-md-12"><h4>{{session('business.rp_name')}}</h4></div>
			    	<div class="col-md-4">
				        <div class="form-group">
				            {!! Form::label('rp_redeemed_modal', __('lang_v1.redeemed') . ':' ) !!}
				            <div class="input-group">
				                <span class="input-group-addon">
				                    <i class="fa fa-gift"></i>
				                </span>
				                {!! Form::number('rp_redeemed_modal', 0, ['class' => 'form-control direct_sell_rp_input', 'data-amount_per_unit_point' => session('business.redeem_amount_per_unit_rp'), 'min' => 0, 'data-max_points' => 0, 'data-min_order_total' => session('business.min_order_total_for_redeem') ]); !!}
				                <input type="hidden" id="rp_name" value="{{session('business.rp_name')}}">
				            </div>
				        </div>
				    </div>
				    <div class="col-md-4">
				    	<p><strong>@lang('lang_v1.available'):</strong> <span id="available_rp">0</span></p>
				    </div>
				    <div class="col-md-4">
				    	<p><strong>@lang('lang_v1.redeemed_amount'):</strong> (-)<span id="rp_redeemed_amount_text">0</span></p>
				    </div>
			    </div>
			    <div class="clearfix"></div>
			    <div class="col-md-4  @if($sale_type == 'sales_order') hide @endif">
			    	<div class="form-group">
			            {!! Form::label('tax_rate_id', __('sale.order_tax') . ':*' ) !!}
			            <div class="input-group">
			                <span class="input-group-addon">
			                    <i class="fa fa-info"></i>
			                </span>
			                {!! Form::select('tax_rate_id', $taxes['tax_rates'], $default_sales_tax, ['placeholder' => __('messages.please_select'), 'class' => 'form-control', 'data-default'=> $default_sales_tax], $taxes['attributes']); !!}

							<input type="hidden" name="tax_calculation_amount" id="tax_calculation_amount" 
							value="@if(empty($edit)) {{@num_format($business_details->tax_calculation_amount)}} @else {{@num_format($transaction->tax?->amount)}} @endif" data-default="{{$business_details->tax_calculation_amount}}">
			            </div>
			        </div>
			    </div>
			    <div class="col-md-4 col-md-offset-4  @if($sale_type == 'sales_order') hide @endif">
			    	<b>@lang( 'sale.order_tax' ):</b>(+) 
					<span class="display_currency" id="order_tax">0</span>
			    </div>				
				
			    <div class="col-md-12">
			    	<div class="form-group">
						{!! Form::label('sell_note',__('sale.sell_note')) !!}
						{!! Form::textarea('sale_note', null, ['class' => 'form-control', 'rows' => 3]); !!}
					</div>
			    </div>
				<input type="hidden" name="is_direct_sale" value="1">
			@endcomponent
			{{-- Shipping and total payable: hidden in BC mode, defaults still submitted --}}
			@component('components.widget', ['class' => 'box-solid' . (!empty($bc_inline_entry) ? ' hide' : '')])
			<div class="col-md-4">
				<div class="form-group">
		            {!! Form::label('shipping_details', __('sale.shipping_details')) !!}
		            {!! Form::textarea('shipping_details',null, ['class' => 'form-control','placeholder' => __('sale.shipping_details') ,'rows' => '3', 'cols'=>'30']); !!}
		        </div>
			</div>
			<div class="col-md-4">
				<div class="form-group">
		            {!! Form::label('shipping_address', __('lang_v1.shipping_address')) !!}
		            {!! Form::textarea('shipping_address',null, ['class' => 'form-control','placeholder' => __('lang_v1.shipping_address') ,'rows' => '3', 'cols'=>'30']); !!}
		        </div>
			</div>
			<div class="col-md-4">
				<div class="form-group">
					{!!Form::label('shipping_charges', __('sale.shipping_charges'))!!}
					<div class="input-group">
					<span class="input-group-addon">
					<i class="fa fa-info"></i>
					</span>
					{!!Form::text('shipping_charges',@num_format(0.00),['class'=>'form-control input_number','placeholder'=> __('sale.shipping_charges')]);!!}
					</div>
				</div>
			</div>
			<div class="clearfix"></div>
			<div class="col-md-4">
				<div class="form-group">
		            {!! Form::label('shipping_status', __('lang_v1.shipping_status')) !!}
		            {!! Form::select('shipping_status',$shipping_statuses, null, ['class' => 'form-control','placeholder' => __('messages.please_select')]); !!}
		        </div>
			</div>
			<div class="col-md-4">
		        <div class="form-group">
		            {!! Form::label('delivered_to', __('lang_v1.delivered_to') . ':' ) !!}
		            {!! Form::text('delivered_to', null, ['class' => 'form-control','placeholder' => __('lang_v1.delivered_to')]); !!}
		        </div>
		    </div>
			<div class="col-md-4">
				<div class="form-group">
					{!! Form::label('delivery_person', __('lang_v1.delivery_person') . ':' ) !!}
					{!! Form::select('delivery_person', $users, null, ['class' => 'form-control select2','placeholder' => __('messages.please_select')]); !!}
				</div>
			</div>
		    @php
		        $shipping_custom_label_1 = !empty($custom_labels['shipping']['custom_field_1']) ? $custom_labels['shipping']['custom_field_1'] : '';

		        $is_shipping_custom_field_1_required = !empty($custom_labels['shipping']['is_custom_field_1_required']) && $custom_labels['shipping']['is_custom_field_1_required'] == 1 ? true : false;

		        $shipping_custom_label_2 = !empty($custom_labels['shipping']['custom_field_2']) ? $custom_labels['shipping']['custom_field_2'] : '';

		        $is_shipping_custom_field_2_required = !empty($custom_labels['shipping']['is_custom_field_2_required']) && $custom_labels['shipping']['is_custom_field_2_required'] == 1 ? true : false;

		        $shipping_custom_label_3 = !empty($custom_labels['shipping']['custom_field_3']) ? $custom_labels['shipping']['custom_field_3'] : '';
		        
		        $is_shipping_custom_field_3_required = !empty($custom_labels['shipping']['is_custom_field_3_required']) && $custom_labels['shipping']['is_custom_field_3_required'] == 1 ? true : false;

		        $shipping_custom_label_4 = !empty($custom_labels['shipping']['custom_field_4']) ? $custom_labels['shipping']['custom_field_4'] : '';
		        
		        $is_shipping_custom_field_4_required = !empty($custom_labels['shipping']['is_custom_field_4_required']) && $custom_labels['shipping']['is_custom_field_4_required'] == 1 ? true : false;

		        $shipping_custom_label_5 = !empty($custom_labels['shipping']['custom_field_5']) ? $custom_labels['shipping']['custom_field_5'] : '';
		        
		        $is_shipping_custom_field_5_required = !empty($custom_labels['shipping']['is_custom_field_5_required']) && $custom_labels['shipping']['is_custom_field_5_required'] == 1 ? true : false;
	        @endphp

	        @if(!empty($shipping_custom_label_1))
	        	@php
	        		$label_1 = $shipping_custom_label_1 . ':';
	        		if($is_shipping_custom_field_1_required) {
	        			$label_1 .= '*';
	        		}
	        	@endphp

	        	<div class="col-md-4">
			        <div class="form-group">
			            {!! Form::label('shipping_custom_field_1', $label_1 ) !!}
			            {!! Form::text('shipping_custom_field_1', !empty($walk_in_customer['shipping_custom_field_details']['shipping_custom_field_1']) ? $walk_in_customer['shipping_custom_field_details']['shipping_custom_field_1'] : null, ['class' => 'form-control','placeholder' => $shipping_custom_label_1, 'required' => $is_shipping_custom_field_1_required]); !!}
			        </div>
			    </div>
	        @endif
	        @if(!empty($shipping_custom_label_2))
	        	@php
	        		$label_2 = $shipping_custom_label_2 . ':';
	        		if($is_shipping_custom_field_2_required) {
	        			$label_2 .= '*';
	        		}
	        	@endphp

	        	<div class="col-md-4">
			        <div class="form-group">
			            {!! Form::label('shipping_custom_field_2', $label_2 ) !!}
			            {!! Form::text('shipping_custom_field_2', !empty($walk_in_customer['shipping_custom_field_details']['shipping_custom_field_2']) ? $walk_in_customer['shipping_custom_field_details']['shipping_custom_field_2'] : null, ['class' => 'form-control','placeholder' => $shipping_custom_label_2, 'required' => $is_shipping_custom_field_2_required]); !!}
			        </div>
			    </div>
	        @endif
	        @if(!empty($shipping_custom_label_3))
	        	@php
	        		$label_3 = $shipping_custom_label_3 . ':';
	        		if($is_shipping_custom_field_3_required) {
	        			$label_3 .= '*';
	        		}
	        	@endphp

	        	<div class="col-md-4">
			        <div class="form-group">
			            {!! Form::label('shipping_custom_field_3', $label_3 ) !!}
			            {!! Form::text('shipping_custom_field_3', !empty($walk_in_customer['shipping_custom_field_details']['shipping_custom_field_3']) ? $walk_in_customer['shipping_custom_field_details']['shipping_custom_field_3'] : null, ['class' => 'form-control','placeholder' => $shipping_custom_label_3, 'required' => $is_shipping_custom_field_3_required]); !!}
			        </div>
			    </div>
	        @endif
	        @if(!empty($shipping_custom_label_4))
	        	@php
	        		$label_4 = $shipping_custom_label_4 . ':';
	        		if($is_shipping_custom_field_4_required) {
	        			$label_4 .= '*';
	        		}
	        	@endphp

	        	<div class="col-md-4">
			        <div class="form-group">
			            {!! Form::label('shipping_custom_field_4', $label_4 ) !!}
			            {!! Form::text('shipping_custom_field_4', !empty($walk_in_customer['shipping_custom_field_details']['shipping_custom_field_4']) ? $walk_in_customer['shipping_custom_field_details']['shipping_custom_field_4'] : null, ['class' => 'form-control','placeholder' => $shipping_custom_label_4, 'required' => $is_shipping_custom_field_4_required]); !!}
			        </div>
			    </div>
	        @endif
	        @if(!empty($shipping_custom_label_5))
	        	@php
	        		$label_5 = $shipping_custom_label_5 . ':';
	        		if($is_shipping_custom_field_5_required) {
	        			$label_5 .= '*';
	        		}
	        	@endphp

	        	<div class="col-md-4">
			        <div class="form-group">
			            {!! Form::label('shipping_custom_field_5', $label_5 ) !!}
			            {!! Form::text('shipping_custom_field_5', !empty($walk_in_customer['shipping_custom_field_details']['shipping_custom_field_5']) ? $walk_in_customer['shipping_custom_field_details']['shipping_custom_field_5'] : null, ['class' => 'form-control','placeholder' => $shipping_custom_label_5, 'required' => $is_shipping_custom_field_5_required]); !!}
			        </div>
			    </div>
	        @endif
	        <div class="col-md-4">
                <div class="form-group">
                    {!! Form::label('shipping_documents', __('lang_v1.shipping_documents') . ':') !!}
                    {!! Form::file('shipping_documents[]', ['id' => 'shipping_documents', 'multiple', 'accept' => implode(',', array_keys(config('constants.document_upload_mimes_types')))]); !!}
                    <p class="help-block">
                    	@lang('purchase.max_file_size', ['size' => (config('constants.document_size_limit') / 1000000)])
                    	@includeIf('components.document_help_text')
                    </p>
                </div>
            </div>
	        <div class="clearfix"></div>
	        <div class="col-md-12 text-center">
				<button type="button" class="tw-dw-btn tw-dw-btn-primary tw-dw-btn-sm tw-text-white" id="toggle_additional_expense"> <i class="fas fa-plus"></i> @lang('lang_v1.add_additional_expenses') <i class="fas fa-chevron-down"></i></button>
			</div>
			<div class="col-md-8 col-md-offset-4" id="additional_expenses_div" style="display: none;">
				<table class="table table-condensed">
					<thead>
						<tr>
							<th>@lang('lang_v1.additional_expense_name')</th>
							<th>@lang('sale.amount')</th>
						</tr>
					</thead>
					<tbody>
						<tr>
							<td>
								{!! Form::text('additional_expense_key_1', null, ['class' => 'form-control', 'id' => 'additional_expense_key_1']); !!}
							</td>
							<td>
								{!! Form::text('additional_expense_value_1', 0, ['class' => 'form-control input_number', 'id' => 'additional_expense_value_1']); !!}
							</td>
						</tr>
						<tr>
							<td>
								{!! Form::text('additional_expense_key_2', null, ['class' => 'form-control', 'id' => 'additional_expense_key_2']); !!}
							</td>
							<td>
								{!! Form::text('additional_expense_value_2', 0, ['class' => 'form-control input_number', 'id' => 'additional_expense_value_2']); !!}
							</td>
						</tr>
						<tr>
							<td>
								{!! Form::text('additional_expense_key_3', null, ['class' => 'form-control', 'id' => 'additional_expense_key_3']); !!}
							</td>
							<td>
								{!! Form::text('additional_expense_value_3', 0, ['class' => 'form-control input_number', 'id' => 'additional_expense_value_3']); !!}
							</td>
						</tr>
						<tr>
							<td>
								{!! Form::text('additional_expense_key_4', null, ['class' => 'form-control', 'id' => 'additional_expense_key_4']); !!}
							</td>
							<td>
								{!! Form::text('additional_expense_value_4', 0, ['class' => 'form-control input_number', 'id' => 'additional_expense_value_4']); !!}
							</td>
						</tr>
					</tbody>
				</table>
			</div>
		    <div class="col-md-4 col-md-offset-8">
		    	@if(!empty($pos_settings['amount_rounding_method']) && $pos_settings['amount_rounding_method'] > 0)
		    	<small id="round_off"><br>(@lang('lang_v1.round_off'): <span id="round_off_text">0</span>)</small>
				<br/>
				<input type="hidden" name="round_off_amount" 
					id="round_off_amount" value=0>
				@endif
		    	<div><b>@lang('sale.total_payable'): </b>
					<input type="hidden" name="final_total" id="final_total_input">
					<span id="total_payable">0</span>
				</div>
		    </div>
			@endcomponent
		</div>
	</div>
	@if(!empty($common_settings['is_enabled_export']) && $sale_type != 'sales_order')
		@component('components.widget', ['class' => 'box-solid', 'title' => __('lang_v1.export')])
			<div class="col-md-12 mb-12">
                <div class="form-check">
                    <input type="checkbox" name="is_export" class="form-check-input" id="is_export" @if(!empty($walk_in_customer['is_export'])) checked @endif>
                    <label class="form-check-label" for="is_export">@lang('lang_v1.is_export')</label>
                </div>
            </div>
	        @php
	            $i = 1;
	        @endphp
	        @for($i; $i <= 6 ; $i++)
	            <div class="col-md-4 export_div" @if(empty($walk_in_customer['is_export'])) style="display: none;" @endif>
	                <div class="form-group">
	                    {!! Form::label('export_custom_field_'.$i, __('lang_v1.export_custom_field'.$i).':') !!}
	                    {!! Form::text('export_custom_fields_info['.'export_custom_field_'.$i.']', !empty($walk_in_customer['export_custom_field_'.$i]) ? $walk_in_customer['export_custom_field_'.$i] : null, ['class' => 'form-control','placeholder' => __('lang_v1.export_custom_field'.$i), 'id' => 'export_custom_field_'.$i]); !!}
	                </div>
	            </div>
	        @endfor
		@endcomponent
	@endif
	@php
		$is_enabled_download_pdf = config('constants.enable_download_pdf');
		$payment_body_id = 'payment_rows_div';
		if ($is_enabled_download_pdf) {
			$payment_body_id = '';
		}
	@endphp
	@if((empty($status) || (!in_array($status, ['quotation', 'draft'])) || $is_enabled_download_pdf) && $sale_type != 'sales_order')
		@can('sell.payments')
			{{-- Outer wrapper keeps the payment section hidden in BC mode even when
			page JS toggles the 'hide' class on #payment_rows_div itself --}}
			<div class="@if(!empty($bc_inline_entry)) hide @endif">
			@component('components.widget', ['class' => 'box-solid' . (!empty($bc_inline_entry) ? ' hide' : ''), 'id' => $payment_body_id, 'title' => __('purchase.add_payment')])
			@if($is_enabled_download_pdf)
				<div class="well row">
					<div class="col-md-6">
						<div class="form-group">
							{!! Form::label("prefer_payment_method" , __('lang_v1.prefer_payment_method') . ':') !!}
							@show_tooltip(__('lang_v1.this_will_be_shown_in_pdf'))
							<div class="input-group">
								<span class="input-group-addon">
									<i class="fas fa-money-bill-alt"></i>
								</span>
								{!! Form::select("prefer_payment_method", $payment_types, 'cash', ['class' => 'form-control','style' => 'width:100%;']); !!}
							</div>
						</div>
					</div>
					<div class="col-md-6">
						<div class="form-group">
							{!! Form::label("prefer_payment_account" , __('lang_v1.prefer_payment_account') . ':') !!}
							@show_tooltip(__('lang_v1.this_will_be_shown_in_pdf'))
							<div class="input-group">
								<span class="input-group-addon">
									<i class="fas fa-money-bill-alt"></i>
								</span>
								{!! Form::select("prefer_payment_account", $accounts, null, ['class' => 'form-control','style' => 'width:100%;']); !!}
							</div>
						</div>
					</div>
				</div>
			@endif
			@if(empty($status) || !in_array($status, ['quotation', 'draft']))
				<div class="payment_row" @if($is_enabled_download_pdf) id="payment_rows_div" @endif>
					<div class="row">
						<div class="col-md-12 mb-12">
							<strong>@lang('lang_v1.advance_balance'):</strong> <span id="advance_balance_text"></span>
							{!! Form::hidden('advance_balance', null, ['id' => 'advance_balance', 'data-error-msg' => __('lang_v1.required_advance_balance_not_available')]); !!}
						</div>
					</div>
					@include('sale_pos.partials.payment_row_form', ['row_index' => 0, 'show_date' => true, 'show_denomination' => true])
                </div>
                <div class="payment_row">
					<div class="row">
						<div class="col-md-12">
			        		<hr>
			        		<strong>
			        			@lang('lang_v1.change_return'):
			        		</strong>
			        		<br/>
			        		<span class="lead text-bold change_return_span">0</span>
			        		{!! Form::hidden("change_return", $change_return['amount'], ['class' => 'form-control change_return input_number', 'required', 'id' => "change_return"]); !!}
			        		<!-- <span class="lead text-bold total_quantity">0</span> -->
			        		@if(!empty($change_return['id']))
			            		<input type="hidden" name="change_return_id" 
			            		value="{{$change_return['id']}}">
			            	@endif
						</div>
					</div>
					<div class="row hide payment_row" id="change_return_payment_data">
						<div class="col-md-4">
							<div class="form-group">
								{!! Form::label("change_return_method" , __('lang_v1.change_return_payment_method') . ':*') !!}
								<div class="input-group">
									<span class="input-group-addon">
										<i class="fas fa-money-bill-alt"></i>
									</span>
									@php
										$_payment_method = empty($change_return['method']) && array_key_exists('cash', $payment_types) ? 'cash' : $change_return['method'];

										$_payment_types = $payment_types;
										if(isset($_payment_types['advance'])) {
											unset($_payment_types['advance']);
										}
									@endphp
									{!! Form::select("payment[change_return][method]", $_payment_types, $_payment_method, ['class' => 'form-control col-md-12 payment_types_dropdown', 'id' => 'change_return_method', 'style' => 'width:100%;']); !!}
								</div>
							</div>
						</div>
						@if(!empty($accounts))
						<div class="col-md-4">
							<div class="form-group">
								{!! Form::label("change_return_account" , __('lang_v1.change_return_payment_account') . ':') !!}
								<div class="input-group">
									<span class="input-group-addon">
										<i class="fas fa-money-bill-alt"></i>
									</span>
									{!! Form::select("payment[change_return][account_id]", $accounts, !empty($change_return['account_id']) ? $change_return['account_id'] : '' , ['class' => 'form-control select2', 'id' => 'change_return_account', 'style' => 'width:100%;']); !!}
								</div>
							</div>
						</div>
						@endif
						@include('sale_pos.partials.payment_type_details', ['payment_line' => $change_return, 'row_index' => 'change_return'])
					</div>
					<hr>
					<div class="row">
						<div class="col-sm-12">
							<div class="pull-right"><strong>@lang('lang_v1.balance'):</strong> <span class="balance_due">0.00</span></div>
						</div>
					</div>
				</div>
			@endif
			@endcomponent
			</div>
		@endcan
	@endif
	
	<div class="row">
		{!! Form::hidden('is_save_and_print', 0, ['id' => 'is_save_and_print']); !!}
		<div class="col-sm-12 text-center tw-mt-4">
			<button type="button" id="submit-sell" class="tw-dw-btn tw-dw-btn-primary tw-dw-btn-lg tw-text-white">Post</button>
			<button type="button" id="save-and-print" class="tw-dw-btn tw-dw-btn-success tw-dw-btn-lg tw-text-white">Post & Print</button>
		</div>
	</div>
	
	@if(empty($pos_settings['disable_recurring_invoice']))
		@include('sale_pos.partials.recurring_invoice_modal')
	@endif
	
	{!! Form::close() !!}
</section>

<div class="modal fade contact_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">
	@include('contact.create', ['quick_add' => true])
</div>
<!-- /.content -->
<div class="modal fade register_details_modal" tabindex="-1" role="dialog" 
	aria-labelledby="gridSystemModalLabel">
</div>
<div class="modal fade close_register_modal" tabindex="-1" role="dialog" 
	aria-labelledby="gridSystemModalLabel">
</div>

<!-- quick product modal -->
<div class="modal fade quick_add_product_modal" tabindex="-1" role="dialog" aria-labelledby="modalTitle"></div>

<div class="modal fade types_of_service_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel"></div>


@include('sale_pos.partials.configure_search_modal')

<style>
	/* Make the Add Sale product autocomplete look like the stocktake inline dropdown */
	.ui-autocomplete {
		position: absolute;
		background: #ffffff;
		border: 1px solid #ddd;
		border-radius: 4px;
		box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
		z-index: 1000;
		max-height: 200px;
		overflow-y: auto;
		width: 100%;
	}

	.ui-menu .ui-menu-item-wrapper {
		padding: 8px 12px;
		cursor: pointer;
		border-bottom: 1px solid #eee;
		color: #0f172a;
		transition: background-color 0.15s ease, color 0.15s ease, box-shadow 0.15s ease;
	}

	.ui-menu .ui-menu-item-wrapper > div {
		color: inherit;
		font-weight: 500;
		line-height: 1.45;
	}

	.ui-menu .ui-menu-item-wrapper.ui-state-active,
	.ui-menu .ui-menu-item-wrapper.ui-state-focus {
		background: linear-gradient(135deg, #0f766e 0%, #14b8a6 100%);
		border-color: #0f766e;
		box-shadow: inset 4px 0 0 #0f172a;
		color: #ffffff;
		margin: 0;
	}

	.ui-menu .ui-menu-item-wrapper.ui-state-active > div,
	.ui-menu .ui-menu-item-wrapper.ui-state-focus > div {
		color: #ffffff;
	}

	#pos_table > tbody > tr > td {
		vertical-align: top;
	}

	#pos_table .pos-product-cell {
		min-width: 290px;
	}

	#pos_table .pos-itemno-cell {
		min-width: 110px;
		white-space: nowrap;
	}

	#pos_table .pos-uom-cell {
		min-width: 110px;
	}

	#pos_table .pos-product-summary {
		min-width: 0;
	}

	#pos_table .pos-product-main {
		display: flex;
		align-items: flex-start;
		justify-content: space-between;
		gap: 12px;
	}

	#pos_table .pos-product-name-wrap {
		min-width: 0;
		flex: 1 1 auto;
	}

	#pos_table .pos-product-name {
		display: block;
		font-weight: 600;
		line-height: 1.45;
		word-break: break-word;
	}

	#pos_table .pos-product-stock {
		display: block;
		margin-top: 6px;
		line-height: 1.4;
	}

	#pos_table .pos-product-cell img[alt="product-img"] {
		display: none !important;
	}

	#pos_table .pos-product-note {
		margin-top: 12px;
	}

	#pos_table .pos-qty-cell,
	#pos_table .pos-price-cell,
	#pos_table .pos-discount-cell,
	#pos_table .pos-price-inc-tax-cell,
	#pos_table .pos-subtotal-cell {
		min-width: 120px;
	}

	#pos_table .pos-qty-cell .input-group,
	#pos_table .pos-price-cell .form-control,
	#pos_table .pos-discount-cell .form-control,
	#pos_table .pos-price-inc-tax-cell .form-control,
	#pos_table .pos-subtotal-cell .form-control {
		min-width: 0;
	}

	#pos_table .pos-subunit-wrap,
	#pos_table .pos-subunit-label,
	#pos_table .pos-bonus-help,
	#pos_table .pos-inline-meta {
		display: block;
		margin-top: 8px;
		line-height: 1.4;
	}

	#pos_table .pos-discount-cell .help-block {
		margin-bottom: 0;
	}

	#pos_table .pos-subtotal-cell {
		white-space: nowrap;
	}

@if(!empty($bc_inline_entry))
	/* ---- Business Central (Fluent) look, active while BC integration is enabled ---- */
	.bc-ui,
	.bc-ui .form-control,
	.bc-ui .btn,
	.bc-ui label,
	.bc-ui table,
	.bc-ui h1 {
		font-family: "Segoe UI", "Segoe UI Web (West European)", -apple-system, BlinkMacSystemFont, Roboto, "Helvetica Neue", sans-serif;
	}

	.bc-ui h1 {
		font-weight: 600 !important;
		color: #323130 !important;
	}

	/* Flat white cards with a thin gray border, like BC FastTab panels */
	.bc-ui .box-solid {
		box-shadow: none !important;
		border: 1px solid #e1dfdd !important;
		border-radius: 2px !important;
		transform: none !important;
	}

	.bc-ui .box-solid:hover {
		box-shadow: none !important;
		transform: none !important;
	}

	.bc-ui .bc-fasttab-header {
		font-size: 16px;
		font-weight: 600;
		color: #323130;
		margin: 2px 2px 6px;
		display: flex;
		justify-content: space-between;
		align-items: center;
	}

	.bc-ui .bc-fasttab-toggle {
		cursor: pointer;
	}

	.bc-ui .bc-fasttab-toggle .bc-fasttab-state {
		font-size: 12px;
		font-weight: 400;
		color: #0078d4;
	}

	.bc-ui .bc-fasttab-toggle .bc-fasttab-state i {
		font-size: 10px;
	}

	.bc-ui .bc-totals-panel {
		border: 1px solid #e1dfdd;
		border-radius: 2px;
		background: #faf9f8;
		padding: 10px 14px;
		margin-top: 8px;
	}

	.bc-ui .bc-totals-panel .bc-total-row {
		display: flex;
		justify-content: space-between;
		gap: 16px;
		padding: 3px 0;
		font-size: 13px;
		color: #323130;
	}

	.bc-ui .bc-totals-panel .bc-total-row .bc-total-label {
		color: #605e5c;
	}

	.bc-ui .bc-totals-panel .bc-total-row.bc-total-grand {
		font-weight: 600;
		border-top: 1px solid #e1dfdd;
		margin-top: 4px;
		padding-top: 6px;
	}

	.bc-ui label {
		color: #605e5c;
		font-weight: 400;
		font-size: 12px;
	}

	.bc-ui .bc-locked-select {
		pointer-events: none;
	}

	/* Keep select2 widgets full width of their column (avoids squeezed boxes) */
	.bc-ui .form-group .select2-container {
		width: 100% !important;
	}

	/* BC General FastTab field grid: small gray label above the value */
	.bc-ui .bc-general-grid {
		display: grid;
		grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
		gap: 2px 28px;
		margin: 0 0 14px;
	}

	.bc-ui .bc-general-grid .bc-field {
		padding: 4px 0 3px;
		border-bottom: 1px solid #f3f2f1;
	}

	.bc-ui .bc-general-grid label {
		display: block;
		margin: 0;
	}

	.bc-ui .bc-general-grid .bc-value {
		font-size: 13px;
		color: #323130;
		min-height: 18px;
		word-break: break-word;
	}

	/* Collapsed General FastTab: only the BC summary grid stays visible */
	.bc-ui #bc_general_card.bc-collapsed .tw-py-2 > *:not(.bc-general-grid) {
		display: none !important;
	}

	.bc-ui #bc_general_card.bc-collapsed .bc-general-grid {
		margin-bottom: 0;
	}

	.bc-ui .form-control {
		border-radius: 2px;
	}

	.bc-ui .form-control:focus {
		border-color: #0078d4;
		box-shadow: none;
	}

	/* BC-style lines grid: light horizontal rules only, no striping */
	.bc-ui #pos_table,
	.bc-ui #pos_table.table-bordered {
		border: none !important;
	}

	.bc-ui #pos_table.table-bordered > thead > tr > th,
	.bc-ui #pos_table.table-bordered > thead > tr > td,
	.bc-ui #pos_table.table-bordered > tbody > tr > td {
		border: none;
		border-bottom: 1px solid #edebe9;
	}

	.bc-ui #pos_table > thead > tr > th {
		background: #ffffff;
		color: #605e5c;
		font-weight: 600;
		font-size: 12px;
		border-bottom: 1px solid #8a8886;
		vertical-align: bottom;
	}

	.bc-ui #pos_table.table-striped > tbody > tr:nth-of-type(odd) {
		background: #ffffff;
	}

	.bc-ui #pos_table > tbody > tr:hover {
		background: #f3f2f1;
	}

	/* Grid cells look like plain text until hovered/focused, like the BC grid */
	.bc-ui #pos_table .form-control {
		border: 1px solid transparent;
		box-shadow: none;
		background: transparent;
		border-radius: 2px;
		font-size: 13px;
	}

	.bc-ui #pos_table input.form-control {
		height: 28px;
		padding: 2px 6px;
	}

	.bc-ui #pos_table .form-control:hover:not([readonly]):not([disabled]),
	.bc-ui #pos_table .form-control:focus {
		background: #ffffff;
		border-color: #c8c6c4;
	}

	.bc-ui #pos_table .form-control:focus {
		border-color: #0078d4;
	}

	.bc-ui #pos_table input.form-control[readonly],
	.bc-ui #pos_table select.form-control[disabled] {
		color: #323130;
		background: transparent;
		border-color: transparent;
		cursor: default;
		-webkit-appearance: none;
		-moz-appearance: none;
		appearance: none;
	}

	/* BC has no +/- quantity steppers */
	.bc-ui #pos_table .quantity-down,
	.bc-ui #pos_table .quantity-up {
		display: none;
	}

	.bc-ui #pos_table .input-group.input-number {
		width: 100%;
	}

	/* The always-on-top entry row keeps visible input borders, like BC's new line */
	.bc-ui #bc_inline_entry_row .form-control {
		border: 1px solid #c8c6c4;
		background: #ffffff;
	}

	.bc-ui #bc_inline_entry_row .form-control:focus {
		border-color: #0078d4;
	}

	.bc-ui #bc_inline_entry_row input[disabled] {
		border: 1px dashed #e1dfdd;
		background: #faf9f8;
	}

	/* Fluent blue action buttons */
	.bc-ui .btn-primary,
	.bc-ui .tw-dw-btn-primary {
		background-color: #0078d4 !important;
		border-color: #0078d4 !important;
		border-radius: 2px !important;
	}

	.bc-ui .btn-primary:hover,
	.bc-ui .tw-dw-btn-primary:hover {
		background-color: #106ebe !important;
		border-color: #106ebe !important;
	}

	.bc-ui .tw-dw-btn-success {
		background-color: #107c10 !important;
		border-color: #107c10 !important;
		border-radius: 2px !important;
	}

	/* Product suggestion dropdown highlight in Fluent blue instead of teal */
	.ui-menu .ui-menu-item-wrapper.ui-state-active,
	.ui-menu .ui-menu-item-wrapper.ui-state-focus {
		background: #0078d4;
		border-color: #0078d4;
		box-shadow: none;
	}
@endif
</style>

@stop

@section('javascript')
	@if(session('status') && (session('status.success') === true || session('status.success') === 1))
		<script>
			try {
				localStorage.setItem('pos-dashboard-sale-update', JSON.stringify({
					event: 'sale-finalized',
					timestamp: Date.now(),
					location_id: $('input#location_id').val() || null
				}));
			} catch (error) {
				console.warn('Unable to notify dashboard about finalized sale.', error);
			}
		</script>
	@endif
	<script src="{{ asset('js/pos.js?v=' . filemtime(public_path('js/pos.js'))) }}"></script>
	<script src="{{ asset('js/product.js?v=' . $asset_v) }}"></script>
	<script src="{{ asset('js/opening_stock.js?v=' . $asset_v) }}"></script>

	<!-- Call restaurant module if defined -->
    @if(in_array('tables' ,$enabled_modules) || in_array('modifiers' ,$enabled_modules) || in_array('service_staff' ,$enabled_modules))
    	<script src="{{ asset('js/restaurant.js?v=' . $asset_v) }}"></script>
    @endif
    <script type="text/javascript">
    	$(document).ready( function() {
    		$('#status').change(function(){
    			if ($(this).val() == 'final') {
    				$('#payment_rows_div').removeClass('hide');
    			} else {
    				$('#payment_rows_div').addClass('hide');
    			}
    		});
    		$('.paid_on').datetimepicker({
                format: moment_date_format + ' ' + moment_time_format,
                ignoreReadonly: true,
            });

            $('#shipping_documents').fileinput({
		        showUpload: false,
		        showPreview: false,
		        browseLabel: LANG.file_browse_label,
		        removeLabel: LANG.remove,
		    });

		    $(document).on('change', '#prefer_payment_method', function(e) {
			    var default_accounts = $('select#select_location_id').length ? 
			                $('select#select_location_id')
			                .find(':selected')
			                .data('default_payment_accounts') : $('#location_id').data('default_payment_accounts');
			    var payment_type = $(this).val();
			    if (payment_type) {
			        var default_account = default_accounts && default_accounts[payment_type]['account'] ? 
			            default_accounts[payment_type]['account'] : '';
			        var account_dropdown = $('select#prefer_payment_account');
			        if (account_dropdown.length && default_accounts) {
			            account_dropdown.val(default_account);
			            account_dropdown.change();
			        }
			    }
			});

		    function setPreferredPaymentMethodDropdown() {
			    var payment_settings = $('#location_id').data('default_payment_accounts');
			    payment_settings = payment_settings ? payment_settings : [];
			    enabled_payment_types = [];
			    for (var key in payment_settings) {
			        if (payment_settings[key] && payment_settings[key]['is_enabled']) {
			            enabled_payment_types.push(key);
			        }
			    }
			    if (enabled_payment_types.length) {
			        $("#prefer_payment_method > option").each(function() {
		                if (enabled_payment_types.indexOf($(this).val()) != -1) {
		                    $(this).removeClass('hide');
		                } else {
		                    $(this).addClass('hide');
		                }
			        });
			    }
			}
			
			setPreferredPaymentMethodDropdown();

			$('#is_export').on('change', function () {
	            if ($(this).is(':checked')) {
	                $('div.export_div').show();
	            } else {
	                $('div.export_div').hide();
	            }
	        });

			if($('.payment_types_dropdown').length){
				$('.payment_types_dropdown').change();
			}

			@if(!empty($bc_inline_entry))
				//The sale date always stays the current date in BC mode: the
				//input is readonly, so removing the picker locks it entirely.
				var bc_td_picker = $('#transaction_date').data('DateTimePicker');
				if (bc_td_picker) {
					bc_td_picker.destroy();
				}

				//Business Central style General info grid
				var bc_location_codes = @json($bc_location_codes);
				var bc_customer_location_code = null;

				function bc_info_text(value) {
					return value && String(value).trim() !== '' && value !== '-' ? value : '\u2014';
				}

				function bc_update_location_code() {
					var code = bc_customer_location_code;
					if (!code) {
						code = bc_location_codes[$('#location_id').val()] || null;
					}
					$('#bc_info_location_code').text(bc_info_text(code));
				}

				$('#customer_id').on('select2:select', function(e) {
					var data = e.params.data || {};
					bc_customer_location_code = data.bc_location_code || null;
					$('#bc_info_customer_no').text(bc_info_text(data.contact_number));
					$('#bc_info_customer_name').text(bc_info_text(data.contact_name));
					$('#bc_info_phone').text(bc_info_text(data.mobile));
					$('#bc_info_email').text(bc_info_text(data.email));
					$('#bc_info_address').text(bc_info_text(data.address_line_1));
					$('#bc_info_city').text(bc_info_text(data.city));
					bc_update_location_code();
					//Customer chosen: flip the General card to its summary view
					bc_set_general_collapsed(true);
				});

				$('#select_location_id').on('change', bc_update_location_code);

				//Business Central style invoice totals panel
				function bc_update_totals() {
					var subtotal = typeof get_subtotal === 'function' ? get_subtotal() : 0;
					var discount_type = $('#discount_type').val();
					var discount_input = __read_number($('#discount_amount')) || 0;
					var discount = __calculate_amount(discount_type, discount_input, subtotal) || 0;
					var total_excl = subtotal - discount;
					var vat = 0;
					if ($('#tax_rate_id').val()) {
						vat = __calculate_amount('percentage', __read_number($('#tax_calculation_amount')), total_excl) || 0;
					}
					var total_incl = total_excl + vat;
					if ($('input#final_total_input').length && $('input#final_total_input').val()) {
						total_incl = __read_number($('input#final_total_input'));
					}

					$('#bc_subtotal_excl').text(__currency_trans_from_en(subtotal, true));
					$('#bc_inv_discount').text(__currency_trans_from_en(discount, true));
					$('#bc_invoice_discount_pct').text(discount_type == 'percentage' ? __number_f(discount_input) : 0);
					$('#bc_total_excl').text(__currency_trans_from_en(total_excl, true));
					$('#bc_total_vat').text(__currency_trans_from_en(vat, true));
					$('#bc_total_incl').text(__currency_trans_from_en(total_incl, true));
				}
				$(document).on('invoice_total_calculated', bc_update_totals);
				bc_update_totals();

				//Collapse/expand the General section like BC's Show more/Show
				//less: collapsed keeps only the BC summary grid visible
				function bc_set_general_collapsed(collapsed) {
					$('#bc_general_card').toggleClass('bc-collapsed', collapsed);
					$('#bc_general_toggle .bc-fasttab-state').html(
						collapsed
							? '<i class="fa fa-chevron-down"></i> Show more'
							: '<i class="fa fa-chevron-up"></i> Show less'
					);
				}
				$('#bc_general_toggle').on('click keypress', function(e) {
					if (e.type == 'keypress' && e.which != 13 && e.which != 32) {
						return;
					}
					bc_set_general_collapsed(!$('#bc_general_card').hasClass('bc-collapsed'));
				});
			@endif

    	});
    </script>
@endsection
