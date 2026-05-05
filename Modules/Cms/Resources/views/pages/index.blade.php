@extends('layouts.app')
@section('title', __('cms::lang.cms'))

@section('content')
@section('css')
<style>
    .dataTables_wrapper .btn-group-sm > .btn { margin-right: 3px; }
</style>
@endsection

<!-- Content Header -->
<section class="content-header no-print">
    <h1>@lang('cms::lang.cms') <small>@lang('cms::lang.pages')</small></h1>
</section>

<!-- Main content -->
<section class="content no-print">
    <div class="row">
        <div class="col-md-12">
            <div class="box box-solid">
                <div class="box-header with-border">
                    <h3 class="box-title">@lang('cms::lang.pages')</h3>
                    <div class="box-tools pull-right">
                        @can('cms.create_page')
                        <button type="button" class="btn btn-primary btn-sm" id="add_page_btn">
                            <i class="fas fa-plus"></i> @lang('cms::lang.add_page')
                        </button>
                        @endcan
                    </div>
                </div>
                <div class="box-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped" id="pages_table">
                            <thead>
                                <tr>
                                    <th>@lang('messages.title')</th>
                                    <th>@lang('cms::lang.slug')</th>
                                    <th>@lang('messages.status')</th>
                                    <th>@lang('messages.date')</th>
                                    <th>@lang('messages.action')</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Add/Edit Page Modal -->
<div class="modal fade" id="page_modal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                <h4 class="modal-title" id="page_modal_title">@lang('cms::lang.add_page')</h4>
            </div>
            <div class="modal-body">
                <form id="page_form">
                    @csrf
                    <input type="hidden" id="page_id" name="page_id" value="">
                    <div class="form-group">
                        <label>@lang('messages.title') <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="title" id="page_title" required>
                    </div>
                    <div class="form-group">
                        <label>@lang('cms::lang.content')</label>
                        <textarea class="form-control" name="content" id="page_content" rows="10"></textarea>
                    </div>
                    <div class="form-group">
                        <label>@lang('messages.status') <span class="text-danger">*</span></label>
                        <select class="form-control" name="status" id="page_status" required>
                            <option value="draft">@lang('cms::lang.status_draft')</option>
                            <option value="published">@lang('cms::lang.status_published')</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.cancel')</button>
                <button type="button" class="btn btn-primary" id="save_page_btn">@lang('messages.save')</button>
            </div>
        </div>
    </div>
</div>

@section('javascript')
<script>
$(document).ready(function() {
    var table = $('#pages_table').DataTable({
        processing: true,
        serverSide: true,
        ajax: '{{ url("cms/pages") }}',
        columns: [
            { data: 'title', name: 'title' },
            { data: 'slug', name: 'slug' },
            { data: 'status', name: 'status' },
            { data: 'created_at', name: 'created_at' },
            { data: 'action', name: 'action', orderable: false, searchable: false }
        ]
    });

    // Add page
    $('#add_page_btn').on('click', function() {
        $('#page_modal_title').text('{{ __("cms::lang.add_page") }}');
        $('#page_form')[0].reset();
        $('#page_id').val('');
        $('#page_modal').modal('show');
    });

    // Edit page
    $(document).on('click', '.edit_page_btn', function() {
        var id = $(this).data('id');
        $.get('/cms/pages/' + id, function(data) {
            $('#page_modal_title').text('{{ __("cms::lang.edit_page") }}');
            $('#page_id').val(data.id);
            $('#page_title').val(data.title);
            $('#page_content').val(data.content);
            $('#page_status').val(data.status);
            $('#page_modal').modal('show');
        });
    });

    // Save page
    $('#save_page_btn').on('click', function() {
        var id = $('#page_id').val();
        var url = id ? '/cms/pages/' + id : '/cms/pages';
        var method = id ? 'PUT' : 'POST';
        var data = {
            _token: $('input[name="_token"]').val(),
            title: $('#page_title').val(),
            content: $('#page_content').val(),
            status: $('#page_status').val()
        };
        if (method === 'PUT') data._method = 'PUT';

        $.post(url, data, function(result) {
            if (result.success) {
                $('#page_modal').modal('hide');
                table.ajax.reload();
                toastr.success(result.msg);
            }
        }).fail(function(xhr) {
            toastr.error('{{ __("messages.something_went_wrong") }}');
        });
    });

    // Delete page
    $(document).on('click', '.delete_page_btn', function() {
        var id = $(this).data('id');
        swal({
            title: '{{ __("messages.are_you_sure") }}',
            icon: 'warning',
            buttons: true,
            dangerMode: true
        }).then(function(confirmed) {
            if (confirmed) {
                $.ajax({
                    url: '/cms/pages/' + id,
                    type: 'DELETE',
                    data: { _token: $('input[name="_token"]').val() },
                    success: function(result) {
                        if (result.success) {
                            table.ajax.reload();
                            toastr.success(result.msg);
                        }
                    }
                });
            }
        });
    });
});
</script>
@endsection
@endsection
