@extends('layouts.app')
@section('title', __( 'account.balance_sheet' ))

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">@lang( 'account.balance_sheet')
    </h1>
</section>

<!-- Main content -->
<section class="content">
    <div class="row no-print">
        <div class="col-sm-12">
            @component('components.filters', ['title' => __('report.filters')])
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('bal_sheet_location_id',  __('purchase.business_location') . ':') !!}
                    {!! Form::select('bal_sheet_location_id', $business_locations, null, ['class' => 'form-control select2', 'style' => 'width:100%']); !!}
                </div>
            </div>
            <div class="col-sm-3 col-xs-6">
                    <label for="end_date">@lang('messages.filter_by_date'):</label>
                    <div class="input-group">
                        <span class="input-group-addon">
                            <i class="fa fa-calendar"></i>
                        </span>
                        <input type="text" id="end_date" value="{{@format_date('now')}}" class="form-control" readonly>
                    </div>
            </div>
            @endcomponent
        </div>
    </div>
    <br>
    <div class="box box-solid">
        <div class="box-header print_section">
            <h3 class="box-title">{{session()->get('business.name')}} - @lang( 'account.balance_sheet') - <span id="hidden_date">{{@format_date('now')}}</span></h3>
        </div>
        <div class="box-body">
            <table class="table table-border-center no-border table-pl-12">
                <thead>
                    <tr class="bg-gray">
                        <th>@lang('account.liabilities_and_equity')</th>
                        <th>@lang( 'account.assets')</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            <table class="table" id="liabilities_table">
                                <tbody>
                                    <tr>
                                        <th>@lang('account.supplier_due'):</th>
                                        <td>
                                            <input type="hidden" id="hidden_supplier_due" class="liability">
                                            <span class="remote-data" id="supplier_due">
                                                <i class="fas fa-sync fa-spin fa-fw"></i>
                                            </span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th colspan="2">@lang('account.account_balances'):</th>
                                    </tr>
                                </tbody>
                                <tbody id="liability_account_balances" class="pl-20-td">
                                    <tr><td colspan="2"><i class="fas fa-sync fa-spin fa-fw"></i></td></tr>
                                </tbody>
                            </table>
                        </td>
                        <td>
                            <table class="table" id="assets_table">
                                <tbody>
                                    <tr>
                                        <th>@lang('account.customer_due'):</th>
                                        <td>
                                            <input type="hidden" id="hidden_customer_due" class="asset">
                                            <span class="remote-data" id="customer_due">
                                                <i class="fas fa-sync fa-spin fa-fw"></i>
                                            </span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>@lang('report.closing_stock'):</th>
                                        <td>
                                            <input type="hidden" id="hidden_closing_stock" class="asset">
                                            <span class="remote-data" id="closing_stock">
                                                <i class="fas fa-sync fa-spin fa-fw"></i>
                                            </span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th colspan="2">@lang('account.account_balances'):</th>
                                    </tr>
                                </tbody>
                                <tbody id="asset_account_balances" class="pl-20-td">
                                    <tr><td colspan="2"><i class="fas fa-sync fa-spin fa-fw"></i></td></tr>
                                </tbody>
                            </table>
                        </td>
                    </tr>
                </tbody>
                <tfoot>
                    <tr class="bg-gray">
                        <td>
                            <table class="table bg-gray mb-0 no-border">
                                <tr>
                                    <th>
                                        @lang('account.total_liability'): 
                                    </th>
                                    <td>
                                        <span id="total_liabilty"><i class="fas fa-sync fa-spin fa-fw"></i></span>
                                    </td>
                                </tr>
                            </table>
                        </td>
                        <td>
                            <table class="table bg-gray mb-0 no-border">
                                <tr>
                                    <th>
                                        @lang('account.total_assets'): 
                                    </th>
                                    <td>
                                        <span id="total_assets"><i class="fas fa-sync fa-spin fa-fw"></i></span>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
        <div class="box-footer">
            <button type="button" class="tw-dw-btn tw-dw-btn-primary tw-text-white no-print pull-right"onclick="window.print()">
          <i class="fa fa-print"></i> @lang('messages.print')</button>
        </div>
    </div>

</section>
<!-- /.content -->
@stop
@section('javascript')

