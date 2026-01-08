@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4 class="mb-0">Leave Types</h4>
            <div>
                <a href="{{ route('hrm.leave_types.create') }}" class="btn btn-primary">Create Leave Type</a>
            </div>
        </div>
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="search" class="form-label">Search</label>
                    <input id="search" class="form-control" placeholder="Search by name" oninput="applyFilters()" />
                </div>
                <div class="col-md-6 d-flex align-items-end justify-content-end">
                    <small class="text-muted">Total types: {{ $totalRows ?? 0 }}</small>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Name</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="types_table_body">
                        @forelse($leave_types as $t)
                            <tr>
                                <td>{{ $t['name'] ?? '-' }}</td>
                                <td class="text-end">
                                    <a href="{{ route('hrm.leave_types.edit', $t['id']) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                                    <button class="btn btn-sm btn-outline-danger" onclick="deleteType({{ $t['id'] }})">Delete</button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="2" class="text-center text-muted">No leave types found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-between align-items-center mt-3">
                <div></div>
                <div>
                    @if(isset($paginator))
                        {{ $paginator->appends(request()->query())->links() }}
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

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
            tbody.innerHTML = '<tr><td colspan="2" class="text-center text-muted">No leave types found.</td></tr>';
            return;
        }
        json.leave_types.forEach(t => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>${t.name || '-'}</td>
                <td class="text-end">
                    <a href="${window.location.pathname}/${t.id}/edit" class="btn btn-sm btn-outline-secondary">Edit</a>
                    <button class="btn btn-sm btn-outline-danger" onclick="deleteType(${t.id})">Delete</button>
                </td>
            `;
            tbody.appendChild(tr);
        });
        document.querySelectorAll('.text-muted').forEach(el => {
            if (el.textContent.trim().startsWith('Total types:')) {
                el.textContent = 'Total types: ' + (json.totalRows ?? 0);
            }
        });
    }

    function deleteType(id) {
        if (!confirm('Delete this type?')) return;
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
                if (window.toastr) { toastr.success('Deleted successfully'); }
                if (window.playSuccess) { window.playSuccess(); }
                applyFilters();
            } else {
                if (window.toastr) { toastr.error('Delete failed'); }
                if (window.playError) { window.playError(); }
            }
        })
        .catch(() => { if (window.toastr) { toastr.error('Delete failed'); } if (window.playError) { window.playError(); } });
    }
</script>
@endpush

@endsection
