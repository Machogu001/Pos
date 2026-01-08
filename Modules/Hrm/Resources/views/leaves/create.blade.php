@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4 class="mb-0">Create Leave</h4>
            <div>
                <a href="{{ route('hrm.leaves.index') }}" class="btn btn-secondary">Back to Leaves</a>
            </div>
        </div>
        <div class="card-body">
            <form id="leaveForm" enctype="multipart/form-data">
                @csrf
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Company</label>
                        <select id="company_id" name="company_id" class="form-select">
                            @if(isset($companies) && count($companies) > 0)
                                <option value="">-- Select Company --</option>
                                @foreach($companies as $c)
                                    <option value="{{ $c->id }}">{{ $c->name }}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Leave Type</label>
                        <select id="leave_type_id" name="leave_type_id" class="form-select">
                            @if(isset($leave_types) && count($leave_types) > 0)
                                <option value="">-- Select Leave Type --</option>
                                @foreach($leave_types as $t)
                                    <option value="{{ $t->id }}">{{ $t->name ?? $t->title ?? 'Type #'.$t->id }}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Employee</label>
                        <select id="employee_id" name="employee_id" class="form-select"></select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Department</label>
                        <select id="department_id" name="department_id" class="form-select">
                            @if(isset($departments) && count($departments) > 0)
                                <option value="">-- Select Department --</option>
                                @foreach($departments as $d)
                                    <option value="{{ $d->id }}" data-company="{{ $d->company_id ?? '' }}">{{ $d->department }}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-3">
                        <label class="form-label">Start Date</label>
                        <input type="date" id="start_date" name="start_date" class="form-control" />
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">End Date</label>
                        <input type="date" id="end_date" name="end_date" class="form-control" />
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Reason</label>
                    <textarea id="reason" name="reason" class="form-control" rows="3"></textarea>
                </div>

                <div class="row mb-3">
                    <div class="col-md-4">
                        <label class="form-label">Attachment</label>
                        <input type="file" id="attachment" name="attachment" class="form-control" />
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Half Day</label>
                        <select id="half_day" name="half_day" class="form-select">
                            <option value="0">No</option>
                            <option value="1">Yes</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Status</label>
                        <select id="status" name="status" class="form-select">
                            <option value="pending">Pending</option>
                            <option value="approved">Approved</option>
                            <option value="rejected">Rejected</option>
                        </select>
                    </div>
                </div>

                <div class="d-flex justify-content-end">
                    <button type="submit" class="btn btn-primary">Create</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
        document.addEventListener('DOMContentLoaded', () => {
            // fetch initial data (companies, leave_types, departments)
            // Include credentials so session/auth cookies are sent and the route can return JSON
            fetch('{{ route('hrm.leaves.create') }}', { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
                .then(r => {
                    // only try to parse JSON when the server actually returned JSON
                    const ct = r.headers.get('content-type') || '';
                    if (!r.ok) throw new Error('Network response was not ok: ' + r.status);
                    if (!ct.includes('application/json')) {
                        // server returned HTML (likely a login/redirect). Let server-side options remain
                        throw new Error('Expected JSON but got ' + ct);
                    }
                    return r.json();
                })
                .then(json => {
                    const companies = json.companies || [];
                    const leave_types = json.leave_types || [];
                    const departments = json.departments || [];
                    const compSel = document.getElementById('company_id');
                    companies.forEach(c => { const o = document.createElement('option'); o.value = c.id; o.text = c.name; compSel.appendChild(o); });
                    const ltSel = document.getElementById('leave_type_id');
                    leave_types.forEach(t => { const o = document.createElement('option'); o.value = t.id; o.text = t.name || t.title || ('#'+t.id); ltSel.appendChild(o); });
                    const depSel = document.getElementById('department_id');
                    departments.forEach(d => { const o = document.createElement('option'); o.value = d.id; o.dataset.company = d.company_id; o.text = d.department; depSel.appendChild(o); });

                    // If we have at least one company, trigger a change so employees are loaded for the selected company
                    if (compSel && compSel.options && compSel.options.length > 0) {
                        // if no selected option, select the first non-empty option
                        if (!compSel.value) {
                            // prefer first non-empty option
                            for (let i=0;i<compSel.options.length;i++){
                                if (compSel.options[i].value){ compSel.selectedIndex = i; break; }
                            }
                        }
                        compSel.dispatchEvent(new Event('change'));
                    }
                })
                .catch((err) => {
                    // keep server-rendered options if any and log for debugging
                    console.warn('Could not load initial HRM metadata (companies/leave_types/departments):', err && err.message);
                });

            // when company changes, fetch employees for that company and filter departments
                document.getElementById('company_id').addEventListener('change', (e) => {
                const id = e.target.value;
                const empSel = document.getElementById('employee_id');
                const depSel = document.getElementById('department_id');
                empSel.innerHTML = '';
                // show loading placeholder
                var loadingOpt = document.createElement('option'); loadingOpt.text = 'Loading...'; loadingOpt.disabled = true; empSel.appendChild(loadingOpt);
                // filter departments client-side by company
                Array.from(depSel.options).forEach(opt => {
                    // treat empty dataset.company as a global department and keep it visible
                    const comp = opt.dataset.company || '';
                    opt.style.display = (!id || comp === '' || comp == id) ? '' : 'none';
                });
                // if currently selected department is now hidden, clear selection and pick the first visible option
                if (depSel.value) {
                    const curOpt = Array.from(depSel.options).find(o => o.value == depSel.value);
                    if (curOpt && curOpt.style.display === 'none') {
                        depSel.value = '';
                        const firstVisible = Array.from(depSel.options).find(o => o.style.display !== 'none');
                        if (firstVisible) depSel.value = firstVisible.value;
                    }
                }
                if (!id) {
                    empSel.innerHTML = ''; // clear
                    var placeholder = document.createElement('option'); placeholder.text = '-- Select Employee --'; placeholder.value = ''; empSel.appendChild(placeholder);
                    return;
                }
                fetch('{{ route('hrm.employees.by_company') }}?id=' + encodeURIComponent(id), { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' })
                    .then(r => {
                        const ct = r.headers.get('content-type') || '';
                        if (!r.ok) throw new Error('Network response was not ok: ' + r.status);
                        if (!ct.includes('application/json')) throw new Error('Expected JSON but got ' + ct);
                        return r.json();
                    })
                    .then(json => {
                        empSel.innerHTML = '';
                        var list = (json && json.employees) ? json.employees : (Array.isArray(json) ? json : []);
                        if (!list || list.length === 0) {
                            var noOpt = document.createElement('option'); noOpt.text = 'No employees found'; noOpt.value = ''; noOpt.disabled = true; empSel.appendChild(noOpt);
                            return;
                        }
                        list.forEach(emp => {
                            const o = document.createElement('option'); o.value = emp.id; o.text = emp.name || emp.username || ((emp.firstname || '') + ' ' + (emp.lastname||'')); empSel.appendChild(o);
                        });
                    })
                    .catch((err) => {
                        console.error('Failed to load employees for company', id, err && err.message);
                        empSel.innerHTML = '';
                        var errOpt = document.createElement('option'); errOpt.text = 'Error loading employees'; errOpt.value = ''; errOpt.disabled = true; empSel.appendChild(errOpt);
                    });
            });

        // submit form via AJAX with FormData
        document.getElementById('leaveForm').addEventListener('submit', function(ev) {
            ev.preventDefault();
            const fd = new FormData(this);
            fetch('{{ route('hrm.leaves.store') }}', { method: 'POST', body: fd, headers: { 'Accept': 'application/json' } })
                .then(async r => { const text = await r.text(); try { return JSON.parse(text); } catch(e){ return { success:false, message:text }; } })
                .then(json => {
                    if (json && json.success) {
                        if (window.toastr) { toastr.success('Created successfully'); }
                        if (window.playSuccess) { window.playSuccess(); }
                        window.location = '{{ route('hrm.leaves.index') }}';
                    } else if (json && json.isvalid === false && json.remaining_leave) {
                        if (window.toastr) { toastr.error(json.remaining_leave); } else { alert(json.remaining_leave); }
                        if (window.playError) { window.playError(); }
                    } else {
                        if (window.toastr) { toastr.error('Create failed'); } else { alert('Error creating leave'); }
                        if (window.playError) { window.playError(); }
                    }
                })
                .catch((err) => { if (window.toastr) { toastr.error('Network error'); } else { alert('Network error'); } if (window.playError) { window.playError(); } });
        });
    });
</script>
@endpush

@endsection