<script type="text/javascript">
    $(document).ready( function(){
        //Date picker
        $('#end_date').datepicker({
            autoclose: true,
            format: datepicker_date_format
        });
        update_balance_sheet();

        $('#end_date').change( function() {
            update_balance_sheet();
            $('#hidden_date').text($(this).val());
        });
        $('#bal_sheet_location_id').change( function() {
            update_balance_sheet();
        });
    });

    function update_balance_sheet(){
        var loader = '<i class="fas fa-sync fa-spin fa-fw"></i>';
        $('span.remote-data').each( function() {
            $(this).html(loader);
        });

        $('table#assets_table tbody#asset_account_balances').html('<tr><td colspan="2"><i class="fas fa-sync fa-spin fa-fw"></i></td></tr>');
        $('table#liabilities_table tbody#liability_account_balances').html('<tr><td colspan="2"><i class="fas fa-sync fa-spin fa-fw"></i></td></tr>');

        var end_date = $('input#end_date').val();
        var location_id = $('#bal_sheet_location_id').val()
        $.ajax({
            url: "{{action([\App\Http\Controllers\AccountReportsController::class, 'balanceSheet'])}}?end_date=" + end_date + '&location_id=' + location_id, 
            dataType: "json",
            success: function(result){
                $('span#supplier_due').text(__currency_trans_from_en(result.supplier_due, true));
                __write_number($('input#hidden_supplier_due'), result.supplier_due);

                $('span#customer_due').text(__currency_trans_from_en(result.customer_due, true));
                __write_number($('input#hidden_customer_due'), result.customer_due);

                $('span#closing_stock').text(__currency_trans_from_en(result.closing_stock, true));
                __write_number($('input#hidden_closing_stock'), result.closing_stock);
                var asset_account_balances = result.asset_account_balances || [];
                $('table#assets_table tbody#asset_account_balances').html('');
                for (var i in asset_account_balances) {
                    var assetAccount = asset_account_balances[i] || {};
                    var assetSignedBalance = parseFloat(assetAccount.balance) || 0;
                    var assetDisplayBalanceWithSym = __currency_trans_from_en(Math.abs(assetSignedBalance), true);
                    var assetSide = assetAccount.side ? ' <small class="text-muted">' + (assetAccount.side === 'debit' ? 'Dr' : 'Cr') + '</small>' : '';
                    var assetRow = '<tr><td class="pl-20-td">' + (assetAccount.name || '') + ':</td><td><input type="hidden" class="asset" value="' + Math.abs(assetSignedBalance) + '">' + assetDisplayBalanceWithSym + assetSide + '</td></tr>';
                    $('table#assets_table tbody#asset_account_balances').append(assetRow);
                }

                var liability_account_balances = result.liability_account_balances || [];
                $('table#liabilities_table tbody#liability_account_balances').html('');
                for (var j in liability_account_balances) {
                    var liabilityAccount = liability_account_balances[j] || {};
                    var liabilitySignedBalance = parseFloat(liabilityAccount.balance) || 0;
                    var liabilityDisplayBalanceWithSym = __currency_trans_from_en(Math.abs(liabilitySignedBalance), true);
                    var liabilitySide = liabilityAccount.side ? ' <small class="text-muted">' + (liabilityAccount.side === 'debit' ? 'Dr' : 'Cr') + '</small>' : '';
                    var liabilityRow = '<tr><td class="pl-20-td">' + (liabilityAccount.name || '') + ':</td><td><input type="hidden" class="liability" value="' + Math.abs(liabilitySignedBalance) + '">' + liabilityDisplayBalanceWithSym + liabilitySide + '</td></tr>';
                    $('table#liabilities_table tbody#liability_account_balances').append(liabilityRow);
                }


                var total_liabilty = 0;
                var total_assets = 0;
                $('input.liability').each( function(){
                    total_liabilty += __read_number($(this));
                });
                $('input.asset').each( function(){
                    total_assets += __read_number($(this));
                });

                $('span#total_liabilty').text(__currency_trans_from_en(total_liabilty, true));
                $('span#total_assets').text(__currency_trans_from_en(total_assets, true));
                
            }
        });
    }
</script>

@endsection