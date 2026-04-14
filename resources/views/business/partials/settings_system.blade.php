<div class="pos-tab-content">
     <div class="row">
        <div class="col-sm-4">
            <div class="form-group">
                {!! Form::label('theme_color', __('lang_v1.theme_color')); !!}
                {!! Form::select('theme_color', $theme_colors,   $business->theme_color, 
                    ['class' => 'form-control select2', 'placeholder' => __('messages.please_select'), 'style' => 'width: 100%;']); !!}
            </div>
        </div>
        <div class="col-sm-4">
            <div class="form-group">
                @php
                    $page_entries = [25 => 25, 50 => 50, 100 => 100, 200 => 200, 500 => 500, 1000 => 1000, -1 => __('lang_v1.all')];
                @endphp
                {!! Form::label('default_datatable_page_entries', __('lang_v1.default_datatable_page_entries')); !!}
                {!! Form::select('common_settings[default_datatable_page_entries]', $page_entries, !empty($common_settings['default_datatable_page_entries']) ? $common_settings['default_datatable_page_entries'] : 25 , 
                    ['class' => 'form-control select2', 'style' => 'width: 100%;', 'id' => 'default_datatable_page_entries']); !!}
            </div>
        </div>
        <div class="col-sm-4">
            <div class="form-group">
                <div class="checkbox">
                  <label>
                    {!! Form::checkbox('enable_tooltip', 1, $business->enable_tooltip , 
                    [ 'class' => 'input-icheck']); !!} {{ __( 'business.show_help_text' ) }}
                  </label>
                </div>
            </div>
        </div>
    </div>

    <hr>

    <div class="row">
        <div class="col-sm-12">
            <h4>@lang('business.default_account_mappings')</h4>
            <p class="help-block">@lang('business.default_account_mappings_help')</p>
        </div>
        @php
            $type_mappings = !empty($common_settings['default_account_mappings']) ? $common_settings['default_account_mappings'] : [];
            $account_list = \App\Account::forDropdown(session('business.id'), false);
        @endphp
        <div class="col-sm-3">
            <div class="form-group">
                {!! Form::label('default_account_sell', __('business.default_account_sell') . ':') !!}
                {!! Form::select('common_settings[default_account_mappings][sell]', $account_list, $type_mappings['sell'] ?? null, ['class' => 'form-control select2', 'style' => 'width: 100%;', 'placeholder' => __('messages.please_select')]) !!}
            </div>
        </div>
        <div class="col-sm-3">
            <div class="form-group">
                {!! Form::label('default_account_purchase', __('business.default_account_purchase') . ':') !!}
                {!! Form::select('common_settings[default_account_mappings][purchase]', $account_list, $type_mappings['purchase'] ?? null, ['class' => 'form-control select2', 'style' => 'width: 100%;', 'placeholder' => __('messages.please_select')]) !!}
            </div>
        </div>
        <div class="col-sm-3">
            <div class="form-group">
                {!! Form::label('default_account_expense', __('business.default_account_expense') . ':') !!}
                {!! Form::select('common_settings[default_account_mappings][expense]', $account_list, $type_mappings['expense'] ?? null, ['class' => 'form-control select2', 'style' => 'width: 100%;', 'placeholder' => __('messages.please_select')]) !!}
            </div>
        </div>
        <div class="col-sm-3">
            <div class="form-group">
                {!! Form::label('default_account_payroll', __('business.default_account_payroll') . ':') !!}
                {!! Form::select('common_settings[default_account_mappings][payroll]', $account_list, $type_mappings['payroll'] ?? null, ['class' => 'form-control select2', 'style' => 'width: 100%;', 'placeholder' => __('messages.please_select')]) !!}
            </div>
        </div>
        <div class="col-sm-3">
            <div class="form-group">
                {!! Form::label('default_account_payment', __('business.default_account_payment') . ':') !!}
                {!! Form::select('common_settings[default_account_mappings][payment]', $account_list, $type_mappings['payment'] ?? null, ['class' => 'form-control select2', 'style' => 'width: 100%;', 'placeholder' => __('messages.please_select')]) !!}
            </div>
        </div>
        <div class="col-sm-3">
            <div class="form-group">
                {!! Form::label('default_account_cogs', __('business.default_account_cogs') . ':') !!}
                {!! Form::select('common_settings[default_account_mappings][cogs]', $account_list, $type_mappings['cogs'] ?? null, ['class' => 'form-control select2', 'style' => 'width: 100%;', 'placeholder' => __('messages.please_select')]) !!}
            </div>
        </div>
        <div class="col-sm-3">
            <div class="form-group">
                {!! Form::label('default_account_inventory', __('business.default_account_inventory') . ':') !!}
                {!! Form::select('common_settings[default_account_mappings][inventory]', $account_list, $type_mappings['inventory'] ?? null, ['class' => 'form-control select2', 'style' => 'width: 100%;', 'placeholder' => __('messages.please_select')]) !!}
            </div>
        </div>
        <div class="col-sm-3">
            <div class="form-group">
                {!! Form::label('default_account_inventory_gain', __('business.default_account_inventory_gain') . ':') !!}
                {!! Form::select('common_settings[default_account_mappings][inventory_gain]', $account_list, $type_mappings['inventory_gain'] ?? null, ['class' => 'form-control select2', 'style' => 'width: 100%;', 'placeholder' => __('messages.please_select')]) !!}
            </div>
        </div>
        <div class="col-sm-3">
            <div class="form-group">
                {!! Form::label('default_account_inventory_loss', __('business.default_account_inventory_loss') . ':') !!}
                {!! Form::select('common_settings[default_account_mappings][inventory_loss]', $account_list, $type_mappings['inventory_loss'] ?? null, ['class' => 'form-control select2', 'style' => 'width: 100%;', 'placeholder' => __('messages.please_select')]) !!}
            </div>
        </div>
        <div class="col-sm-3">
            <div class="form-group">
                {!! Form::label('default_account_purchase_tax', __('business.default_account_purchase_tax') . ':') !!}
                {!! Form::select('common_settings[default_account_mappings][purchase_tax]', $account_list, $type_mappings['purchase_tax'] ?? ($type_mappings['tax'] ?? null), ['class' => 'form-control select2', 'style' => 'width: 100%;', 'placeholder' => __('messages.please_select')]) !!}
            </div>
        </div>
        <div class="col-sm-3">
            <div class="form-group">
                {!! Form::label('default_account_sales_tax', __('business.default_account_sales_tax') . ':') !!}
                {!! Form::select('common_settings[default_account_mappings][sales_tax]', $account_list, $type_mappings['sales_tax'] ?? ($type_mappings['tax'] ?? null), ['class' => 'form-control select2', 'style' => 'width: 100%;', 'placeholder' => __('messages.please_select')]) !!}
            </div>
        </div>
        <div class="col-sm-3">
            <div class="form-group">
                {!! Form::label('default_account_accounts_receivable', __('business.default_account_accounts_receivable') . ':') !!}
                {!! Form::select('common_settings[default_account_mappings][accounts_receivable]', $account_list, $type_mappings['accounts_receivable'] ?? null, ['class' => 'form-control select2', 'style' => 'width: 100%;', 'placeholder' => __('messages.please_select')]) !!}
            </div>
        </div>
        <div class="col-sm-3">
            <div class="form-group">
                {!! Form::label('default_account_opening_stock_equity', __('business.default_account_opening_stock_equity') . ':') !!}
                {!! Form::select('common_settings[default_account_mappings][opening_stock_equity]', $account_list, $type_mappings['opening_stock_equity'] ?? null, ['class' => 'form-control select2', 'style' => 'width: 100%;', 'placeholder' => __('messages.please_select')]) !!}
            </div>
        </div>
    </div>

    <hr>

    <div class="row">
        <div class="col-sm-12">
            <h4>@lang('business.stocktake_accounting_rules')</h4>
            <p class="help-block">@lang('business.stocktake_accounting_rules_help')</p>
        </div>
        <div class="col-sm-3">
            <div class="form-group">
                {!! Form::label('stocktake_opening_balance_cutoff_date', __('business.stocktake_opening_balance_cutoff_date') . ':') !!}
                {!! Form::date('common_settings[stocktake_opening_balance_cutoff_date]', !empty($common_settings['stocktake_opening_balance_cutoff_date']) ? $common_settings['stocktake_opening_balance_cutoff_date'] : null, ['class' => 'form-control']) !!}
            </div>
        </div>
    </div>
</div>