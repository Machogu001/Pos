@extends('layouts.install', ['no_header' => 1])
@section('title', __('ui.pos_installation_check_server'))

@section('content')
<div class="container">
    <div class="row">
        <h3 class="text-center">{{ config('app.name', 'POS') }} {{ __('ui.installation') }} <small>{{ __('ui.step_3_of_3') }}</small></h3>

        <div class="col-md-8 col-md-offset-2">
          <hr/>
          @include('install.partials.nav', ['active' => 'app_details'])

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
                      action="{{route('install.postDetails')}}">
                  {{ csrf_field() }}

                  <h4>{{ __('ui.application_details') }}</h4>
                  <hr/>

                  <div class="col-md-6">
                    <div class="form-group">
                        <label for="app_name">{{ __('ui.application_name') }}</label>
                        <input type="text" class="form-control" name="APP_NAME" id="app_name" placeholder="{{ __('ui.ultimate_pos') }}" required>
                    </div>
                  </div>
                  
                  <div class="col-md-6">
                    <div class="form-group">
                        <label for="app_title">{{ __('ui.application_title') }}</label>
                        <input type="text" name="APP_TITLE" class="form-control" id="app_title">
                    </div>
                  </div>

                <h4> {{ __('ui.license_details') }} <small class="text-danger">{{ __('ui.make_sure_to_provide_correct_information_from_envato_codecanyon') }}</small></h4>
                <hr/>

                <div class="col-md-4">
                    <div class="form-group">
                        <label for="envato_purchase_code">{{ __('ui.envato_purchase_code') }}</label>
                        <input type="password" name="ENVATO_PURCHASE_CODE" required class="form-control" id="envato_purchase_code">
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

                  @if($activation_key)
                    <div class="col-md-6">
                      <div class="form-group">
                          <label for="envato_purchase_code">{{ __('ui.activation_licence_code') }}</label>
                          <input type="password" name="MAC_LICENCE_CODE" required class="form-control" id="activation_licence_code">
                      </div>
                    </div>
                  @endif
                  
                  <div class="clearfix"></div>
                  
                  <h4> {{ __('ui.database_details') }} <small>{{ __('ui.make_sure_to_provide_correct_information') }}</small></h4>
                  <hr/>

                  <div class="col-md-4">
                    <div class="form-group">
                        <label for="db_host">{{ __('ui.database_host') }}</label>
                        <input type="text" class="form-control" id="db_host" name="DB_HOST" required placeholder="{{ __('ui.localhost_127_0_0_1') }}">
                    </div>
                  </div>

                  <div class="col-md-4">
                    <div class="form-group">
                        <label for="db_port">{{ __('ui.database_port') }}</label>
                        <input type="text" class="form-control" id="db_port" name="DB_PORT" required value="3306">
                    </div>
                  </div>

                  <div class="col-md-4">
                    <div class="form-group">
                        <label for="db_database">{{ __('ui.database_name') }}</label>
                        <input type="text" class="form-control" id="db_database" name="DB_DATABASE" required>
                    </div>
                  </div>

                  <div class="col-md-6">
                    <div class="form-group">
                        <label for="db_username">{{ __('ui.database_username') }}</label>
                        <input type="text" class="form-control" id="db_username" name="DB_USERNAME" required>
                    </div>
                  </div>

                  <div class="col-md-6">
                    <div class="form-group">
                        <label for="db_password">{{ __('ui.database_password') }}</label>
                        <input type="password" class="form-control" id="db_password" name="DB_PASSWORD" required>
                    </div>
                  </div>

                  <div class="clearfix"></div>

                  <h4>{{ __('ui.email_configuration') }}<small> {{ __('ui.use_for_sending_mails') }}</small></h4>
                  <hr/>
                  <div class="col-md-6">
                    <div class="form-group">
                        <label for="MAIL_MAILER">{{ __('ui.send_mails_using') }}</label>
                        <select class="form-control" name="MAIL_MAILER" id="MAIL_MAILER">
                          <option value="sendmail">{{ __('ui.php_mail') }}</option>
                          <option value="smtp">SMTP</option>
                        </select>
                    </div>
                  </div>
                  <div class="clearfix"></div>

                  <div class="col-md-6">
                    <div class="form-group">
                        <label for="MAIL_FROM_ADDRESS">{{ __('ui.default_from_address') }}</label>
                        <input type="email" class="form-control" id="MAIL_FROM_ADDRESS" name="MAIL_FROM_ADDRESS" placeholder="{{ __('ui.hello_bremac360_com') }}" required>
                    </div>
                  </div>

                  <div class="col-md-6">
                    <div class="form-group">
                        <label for="MAIL_FROM_NAME">{{ __('ui.default_from_name') }}</label>
                        <input type="text" class="form-control" id="MAIL_FROM_NAME" name="MAIL_FROM_NAME" placeholder="{{ __('ui.ultimate_pos_optional') }}">
                    </div>
                  </div>

                  <div class="col-md-4 smtp hide">
                    <div class="form-group">
                        <label for="MAIL_HOST">{{ __('ui.smtp_host') }}</label>
                        <input type="text" class="form-control smtp_input" id="MAIL_HOST" name="MAIL_HOST" required disabled>
                    </div>
                  </div>

                  <div class="col-md-4  smtp hide">
                    <div class="form-group">
                        <label for="MAIL_PORT">{{ __('ui.smtp_mail_port') }}</label>
                        <input type="text" class="form-control smtp_input" id="MAIL_PORT" name="MAIL_PORT" required disabled>
                    </div>
                  </div>

                  <div class="col-md-4  smtp hide">
                    <div class="form-group">
                        <label for="MAIL_ENCRYPTION">{{ __('ui.smtp_mail_encryption') }}</label>
                        <input type="text" class="form-control smtp_input" id="MAIL_ENCRYPTION" name="MAIL_ENCRYPTION" required disabled placeholder="{{ __('ui.tls_or_ssl') }}">
                    </div>
                  </div>

                  <div class="col-md-6  smtp hide">
                    <div class="form-group">
                        <label for="MAIL_USERNAME">{{ __('ui.smtp_username') }}</label>
                        <input type="text" class="form-control smtp_input" id="MAIL_USERNAME" name="MAIL_USERNAME" required disabled>
                    </div>
                  </div>

                  <div class="col-md-6  smtp hide">
                    <div class="form-group">
                        <label for="MAIL_PASSWORD">{{ __('ui.smtp_password') }}</label>
                        <input type="password" class="form-control smtp_input" id="MAIL_PASSWORD" name="MAIL_PASSWORD" required disabled>
                    </div>
                  </div>

                  <hr/>

                  <div class="col-md-12">
                    <a href="{{route('install.index')}}" class="btn btn-default pull-left back_button" tabindex="-1">{{ __('ui.back') }}</a>
                    <button type="submit" id="install_button" class="btn btn-primary pull-right">{{ __('ui.install') }}</button>
                  </div>

                  <div class="col-md-12 text-center text-danger install_msg hide">
                    <strong>{{ __('ui.installation_in_progress_please_do_not_refresh_go_back_or_close_the_browser') }}</strong>
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
        $('button#install_button').attr('disabled', true).text('Installing...');
        $('div.install_msg').removeClass('hide');
        $('.back_button').hide();
      });

    })
  </script>
@endsection