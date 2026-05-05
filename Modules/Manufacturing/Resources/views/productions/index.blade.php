@extends('layouts.app')
@section('title', __('manufacturing::lang.manufacturing'))

@section('content')
@section('css')
<style>
    .dataTables_wrapper .btn-group-sm > .btn { margin-right: 3px; }
</style>
@endsection

<!-- Content Header -->
<section class="content-header no-print">
    <h1>@lang('manufacturing::lang.manufacturing') <small>@lang('manufacturing::lang.productions')</small></h1>
</section>

<!-- Main content -->
<section class="content no-print">
    <div class="row">
        <div class="col-md-12">
            <div class="box box-solid">
                <div class="box-header with-border">
                    <h3 class="box-title">@lang('manufacturing::lang.productions')</h3>
                    <div class="box-tools pull-right">
                        @can('manufacturing.create_production')
                        <button type="button" class="btn btn-primary btn-sm" id="add_production_btn">
                            <i class="fas fa-plus"></i> @lang('manufacturing::lang.create_production')
                        </button>
                        @endcan
                    </div>
                </div>
                <div class="box-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped" id="productions_table">
                            <thead>
                                <tr>
                                    <th>@lang('manufacturing::lang.finished_product')</th>
                                    <th>@lang('manufacturing::lang.quantity_produced')</th>
                                    <th>@lang('messages.status')</th>
                                    <th>@lang('messages.note')</th>
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

<!-- Add/Edit Production Modal -->
<div class="modal fade" id="production_modal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                <h4 class="modal-title" id="production_modal_title">@lang('manufacturing::lang.create_production')</h4>
            </div>
            <div class="modal-body">
                <form id="production_form">
                    @csrf
                    <input type="hidden" id="production_id" name="production_id" value="">
                    <div class="form-group">
                        <label>@lang('manufacturing::lang.recipe') <span class="text-danger">*</span></label>
                        <select class="form-control" name="recipe_id" id="production_recipe_id" required>
                            <option value="">-- @lang('messages.select') --</option>
                            @foreach($recipes as $recipe)
                            <option value="{{ $recipe['id'] }}">{{ $recipe['name'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>@lang('manufacturing::lang.quantity_produced') <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" name="quantity_produced" id="production_qty" step="0.0001" min="0.0001" required>
                    </div>
                    @if($locations->count())
                    <div class="form-group">
                        <label>@lang('business.business_location')</label>
                        <select class="form-control" name="location_id" id="production_location">
                            <option value="">-- @lang('messages.select') --</option>
                            @foreach($locations as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    @endif
                    <div class="form-group">
                        <label>@lang('messages.status') <span class="text-danger">*</span></label>
                        <select class="form-control" name="status" id="production_status" required>
                            <option value="pending">@lang('manufacturing::lang.status_pending')</option>
                            <option value="in_progress">@lang('manufacturing::lang.status_in_progress')</option>
                            <option value="completed">@lang('manufacturing::lang.status_completed')</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>@lang('messages.note')</label>
                        <textarea class="form-control" name="notes" id="production_notes" rows="3"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.cancel')</button>
                <button type="button" class="btn btn-primary" id="save_production_btn">@lang('messages.save')</button>
            </div>
        </div>
    </div>
</div>

@section('javascript')
<script>
$(document).ready(function() {
    var table = $('#productions_table').DataTable({
        processing: true,
        serverSide: true,
        ajax: '{{ url("manufacturing/productions") }}',
        columns: [
            { data: 'recipe_name', name: 'recipe_name', orderable: false },
            { data: 'quantity_produced', name: 'quantity_produced' },
            { data: 'status', name: 'status' },
            { data: 'notes', name: 'notes' },
            { data: 'created_at', name: 'created_at' },
            { data: 'action', name: 'action', orderable: false, searchable: false }
        ]
    });

    $('#add_production_btn').on('click', function() {
        $('#production_modal_title').text('{{ __("manufacturing::lang.create_production") }}');
        $('#production_form')[0].reset();
        $('#production_id').val('');
        $('#production_modal').modal('show');
    });

    $(document).on('click', '.edit_production_btn', function() {
        var id = $(this).data('id');
        $.get('/manufacturing/productions/' + id, function(data) {
            $('#production_modal_title').text('{{ __("manufacturing::lang.create_production") }}');
            $('#production_id').val(data.id);
            $('#production_recipe_id').val(data.recipe_id);
            $('#production_qty').val(data.quantity_produced);
            $('#production_status').val(data.status);
            $('#production_notes').val(data.notes);
            $('#production_modal').modal('show');
        });
    });

    $('#save_production_btn').on('click', function() {
        var id = $('#production_id').val();
        var url = id ? '/manufacturing/productions/' + id : '/manufacturing/productions';
        var data = {
            _token: $('input[name="_token"]').val(),
            recipe_id: $('#production_recipe_id').val(),
            quantity_produced: $('#production_qty').val(),
            status: $('#production_status').val(),
            notes: $('#production_notes').val(),
            location_id: $('#production_location').val() || ''
        };
        if (id) data._method = 'PUT';

        $.post(url, data, function(result) {
            if (result.success) {
                $('#production_modal').modal('hide');
                table.ajax.reload();
                toastr.success(result.msg);
            }
        }).fail(function() {
            toastr.error('{{ __("messages.something_went_wrong") }}');
        });
    });

    $(document).on('click', '.delete_production_btn', function() {
        var id = $(this).data('id');
        swal({
            title: '{{ __("messages.are_you_sure") }}',
            icon: 'warning',
            buttons: true,
            dangerMode: true
        }).then(function(confirmed) {
            if (confirmed) {
                $.ajax({
                    url: '/manufacturing/productions/' + id,
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
