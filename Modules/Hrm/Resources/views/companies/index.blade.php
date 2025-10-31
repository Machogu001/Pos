@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="mb-0">Companies</h2>
        <a href="{{ route('hrm.companies.create') }}" class="btn btn-primary">Add Company</a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <p class="text-muted">Manage companies (these are used as the source for the Company dropdowns in HRM).</p>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Country</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($companies as $c)
                            <tr>
                                <td>{{ $c->id }}</td>
                                <td>{{ $c->name }}</td>
                                <td>{{ $c->email }}</td>
                                <td>{{ $c->phone }}</td>
                                <td>{{ $c->country }}</td>
                                <td class="text-end">
                                    <a href="{{ route('hrm.companies.edit', $c->id) }}" class="btn btn-sm btn-secondary">Edit</a>
                                    <form action="{{ route('hrm.companies.destroy', $c->id) }}" method="POST" style="display:inline-block" onsubmit="return confirm('Delete this company?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-danger">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted">No companies found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @if(isset($totalRows) && $totalRows > (int)($perPage ?? 0) && isset($paginator))
        <div class="mt-3">
            {{ $paginator->appends(request()->query())->links() }}
        </div>
    @endif
</div>
@endsection
