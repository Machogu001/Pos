<div class="pos-tab-content">
	<div class="row">
		<input type="hidden" name="modules_section_present" value="1">
  @if(!empty($modules))
    @php
        // Ensure HRM appears in the modules list and enabled_modules variable exists
        if (!isset($modules['hrm'])) {
            $modules['hrm'] = ['name' => 'HRM'];
        }
        $enabled_modules = isset($enabled_modules) && is_array($enabled_modules) ? $enabled_modules : (isset($business) && is_array($business->enabled_modules ?? null) ? $business->enabled_modules : []);
    @endphp
		<h4>@lang('lang_v1.enable_disable_modules')</h4>
		@foreach($modules as $k => $v)
            <div class="col-sm-4">
                <div class="form-group">
                    <div class="checkbox">
                    <br>
                      <label>
                        {!! Form::checkbox('enabled_modules[]', $k,  in_array($k, $enabled_modules) , 
                        ['class' => 'input-icheck']); !!} {{$v['name']}}
                      </label>
                      @if(!empty($v['tooltip'])) @show_tooltip($v['tooltip']) @endif
                    </div>
                </div>
            </div>
        @endforeach
	@endif
	</div>
</div>