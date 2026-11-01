@extends('layouts.app')

@section('title', __('aiassistance::lang.aiassistance'))

@section('content')

@include('aiassistance::layouts.nav')
<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>@lang('aiassistance::lang.history')
        <small>@lang('aiassistance::lang.history_generation')</small>
    </h1>
</section>

<!-- Main content -->
<section class="content">


    <div class="row">

        <div class="col-md-12">
            @component('components.filters', ['title' => __('report.filters')])
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('tool_type', __('aiassistance::lang.tool') . ':') !!}
                    {!! Form::select('tool_type', $tools, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('lang_v1.all')]); !!}
                </div>
            </div>

            @endcomponent
        </div>

        <div class="col-sm-12">
            @component('components.widget', ['class' => 'box-primary'])
            <div class="row" style="margin-bottom: 10px;">
                <div class="col-sm-12">
                    <button type="button" class="btn btn-danger btn-sm" id="delete-selected-history">
                        <i class="fa fa-trash"></i> @lang('lang_v1.delete_selected')
                    </button>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-bordered table-striped" id="ai_history">
                    <thead>
                        <tr>
                            <th style="width: 30px;"><input type="checkbox" id="select-all-history" title="@lang('aiassistance::lang.select_all')"></th>
                            <th class="col-md-3">@lang( 'aiassistance::lang.input' )</th>
                            <th class="col-md-5">@lang( 'aiassistance::lang.output' )</th>
                            <th class="col-md-2">@lang( 'aiassistance::lang.tool' )</th>
                            <th>@lang( 'lang_v1.added_by' )</th>
                            <th class="col-md-2">@lang('lang_v1.created_at')</th>
                        </tr>
                    </thead>
                </table>
            </div>
            @endcomponent
        </div>
    </div>
</section>
<!-- /.content -->

@endsection

@section('javascript')
<script>
    $(document).ready(function() {

        ai_history = $('#ai_history').DataTable({
            processing: true,
            serverSide: true,

            ajax: {
                url: '/aiassistance/history',
                data: function(d) {
                    d.tool_type = $('#tool_type').val();
                },
            },
            order: [[5, 'desc']],
            columns: [{
                    data: 'checkbox',
                    name: 'checkbox',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'input_data',
                    name: 'input_data'
                },
                {
                    data: 'output_data',
                    name: 'output_data'
                },
                {
                    data: 'tool_type',
                    name: 'tool_type'
                },
                {
                    data: 'added_by',
                    name: 'u.first_name',
                },
                {
                    data: 'created_at',
                    name: 'created_at'
                }
            ],

        });

        $(document).on('change', '#tool_type', function(e) {
            ai_history.ajax.reload();
        });

        //Select all rows (current page)
        $(document).on('change', '#select-all-history', function() {
            $('#ai_history tbody .history-select').prop('checked', $(this).is(':checked'));
        });

        //Uncheck the select-all box whenever the table redraws (page/sort/search)
        $('#ai_history').on('draw.dt', function() {
            $('#select-all-history').prop('checked', false);
        });

        //Delete selected rows
        $(document).on('click', '#delete-selected-history', function(e) {
            e.preventDefault();
            var ids = $('#ai_history .history-select:checked').map(function() {
                return $(this).val();
            }).get();

            if (ids.length === 0) {
                swal('@lang('lang_v1.no_row_selected')');
                return;
            }

            swal({
                title: LANG.sure,
                icon: "warning",
                buttons: true,
                dangerMode: true,
            }).then((willDelete) => {
                if (willDelete) {
                    $.ajax({
                        method: "DELETE",
                        url: '/aiassistance/history',
                        data: { ids: ids },
                        dataType: "json",
                        success: function(result) {
                            if (result.success == true) {
                                toastr.success(result.msg);
                                $('#select-all-history').prop('checked', false);
                                ai_history.ajax.reload();
                            } else {
                                toastr.error(result.msg);
                            }
                        }
                    });
                }
            });
        });

    });
</script>
@endsection