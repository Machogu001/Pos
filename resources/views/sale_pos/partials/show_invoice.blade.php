@extends('layouts.guest')
@section('title', $title)
@section('content')

<div class="container">
    <div class="spacer"></div>
    <div class="row">
        <div class="col-md-12 text-right mb-12" >
            @if(!empty($payment_link))
                <a href="{{$payment_link}}" class="btn btn-info no-print" style="margin-right: 20px;"><i class="fas fa-money-check-alt" title="@lang('lang_v1.pay')"></i> @lang('lang_v1.pay')
                </a>
            @endif
            <button type="button" class="tw-dw-btn tw-dw-btn-primary tw-text-white no-print tw-dw-btn-sm" id="print_invoice" 
                 aria-label="Print"><i class="fas fa-print"></i> @lang( 'messages.print' )
            </button>
            @auth
                <a href="{{action([\App\Http\Controllers\SellController::class, 'index'])}}" class="tw-dw-btn tw-dw-btn-success tw-text-white no-print tw-dw-btn-sm" ><i class="fas fa-backward"></i>
                </a>
            @endauth
        </div>
    </div>
    <div class="row">
        <div class="col-md-8 col-md-offset-2 col-sm-12" style="border: 1px solid #ccc;">
            <div class="spacer"></div>
            <div id="invoice_content">
                {!! $receipt['html_content'] !!}
            </div>
            <div class="spacer"></div>
        </div>
    </div>
    <div class="spacer"></div>
</div>
@stop
@section('javascript')
<script type="text/javascript">
    $(document).ready(function(){
        $(document).on('click', '#print_invoice', function(){
            $('#invoice_content').printThis();
        });
    });
    @if(!empty($bc_invoice_url))
        //Business Central mode: load and print the BC report instead of the
        //POS receipt. On BC refusal the error is shown and nothing prints.
        function bcPrintError(msg){
            msg = msg || 'Business Central could not provide this invoice printout.';
            if (window.toastr) { toastr.error(msg); } else { alert(msg); }
        }
        $(window).on('load', function(){
            $.ajax({
                method: 'GET',
                url: '{{ $bc_invoice_url }}?format=html',
                dataType: 'json',
                success: function(result){
                    if (result && result.success == 1 && result.html) {
                        $('#invoice_content').html(result.html);
                        @if(!empty(request()->input('print_on_load')))
                            //Small delay lets the report page images render
                            setTimeout(function(){ $('#invoice_content').printThis(); }, 250);
                        @endif
                    } else {
                        bcPrintError(result && result.msg);
                    }
                },
                error: function(){ bcPrintError(); }
            });
        });
    @elseif(!empty(request()->input('print_on_load')))
        $(window).on('load', function(){
            $('#invoice_content').printThis();
        });
    @endif
</script>
@endsection