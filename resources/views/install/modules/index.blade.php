@extends('layouts.app')
@section('title', __('lang_v1.manage_modules'))

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">
        @lang('lang_v1.manage_modules')
        <small class="tw-text-sm md:tw-text-base tw-text-gray-700 tw-font-semibold">Only superadmin can access manage modules</small>
    </h1>
    <!-- <ol class="breadcrumb">
        <li><a href="#"><i class="fa fa-dashboard"></i> Level</a></li>
        <li class="active">Here</li>
    </ol> -->
    <button class="tw-dw-btn tw-dw-btn-primary tw-text-white tw-dw-btn-sm upload_module_btn tw-mt-4">
        <i class="fas fa-upload"></i>
        @lang('lang_v1.upload_module')
    </button>

    <a class="tw-dw-btn tw-dw-btn-primary tw-text-white tw-dw-btn-sm pull-right tw-mt-4" href="{{action([\App\Http\Controllers\Install\ModulesController::class, 'regenerate'])}}">
        <i class="fas fa-tools"></i>
        Regenerate @show_tooltip("<br/>1. Regenerate/publish modules css/js to fix not found issue. <br/> 2. Publish api module oauth files")
    </a>
</section>

<!-- Main content -->
<section class="content">
<div class="row">
    <div class="col-md-12 form_col" style="display: none;">
        @component('components.widget')
            {!! Form::open(['url' => action([\App\Http\Controllers\Install\ModulesController::class, 'uploadModule']), 'id' => 'upload_module_form','files' => true, 'style' => 'display:none']) !!}
                <div class="row">
                    <div class="col-md-5">
                        <div class="form-group">
                            {!! Form::label('module', __('lang_v1.upload_module') . ":*") !!}

                            {!! Form::file('module', ['required', 'accept' => 'application/zip']) !!}
                            <p class="help-block">
                                @lang("lang_v1.pls_upload_valid_zip_file")
                            </p>
                        </div>
                    </div>
                    <div class="clearfix"></div>
                    <div class="col-sm-4">
                        <button type="submit" class="tw-dw-btn tw-dw-btn-primary tw-text-white tw-dw-btn-sm">
                            @lang('lang_v1.upload')
                        </button>
                        &nbsp;
                        <button type="button" class="tw-dw-btn tw-dw-btn-error tw-text-white tw-dw-btn-sm cancel_upload_btn">
                            @lang('messages.cancel')
                        </button>
                    </div>
                </div>
            {!! Form::close() !!}
        @endcomponent()
    </div>
    <div class="col-md-12">
    @component('components.widget')
        <table class="table">
            <tr class="success">
                <th class="col-md-1">#</th>
                <th class="col-md-4">@lang('lang_v1.modules')</th>
                <th class="col-md-7">@lang('lang_v1.description')</th>
            </tr>
            @foreach($modules as $module)

                <tr>
                    <td>
                        {{$loop->iteration}}
                    </td>
                    <td>
                        <strong>{{$module['name']}}</strong> <br/>
                        @if(!$module['is_installed'])
                            <a class="tw-dw-btn tw-dw-btn-xs tw-dw-btn-outline  tw-dw-btn-accent" 
                            @if($is_demo)
                                href="#"
                                title="@lang('lang_v1.disabled_in_demo')"
                                disabled
                            @else
                                href="{{$module['install_link']}}"
                            @endif
                            > @lang('lang_v1.install')</a>
                        @else
                            <a class="btn btn-warning btn-xs uninstall-module-btn"
                                @if($is_demo)
                                    href="#"
                                    disabled
                                    title="@lang('lang_v1.disabled_in_demo')"
                                @else
                                    href="{{$module['uninstall_link']}}"
                                @endif
                                data-name="{{$module['name']}}"
                            >@lang('lang_v1.uninstall')
                            </a>

                            {{-- Commented Activate/Deactivate
                            @if($module['active'] == 1)
                                <form 
                                    action="{{action([\App\Http\Controllers\Install\ModulesController::class, 'update'], ['module_name' => $module['name']])}}" 
                                    style="display: inline;" 
                                    method="post">
                                    @method('PUT')
                                    @csrf
                                    <input type="hidden" name="action_type" value="deactivate">
                                    <button class="btn btn-warning btn-xs">Deactivate</button>
                                </form>
                            @else
                                <form action="{{action([\App\Http\Controllers\Install\ModulesController::class, 'update'], ['module_name' => $module['name']])}}" 
                                    style="display: inline;" 
                                    method="post"
                                >
                                    @method('PUT')
                                    @csrf
                                    <input type="hidden" name="action_type" value="activate">
                                    <button class="tw-dw-btn tw-dw-btn-xs tw-dw-btn-outline  tw-dw-btn-accent">Activate</button>
                                </form>
                            @endif
                            --}}
                        @endif

                        <form 
                            action="{{action([\App\Http\Controllers\Install\ModulesController::class, 'destroy'], ['module_name' => $module['name']])}}"
                                style="display: inline;" 
                                method="post"
                                class="delete-module-form"
                            >
                                @method('DELETE')
                                @csrf
                                <button type="button" class="tw-dw-btn tw-dw-btn-xs tw-dw-btn-outline  tw-dw-btn-error delete-module-btn"
                                    @if($is_demo)
                                    disabled="disabled" 
                                    title="@lang('lang_v1.disabled_in_demo')"
                                    @endif
                                >
                                @lang('messages.delete')</button>
                            </form>
                    </td>

                    <td>
                        {{$module['description']}} <br/>
                        @isset($module['version'])
                            <small class="label bg-gray">@lang('lang_v1.version') {{$module['version']['installed_version']}}</small>
                        @endisset

                        @if(!empty($module['version']) && $module['version']['is_update_available'])
                            <div class="alert alert-warning mt-5">
                                <i class="fas fa-sync"></i> @lang('lang_v1.module_new_version', ['module' => $module['name'], 'link' => $module['update_link']]) 
                            </div>
                        @endif
                    </td>
                </tr>
            @endforeach

            <tr>
                <td colspan="3">&nbsp;</td>
            </tr>
            @php
                $mods = unserialize($mods);
            @endphp

            @foreach($mods as $mod)
                @if(!isset($modules[$mod['n']]))
                    <tr>
                        <td><i class="fas fa-hand-point-right fa-2x"></i></td>
                        <td>
                            <strong>{{$mod['dn']}}</strong> <br/>
                            <button onclick="window.open('{{$mod['u']}}', '_blank')" 
                            class="tw-dw-btn tw-dw-btn-xs tw-dw-btn-outline tw-dw-btn-accent"><i class="fas fa-money-bill"></i> Buy</button>
                        </td>
                        <td>
                            {{$mod['d']}}
                        </td>
                    </tr>
                @endif
            @endforeach
        </table>
        @endcomponent()
    </div>
