@extends('layouts.app')

@section('title', 'Attendance')

@section('content')
@include('hrm::partials.hrm_page_header', [
    'title' => 'Attendance',
    'subtitle' => 'Track employee clock-in and clock-out records.',
    'actions' => '<a href="'.route('hrm.attendances.create').'" class="btn btn-primary"><i class="fa fa-plus"></i> Create Attendance</a>'
])

<section class="content">
    <div class="box box-primary">
        <div class="box-header with-border">
            <h2 class="box-title h3">Attendance Register</h2>
            <div class="box-tools pull-right">
                <span class="label label-info">Total records: {{ $totalRows ?? 0 }}</span>
            </div>
        </div>
        <div class="box-body table-responsive">
            <table class="table table-hover table-striped">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Employee</th>
                        <th>Company</th>
                        <th>Clock In</th>
                        <th>Clock Out</th>
                        <th>Total Work</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($attendances as $attendance)
                        <tr>
                            <td>{{ !empty($attendance['date']) ? \Carbon\Carbon::parse($attendance['date'])->format('d m Y') : '-' }}</td>
                            <td>{{ $attendance['employee_username'] ?: '-' }}</td>
                            <td>{{ $attendance['company_name'] ?: '-' }}</td>
                            <td>{{ $attendance['clock_in'] ?: '-' }}</td>
                            <td>{{ $attendance['clock_out'] ?: '-' }}</td>
                            <td>{{ $attendance['total_work'] ?: '-' }}</td>
                            <td class="text-right">
                                <a href="{{ route('hrm.attendances.edit', $attendance['id']) }}" class="btn btn-sm btn-default">Edit</a>
                                <form action="{{ route('hrm.attendances.destroy', $attendance['id']) }}" method="POST" style="display:inline-block" data-hrm-confirm="Delete this attendance?" data-hrm-confirm-title="Delete Attendance">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted">No attendance records found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</section>
@endsection