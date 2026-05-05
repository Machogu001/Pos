<div class="modal-dialog" role="document">
    <div class="modal-content">
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
            <h4 class="modal-title">@lang('messages.edit') To Do</h4>
        </div>
        {!! Form::open(['route' => ['essentials.todos.update', $todo->id], 'method' => 'put', 'id' => 'todo_edit_form']) !!}
        <div class="modal-body">
            <div class="form-group">
                {!! Form::label('title', __('lang_v1.title') . ':') !!}
                {!! Form::text('title', $todo->title, ['class' => 'form-control', 'required']) !!}
            </div>
            <div class="form-group">
                {!! Form::label('description', __('lang_v1.description') . ':') !!}
                {!! Form::textarea('description', $todo->description, ['class' => 'form-control', 'rows' => 3]) !!}
            </div>
            <div class="row">
                <div class="col-sm-6">
                    <div class="form-group">
                        {!! Form::label('due_date', __('lang_v1.due_date') . ':') !!}
                        {!! Form::date('due_date', $todo->due_date ? $todo->due_date->format('Y-m-d') : null, ['class' => 'form-control']) !!}
                    </div>
                </div>
                <div class="col-sm-6">
                    <div class="form-group">
                        {!! Form::label('priority', __('lang_v1.priority') . ':') !!}
                        {!! Form::select('priority', ['low' => 'Low', 'medium' => 'Medium', 'high' => 'High'], $todo->priority, ['class' => 'form-control select2']) !!}
                    </div>
                </div>
            </div>
            <div class="form-group">
                <label>
                    {!! Form::checkbox('is_completed', 1, $todo->is_completed, ['id' => 'is_completed']) !!}
                    Completed
                </label>
            </div>
        </div>
        <div class="modal-footer">
            <button type="submit" class="tw-dw-btn tw-dw-btn-primary tw-text-white">
                @lang('messages.update')
            </button>
            <button type="button" class="tw-dw-btn" data-dismiss="modal">
                @lang('messages.cancel')
            </button>
        </div>
        {!! Form::close() !!}
    </div>
</div>

<script>
$(document).ready(function() {
    $('#todo_edit_form').on('submit', function(e) {
        e.preventDefault();
        var form = $(this);
        $.ajax({
            method: 'PUT',
            url: form.attr('action'),
            data: form.serialize(),
            success: function(result) {
                if (result.success) {
                    $('#task_modal').modal('hide');
                    toastr.success(result.msg);
                } else {
                    toastr.error(result.msg);
                }
            },
            error: function() {
                toastr.error('@lang("messages.something_went_wrong")');
            }
        });
    });
});
</script>
