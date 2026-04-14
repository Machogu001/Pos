@extends('layouts.app')

@section('title', 'Employee Profile')

@section('content')
@include('hrm::partials.hrm_page_header', [
    'title' => 'Employee Profile',
    'subtitle' => trim(($employee->firstname ?? '').' '.($employee->lastname ?? '')),
    'actions' => '<a href="'.route('hrm.employees.index').'" class="btn btn-default"><i class="fa fa-arrow-left"></i> Back to Employees</a>'
])

<section class="content">
    <div class="row">
        <div class="col-md-4">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">Profile Summary</h3>
                </div>
                <div class="box-body">
                    <p><strong>Company:</strong> {{ optional($employee->company)->name }}</p>
                    <p><strong>Department:</strong> {{ optional($employee->department)->department }}</p>
                    <p><strong>Designation:</strong> {{ optional($employee->designation)->designation }}</p>
                    <p><strong>Total annual leave:</strong> {{ $employee->total_leave ?? config('hrm.default_annual_leave', 21) }} days</p>
                    <p><strong>Remaining leave:</strong> {{ $employee->remaining_leave ?? ($employee->total_leave ?? config('hrm.default_annual_leave', 21)) }} days</p>
                    <p><strong>Phone:</strong> {{ $employee->phone }}</p>
                    <p><strong>Email:</strong> {{ $employee->email }}</p>
                    <p><strong>Suspended:</strong> {{ $employee->suspended ? 'Yes' : 'No' }}</p>

                    <form method="POST" action="{{ route('hrm.employees.suspend', $employee->id) }}">
                        @csrf
                        <input type="hidden" name="action" value="{{ $employee->suspended ? 'unsuspend' : 'suspend' }}" />
                        <button class="btn btn-{{ $employee->suspended ? 'success' : 'warning' }} btn-block">{{ $employee->suspended ? 'Unsuspend Payroll' : 'Suspend Payroll' }}</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">Deductions</h3>
                </div>
                <div class="box-body">
                    <form method="POST" action="{{ route('hrm.employees.deductions.store', $employee->id) }}">
                        @csrf
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Amount</label>
                                    <input name="amount" class="form-control" required />
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Type</label>
                                    <input name="type" class="form-control" />
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Reason</label>
                                    <input name="reason" class="form-control" />
                                </div>
                            </div>
                        </div>
                        <button class="btn btn-primary">Add Deduction</button>
                    </form>
                </div>

                <div class="box-body table-responsive no-padding">
                    <table class="table table-hover table-striped mb-0">
                        <thead><tr><th>Amount</th><th>Type</th><th>Reason</th><th>Date</th><th class="text-right">Action</th></tr></thead>
                        <tbody>
                            @forelse($deductions as $d)
                                <tr>
                                    <td>{{ $d->amount }}</td>
                                    <td>{{ $d->type }}</td>
                                    <td>{{ $d->reason }}</td>
                                    <td>{{ $d->created_at }}</td>
                                    <td class="text-right">
                                        <form method="POST" action="{{ route('hrm.employees.deductions.destroy', [$employee->id, $d->id]) }}" style="display:inline-block">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-sm btn-danger">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-muted">No deductions found.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
