@extends('layouts.app')

@section('title', 'Payroll')

@section('content')
@include('hrm::partials.hrm_page_header', [
    'title' => 'Payroll',
    'subtitle' => 'Process salaries, monitor posting status, and review payroll history.',
    'actions' => '<a href="'.route('hrm.payrolls.create').'" class="btn btn-success"><i class="fa fa-plus"></i> Create Payroll</a>'
])

<section class="content">
    <div class="box box-success">
        <div class="box-header with-border">
            <h2 class="box-title h3">Payroll Register</h2>
        </div>
        <div class="box-body">
            <form method="GET" class="form-inline" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap; margin-bottom: 15px;">
                <label for="payroll_company_id" class="sr-only">Company</label>
                <select id="payroll_company_id" name="company_id" class="form-control" aria-label="Company filter">
                    <option value="">All Companies</option>
                    @foreach($companies as $c)
                        <option value="{{ $c->id }}" {{ request('company_id') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                    @endforeach
                </select>
                <label for="payroll_employee_id" class="sr-only">Employee</label>
                <select id="payroll_employee_id" name="employee_id" class="form-control" aria-label="Employee filter">
                    <option value="">All Employees</option>
                    @foreach($employees as $e)
                        @php $label = $e->username ?? trim((($e->firstname ?? '') . ' ' . ($e->lastname ?? ''))); @endphp
                        <option value="{{ $e->id }}" {{ request('employee_id') == $e->id ? 'selected' : '' }}>{{ $label ?: 'Employee #'.$e->id }}</option>
                    @endforeach
                </select>
                <button class="btn btn-default" type="submit">Filter</button>
            </form>

            @if(isset($payrolls) && $payrolls->count())
                <div class="table-responsive">
                    <table class="table table-hover table-striped">
                        <thead>
                            <tr>
                                <th>Payroll No</th>
                                <th>Employee</th>
                                <th>Employer</th>
                                <th>Period</th>
                                <th>Gross</th>
                                <th>Deductions</th>
                                <th>Net</th>
                                <th>Posting</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($payrolls as $p)
                                <tr>
                                    <td><strong>{{ $p->id }}</strong></td>
                                    <td>{{ $p->employee_name ?? ($p->employee_id ?? '-') }}</td>
                                    <td>{{ $p->company_name ?? ($p->company_id ?? '-') }}</td>
                                    <td>{{ $p->period_start }} - {{ $p->period_end }}</td>
                                    <td>{{ number_format($p->gross, 2) }}</td>
                                    <td>{{ number_format($p->deductions, 2) }}</td>
                                    <td><strong>{{ number_format($p->net, 2) }}</strong></td>
                                    <td>
                                        @if(!empty($p->posted_to_accounts))
                                            <span class="label label-success">Posted to accounts</span>
                                        @else
                                            <span class="label label-warning">Pending posting</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route('hrm.payrolls.show', $p->id) }}" class="btn btn-sm btn-info" title="View Payslip"><i class="fa fa-file-text"></i> Payslip</a>
                                        <a href="{{ route('hrm.payrolls.p9', ['employee_id' => $p->employee_id, 'year' => \Carbon\Carbon::parse($p->period_start)->year]) }}" class="btn btn-sm btn-default" title="P9 Tax Certificate"><i class="fa fa-table"></i> P9</a>
                                        <a href="{{ route('hrm.payrolls.edit', $p->id) }}" class="btn btn-sm btn-default"><i class="fa fa-pencil"></i> Edit</a>
                                        <form action="{{ route('hrm.payrolls.destroy', $p->id) }}" method="POST" style="display:inline-block" data-hrm-confirm="Delete this payroll?" data-hrm-confirm-title="Delete Payroll">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-sm btn-danger"><i class="fa fa-trash"></i> Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="alert alert-info mb-0">No payrolls yet.</div>
            @endif

            @if(isset($payrolls) && method_exists($payrolls, 'links'))
                <div class="text-right" style="margin-top: 15px;">{{ $payrolls->links() }}</div>
            @endif
        </div>
    </div>
</section>
@endsection
