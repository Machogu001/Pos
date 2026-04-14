<h3 class="text-muted mb-0">
    @lang('lang_v1.cogs') <span class="display_currency" data-currency_symbol="true">{{ number_format(round($data['cogs'], 2), 2, '.', '') }}</span>
</h3>
    <small class="help-block">
        This shows the actual inventory cost of goods sold. It does not drop to zero when the purchase is paid.
    </small>
@if(!empty($data['total_stocktake_adjustment']))
    <h3 class="text-muted mb-0">
        Stocktake adjustments <span class="display_currency" data-currency_symbol="true">{{ number_format(round($data['total_stocktake_adjustment'], 2), 2, '.', '') }}</span>
    </h3>
    <small class="help-block">
        Stocktake adjustments are tracked separately from normal/abnormal stock adjustments and do not get deducted again in net profit.
    </small>
@endif
<h3 class="text-muted mb-0">
    Stock adjustment effect <span class="display_currency" data-currency_symbol="true">{{ number_format(round($data['total_adjustment_effect'], 2), 2, '.', '') }}</span>
</h3>
<small class="help-block">
    Net inventory gains less inventory losses posted through the ledger for the selected period.
</small>
<h3 class="text-muted mb-0">
    {{ __('lang_v1.gross_profit') }}: 
    <span class="display_currency" data-currency_symbol="true">{{ number_format(round($data['gross_profit'], 2), 2, '.', '') }}</span>
</h3>
<small class="help-block">
    (@lang('report.total_sell') - @lang('lang_v1.cogs'))
    @if(!empty($data['gross_profit_label']))
        {{-- + {{$data['gross_profit_label']}} --}}
        @foreach ($data['gross_profit_label'] as $val)
            + {{$val}}
        @endforeach
    @endif
</small>

<h3 class="text-muted mb-0">
    {{ __('report.net_profit') }}: 
    <span class="display_currency" data-currency_symbol="true">{{ number_format(round($data['net_profit'], 2), 2, '.', '') }}</span>
</h3>
<small class="help-block">@lang('lang_v1.gross_profit') + (@lang('report.total_stock_recovered') + @lang('lang_v1.total_sell_shipping_charge') + @lang('lang_v1.sell_additional_expense') + @lang('lang_v1.total_purchase_discount') + @lang('lang_v1.total_sell_round_off') + Stock adjustment gains/losses 
@foreach($data['right_side_module_data'] as $module_data)
    @if(!empty($module_data['add_to_net_profit']))
        + {{$module_data['label']}} 
    @endif
@endforeach
) <br> - ( @lang('report.total_expense') + @lang('lang_v1.total_purchase_shipping_charge') + @lang('lang_v1.total_transfer_shipping_charge') + @lang('lang_v1.purchase_additional_expense') + @lang('lang_v1.total_sell_discount') + @lang('lang_v1.total_reward_amount') 
@foreach($data['left_side_module_data'] as $module_data)
    @if(!empty($module_data['add_to_net_profit']))
        + {{$module_data['label']}}
    @endif 
@endforeach )</small>