</div>
</section>
@endsection
@section('css')
<link rel="stylesheet" href="{{ asset('css/sweetalert2.min.css') }}">
@endsection
@section('javascript')
<script src="{{ asset('js/sweetalert2.min.js') }}"></script>
<script type="text/javascript">
    // Normalise export name — UMD bundle sets window.Sweetalert2, not window.Swal
    if (typeof Sweetalert2 !== 'undefined' && typeof Swal === 'undefined') { window.Swal = Sweetalert2; }
    //show a hidden form on upload_module_btn click
    $(document).on('click', '.upload_module_btn', function(){
        $(".form_col,form#upload_module_form").fadeToggle();
    });

    //hide form on cancel_upload_btn click
    $(document).on('click', '.cancel_upload_btn', function(){
        $("form#upload_module_form")[0].reset();
        $(".form_col,form#upload_module_form").fadeOut();
    });

    // Uninstall confirmation
    $(document).on('click', '.uninstall-module-btn', function(e){
        e.preventDefault();
        var href = $(this).attr('href');
        var name = $(this).data('name');
        if (!href || href === '#') return;
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Uninstall ' + name + '?',
                text: 'The module will be uninstalled but all data will be kept.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#f59e0b',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Yes, uninstall',
                cancelButtonText: 'Cancel'
            }).then(function(result){
                if (result.isConfirmed) {
                    window.location.href = href;
                }
            });
        } else {
            if (confirm('Uninstall ' + name + '? The module will be uninstalled but data will be kept.')) {
                window.location.href = href;
            }
        }
    });

    // Delete module confirmation
    $(document).on('click', '.delete-module-btn', function(e){
        e.preventDefault();
        var $form = $(this).closest('form.delete-module-form');
        var name = $form.closest('td').find('strong').first().text().trim();
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Delete ' + name + ' module?',
                text: 'The module code will be permanently deleted. Data will not be deleted.',
                icon: 'error',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Yes, delete it',
                cancelButtonText: 'Cancel'
            }).then(function(result){
                if (result.isConfirmed) {
                    $form.submit();
                }
            });
        } else {
            if (confirm('Delete ' + name + ' module code? Data will not be deleted.')) {
                $form.submit();
            }
        }
    });

</script>
@endsection