@extends('layouts.app')

@section('title', __('ui.edit_leave'))

@section('content')
@include('hrm::partials.hrm_page_header', [
    'title' => __('ui.edit_leave'),
    'subtitle' => __('ui.review_and_update_an_existing_leave_request'),
    'actions' => '<a href="'.route('hrm.leaves.index').'" class="btn btn-default"><i class="fa fa-arrow-left"></i> '. __('ui.back_to_leaves') .'</a>'
])

<section class="content">
    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title">{{ __('ui.leave_details') }}</h3>
        </div>
        <div class="box-body">
            <form id="leaveForm" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <input type="hidden" id="leave_id" />
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label" for="company_id">{{ __('ui.company') }}</label>
                            <select id="company_id" name="company_id" class="form-control select2"></select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label" for="leave_type_id">{{ __('ui.leave_type') }}</label>
                            <select id="leave_type_id" name="leave_type_id" class="form-control select2"></select>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label" for="employee_id">{{ __('ui.employee') }}</label>
                            <select id="employee_id" name="employee_id" class="form-control select2"></select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label" for="department_id">{{ __('ui.department') }}</label>
                            <select id="department_id" name="department_id" class="form-control select2"></select>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="form-label" for="start_date">{{ __('ui.start_date') }}</label>
                            <input type="date" id="start_date" name="start_date" class="form-control" />
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="form-label" for="end_date">{{ __('ui.end_date') }}</label>
                            <input type="date" id="end_date" name="end_date" class="form-control" />
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="form-label" for="half_day">{{ __('ui.half_day') }}</label>
                            <select id="half_day" name="half_day" class="form-control">
                                <option value="0">{{ __('ui.no') }}</option>
                                <option value="1">{{ __('ui.yes') }}</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="form-label" for="status">{{ __('ui.status') }}</label>
                            <select id="status" name="status" class="form-control">
                                <option value="pending">{{ __('ui.pending') }}</option>
                                <option value="approved">{{ __('ui.approved') }}</option>
                                <option value="rejected">{{ __('ui.rejected') }}</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-8">
                        <div class="form-group">
                            <label class="form-label" for="reason">{{ __('ui.reason') }}</label>
                            <textarea id="reason" name="reason" class="form-control" rows="4"></textarea>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label" for="attachment">{{ __('ui.attachment') }}</label>
                            <input type="file" id="attachment" name="attachment" class="form-control" />
                            <div id="current_attachment" class="mt-2"></div>
                        </div>
                    </div>
                </div>

                <div class="text-right">
                    <a href="{{ route('hrm.leaves.index') }}" class="btn btn-default">{{ __('ui.cancel') }}</a>
                    <button type="submit" class="btn btn-primary">{{ __('ui.update_leave') }}</button>
                </div>
            </form>
        </div>
    </div>
