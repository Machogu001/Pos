@extends('layouts.app')

@section('title', __('ui.leave_types'))

@section('content')
@include('hrm::partials.hrm_page_header', [
    'title' => __('ui.leave_types'),
    'subtitle' => __('ui.define_the_leave_categories_available_to_employees_and_managers'),
    'actions' => '<a href="'.route('hrm.leave_types.create').'" class="btn btn-primary"><i class="fa fa-plus"></i> '. __('ui.create_leave_type') .'</a>'
])

<section class="content">
    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title">{{ __('ui.leave_type_catalog') }}</h3>
            <div class="box-tools pull-right">
                <span class="label label-info">{{ __('ui.total_types') }} {{ $totalRows ?? 0 }}</span>
            </div>
        </div>
        <div class="box-body">
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="search" class="form-label">{{ __('ui.search') }}</label>
                    <input id="search" class="form-control" placeholder="{{ __('ui.search_by_name') }}" oninput="applyFilters()" />
                </div>
                <div class="col-md-6 d-flex align-items-end justify-content-end">
                    <small class="text-muted">{{ __('ui.keep_leave_labels_simple_and_consistent_for_approval_workflows') }}</small>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover table-striped align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>{{ __('ui.name') }}</th>
                            <th class="text-end">{{ __('ui.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody id="types_table_body">
                        @forelse($leave_types as $t)
                            <tr>
                                <td><strong>{{ $t['name'] ?? '-' }}</strong></td>
                                <td class="text-end">
                                    <a href="{{ route('hrm.leave_types.edit', $t['id']) }}" class="btn btn-sm btn-default">{{ __('ui.edit') }}</a>
                                    <button class="btn btn-sm btn-danger" onclick="deleteType({{ $t['id'] }})">{{ __('ui.delete') }}</button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="2" class="text-center text-muted">{{ __('ui.no_leave_types_found') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="row" style="margin-top:15px;">
                <div class="col-md-6"></div>
                <div class="col-md-6 text-right">
                    @if(isset($paginator))
                        {{ $paginator->appends(request()->query())->links() }}
                    @endif
                </div>
            </div>
        </div>
    </div>
</section>

@push('scripts')
<script>
    let hrmTypeTimer = null;
    function applyFilters() {
        const search = document.getElementById('search').value;
        const params = new URLSearchParams(window.location.search);
        if (search) params.set('search', search); else params.delete('search');
        params.delete('page');
        if (hrmTypeTimer) clearTimeout(hrmTypeTimer);
        hrmTypeTimer = setTimeout(() => {
            fetch(window.location.pathname + '?' + params.toString(), { headers: { 'Accept': 'application/json' } })
                .then(r => {
                    const ct = r.headers.get('content-type') || '';
                    if (ct.indexOf('application/json') === -1) { window.location.search = params.toString(); return null; }
                    return r.json();
                })
                .then(json => { if (!json) return; updateTable(json); })
                .catch(() => { window.location.search = params.toString(); });
        }, 250);
    }
    function updateTable(json) {
        const tbody = document.getElementById('types_table_body');
        tbody.innerHTML = '';
        if (!json.leave_types || !json.leave_types.length) {
            tbody.innerHTML = '<tr><td colspan="2" class="text-center text-muted">{{ __('ui.no_leave_types_found') }}</td></tr>';
            return;
        }
        json.leave_types.forEach(t => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>${t.name || '-'}</td>
                <td class="text-end">
                    <a href="${window.location.pathname}/${t.id}/edit" class="btn btn-sm btn-outline-secondary">{{ __('ui.edit') }}</a>
                    <button class="btn btn-sm btn-outline-danger" onclick="deleteType(${t.id})">{{ __('ui.delete') }}</button>
                </td>
            `;
            tbody.appendChild(tr);
        });
        document.querySelectorAll('.text-muted').forEach(el => {
            if (el.textContent.trim().startsWith("{{ __('ui.total_types') }}")) {
                el.textContent = "{{ __('ui.total_types') }}" + ' ' + (json.totalRows ?? 0);
            }
        });
    }

    function deleteType(id) {
        window.hrmConfirm("{{ __('ui.delete_this_type') }}", { title: "{{ __('ui.delete_leave_type') }}", confirmButtonText: "{{ __('ui.delete') }}" }).then(confirmed => {
            if (!confirmed) return;
            fetch(`${window.location.pathname}/${id}`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: new URLSearchParams({ _method: 'DELETE' })
            })
            .then(r => r.json())
            .then(json => {
                if (json && json.success) {
                    if (window.toastr) { toastr.success("{{ __('ui.deleted_successfully') }}"); }
                    if (window.playSuccess) { window.playSuccess(); }
                    applyFilters();
                } else {
                    if (window.toastr) { toastr.error("{{ __('ui.delete_failed') }}"); }
                    if (window.playError) { window.playError(); }
                }
            })
            .catch(() => { if (window.toastr) { toastr.error("{{ __('ui.delete_failed') }}"); } if (window.playError) { window.playError(); } });
        });
    }
</script>
@endpush

@endsection
