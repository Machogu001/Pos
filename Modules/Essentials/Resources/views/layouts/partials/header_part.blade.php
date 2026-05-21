@if($__is_essentials_enabled && $is_employee_allowed)
@php
    $clock_out_tooltip = __('essentials::lang.clock_out');
    if (!empty($clock_in)) {
		$__dt_format = session('business.date_format') . ' H:i:s';
        $__clocked_in_formatted = \Carbon\Carbon::createFromTimestamp(strtotime($clock_in->clock_in_time))->format($__dt_format);
        $clock_out_tooltip .= ' &mdash; <small><b>' . __('essentials::lang.clocked_in_at') . ':</b> ' . $__clocked_in_formatted . '</small>';
        if (!empty($clock_in->shift_name)) {
            $clock_out_tooltip .= ' <small><b>' . __('essentials::lang.shift') . ':</b> ' . ucfirst($clock_in->shift_name) . '</small>';
        }
        if (!empty($clock_in->start_time) && !empty($clock_in->end_time)) {
			$__start = \Carbon\Carbon::createFromTimestamp(strtotime($clock_in->start_time))->format('H:i:s');
			$__end = \Carbon\Carbon::createFromTimestamp(strtotime($clock_in->end_time))->format('H:i:s');
            $clock_out_tooltip .= ' <small><b>' . __('restaurant.start_time') . ':</b> ' . $__start . ' &bull; <b>' . __('restaurant.end_time') . ':</b> ' . $__end . '</small>';
        }
    }
@endphp
	<button 
		type="button" 
		class="btn bg-blue btn-flat 
		pull-left m-8 btn-sm mt-10 
		clock_in_btn
		@if(!empty($clock_in))
	    	hide
	    @endif
		"
	    data-type="clock_in"
	    data-toggle="tooltip"
	    data-placement="bottom"
	    data-original-title="@lang('essentials::lang.clock_in')" 
	    >
	    <i class="fas fa-arrow-circle-down"></i>
	</button>

	<button 
		type="button" 
		class="btn bg-yellow btn-flat pull-left m-8 
		 btn-sm mt-10 clock_out_btn
		@if(empty($clock_in))
	    	hide
	    @endif
		" 	
	    data-type="clock_out"
	    data-toggle="tooltip"
	    data-placement="bottom"
	    data-html="true"
	    data-original-title="{!! $clock_out_tooltip !!}"
	    >
	    <i class="fas fa-hourglass-half fa-spin"></i>
	</button>
@endif