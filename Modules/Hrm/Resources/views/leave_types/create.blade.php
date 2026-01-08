@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4 class="mb-0">Create Leave Type</h4>
            <div>
                    <a href="{{ route('hrm.leave_types.index') }}" class="btn btn-secondary">Back</a>
            </div>
        </div>
        <div class="card-body">
            <form id="typeForm">
                @csrf
                <div class="mb-3">
                    <label class="form-label">Name</label>
                    <input id="name" name="name" class="form-control" />
                </div>
                <div class="d-flex justify-content-end">
                    <button class="btn btn-primary">Create</button>
                </div>
            </form>
        </div>
    </div>
</div>

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
