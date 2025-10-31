@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="mb-0">Designations</h2>
        <a href="{{ route('hrm.designations.create') }}" class="btn btn-primary">Add Designation</a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <p class="text-muted">List of designations and their departments/companies.</p>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Designation</th>
                            <th>Company</th>
                            <th>Department</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($designations_for_view ?? [] as $d)
                            <tr>
                                <td>{{ $d['id'] }}</td>
                                <td>{{ $d['designation'] }}</td>
                                <td>{{ $d['company_name'] }}</td>
                                <td>{{ $d['department_name'] }}</td>
                                <td class="text-end">
                                    <a href="{{ route('hrm.designations.edit', $d['id']) }}" class="btn btn-sm btn-secondary">Edit</a>
                                    <form action="{{ route('hrm.designations.destroy', $d['id']) }}" method="POST" style="display:inline-block">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-danger" onclick="return confirm('Delete?')">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted">No designations found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
