@extends('layouts.app')
@section('title', __('connector::lang.connector'))

@section('content')
@section('css')
<style>
    .token-display { font-family: monospace; word-break: break-all; background: #f5f5f5; padding: 10px; border-radius: 4px; }
</style>
@endsection

<!-- Content Header -->
<section class="content-header no-print">
    <h1>@lang('connector::lang.connector') <small>@lang('connector::lang.api_tokens')</small></h1>
</section>

<!-- Main content -->
<section class="content no-print">
    <div class="row">
        <div class="col-md-12">
            <div class="box box-solid">
                <div class="box-header with-border">
                    <h3 class="box-title">@lang('connector::lang.api_tokens')</h3>
                    <div class="box-tools pull-right">
                        @can('connector.manage_tokens')
                        <button type="button" class="btn btn-primary btn-sm" id="generate_token_btn">
                            <i class="fas fa-key"></i> @lang('connector::lang.generate_token')
                        </button>
                        @endcan
                    </div>
                </div>
                <div class="box-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped" id="tokens_table">
                            <thead>
                                <tr>
                                    <th>@lang('connector::lang.description')</th>
                                    <th>@lang('connector::lang.is_active')</th>
                                    <th>@lang('connector::lang.last_used_at')</th>
                                    <th>@lang('connector::lang.expires_at')</th>
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

<!-- Generate Token Modal -->
<div class="modal fade" id="token_modal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                <h4 class="modal-title">@lang('connector::lang.generate_token')</h4>
            </div>
            <div class="modal-body">
                <div id="token_form_section">
                    <form id="token_form">
                        @csrf
                        <div class="form-group">
                            <label>@lang('connector::lang.description')</label>
                            <input type="text" class="form-control" name="description" id="token_description" placeholder="e.g. Mobile App, POS Terminal">
                        </div>
                        <div class="form-group">
                            <label>@lang('connector::lang.expires_at')</label>
                            <input type="date" class="form-control" name="expires_at" id="token_expires_at">
                            <small class="text-muted">@lang('messages.leave_blank_for_no_expiry')</small>
                        </div>
                    </form>
                </div>
                <div id="token_result_section" style="display:none;">
                    <div class="alert alert-warning">
                        <strong><i class="fas fa-exclamation-triangle"></i></strong>
                        Copy this token now. It will not be shown again.
                    </div>
                    <div class="token-display" id="generated_token_value"></div>
                    <br>
                    <button class="btn btn-sm btn-default" id="copy_token_btn">
                        <i class="fas fa-copy"></i> Copy
                    </button>
                </div>
            </div>
            <div class="modal-footer" id="token_modal_footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.cancel')</button>
                <button type="button" class="btn btn-primary" id="save_token_btn">@lang('connector::lang.generate_token')</button>
            </div>
        </div>
    </div>
</div>

@section('javascript')
<script>
$(document).ready(function() {
    var table = $('#tokens_table').DataTable({
        processing: true,
        serverSide: true,
        ajax: '{{ url("connector/tokens") }}',
        columns: [
            { data: 'description', name: 'description' },
            { data: 'is_active', name: 'is_active' },
            { data: 'last_used_at', name: 'last_used_at' },
            { data: 'expires_at', name: 'expires_at' },
            { data: 'created_at', name: 'created_at' },
            { data: 'action', name: 'action', orderable: false, searchable: false }
        ]
    });

    $('#generate_token_btn').on('click', function() {
        $('#token_form')[0].reset();
        $('#token_form_section').show();
        $('#token_result_section').hide();
        $('#save_token_btn').show();
        $('#token_modal').modal('show');
    });

    $('#save_token_btn').on('click', function() {
        $.post('/connector/tokens', {
            _token: $('input[name="_token"]').val(),
            description: $('#token_description').val(),
            expires_at: $('#token_expires_at').val()
        }, function(result) {
            if (result.success) {
                $('#token_form_section').hide();
                $('#generated_token_value').text(result.token);
                $('#token_result_section').show();
                $('#save_token_btn').hide();
                table.ajax.reload();
            }
        }).fail(function() {
            toastr.error('{{ __("messages.something_went_wrong") }}');
        });
    });

    $('#copy_token_btn').on('click', function() {
        var text = $('#generated_token_value').text();
        navigator.clipboard.writeText(text).then(function() {
            toastr.success('{{ __("connector::lang.token_copied") }}');
        });
    });

    $(document).on('click', '.toggle_token_btn', function() {
        var id = $(this).data('id');
        $.post('/connector/tokens/' + id + '/toggle', {
            _token: $('input[name="_token"]').val()
        }, function(result) {
            if (result.success) {
                table.ajax.reload();
                toastr.success(result.msg);
            }
        });
    });

    $(document).on('click', '.delete_token_btn', function() {
        var id = $(this).data('id');
        swal({
            title: '{{ __("messages.are_you_sure") }}',
            icon: 'warning',
            buttons: true,
            dangerMode: true
        }).then(function(confirmed) {
            if (confirmed) {
                $.ajax({
                    url: '/connector/tokens/' + id,
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
