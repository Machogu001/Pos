@if(count($sub_units) > 0)
	<div class="pos-subunit-wrap">
	<select name="products[{{$row_count}}][sub_unit_id]" class="form-control input-sm sub_unit">
        @foreach($sub_units as $key => $value)
            <option value="{{$key}}" data-multiplier="{{$value['multiplier']}}" data-unit_name="{{$value['name']}}" data-allow_decimal="{{$value['allow_decimal']}}" @if(!empty($product->sub_unit_id) && $product->sub_unit_id == $key) selected @endif>
                {{$value['name']}}
            </option>
        @endforeach
   </select>
	</div>
@else
	<div class="pos-subunit-label">{{$product->unit}}</div>
@endif
