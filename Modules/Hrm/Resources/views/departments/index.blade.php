@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Departments</h2>
    <a href="{{ route('hrm.departments.create') }}" class="btn btn-primary">Create Department</a>
    <table class="table mt-3">
        <thead>
            <tr><th>Name</th><th>Description</th><th>Actions</th></tr>
        </thead>
        <tbody>
            @foreach($departments as $dept)
                <tr>
                    <td>{{ $dept->name }}</td>
                    <td>{{ $dept->description }}</td>
                    <td>
                        <a href="{{ route('hrm.departments.edit', $dept->id) }}" class="btn btn-sm btn-secondary">Edit</a>
                        <form action="{{ route('hrm.departments.destroy', $dept->id) }}" method="POST" style="display:inline-block">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-sm btn-danger" onclick="return confirm('Delete?')">Delete</button>
                        </form>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
