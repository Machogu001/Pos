@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4 class="mb-0">Deleted Employees</h4>
            <a href="{{ route('hrm.employees.index') }}" class="btn btn-secondary">Back to Employees</a>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Deleted At</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($employees as $e)
                            <tr>
                                <td>{{ $e['id'] }}</td>
                                <td>{{ $e['firstname'] }} {{ $e['lastname'] }}</td>
                                <td>{{ $e['deleted_at'] }}</td>
                                <td class="text-end">
                                    <button class="btn btn-sm btn-success" onclick="restore({{ $e['id'] }})">Restore</button>
                                    <button class="btn btn-sm btn-danger" onclick="forceDelete({{ $e['id'] }})">Delete Permanently</button>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted">No deleted employees.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                @if(isset($paginator))
                    {{ $paginator->appends(request()->query())->links() }}
                @endif
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function restore(id) {
    if (!confirm('Restore this employee?')) return;
    fetch("{{ url('hrm/employees') }}" + '/' + id + '/restore', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
    }).then(r => r.json()).then(j => { if (j.success) location.reload(); else alert('Failed') });
}
function forceDelete(id) {
    if (!confirm('Permanently delete this employee? This cannot be undone.')) return;
    fetch("{{ url('hrm/employees') }}" + '/' + id + '/force-delete', {
        method: 'DELETE',
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
    }).then(r => r.json()).then(j => { if (j.success) location.reload(); else alert('Failed') });
}
</script>
@endpush

@endsection
