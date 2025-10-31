@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="mb-0">Departments</h2>
        <a href="{{ route('hrm.departments.create') }}" class="btn btn-primary">Create Department</a>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Department</th>
                            <th>Company</th>
                            <th>Head</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($departments as $dept)
                            <tr>
                                <td>{{ $dept->id }}</td>
                                <td>{{ $dept->department ?? $dept->name ?? '-' }}</td>
                                <td>{{ $dept->company_name ?? ($dept->company->name ?? '-') }}</td>
                                <td>{{ $dept->employee_head ?? ($dept->employee->username ?? '-') }}</td>
                                <td class="text-end">
                                    <a href="{{ route('hrm.departments.edit', $dept->id) }}" class="btn btn-sm btn-secondary">Edit</a>
                                    <form action="{{ route('hrm.departments.destroy', $dept->id) }}" method="POST" style="display:inline-block">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-danger" onclick="return confirm('Delete?')">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted">No departments found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
