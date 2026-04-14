@extends('layouts.app')

@section('title', 'Employees')

@section('content')
@include('hrm::partials.hrm_page_header', [
    'title' => 'Employees',
    'subtitle' => 'Track staff records, assignments, and personnel information in one place.',
    'actions' => '<a href="'.route('hrm.employees.create').'" class="btn btn-primary"><i class="fa fa-plus"></i> Create Employee</a>'
])

<section class="content">
    <div class="box box-primary">
        <div class="box-header with-border">
            <h2 class="box-title h3">Employee Register</h2>
            <div class="box-tools pull-right">
                <span class="label label-info">Total employees: {{ $totalRows ?? 0 }}</span>
            </div>
        </div>
        <div class="box-body">
            <div class="row mb-3">
                <div class="col-md-4">
                    <label for="company_filter" class="form-label">Company</label>
                    <select id="company_filter" class="form-control" onchange="applyFilters()">
                        <option value="">All companies</option>
                        @foreach($companies as $c)
                            <option value="{{ $c->id }}">{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="search" class="form-label">Search</label>
                    <input id="search" class="form-control" placeholder="Search by name or username" oninput="applyFilters()" />
                </div>
                <div class="col-md-4 d-flex align-items-end justify-content-end">
                    <small class="text-muted">Use the filters to narrow the staff list.</small>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover table-striped align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Name</th>
                            <th>Department</th>
                            <th>Designation</th>
                            <th>Office Shift</th>
                            <th>Phone</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="employees_table_body">
                        @forelse($employees as $emp)
                            <tr>
                                <td>
                                    <strong>{{ $emp['firstname'] ?? '' }} {{ $emp['lastname'] ?? '' }}</strong>
                                    @if(!empty($emp['is_system_user']))
                                        <span class="label label-warning" style="margin-left:6px;">System User</span>
                                    @endif
                                </td>
                                <td>{{ $emp['department_name'] ?? '-' }}</td>
                                <td>{{ $emp['designation_name'] ?? '-' }}</td>
                                <td>{{ $emp['office_shift_name'] ?? '-' }}</td>
                                <td>{{ $emp['phone'] ?? '-' }}</td>
                                <td class="text-end">
                                    <a href="{{ route('hrm.employees.show', $emp['id']) }}" class="btn btn-sm btn-default">View</a>
                                    <a href="{{ route('hrm.employees.edit', $emp['id']) }}" class="btn btn-sm btn-default">Edit</a>
                                    <form action="{{ route('hrm.employees.destroy', $emp['id']) }}" method="POST" style="display:inline-block">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-danger" data-hrm-confirm-submit="1" data-hrm-confirm="{{ !empty($emp['is_system_user']) ? 'Remove this system user from employee list?' : 'Delete this employee?' }}" data-hrm-confirm-title="{{ !empty($emp['is_system_user']) ? 'Remove System User' : 'Delete Employee' }}">{{ !empty($emp['is_system_user']) ? 'Remove' : 'Delete' }}</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted">No employees found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="row" style="margin-top:15px;">
                <div class="col-md-6">
                    <label for="employees_per_page" class="me-2">Per page:</label>
                    <select id="employees_per_page" class="form-control d-inline-block" style="width:120px" onchange="changePerPage()" aria-label="Employees per page">
                        <option value="10" {{ request('limit') == 10 ? 'selected' : '' }}>10</option>
                        <option value="25" {{ request('limit') == 25 ? 'selected' : '' }}>25</option>
                        <option value="50" {{ request('limit') == 50 ? 'selected' : '' }}>50</option>
                        <option value="100" {{ request('limit') == 100 ? 'selected' : '' }}>100</option>
                        <option value="-1" {{ request('limit') == '-1' ? 'selected' : '' }}>All</option>
                    </select>
                </div>
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
    // Debounced live search + filter with server-side JSON fallback
    let hrmLiveTimer = null;

    function applyFilters() {
        const company = document.getElementById('company_filter').value;
        const search = document.getElementById('search').value;
        const params = new URLSearchParams(window.location.search);
        if (company) params.set('company_id', company); else params.delete('company_id');
        if (search) params.set('search', search); else params.delete('search');
        // Reset to first page when filters change
        params.delete('page');

        // debounce
        if (hrmLiveTimer) clearTimeout(hrmLiveTimer);
        hrmLiveTimer = setTimeout(() => {
            // Try fetching JSON (AJAX). If server returns HTML (non-JSON), fall back to full page navigation.
            fetch(window.location.pathname + '?' + params.toString(), { headers: { 'Accept': 'application/json' } })
                .then(resp => {
                    const ct = resp.headers.get('content-type') || '';
                    if (ct.indexOf('application/json') === -1) {
                        // not JSON — do a full navigation so pagination and links work normally
                        window.location.search = params.toString();
                        return null;
                    }
                    return resp.json();
                })
                .then(json => {
                    if (!json) return;
                    updateTable(json);
                })
                .catch(() => {
                    // on network error, fall back to navigation
                    window.location.search = params.toString();
                });
        }, 300);
    }

    function changePerPage() {
        const per = document.getElementById('employees_per_page').value;
        const params = new URLSearchParams(window.location.search);
        if (per) params.set('limit', per); else params.delete('limit');
        params.delete('page');
        window.location.search = params.toString();
    }

    function updateTable(json) {
        if (!json) return;
        const tbody = document.getElementById('employees_table_body');
        tbody.innerHTML = '';
        if (!json.employees || !json.employees.length) {
            tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">No employees found.</td></tr>';
            return;
        }
        json.employees.forEach(emp => {
            const tr = document.createElement('tr');
            const sourceBadge = emp.is_system_user ? '<span class="label label-warning" style="margin-left:6px;">System User</span>' : '';
            const removeLabel = emp.is_system_user ? 'Remove' : 'Delete';
            const removeConfirm = emp.is_system_user ? 'Remove this system user from employee list?' : 'Delete this employee?';
            const removeTitle = emp.is_system_user ? 'Remove System User' : 'Delete Employee';
            tr.innerHTML = `
                <td><strong>${emp.firstname || ''} ${emp.lastname || ''}</strong>${sourceBadge}</td>
                <td>${emp.department_name || '-'}</td>
                <td>${emp.designation_name || '-'}</td>
                <td>${emp.office_shift_name || '-'}</td>
                <td>${emp.phone || '-'}</td>
                <td class="text-end">
                    <a href="${window.location.pathname}/${emp.id}" class="btn btn-sm btn-outline-primary">View</a>
                    <a href="${window.location.pathname}/${emp.id}/edit" class="btn btn-sm btn-outline-secondary">Edit</a>
                    <form action="${window.location.pathname}/${emp.id}" method="POST" style="display:inline-block">
                        <input type="hidden" name="_token" value="{{ csrf_token() }}">
                        <input type="hidden" name="_method" value="DELETE">
                        <button class="btn btn-sm btn-danger" data-hrm-confirm-submit="1" data-hrm-confirm="${removeConfirm}" data-hrm-confirm-title="${removeTitle}">${removeLabel}</button>
                    </form>
                </td>
            `;
            tbody.appendChild(tr);
        });
        // update total display if present
        const total = json.totalRows ?? 0;
        document.querySelectorAll('.text-muted').forEach(el => {
            if (el.textContent.trim().startsWith('Total employees:')) {
                el.textContent = 'Total employees: ' + total;
            }
        });
    }

    // Preselect filters from query string on load
    (function() {
        const params = new URLSearchParams(window.location.search);
        const per = params.get('limit');
        const company = params.get('company_id');
        const search = params.get('search');
        if (per) document.getElementById('employees_per_page').value = per;
        if (company) document.getElementById('company_filter').value = company;
        if (search) document.getElementById('search').value = search;
    })();
</script>
@endpush

@endsection
