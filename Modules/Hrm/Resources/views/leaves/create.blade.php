@extends('layouts.app')

@section('title', __('ui.create_leave'))

@section('content')
@include('hrm::partials.hrm_page_header', [
    'title' => __('ui.create_leave'),
    'subtitle' => __('ui.submit_a_leave_request_using_a_structured_and_easy_to_review_form'),
    'actions' => '<a href="'.route('hrm.leaves.index').'" class="btn btn-default"><i class="fa fa-arrow-left"></i> '. __('ui.back_to_leaves') .'</a>'
])

<section class="content">
    <div class="box box-warning">
        <div class="box-header with-border">
            <h3 class="box-title">{{ __('ui.leave_request') }}</h3>
        </div>
        <div class="box-body">
            <form id="leaveForm" enctype="multipart/form-data">
                @csrf
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">{{ __('ui.company') }}</label>
                        <select id="company_id" name="company_id" class="form-select">
                            @if(isset($companies) && count($companies) > 0)
                                <option value="">{{ __('ui.select_company') }}</option>
                                @foreach($companies as $c)
                                    <option value="{{ $c->id }}">{{ $c->name }}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">{{ __('ui.leave_type') }}</label>
                        <select id="leave_type_id" name="leave_type_id" class="form-select">
                            @if(isset($leave_types) && count($leave_types) > 0)
                                <option value="">{{ __('ui.select_leave_type') }}</option>
                                @foreach($leave_types as $t)
                                    <option value="{{ $t->id }}">{{ $t->name ?? $t->title ?? __('ui.type_2') . $t->id }}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">{{ __('ui.employee') }}</label>
                        <select id="employee_id" name="employee_id" class="form-select"></select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">{{ __('ui.department') }}</label>
                        <select id="department_id" name="department_id" class="form-select">
                            @if(isset($departments) && count($departments) > 0)
                                <option value="">{{ __('ui.select_department') }}</option>
                                @foreach($departments as $d)
                                    <option value="{{ $d->id }}" data-company="{{ $d->company_id ?? '' }}">{{ $d->department }}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-3">
                        <label class="form-label">{{ __('ui.start_date') }}</label>
                        <input type="date" id="start_date" name="start_date" class="form-control" />
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">{{ __('ui.end_date') }}</label>
                        <input type="date" id="end_date" name="end_date" class="form-control" />
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">{{ __('ui.reason') }}</label>
                    <textarea id="reason" name="reason" class="form-control" rows="3"></textarea>
                </div>

                <div class="row mb-3">
                    <div class="col-md-4">
                        <label class="form-label">{{ __('ui.attachment') }}</label>
                        <input type="file" id="attachment" name="attachment" class="form-control" />
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">{{ __('ui.half_day') }}</label>
                        <select id="half_day" name="half_day" class="form-select">
                            <option value="0">{{ __('ui.no') }}</option>
                            <option value="1">{{ __('ui.yes') }}</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">{{ __('ui.status') }}</label>
                        <select id="status" name="status" class="form-select">
                            <option value="pending">{{ __('ui.pending') }}</option>
                            <option value="approved">{{ __('ui.approved') }}</option>
                            <option value="rejected">{{ __('ui.rejected') }}</option>
                        </select>
                    </div>
                </div>

                <div class="text-right">
                    <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> {{ __('ui.create_leave') }}</button>
                </div>
            </form>
        </div>
    </div>
</section>

@push('scripts')
<script>
        document.addEventListener('DOMContentLoaded', () => {
            // fetch initial data (companies, leave_types, departments)
            // Include credentials so session/auth cookies are sent and the route can return JSON
            fetch('{{ route('hrm.leaves.create') }}', { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
                .then(r => {
                    // only try to parse JSON when the server actually returned JSON
                    const ct = r.headers.get('content-type') || '';
                    if (!r.ok) throw new Error("{{ __('ui.network_response_was_not_ok') }}" + ' ' + r.status);
                    if (!ct.includes('application/json')) {
                        // server returned HTML (likely a login/redirect). Let server-side options remain
                        throw new Error("{{ __('ui.expected_json_but_got') }}" + ' ' + ct);
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
                    console.warn("{{ __('ui.could_not_load_initial_hrm_metadata_companies_leave_types_departments') }}", err && err.message);
                });

            // when company changes, fetch employees for that company and filter departments
                document.getElementById('company_id').addEventListener('change', (e) => {
                const id = e.target.value;
                const empSel = document.getElementById('employee_id');
                const depSel = document.getElementById('department_id');
                empSel.innerHTML = '';
                // show loading placeholder
                var loadingOpt = document.createElement('option'); loadingOpt.text = "{{ __('ui.loading') }}"; loadingOpt.disabled = true; empSel.appendChild(loadingOpt);
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
                    var placeholder = document.createElement('option'); placeholder.text = "{{ __('ui.select_employee') }}"; placeholder.value = ''; empSel.appendChild(placeholder);
                    return;
                }
                fetch('{{ route('hrm.employees.by_company') }}?id=' + encodeURIComponent(id), { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' })
                    .then(r => {
                        const ct = r.headers.get('content-type') || '';
                        if (!r.ok) throw new Error("{{ __('ui.network_response_was_not_ok') }}" + ' ' + r.status);
                        if (!ct.includes('application/json')) throw new Error("{{ __('ui.expected_json_but_got') }}" + ' ' + ct);
                        return r.json();
                    })
                    .then(json => {
                        empSel.innerHTML = '';
                        var list = (json && json.employees) ? json.employees : (Array.isArray(json) ? json : []);
                        if (!list || list.length === 0) {
                            var noOpt = document.createElement('option'); noOpt.text = "{{ __('ui.no_employees_found_2') }}"; noOpt.value = ''; noOpt.disabled = true; empSel.appendChild(noOpt);
                            return;
                        }
                        list.forEach(emp => {
                            const o = document.createElement('option'); o.value = emp.id; o.text = emp.name || emp.username || ((emp.firstname || '') + ' ' + (emp.lastname||'')); empSel.appendChild(o);
                        });
                    })
                    .catch((err) => {
                        console.error("{{ __('ui.failed_to_load_employees_for_company') }}", id, err && err.message);
                        empSel.innerHTML = '';
                        var errOpt = document.createElement('option'); errOpt.text = "{{ __('ui.error_loading_employees') }}"; errOpt.value = ''; errOpt.disabled = true; empSel.appendChild(errOpt);
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
                        if (window.toastr) { toastr.success("{{ __('ui.created_successfully') }}"); }
                        if (window.playSuccess) { window.playSuccess(); }
                        window.location = '{{ route('hrm.leaves.index') }}';
                    } else if (json && json.isvalid === false && json.remaining_leave) {
                        if (window.toastr) { toastr.error(json.remaining_leave); } else { window.hrmAlert(json.remaining_leave); }
                        if (window.playError) { window.playError(); }
                    } else {
                        if (window.toastr) { toastr.error("{{ __('ui.create_failed') }}"); } else { window.hrmAlert("{{ __('ui.error_creating_leave') }}"); }
                        if (window.playError) { window.playError(); }
                    }
                })
                .catch((err) => { if (window.toastr) { toastr.error("{{ __('ui.network_error_2') }}"); } else { window.hrmAlert("{{ __('ui.network_error_2') }}"); } if (window.playError) { window.playError(); } });
        });
    });
</script>
@endpush

@endsection
