@if (session('status'))
<div class="alert alert-{{ session('status.success') ? 'success' : 'danger' }} border-0 shadow-sm">
    <div class="d-flex">
        <i class="fas fa-{{ session('status.success') ? 'check-circle' : 'exclamation-circle' }} fs-4 me-3 text-{{ session('status.success') ? 'success' : 'danger' }} mt-1"></i>
        <div>
            <h5 class="alert-heading mb-1">
                @if(session('status.success'))
                    @lang('stocktake.success')
                @else
                    @lang('stocktake.error')
                @endif
            </h5>
            <p class="mb-0">{{ session('status.msg') }}</p>
        </div>
    </div>
</div>
@endif