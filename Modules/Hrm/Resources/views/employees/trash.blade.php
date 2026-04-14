@extends('layouts.app')

@section('title', 'Deleted Employees')

@section('content')
@include('hrm::partials.hrm_page_header', [
    'title' => 'Deleted Employees',
    'subtitle' => 'Restore removed employees or delete them permanently.',
    'actions' => '<a href="'.route('hrm.employees.index').'" class="btn btn-default"><i class="fa fa-arrow-left"></i> Back to Employees</a>'
])

<section class="content">
    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title">Trash</h3>
        </div>
        <div class="box-body table-responsive no-padding">
            <table class="table table-hover table-striped mb-0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Deleted At</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($employees as $e)
                        <tr>
                            <td>{{ $e['id'] }}</td>
                            <td>{{ $e['firstname'] }} {{ $e['lastname'] }}</td>
                            <td>{{ $e['deleted_at'] }}</td>
                            <td class="text-right">
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

        <div class="box-footer clearfix">
            @if(isset($paginator))
                <div class="text-right">
                    {{ $paginator->appends(request()->query())->links() }}
                </div>
            @endif
        </div>
    </div>
</section>

@push('scripts')
<script>
function restore(id) {
    window.hrmConfirm('Restore this employee?', { title: 'Restore Employee', confirmButtonText: 'Restore' }).then(confirmed => {
        if (!confirmed) return;
        fetch("{{ url('hrm/employees') }}" + '/' + id + '/restore', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
        }).then(r => r.json()).then(j => { if (j.success) location.reload(); else window.hrmAlert('Failed'); });
    });
}
function forceDelete(id) {
    window.hrmConfirm('Permanently delete this employee? This cannot be undone.', { title: 'Delete Employee', confirmButtonText: 'Delete' }).then(confirmed => {
        if (!confirmed) return;
        fetch("{{ url('hrm/employees') }}" + '/' + id + '/force-delete', {
            method: 'DELETE',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
        }).then(r => r.json()).then(j => { if (j.success) location.reload(); else window.hrmAlert('Failed'); });
    });
}
</script>
@endpush

@endsection
