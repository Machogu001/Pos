@extends('layouts.app')

@section('title', 'Create Leave Type')

@section('content')
@include('hrm::partials.hrm_page_header', [
    'title' => 'Create Leave Type',
    'subtitle' => 'Create a reusable leave category for employee requests and approvals.',
    'actions' => '<a href="'.route('hrm.leave_types.index').'" class="btn btn-default"><i class="fa fa-arrow-left"></i> Back to Leave Types</a>'
])

<section class="content">
    <div class="box box-warning">
        <div class="box-header with-border">
            <h3 class="box-title">Leave Type Details</h3>
        </div>
        <div class="box-body">
            <form id="typeForm">
                @csrf
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">Name</label>
                            <input id="name" name="name" class="form-control" placeholder="Annual Leave" />
                        </div>
                    </div>
                </div>
                <div class="text-right">
                    <button class="btn btn-primary"><i class="fa fa-save"></i> Create Leave Type</button>
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
                        if (window.toastr) { toastr.success('Created successfully'); }
                        if (window.playSuccess) { window.playSuccess(); }
                        // go back to index without full reload if possible
                        window.location = '{{ route('hrm.leave_types.index') }}';
                    } else {
                        if (window.toastr) { toastr.error('Create failed'); }
                        if (window.playError) { window.playError(); }
                    }
                })
                .catch(() => { if (window.toastr) { toastr.error('Create failed'); } if (window.playError) { window.playError(); } });
    });
</script>
@endpush

@endsection
