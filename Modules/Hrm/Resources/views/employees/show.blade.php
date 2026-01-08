@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Employee: {{ $employee->firstname }} {{ $employee->lastname }}</h2>

    <p><strong>Company:</strong> {{ optional($employee->company)->name }}</p>
    <p><strong>Department:</strong> {{ optional($employee->department)->department }}</p>
    <p><strong>Designation:</strong> {{ optional($employee->designation)->designation }}</p>
    <p><strong>Total annual leave:</strong> {{ $employee->total_leave ?? config('hrm.default_annual_leave', 21) }} days</p>
    <p><strong>Remaining leave:</strong> {{ $employee->remaining_leave ?? ($employee->total_leave ?? config('hrm.default_annual_leave', 21)) }} days</p>
    <p><strong>Phone:</strong> {{ $employee->phone }}</p>
    <p><strong>Email:</strong> {{ $employee->email }}</p>
    <p><strong>Suspended:</strong> {{ $employee->suspended ? 'Yes' : 'No' }}</p>

    <form method="POST" action="{{ route('hrm.employees.suspend', $employee->id) }}" style="display:inline-block">
        @csrf
        <input type="hidden" name="action" value="{{ $employee->suspended ? 'unsuspend' : 'suspend' }}" />
        <button class="btn btn-{{ $employee->suspended ? 'success' : 'warning' }}">{{ $employee->suspended ? 'Unsuspend Payroll' : 'Suspend Payroll' }}</button>
    </form>

    <hr />
    <h4>Deductions</h4>
    <form method="POST" action="{{ route('hrm.employees.deductions.store', $employee->id) }}">
        @csrf
        <div class="mb-2">
            <label>Amount</label>
            <input name="amount" class="form-control" required />
        </div>
        <div class="mb-2">
            <label>Type</label>
            <input name="type" class="form-control" />
        </div>
        <div class="mb-2">
            <label>Reason</label>
            <textarea name="reason" class="form-control"></textarea>
        </div>
        <button class="btn btn-primary">Add Deduction</button>
    </form>

    <table class="table mt-3">
        <thead><tr><th>Amount</th><th>Type</th><th>Reason</th><th>Date</th><th>Action</th></tr></thead>
        <tbody>
            @foreach($deductions as $d)
                <tr>
                    <td>{{ $d->amount }}</td>
                    <td>{{ $d->type }}</td>
                    <td>{{ $d->reason }}</td>
                    <td>{{ $d->created_at }}</td>
                    <td>
                        <form method="POST" action="{{ route('hrm.employees.deductions.destroy', [$employee->id, $d->id]) }}">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-sm btn-danger">Delete</button>
                        </form>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <a href="{{ route('hrm.employees.index') }}" class="btn btn-secondary">Back</a>
</div>
@endsection