</section>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const idMatch = window.location.pathname.match(/\/(\d+)\/edit\/?$/);
        const leaveId = idMatch ? idMatch[1] : null;
        if (!leaveId) { window.hrmAlert("{{ __('ui.invalid_leave_id') }}"); return; }
        document.getElementById('leave_id').value = leaveId;

        // fetch edit metadata and the leave
        fetch(window.location.pathname, { headers: { 'Accept': 'application/json' } })
            .then(r => r.json())
                .then(json => {
                    const leave = json.leave || {};
                    const companies = json.companies || [];
                    const leave_types = json.leave_types || [];
                    const departments = json.departments || [];

                    const compSel = document.getElementById('company_id');
                    companies.forEach(c => { const o = document.createElement('option'); o.value = c.id; o.text = c.name; compSel.appendChild(o); });
                    const ltSel = document.getElementById('leave_type_id');
                    leave_types.forEach(t => { const o = document.createElement('option'); o.value = t.id; o.text = t.name || t.title || ('#'+t.id); ltSel.appendChild(o); });
                    const depSel = document.getElementById('department_id');
                    departments.forEach(d => { const o = document.createElement('option'); o.value = d.id; o.dataset.company = d.company_id; o.text = d.department; depSel.appendChild(o); });

                // Populate fields
                document.getElementById('company_id').value = leave.company_id || '';
                document.getElementById('leave_type_id').value = leave.leave_type_id || '';
                document.getElementById('start_date').value = leave.start_date || '';
                document.getElementById('end_date').value = leave.end_date || '';
                document.getElementById('reason').value = leave.reason || '';
                document.getElementById('half_day').value = leave.half_day || 0;
                document.getElementById('status').value = leave.status || 'pending';

                if (leave.attachment) {
                    const cur = document.getElementById('current_attachment');
                    const link = document.createElement('a');
                    link.href = '/hrm/leaves/' + leave.id + '/attachment';
                    link.target = '_blank';
                    link.textContent = leave.attachment;
                    cur.appendChild(link);
                }

                // fetch employees for selected company
                if (leave.company_id) {
                    // filter departments for that company
                    Array.from(depSel.options).forEach(opt => {
                        opt.style.display = (!leave.company_id || opt.dataset.company == leave.company_id) ? '' : 'none';
                    });
                    fetch('{{ route('hrm.employees.by_company') }}?id=' + encodeURIComponent(leave.company_id), { headers: { 'Accept': 'application/json' } })
                        .then(r => r.json())
                        .then(j => {
                            const empSel = document.getElementById('employee_id');
                            empSel.innerHTML = '';
                            (j.employees || []).forEach(emp => {
                                const o = document.createElement('option'); o.value = emp.id; o.text = emp.name || emp.username || (emp.firstname + ' ' + (emp.lastname||'')); empSel.appendChild(o);
                            });
                            document.getElementById('employee_id').value = leave.employee_id || '';
                            document.getElementById('department_id').value = leave.department_id || '';
                        });
                }
            })
            .catch(() => { window.hrmAlert("{{ __('ui.failed_to_load_leave_data') }}"); });

        document.getElementById('company_id').addEventListener('change', (e) => {
            const id = e.target.value;
            const empSel = document.getElementById('employee_id');
            const depSel = document.getElementById('department_id');
            empSel.innerHTML = '';
            Array.from(depSel.options).forEach(opt => {
                opt.style.display = (!id || opt.dataset.company == id) ? '' : 'none';
            });
            if (!id) return;
            fetch('{{ route('hrm.employees.by_company') }}?id=' + encodeURIComponent(id), { headers: { 'Accept': 'application/json' } })
                .then(r => r.json())
                .then(json => {
                    (json.employees || []).forEach(emp => {
                        const o = document.createElement('option'); o.value = emp.id; o.text = emp.name || emp.username || (emp.firstname + ' ' + (emp.lastname||'')); empSel.appendChild(o);
                    });
                })
                .catch(() => {});
        });

        document.getElementById('leaveForm').addEventListener('submit', function(ev) {
            ev.preventDefault();
            const fd = new FormData(this);
            // Use PATCH as it's commonly accepted by resource routes and PHP method spoofing
            if (!fd.has('_method')) fd.append('_method', 'PATCH');
            // Ensure CSRF token is present in FormData (use meta tag fallback)
            if (!fd.has('_token')) {
                const meta = document.querySelector('meta[name="csrf-token"]');
                if (meta) fd.append('_token', meta.getAttribute('content'));
            }
            const id = document.getElementById('leave_id').value;
            const endpoint = '{{ url('hrm/leaves') }}' + '/' + encodeURIComponent(id);
            fetch(endpoint, { method: 'POST', body: fd, headers: { 'Accept': 'application/json' } })
                .then(async (r) => {
                    const text = await r.text();
                    let json = null;
                    try { json = text ? JSON.parse(text) : null; } catch (e) { /* not JSON */ }
                    if (!r.ok) {
                        const body = json ? JSON.stringify(json) : text;
                        window.hrmAlert("{{ __('ui.request_failed_2') }}" + ' ' + r.status + ' ' + r.statusText);
                        return;
                    }
                    if (json && json.success) {
                        window.location = '{{ route('hrm.leaves.index') }}';
                        return;
                    }
                    if (json && json.isvalid === false && json.remaining_leave) {
                        window.hrmAlert(json.remaining_leave);
                        return;
                    }
                    window.hrmAlert("{{ __('ui.error_saving_leave') }}");
                })
                .catch((err) => { window.hrmAlert("{{ __('ui.network_error') }}" + ' ' + (err && err.message ? err.message : "{{ __('ui.unknown') }}")); });
        });
    });
</script>
@endpush

@endsection
