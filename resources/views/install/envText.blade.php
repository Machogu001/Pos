@extends('layouts.install', ['no_header' => 1])
@section('title', __('ui.pos_installation_check_server'))

@section('content')
<div class="container">
    <div class="row">
        <h1 class="page-header text-center">{{ config('app.name', 'POS') }}</h1>

        <div class="col-md-8 col-md-offset-2">
          @include('install.partials.nav', ['active' => 'app_details'])

          <div class="box box-primary active">
            <!-- /.box-header -->
            <div class="box-body">

              @if(session('error'))
                <div class="alert alert-danger">
                  {{ session('error') }}
                </div>
              @endif

              @if ($errors->any())
                <div class="alert alert-danger">
                  <ul>
                  @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                  @endforeach
                  </ul>
                </div>
              @endif

              <form class="form" method="post" 
                    action="{{route('install.installAlternate')}}" 
                    id="env_details_form">
                  {{ csrf_field() }}

                  <h4 class="install_instuction">{{ __('ui.hey_i_need_your_help') }}</h4>
                  <p class="install_instuction">
                    {{ __('ui.please_create_a_file_with_name') }} <code>.env</code> {{ __('ui.at') }} <strong>{{$envPath}}</strong> {{ __('ui.with') }} <code>{{ __('ui.read_write_permission') }}</code> {{ __('ui.and_paste_the_below_content') }} <br/> {{ __('ui.press_install_after_it') }}
                  </p>
                  <hr/>

                  <div class="col-md-12">
                    <div class="form-group">
                        <textarea rows="25" cols="50">{{$envContent}}</textarea>
                    </div>
                  </div>
                  
                  <div class="col-md-12">
                    <button type="submit" class="btn btn-primary pull-right" id="install_button">{{ __('ui.install') }}</button>
                  </div>

                  <div class="col-md-12 text-center text-danger install_msg hide">
                    <h3>{{ __('ui.installation_in_progress_please_do_not_refresh_go_back_or_close_the_browser') }}</h3>
              </form>
            </div>
          <!-- /.box-body -->
          </div>
        </div>

    </div>
</div>
@endsection

@section('javascript')
  <script type="text/javascript">
    $(document).ready(function(){

      $('form#env_details_form').submit(function(){
        $('button#install_button').attr('disabled', true).text("{{ __('ui.installing') }}");
        $(".install_instuction").addClass('hide');
        $('div.install_msg').removeClass('hide');
        $('textarea').addClass('hide');
        $('.back_button').hide();
      });

    })
  </script>
@endsection