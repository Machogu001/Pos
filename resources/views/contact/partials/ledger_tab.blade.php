@php
    $transaction_types = [];
    if(in_array($contact->type, ['both', 'supplier'])){
        $transaction_types['purchase'] = __('lang_v1.purchase');
        $transaction_types['purchase_return'] = __('lang_v1.purchase_return');
    }

    if(in_array($contact->type, ['both', 'customer'])){
        $transaction_types['sell'] = __('sale.sale');
        $transaction_types['sell_return'] = __('lang_v1.sell_return');
    }

    $transaction_types['opening_balance'] = __('lang_v1.opening_balance');
@endphp
<div class="row">
    <div class="col-md-12">
        <div class="col-md-3">
            <div class="form-group">
                {!! Form::label('ledger_date_range', __('report.date_range') . ':') !!}
                {!! Form::text('ledger_date_range', null, ['placeholder' => __('lang_v1.select_a_date_range'), 'class' => 'form-control', 'readonly']); !!}
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                <label>@lang('lang_v1.ledger_format')</label>
            <div class="btn-group" data-toggle="buttons">
                <label class="btn btn-default active">
                    <input type="radio" name="ledger_format" value="format_1" checked> @lang('lang_v1.format_1')
                </label>
                <label class="btn btn-default">
                    <input type="radio" name="ledger_format" value="format_2"> @lang('lang_v1.format_2')
                </label>
                <label class="btn btn-default">
                    <input type="radio" name="ledger_format" value="format_3"> @lang('lang_v1.format_3')
                </label>
            </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                {!! Form::label('ledger_location', __('purchase.business_location') . ':') !!}
                {!! Form::select('ledger_location', $business_locations, null , ['class' => 'form-control select2', 'id' => 'ledger_location']); !!}
            </div>
        </div>
        <div class="col-md-2 text-right">
            <button data-href="{{action([\App\Http\Controllers\ContactController::class, 'getLedger'])}}?contact_id={{$contact->id}}&action=pdf" class="btn btn-default btn-xs" id="print_ledger_pdf"><i class="fas fa-file-pdf"></i></button>

            <button type="button" class="btn btn-default btn-xs" id="send_ledger"><i class="fas fa-envelope"></i></button>
        </div>
    </div>
    <div class="col-md-12">
        <div class="form-group">
            <label for="ledger_per_page" id="ledger_show_label"></label>
            <select id="ledger_per_page" class="form-control input-sm" style="display: inline-block; width: auto;">
                @foreach([10, 25, 50, 100] as $page_size)
                    <option value="{{ $page_size }}" @if($page_size == 50) selected @endif>{{ $page_size }}</option>
                @endforeach
            </select>
            <span id="ledger_entries_label"></span>
        </div>
        <div id="ledger_loading" role="status" aria-live="polite" style="display: none;"></div>
    </div>
    <div id="contact_ledger_div" aria-busy="false"></div>
</div>