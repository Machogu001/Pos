@props(['field'])

@if($errors->has($field))
    <div class="text-danger small">{{ $errors->first($field) }}</div>
@endif
