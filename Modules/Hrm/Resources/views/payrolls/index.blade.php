@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Payrolls</h2>
    <div class="d-flex justify-content-between mb-2">
        <a href="{{ route('hrm.payrolls.create') }}" class="btn btn-primary">Create Payroll</a>
        <form method="GET" class="form-inline" style="display:flex;gap:8px;align-items:center;">
            <select name="company_id" class="form-control form-control-sm">
                <option value="">All Companies</option>
                @foreach($companies as $c)
                    <option value="{{ $c->id }}" {{ request('company_id') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                @endforeach
            </select>
            <select name="employee_id" class="form-control form-control-sm">
                <option value="">All Employees</option>
                @foreach($employees as $e)
                    @php $label = $e->username ?? trim((($e->firstname ?? '') . ' ' . ($e->lastname ?? ''))); @endphp
                    <option value="{{ $e->id }}" {{ request('employee_id') == $e->id ? 'selected' : '' }}>{{ $label ?: 'Employee #'.$e->id }}</option>
                @endforeach
            </select>
            <button class="btn btn-sm btn-outline-secondary" type="submit">Filter</button>
        </form>
    </div>
    <hr />

    @if(isset($payrolls) && $payrolls->count())
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Payroll No</th>
                    <th>Employee</th>
                    <th>Employer</th>
                    <th>Period</th>
                    <th>Gross</th>
                    <th>Deductions</th>
                    <th>Net</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($payrolls as $p)
                    <tr>
                        <td>{{ $p->id }}</td>
                        <td>{{ $p->employee_name ?? ($p->employee_id ?? '-') }}</td>
                        <td>{{ $p->company_name ?? ($p->company_id ?? '-') }}</td>
                        <td>{{ $p->period_start }} - {{ $p->period_end }}</td>
                        <td>{{ number_format($p->gross, 2) }}</td>
                        <td>{{ number_format($p->deductions, 2) }}</td>
                        <td>{{ number_format($p->net, 2) }}</td>
                        <td class="text-end">
                            <a href="{{ route('hrm.payrolls.edit', $p->id) }}" class="btn btn-sm btn-secondary">Edit</a>
                            <form action="{{ route('hrm.payrolls.destroy', $p->id) }}" method="POST" style="display:inline-block" onsubmit="return confirm('Delete this payroll?')">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p>No payrolls yet.</p>
    @endif

    @if(isset($payrolls) && method_exists($payrolls, 'links'))
        <div class="mt-3">{{ $payrolls->links() }}</div>
    @endif
</div>
@endsection
