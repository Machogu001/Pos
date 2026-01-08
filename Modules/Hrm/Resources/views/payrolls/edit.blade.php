@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Edit Payroll #{{ $payroll->id }}</h2>
    <a href="{{ route('hrm.payrolls.index') }}" class="btn btn-secondary mb-3">Back to Payrolls</a>

    <form method="POST" action="{{ route('hrm.payrolls.update', $payroll->id) }}">
        @csrf
        @method('PUT')
        @include('hrm::partials.hrm_form_toolbar')

        <div class="mb-3">
            <label class="form-label">Company</label>
            <select name="company_id" class="form-control">
                @foreach($companies as $c)
                    <option value="{{ $c->id }}" {{ $payroll->company_id == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label">Employee</label>
            <select name="employee_id" class="form-control">
                @foreach($employees as $e)
                    @php $label = $e->username ?? trim((($e->firstname ?? '') . ' ' . ($e->lastname ?? ''))); @endphp
                    <option value="{{ $e->id }}" {{ $payroll->employee_id == $e->id ? 'selected' : '' }}>{{ $label ?: 'Employee #'.$e->id }}</option>
                @endforeach
            </select>
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Period Start</label>
                <input type="date" name="period_start" class="form-control" value="{{ $payroll->period_start }}" required />
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Period End</label>
                <input type="date" name="period_end" class="form-control" value="{{ $payroll->period_end }}" required />
            </div>
        </div>

        <div class="row">
            <div class="col-md-4 mb-3">
                <label class="form-label">Gross</label>
                <input type="number" step="0.01" name="gross" class="form-control" value="{{ $payroll->gross }}" required />
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">Deductions</label>
                <input type="number" step="0.01" id="deductions" name="deductions" class="form-control" value="{{ $payroll->deductions }}" />
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">Net</label>
                <input type="number" step="0.01" id="net" name="net" class="form-control" value="{{ $payroll->net }}" readonly />
            </div>
        </div>

        <button class="btn btn-primary">Save</button>
    </form>
</div>
@endsection

@section('javascript')
<script>
    (function(){
        var grossEl = document.querySelector('input[name="gross"]');
        var deductionsEl = document.getElementById('deductions');
        var netEl = document.getElementById('net');

        function recalc(){
            var gross = parseFloat(grossEl && grossEl.value) || 0;
            var ded = parseFloat(deductionsEl && deductionsEl.value) || 0;
            var net = Math.max(0, gross - ded);
            if(netEl) netEl.value = net.toFixed(2);
        }

        if(grossEl) grossEl.addEventListener('input', recalc);
        if(deductionsEl) deductionsEl.addEventListener('input', recalc);
        recalc();
    })();
</script>
@endsection

