@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Edit Employee</h2>
    <form method="POST" action="{{ route('hrm.employees.update', $employee->id) }}">
        @csrf
        @method('PUT')
        @include('hrm::partials.hrm_form_toolbar')
        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        <div class="form-group">
            <label>Company</label>
            <select name="company_id" id="company_id" class="form-control select2">
                @foreach($companies as $c)
                    <option value="{{ $c->id }}" @if($employee->company_id == $c->id) selected @endif>{{ $c->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-group">
            <label>Country</label>
            <input name="country" class="form-control" value="{{ $employee->country }}" />
        </div>
        <div class="form-group">
            <label>Gender</label>
            <select name="gender" class="form-control">
                <option value="Male" @if($employee->gender == 'Male') selected @endif>Male</option>
                <option value="Female" @if($employee->gender == 'Female') selected @endif>Female</option>
                <option value="Other" @if($employee->gender == 'Other') selected @endif>Other</option>
            </select>
        </div>
        <div class="form-group">
            <label>First name</label>
            <input name="firstname" class="form-control" required value="{{ $employee->firstname }}" />
        </div>
        <div class="form-group">
            <label>Last name</label>
            <input name="lastname" class="form-control" value="{{ $employee->lastname }}" />
        </div>
        <div class="form-group">
            <label>Department</label>
            <select name="department_id" class="form-control">
                <option value="">--</option>
                @foreach($departments as $d)
                    <option value="{{ $d->id }}" @if($employee->department_id == $d->id) selected @endif>{{ $d->department ?? ($d->name ?? '') }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-group">
            <label>Email</label>
            <input name="email" type="email" class="form-control" value="{{ $employee->email }}" />
        </div>
        <div class="form-group">
            <label>Phone</label>
            <input name="phone" class="form-control" value="{{ $employee->phone }}" />
        </div>
        <div class="form-group">
            <label>Hire date</label>
            <?php
                // Ensure we have a Carbon instance before calling format() to avoid calling format() on plain strings
                $joiningDate = null;
                if (!empty($employee->joining_date)) {
                    try {
                        $joiningDate = \Carbon\Carbon::parse($employee->joining_date);
                    } catch (\Exception $e) {
                        $joiningDate = null;
                    }
                } elseif (!empty($employee->hire_date)) {
                    try {
                        $joiningDate = \Carbon\Carbon::parse($employee->hire_date);
                    } catch (\Exception $e) {
                        $joiningDate = null;
                    }
                }
                $joiningValue = old('joining_date', $joiningDate ? $joiningDate->format('Y-m-d') : '');
            ?>
            <input name="joining_date" type="date" class="form-control" value="{{ $joiningValue }}" />
        </div>
        <div class="form-group">
            <label>Salary</label>
            <input name="basic_salary" class="form-control" value="{{ $employee->basic_salary ?? $employee->salary ?? '' }}" />
        </div>
        <div class="form-group">
            <label>Total annual leave (days)</label>
            <input id="total_leave" name="total_leave" type="number" min="0" step="1" class="form-control" value="{{ old('total_leave', $employee->total_leave ?? config('hrm.default_annual_leave', 21)) }}" />
            <small class="form-text text-muted">Employee's total annual leave entitlement.</small>
        </div>
        <div class="form-group">
            <label>Remaining leave (days)</label>
            <input id="remaining_leave" name="remaining_leave" type="number" min="0" step="1" class="form-control" value="{{ old('remaining_leave', $employee->remaining_leave ?? $employee->total_leave ?? config('hrm.default_annual_leave', 21)) }}" />
            <small id="remaining_help" class="form-text text-muted">Remaining leave days. Must be less than or equal to total leave.</small>
        </div>
        <div class="form-group">
            <label>Notes</label>
            <textarea name="notes" class="form-control">{{ old('notes', $employee->notes) }}</textarea>
        </div>
        
    </form>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function(){
    function showRemainingMessage(msg, isError){
        var help = document.getElementById('remaining_help');
        if (!help) return;
        help.textContent = msg;
        help.classList.toggle('text-danger', !!isError);
        help.classList.toggle('text-muted', !isError);
        setTimeout(function(){
            help.textContent = 'Remaining leave days. Must be less than or equal to total leave.';
            help.classList.remove('text-danger');
            help.classList.add('text-muted');
        }, 3000);
    }
    function enforceLeaveBounds(){
        var totalEl = document.getElementById('total_leave');
        var remEl = document.getElementById('remaining_leave');
        if (!totalEl || !remEl) return;
        var total = parseInt(totalEl.value, 10);
        var rem = parseInt(remEl.value, 10);
        if (isNaN(total)) total = 0;
        if (isNaN(rem)) rem = 0;
        if (rem < 0) { remEl.value = 0; showRemainingMessage('Remaining cannot be negative', true); }
        if (rem > total) { remEl.value = total; showRemainingMessage('Remaining cannot exceed total; adjusted to total.', true); }
    }
    var totalInput = document.getElementById('total_leave');
    var remainingInput = document.getElementById('remaining_leave');
    if (totalInput) totalInput.addEventListener('input', enforceLeaveBounds);
    if (remainingInput) remainingInput.addEventListener('input', enforceLeaveBounds);
    var formEl = document.querySelector('form');
    if (formEl) formEl.addEventListener('submit', enforceLeaveBounds);
});
</script>
@endpush
