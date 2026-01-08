@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4 class="mb-0">Edit Leave Type</h4>
            <div>
                <a href="{{ route('hrm.leave_types.index') }}" class="btn btn-secondary">Back</a>
            </div>
        </div>
        <div class="card-body">
            <form id="typeForm">
                @csrf
                @method('PUT')
                <div class="mb-3">
                    <label class="form-label">Name</label>
                    <input id="name" name="name" class="form-control" />
                </div>
                <div class="d-flex justify-content-end">
                    <button class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const idMatch = window.location.pathname.match(/\/(\d+)\/edit\/?$/);
        const id = idMatch ? idMatch[1] : null;
        if (!id) { alert('Invalid id'); return; }
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
                        alert('Request failed: ' + r.status + ' ' + r.statusText + '\n' + body);
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
                .catch((err) => { alert('Network error: ' + (err && err.message ? err.message : 'unknown')); });
        });
    });
</script>
@endpush

@endsection
