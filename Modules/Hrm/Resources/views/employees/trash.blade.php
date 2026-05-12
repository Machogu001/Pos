@extends('layouts.app')

@section('title', __('ui.deleted_employees'))

@section('content')
@include('hrm::partials.hrm_page_header', [
    'title' => __('ui.deleted_employees'),
    'subtitle' => __('ui.restore_removed_employees_or_delete_them_permanently'),
    'actions' => '<a href="'.route('hrm.employees.index').'" class="btn btn-default"><i class="fa fa-arrow-left"></i> '. __('ui.back_to_employees') .'</a>'
])

<section class="content">
    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title">{{ __('ui.trash') }}</h3>
        </div>
        <div class="box-body table-responsive no-padding">
            <table class="table table-hover table-striped mb-0">
                <thead>
                    <tr>
                        <th>{{ __('ui.id') }}</th>
                        <th>{{ __('ui.name') }}</th>
                        <th>{{ __('ui.deleted_at') }}</th>
                        <th class="text-right">{{ __('ui.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($employees as $e)
                        <tr>
                            <td>{{ $e['id'] }}</td>
                            <td>{{ $e['firstname'] }} {{ $e['lastname'] }}</td>
                            <td>{{ $e['deleted_at'] }}</td>
                            <td class="text-right">
                                <button class="btn btn-sm btn-success" onclick="restore({{ $e['id'] }})">{{ __('ui.restore') }}</button>
                                <button class="btn btn-sm btn-danger" onclick="forceDelete({{ $e['id'] }})">{{ __('ui.delete_permanently') }}</button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-muted">{{ __('ui.no_deleted_employees') }}</td></tr>
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
    window.hrmConfirm("{{ __('ui.restore_this_employee') }}", { title: "{{ __('ui.restore_employee') }}", confirmButtonText: "{{ __('ui.restore') }}" }).then(confirmed => {
        if (!confirmed) return;
        fetch("{{ url('hrm/employees') }}" + '/' + id + '/restore', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
        }).then(r => r.json()).then(j => { if (j.success) location.reload(); else window.hrmAlert("{{ __('ui.failed') }}"); });
    });
}
function forceDelete(id) {
    window.hrmConfirm("{{ __('ui.permanently_delete_this_employee_this_cannot_be_undone') }}", { title: "{{ __('ui.delete_employee') }}", confirmButtonText: "{{ __('ui.delete') }}" }).then(confirmed => {
        if (!confirmed) return;
        fetch("{{ url('hrm/employees') }}" + '/' + id + '/force-delete', {
            method: 'DELETE',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
        }).then(r => r.json()).then(j => { if (j.success) location.reload(); else window.hrmAlert("{{ __('ui.failed') }}"); });
    });
}
</script>
@endpush

@endsection
