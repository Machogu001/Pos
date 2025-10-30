@if ($errors->any())
<div class="alert alert-danger border-0 shadow-sm">
    <div class="d-flex">
        <i class="fas fa-exclamation-triangle fs-4 me-3 text-danger mt-1"></i>
        <div>
            <h5 class="alert-heading mb-2">@lang('stocktake.validation_errors')</h5>
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    </div>
</div>
@endif