@extends('layouts.app')

@section('title', __('ui.create_leave_type'))

@section('content')
@include('hrm::partials.hrm_page_header', [
    'title' => __('ui.create_leave_type'),
    'subtitle' => __('ui.create_a_reusable_leave_category_for_employee_requests_and_approvals'),
    'actions' => '<a href="'.route('hrm.leave_types.index').'" class="btn btn-default"><i class="fa fa-arrow-left"></i> '. __('ui.back_to_leave_types') .'</a>'
])

<section class="content">
    <div class="box box-warning">
        <div class="box-header with-border">
            <h3 class="box-title">{{ __('ui.leave_type_details') }}</h3>
        </div>
        <div class="box-body">
            <form id="typeForm">
                @csrf
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">{{ __('ui.name') }}</label>
                            <input id="name" name="name" class="form-control" placeholder="{{ __('ui.annual_leave') }}" />
                        </div>
                    </div>
                </div>
                <div class="text-right">
                    <button class="btn btn-primary"><i class="fa fa-save"></i> {{ __('ui.create_leave_type') }}</button>
                </div>
            </form>
        </div>
    </div>
</section>

@push('scripts')
<script>
    document.getElementById('typeForm').addEventListener('submit', function(e){
        e.preventDefault();
        const fd = new FormData(this);
        fetch('{{ route('hrm.leave_types.store') }}', { method: 'POST', body: fd, headers: { 'Accept': 'application/json' } })
                .then(r => r.json())
                .then(json => {
                    if (json && json.success) {
                        if (window.toastr) { toastr.success("{{ __('ui.created_successfully') }}"); }
                        if (window.playSuccess) { window.playSuccess(); }
                        // go back to index without full reload if possible
                        window.location = '{{ route('hrm.leave_types.index') }}';
                    } else {
                        if (window.toastr) { toastr.error("{{ __('ui.create_failed') }}"); }
                        if (window.playError) { window.playError(); }
                    }
                })
                .catch(() => { if (window.toastr) { toastr.error("{{ __('ui.create_failed') }}"); } if (window.playError) { window.playError(); } });
    });
</script>
@endpush

@endsection
