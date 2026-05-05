<div class="modal-dialog" role="document">
    <div class="modal-content">
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
            <h4 class="modal-title">@lang('essentials::lang.add_to_do')</h4>
        </div>
        {!! Form::open(['route' => 'essentials.todos.store', 'method' => 'post', 'id' => 'todo_add_form']) !!}
        <div class="modal-body">
            <div class="form-group">
                {!! Form::label('title', 'Title:') !!}
                {!! Form::text('title', null, ['class' => 'form-control', 'placeholder' => 'Title', 'required']) !!}
            </div>
            <div class="form-group">
                {!! Form::label('description', __('lang_v1.description') . ':') !!}
                {!! Form::textarea('description', null, ['class' => 'form-control', 'rows' => 3, 'placeholder' => 'Description']) !!}
            </div>
            <div class="row">
                <div class="col-sm-6">
                    <div class="form-group">
                        {!! Form::label('due_date', __('lang_v1.due_date') . ':') !!}
                        {!! Form::date('due_date', null, ['class' => 'form-control']) !!}
                    </div>
                </div>
                <div class="col-sm-6">
                    <div class="form-group">
                        {!! Form::label('priority', __('lang_v1.priority') . ':') !!}
                        {!! Form::select('priority', ['low' => 'Low', 'medium' => 'Medium', 'high' => 'High'], 'medium', ['class' => 'form-control select2']) !!}
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="submit" class="tw-dw-btn tw-dw-btn-primary tw-text-white">
                @lang('messages.save')
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
    $('#todo_add_form').on('submit', function(e) {
        e.preventDefault();
        var form = $(this);
        $.ajax({
            method: 'POST',
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
            error: function(xhr) {
                toastr.error('@lang("messages.something_went_wrong")');
            }
        });
    });
});
</script>
