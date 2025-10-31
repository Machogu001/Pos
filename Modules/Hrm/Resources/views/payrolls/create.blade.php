@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Create Payroll</h2>
    <form method="POST" action="{{ route('hrm.payrolls.store') }}">
        @csrf

        <div class="form-group">
            <label>Company</label>
            <select name="company_id" id="company_id" class="form-control">
                <option value="">-- Select Company --</option>
                @foreach($companies as $c)
                    @php
                        $isBusiness = isset($c->business_id) && $c->business_id == session('business.id');
                        $label = $isBusiness ? 'Business - ' . $c->name : $c->name;
                    @endphp
                    <option value="{{ $c->id }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div class="form-group">
            <label>Employee(s)</label>
            <div class="d-flex gap-2 mb-2">
                <div>
                    <button type="button" id="select-all-employees" class="btn btn-sm btn-outline-secondary">Select all for company</button>
                </div>
                <div>
                    <button type="button" id="clear-employees" class="btn btn-sm btn-outline-secondary">Clear selection</button>
                </div>
            </div>
            <select name="employee_id[]" id="employee_id" class="form-control" multiple size="8">
                @foreach($employees as $e)
                    <option value="{{ $e->id }}">{{ $e->username ?? $e->name }}</option>
                @endforeach
            </select>
            <small class="form-text text-muted">Use Ctrl/Cmd+click to select multiple employees, or use the buttons above to select/clear.</small>
        </div>

        <div class="form-row">
            <div class="form-group col-md-6">
                <label>Period Start</label>
                <input type="date" name="period_start" class="form-control" required />
            </div>
            <div class="form-group col-md-6">
                <label>Period End</label>
                <input type="date" name="period_end" class="form-control" required />
            </div>
        </div>

        <div class="form-group">
            <label>Gross</label>
            <input type="number" step="0.01" name="gross" class="form-control" required />
        </div>

        <div class="form-group">
            <label>Deductions</label>
            <input type="number" step="0.01" name="deductions" class="form-control" value="0" />
        </div>

        <button class="btn btn-success">Create Payroll</button>
    </form>
</div>

@endsection

@section('javascript')
<script>
    (function(){
        var companySelect = document.getElementById('company_id');
        var employeeSelect = document.getElementById('employee_id');
        var selectAllBtn = document.getElementById('select-all-employees');
        var clearBtn = document.getElementById('clear-employees');

        function loadEmployeesForCompany(companyId, selectAll) {
            employeeSelect.innerHTML = '<option>Loading...</option>';
            fetch('{{ url("hrm/employees/by-company") }}?id=' + companyId)
            .then(r => r.json())
            .then(data => {
                employeeSelect.innerHTML = '';
                (data || []).forEach(function(e){
                    var opt = document.createElement('option');
                    opt.value = e.id;
                    opt.text = e.username || e.name;
                    if (selectAll) opt.selected = true;
                    employeeSelect.appendChild(opt);
                });
            }).catch(()=>{
                employeeSelect.innerHTML = '';
            });
        }

        companySelect.addEventListener('change', function() {
            var companyId = this.value;
            if (!companyId) { employeeSelect.innerHTML = ''; return; }
            loadEmployeesForCompany(companyId, false);
        });

        selectAllBtn.addEventListener('click', function(){
            var companyId = companySelect.value;
            if (!companyId) { alert('Please select a company first'); return; }
            loadEmployeesForCompany(companyId, true);
        });

        clearBtn.addEventListener('click', function(){
            Array.from(employeeSelect.options).forEach(function(o){ o.selected = false; });
        });
    })();
</script>
@endsection

@push('scripts')
<script>
    // Debug AJAX POST for payrolls
    (function(){
        var btn = document.createElement('button'); btn.type = 'button'; btn.className = 'btn btn-outline-primary mt-2'; btn.id = 'ajax-payrolls-debug'; btn.innerText = 'Send debug POST';
        var container = document.querySelector('.container'); container.appendChild(btn);
        var out = document.createElement('pre'); out.id = 'ajax-payrolls-result'; out.style.whiteSpace = 'pre-wrap'; container.appendChild(out);
        btn.addEventListener('click', function(){
            var form = document.querySelector('form'); var fd = new FormData(form);
            fetch("{{ route('hrm.payrolls.debug') }}", { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }, body: fd })
            .then(r=>r.json().catch(()=>r.text())).then(function(d){ out.textContent = JSON.stringify(d, null, 2); }).catch(function(e){ out.textContent = 'Error: '+e; });
        });
    })();
</script>
@endpush
