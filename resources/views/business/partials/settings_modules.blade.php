<div class="pos-tab-content">
	<div class="row">
		<input type="hidden" name="modules_section_present" value="1">
		<input type="hidden" name="modules_settings_changed" id="modules_settings_changed" value="0">
  @if(!empty($modules))
    @php
        // Ensure HRM appears in the modules list and enabled_modules variable exists
        if (!isset($modules['hrm'])) {
            $modules['hrm'] = ['name' => 'HRM'];
        }
      if (!isset($modules['inventory_management'])) {
        $modules['inventory_management'] = ['name' => 'Inventory Management'];
      }
    if (!isset($enabled_modules)) {
      $enabled_modules = $business->enabled_modules ?? [];
    }

    if (is_string($enabled_modules)) {
      $decoded_modules = json_decode($enabled_modules, true);
      $enabled_modules = is_array($decoded_modules) ? $decoded_modules : [];
    }

    $enabled_modules = is_array($enabled_modules) ? $enabled_modules : [];
    @endphp
		<h4>@lang('lang_v1.enable_disable_modules')</h4>
		@foreach($modules as $k => $v)
            <div class="col-sm-4">
                <div class="form-group">
                    <div class="checkbox">
                    <br>
                      <label>
                        {!! Form::checkbox('enabled_modules[]', $k,  in_array($k, $enabled_modules) , 
            ['class' => 'input-icheck module-toggle-checkbox']); !!} {{$v['name']}}
                      </label>
                      @if(!empty($v['tooltip'])) @show_tooltip($v['tooltip']) @endif
                    </div>
                </div>
            </div>
        @endforeach
	@endif
	</div>
</div>