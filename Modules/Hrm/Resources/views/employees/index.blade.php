@extends('layouts.app')

@section('title', __('ui.employees'))

@section('content')
@include('hrm::partials.hrm_page_header', [
    'title' => __('ui.employees'),
    'subtitle' => __('ui.track_staff_records_assignments_and_personnel_information_in_one_place'),
    'actions' => '<a href="'.route('hrm.employees.create').'" class="btn btn-primary"><i class="fa fa-plus"></i> '. __('ui.create_employee') .'</a>'
])

<section class="content">
    <div class="box box-primary">
        <div class="box-header with-border">
            <h2 class="box-title h3">{{ __('ui.employee_register') }}</h2>
            <div class="box-tools pull-right">
                <span class="label label-info">{{ __('ui.total_employees') }} {{ $totalRows ?? 0 }}</span>
            </div>
        </div>
        <div class="box-body">
            <div class="row mb-3">
                <div class="col-md-4">
                    <label for="company_filter" class="form-label">{{ __('ui.company') }}</label>
                    <select id="company_filter" class="form-control" onchange="applyFilters()">
                        <option value="">{{ __('ui.all_companies') }}</option>
                        @foreach($companies as $c)
                            <option value="{{ $c->id }}">{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="search" class="form-label">{{ __('ui.search') }}</label>
                    <input id="search" class="form-control" placeholder="{{ __('ui.search_by_name_or_username') }}" oninput="applyFilters()" />
                </div>
                <div class="col-md-4 d-flex align-items-end justify-content-end">
                    <small class="text-muted">{{ __('ui.use_the_filters_to_narrow_the_staff_list') }}</small>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover table-striped align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>{{ __('ui.name') }}</th>
                            <th>{{ __('ui.department') }}</th>
                            <th>{{ __('ui.designation') }}</th>
                            <th>{{ __('ui.office_shift_2') }}</th>
                            <th>{{ __('ui.phone') }}</th>
                            <th class="text-end">{{ __('ui.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody id="employees_table_body">
                        @forelse($employees as $emp)
                            <tr>
                                <td>
                                    <strong>{{ $emp['firstname'] ?? '' }} {{ $emp['lastname'] ?? '' }}</strong>
                                    @if(!empty($emp['is_system_user']))
                                        <span class="label label-warning" style="margin-left:6px;">{{ __('ui.system_user') }}</span>
                                    @endif
                                </td>
                                <td>{{ $emp['department_name'] ?? '-' }}</td>
                                <td>{{ $emp['designation_name'] ?? '-' }}</td>
                                <td>{{ $emp['office_shift_name'] ?? '-' }}</td>
                                <td>{{ $emp['phone'] ?? '-' }}</td>
                                <td class="text-end">
                                    <a href="{{ route('hrm.employees.show', $emp['id']) }}" class="btn btn-sm btn-default">{{ __('ui.view') }}</a>
                                    <a href="{{ route('hrm.employees.edit', $emp['id']) }}" class="btn btn-sm btn-default">{{ __('ui.edit') }}</a>
                                    <form action="{{ route('hrm.employees.destroy', $emp['id']) }}" method="POST" style="display:inline-block">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-danger" data-hrm-confirm-submit="1" data-hrm-confirm="{{ !empty($emp['is_system_user']) ? __('ui.remove_this_system_user_from_employee_list') : __('ui.delete_this_employee') }}" data-hrm-confirm-title="{{ !empty($emp['is_system_user']) ? __('ui.remove_system_user') : __('ui.delete_employee') }}">{{ !empty($emp['is_system_user']) ? __('ui.remove') : __('ui.delete') }}</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted">{{ __('ui.no_employees_found') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="row" style="margin-top:15px;">
                <div class="col-md-6">
                    <label for="employees_per_page" class="me-2">{{ __('ui.per_page') }}</label>
                    <select id="employees_per_page" class="form-control d-inline-block" style="width:120px" onchange="changePerPage()" aria-label="{{ __('ui.employees_per_page') }}">
                        <option value="10" {{ request('limit') == 10 ? 'selected' : '' }}>10</option>
                        <option value="25" {{ request('limit') == 25 ? 'selected' : '' }}>25</option>
                        <option value="50" {{ request('limit') == 50 ? 'selected' : '' }}>50</option>
                        <option value="100" {{ request('limit') == 100 ? 'selected' : '' }}>100</option>
                        <option value="-1" {{ request('limit') == '-1' ? 'selected' : '' }}>{{ __('ui.all') }}</option>
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
            tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">{{ __('ui.no_employees_found') }}</td></tr>';
            return;
        }
        json.employees.forEach(emp => {
            const tr = document.createElement('tr');
            const sourceBadge = emp.is_system_user ? '<span class="label label-warning" style="margin-left:6px;">{{ __('ui.system_user') }}</span>' : '';
            const removeLabel = emp.is_system_user ? "{{ __('ui.remove') }}" : "{{ __('ui.delete') }}";
            const removeConfirm = emp.is_system_user ? "{{ __('ui.remove_this_system_user_from_employee_list') }}" : "{{ __('ui.delete_this_employee') }}";
            const removeTitle = emp.is_system_user ? "{{ __('ui.remove_system_user') }}" : "{{ __('ui.delete_employee') }}";
            tr.innerHTML = `
                <td><strong>${emp.firstname || ''} ${emp.lastname || ''}</strong>${sourceBadge}</td>
                <td>${emp.department_name || '-'}</td>
                <td>${emp.designation_name || '-'}</td>
                <td>${emp.office_shift_name || '-'}</td>
                <td>${emp.phone || '-'}</td>
                <td class="text-end">
                    <a href="${window.location.pathname}/${emp.id}" class="btn btn-sm btn-outline-primary">{{ __('ui.view') }}</a>
                    <a href="${window.location.pathname}/${emp.id}/edit" class="btn btn-sm btn-outline-secondary">{{ __('ui.edit') }}</a>
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
            if (el.textContent.trim().startsWith("{{ __('ui.total_employees') }}")) {
                el.textContent = "{{ __('ui.total_employees') }}" + ' ' + total;
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
