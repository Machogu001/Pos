@extends('layouts.app')

@section('title', 'Edit Leave Type')

@section('content')
@include('hrm::partials.hrm_page_header', [
    'title' => 'Edit Leave Type',
    'subtitle' => 'Rename the leave type used in HRM requests.',
    'actions' => '<a href="'.route('hrm.leave_types.index').'" class="btn btn-default"><i class="fa fa-arrow-left"></i> Back to Leave Types</a>'
])

<section class="content">
    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title">Leave Type Details</h3>
        </div>
        <div class="box-body">
            <form id="typeForm">
                @csrf
                @method('PUT')
                <div class="form-group">
                    <label class="form-label" for="name">Name</label>
                    <input id="name" name="name" class="form-control" />
                </div>
                <div class="text-right">
                    <a href="{{ route('hrm.leave_types.index') }}" class="btn btn-default">Cancel</a>
                    <button class="btn btn-primary">Update Leave Type</button>
                </div>
            </form>
        </div>
    </div>
</section>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const idMatch = window.location.pathname.match(/\/(\d+)\/edit\/?$/);
        const id = idMatch ? idMatch[1] : null;
        if (!id) { window.hrmAlert('Invalid id'); return; }
        // fetch the leave type via edit endpoint (returns JSON)
        fetch(window.location.pathname, { headers: { 'Accept': 'application/json' } })
            .then(r => r.json())
            .then(json => {
                const t = json.leave_type || json.leave_types?.[0]?.raw || {};
                document.getElementById('name').value = t.name || t.title || '';
            })
            .catch(()=>{});

        document.getElementById('typeForm').addEventListener('submit', function(e){
            e.preventDefault();
            const fd = new FormData(this);
            if (!fd.has('_method')) fd.append('_method', 'PATCH');
            if (!fd.has('_token')) {
                const meta = document.querySelector('meta[name="csrf-token"]');
                if (meta) fd.append('_token', meta.getAttribute('content'));
            }
            const endpoint = '{{ url('hrm/leave_types') }}' + '/' + encodeURIComponent(id);
            fetch(endpoint, { method: 'POST', body: fd, headers: { 'Accept': 'application/json' } })
                .then(async (r) => {
                    const text = await r.text();
                    let json = null;
                    try { json = text ? JSON.parse(text) : null; } catch (e) { }
                    if (!r.ok) {
                        const body = json ? JSON.stringify(json) : text;
                        window.hrmAlert('Request failed: ' + r.status + ' ' + r.statusText);
                        return;
                    }
                    if (json && json.success) {
                        if (window.toastr) { toastr.success('Updated successfully'); }
                        if (window.playSuccess) { window.playSuccess(); }
                        window.location = '{{ route('hrm.leave_types.index') }}';
                    } else {
                        if (window.toastr) { toastr.error('Update failed'); }
                        if (window.playError) { window.playError(); }
                    }
                })
                .catch((err) => { window.hrmAlert('Network error: ' + (err && err.message ? err.message : 'unknown')); });
        });
    });
</script>
@endpush

@endsection
