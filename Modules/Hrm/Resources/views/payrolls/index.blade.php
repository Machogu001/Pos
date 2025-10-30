@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Payrolls</h2>
    <a href="{{ route('hrm.payrolls.create') }}" class="btn btn-primary">Create Payroll</a>
    <hr />
    @if(!empty($payrolls) && count($payrolls))
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Employee</th>
                    <th>Period</th>
                    <th>Gross</th>
                    <th>Deductions</th>
                    <th>Net</th>
                </tr>
            </thead>
            <tbody>
                @foreach($payrolls as $p)
                    <tr>
                        <td>{{ $p->id }}</td>
                        <td>{{ $p->employee_id }}</td>
                        <td>{{ $p->period_start }} - {{ $p->period_end }}</td>
                        <td>{{ $p->gross }}</td>
                        <td>{{ $p->deductions }}</td>
                        <td>{{ $p->net }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p>No payrolls yet.</p>
    @endif
</div>
@endsection
