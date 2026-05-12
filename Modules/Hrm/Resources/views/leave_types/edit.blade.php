@extends('layouts.app')

@section('title', __('ui.edit_leave_type'))

@section('content')
@include('hrm::partials.hrm_page_header', [
    'title' => __('ui.edit_leave_type'),
    'subtitle' => __('ui.rename_the_leave_type_used_in_hrm_requests'),
    'actions' => '<a href="'.route('hrm.leave_types.index').'" class="btn btn-default"><i class="fa fa-arrow-left"></i> '. __('ui.back_to_leave_types') .'</a>'
])

<section class="content">
    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title">{{ __('ui.leave_type_details') }}</h3>
        </div>
        <div class="box-body">
            <form id="typeForm">
                @csrf
                @method('PUT')
                <div class="form-group">
                    <label class="form-label" for="name">{{ __('ui.name') }}</label>
                    <input id="name" name="name" class="form-control" />
                </div>
                <div class="text-right">
                    <a href="{{ route('hrm.leave_types.index') }}" class="btn btn-default">{{ __('ui.cancel') }}</a>
                    <button class="btn btn-primary">{{ __('ui.update_leave_type') }}</button>
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
        if (!id) { window.hrmAlert("{{ __('ui.invalid_id') }}"); return; }
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
                        window.hrmAlert("{{ __('ui.request_failed_2') }}" + ' ' + r.status + ' ' + r.statusText);
                        return;
                    }
                    if (json && json.success) {
                        if (window.toastr) { toastr.success("{{ __('ui.updated_successfully') }}"); }
                        if (window.playSuccess) { window.playSuccess(); }
                        window.location = '{{ route('hrm.leave_types.index') }}';
                    } else {
                        if (window.toastr) { toastr.error("{{ __('ui.update_failed') }}"); }
                        if (window.playError) { window.playError(); }
                    }
                })
                .catch((err) => { window.hrmAlert("{{ __('ui.network_error') }}" + ' ' + (err && err.message ? err.message : "{{ __('ui.unknown') }}")); });
        });
    });
</script>
@endpush

@endsection
