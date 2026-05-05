@extends('layouts.app')
@section('title', 'To Do List')

@section('css')
<style>
.todo-priority-high   { border-left: 4px solid #e3342f; }
.todo-priority-medium { border-left: 4px solid #f6993f; }
.todo-priority-low    { border-left: 4px solid #38c172; }
.todo-completed td    { opacity: 0.55; text-decoration: line-through; }
.badge-high   { background-color: #e3342f; color:#fff; }
.badge-medium { background-color: #f6993f; color:#fff; }
.badge-low    { background-color: #38c172; color:#fff; }
</style>
@endsection

@section('content')
<section class="content-header">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">
        <i class="fa fa-check-square-o" style="margin-right:6px;"></i>To Do List
        @if($isSuperAdmin)
            <small class="text-muted" style="font-size:14px; font-weight:400;">&nbsp;— All Businesses</small>
        @elseif($isBusinessAdmin)
            <small class="text-muted" style="font-size:14px; font-weight:400;">&nbsp;— Your Business</small>
        @endif
    </h1>
</section>

<section class="content">
    <div class="row" style="margin-bottom:16px;">
        <div class="col-sm-12">
            <a href="#" data-href="{{ route('essentials.todos.create') }}"
               data-container="#task_modal"
               class="btn-modal tw-dw-btn tw-dw-btn-primary tw-text-white">
                <i class="fa fa-plus"></i> @lang('essentials::lang.add_to_do')
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-sm-12">
            <div class="box box-solid">
                <div class="box-body no-padding">
                    <table class="table table-hover table-striped" id="todos_table">
                        <thead>
                            <tr>
                                <th style="width:40px;">#</th>
                                @if($isSuperAdmin || $isBusinessAdmin)
                                <th>User</th>
                                @endif
                                @if($isSuperAdmin)
                                <th>Business</th>
                                @endif
                                <th>Title</th>
                                <th>Priority</th>
                                <th>Due Date</th>
                                <th>Status</th>
                                <th style="width:120px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($todos as $todo)
                            <tr class="todo-priority-{{ $todo->priority }} @if($todo->is_completed) todo-completed @endif"
                                id="todo_row_{{ $todo->id }}">
                                <td>{{ $loop->iteration }}</td>
                                @if($isSuperAdmin || $isBusinessAdmin)
                                <td>{{ $todo->user ? $todo->user->first_name . ' ' . $todo->user->last_name : '—' }}</td>
                                @endif
                                @if($isSuperAdmin)
                                <td>{{ $todo->business_id }}</td>
                                @endif
                                <td>
                                    <strong>{{ $todo->title }}</strong>
                                    @if($todo->description)
                                        <br><small class="text-muted">{{ Str::limit($todo->description, 80) }}</small>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge badge-{{ $todo->priority }}">
                                        {{ ucfirst($todo->priority) }}
                                    </span>
                                </td>
                                <td>{{ $todo->due_date ? $todo->due_date->format('d M Y') : '—' }}</td>
                                <td>
                                    <label class="todo-complete-toggle" style="cursor:pointer; margin:0;">
                                        <input type="checkbox" class="todo-complete-cb"
                                            data-id="{{ $todo->id }}"
                                            {{ $todo->is_completed ? 'checked' : '' }}
                                            style="margin-right:4px;">
                                        {{ $todo->is_completed ? 'Done' : 'Pending' }}
                                    </label>
                                </td>
                                <td>
                                    <a href="#"
                                       data-href="{{ route('essentials.todos.edit', $todo->id) }}"
                                       data-container="#task_modal"
                                       class="btn-modal btn btn-xs btn-primary"
                                       title="Edit">
                                        <i class="fa fa-edit"></i>
                                    </a>
                                    <button class="btn btn-xs btn-danger todo-delete-btn"
                                        data-id="{{ $todo->id }}"
                                        title="Delete">
                                        <i class="fa fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="{{ $isSuperAdmin ? 8 : ($isBusinessAdmin ? 7 : 6) }}" class="text-center text-muted" style="padding:30px;">
                                    No To Do items found.
                                    <a href="#" data-href="{{ route('essentials.todos.create') }}"
                                       data-container="#task_modal" class="btn-modal">Add one now</a>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

@section('javascript')
<script>
$(document).ready(function() {
    if ($.fn.DataTable) {
        $('#todos_table').DataTable({
            pageLength: 25,
            order: [],
            columnDefs: [{ orderable: false, targets: -1 }]
        });
    }
});

// Toggle complete
$(document).on('change', '.todo-complete-cb', function() {
    var cb    = $(this);
    var id    = cb.data('id');
    var done  = cb.is(':checked');
    var label = cb.closest('label');
    var row   = cb.closest('tr');

    $.ajax({
        url: '/essentials/todos/' + id,
        type: 'POST',
        data: {
            _method: 'PUT',
            _token: $('meta[name="csrf-token"]').attr('content'),
            title: row.find('strong').text(),
            is_completed: done ? 1 : 0
        },
        success: function(r) {
            if (r.success) {
                label.html('<input type="checkbox" class="todo-complete-cb" data-id="' + id + '" ' + (done ? 'checked' : '') + ' style="margin-right:4px;">' + (done ? 'Done' : 'Pending'));
                done ? row.addClass('todo-completed') : row.removeClass('todo-completed');
                toastr.success(r.msg);
            }
        }
    });
});

// Delete
$(document).on('click', '.todo-delete-btn', function() {
    var id  = $(this).data('id');
    var row = $(this).closest('tr');

    Swal.fire({
        title: 'Delete this task?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#e3342f',
        confirmButtonText: 'Yes, delete'
    }).then(function(result) {
        if (result.isConfirmed) {
            $.ajax({
                url: '/essentials/todos/' + id,
                type: 'POST',
                data: { _method: 'DELETE', _token: $('meta[name="csrf-token"]').attr('content') },
                success: function(r) {
                    if (r.success) {
                        row.fadeOut(300, function() { $(this).remove(); });
                        toastr.success(r.msg);
                    }
                }
            });
        }
    });
});
</script>
@endsection
