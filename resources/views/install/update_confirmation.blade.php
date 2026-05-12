@extends('layouts.install', ['no_header' => 1])
@section('title', __('ui.pos_installation_update'))

@section('content')
<div class="container">
    <div class="row">

        <div class="col-md-8 col-md-offset-2">
            <br/><br/>
          <div class="box box-primary active">
            <!-- /.box-header -->
            <div class="box-body">

              @if(session('error'))
                <div class="alert alert-danger">
                    {!! session('error') !!}
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

              <form class="form" id="details_form" method="post" 
                      action="{{route('install.update')}}">
                    {{ csrf_field() }}

                    <h4>{{ __('ui.license_details') }} <small class="text-danger">{{ __('ui.make_sure_to_provide_correct_information_from_envato_codecanyon') }}</small></h4>
                    <hr/>

                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="envato_purchase_code">{{ __('ui.envato_purchase_code') }}</label>
                            <input type="text" name="ENVATO_PURCHASE_CODE" required class="form-control" id="envato_purchase_code">
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="envato_username">{{ __('ui.envato_username') }}</label>
                            <input type="text" name="ENVATO_USERNAME" required class="form-control" id="envato_username">
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="form-group">
                          <label for="envato_email">{{ __('ui.your_email') }}</label>
                          <input type="email" name="ENVATO_EMAIL" class="form-control" id="envato_email" placeholder="{{ __('ui.optional') }}">
                          <p class="help-block">{{ __('ui.for_newsletter_support') }}</p>
                        </div>
                    </div>
                    @include('install.partials.i_service')
                    @include('install.partials.e_license')

                    <div class="col-md-12">
                        <button type="submit" id="install_button" class="btn btn-primary pull-right">{{ __('ui.i_agree_update') }}</button>
                    </div>
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
      $('select#MAIL_MAILER').change(function(){
        var driver = $(this).val();

        if(driver == 'smtp'){
          $('div.smtp').removeClass('hide');
          $('input.smtp_input').attr('disabled', false);
        } else {
          $('div.smtp').addClass('hide');
          $('input.smtp_input').attr('disabled', true);
        }
      })

      $('form#details_form').submit(function(){
        $('button#install_button').attr('disabled', true).text("{{ __('ui.installing') }}");
        $('div.install_msg').removeClass('hide');
        $('.back_button').hide();
      });

    })
  </script>
@endsection