@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="mb-0">Office Shifts</h2>
        <a href="{{ route('hrm.office_shifts.create') }}" class="btn btn-primary">Add Office Shift</a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Name</th>
                            <th>Company</th>
                            <th>Mon</th>
                            <th>Tue</th>
                            <th>Wed</th>
                            <th>Thu</th>
                            <th>Fri</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($office_shifts_for_view ?? [] as $s)
                            <tr>
                                <td>{{ $s['id'] }}</td>
                                <td>{{ $s['name'] }}</td>
                                <td>{{ $s['company_name'] }}</td>
                                <td>{{ $s['monday_in'] }} - {{ $s['monday_out'] }}</td>
                                <td>{{ $s['tuesday_in'] }} - {{ $s['tuesday_out'] }}</td>
                                <td>{{ $s['wednesday_in'] }} - {{ $s['wednesday_out'] }}</td>
                                <td>{{ $s['thursday_in'] }} - {{ $s['thursday_out'] }}</td>
                                <td>{{ $s['friday_in'] }} - {{ $s['friday_out'] }}</td>
                                <td class="text-end">
                                    <a href="{{ route('hrm.office_shifts.edit', $s['id']) }}" class="btn btn-sm btn-secondary">Edit</a>
                                    <form action="{{ route('hrm.office_shifts.destroy', $s['id']) }}" method="POST" style="display:inline-block">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-danger" onclick="return confirm('Delete?')">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="9" class="text-center text-muted">No office shifts found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
