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
            <label>Employee</label>
            <select name="employee_id" id="employee_id" class="form-control">
                <option value="">-- Select Employee --</option>
                @foreach($employees as $e)
                    <option value="{{ $e->id }}">{{ $e->username }}</option>
                @endforeach
            </select>
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
    document.getElementById('company_id').addEventListener('change', function() {
        var companyId = this.value;
        var employeeSelect = document.getElementById('employee_id');
        employeeSelect.innerHTML = '<option>Loading...</option>';
    fetch('{{ url("hrm/employees/by-company") }}?id=' + companyId)
            .then(r => r.json())
            .then(data => {
                employeeSelect.innerHTML = '<option value="">-- Select Employee --</option>';
                (data || []).forEach(function(e){
                    var opt = document.createElement('option');
                    opt.value = e.id;
                    opt.text = e.username;
                    employeeSelect.appendChild(opt);
                });
            }).catch(()=>{
                employeeSelect.innerHTML = '<option value="">-- Select Employee --</option>';
            });
    });
</script>
@endsection
