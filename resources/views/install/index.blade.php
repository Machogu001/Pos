@extends('layouts.install', ['no_header' => 1])
@section('title', __('ui.welcome_pos_installation'))

@section('content')
<div class="container">
    
    <div class="row">
      <h3 class="text-center">{{ config('app.name', 'POS') }} {{ __('ui.installation') }} <small>{{ __('ui.step_1_of_3') }}</small></h3>

        <div class="col-md-8 col-md-offset-2">
          <hr/>
          @include('install.partials.nav', ['active' => 'install'])

          <div class="box box-primary active">
            <!-- /.box-header -->
            <div class="box-body">
              <h3 class="text-success">
                {{ __('ui.welcome_to_pos_installation') }}
              </h3>
              <p><strong class="text-danger">{{ __('ui.important') }}</strong> {{ __('ui.before_you_start_installing_make_sure_you_have_following_information_ready_with_you') }}</p>

              <ol>
                <li>
                  <b>{{ __('ui.step_by_step_document') }}</b> - <a href="https://ultimatefosters.com/docs/ultimatepos/getting-started/installing-ultimatepos/" target="_blank">{{ __('ui.documentation') }}</a>
                </li>
                <li>
                  <b>{{ __('ui.application_name_2') }}</b> - {{ __('ui.something_short_meaningful') }}
                </li>
                <li>
                  <b>{{ __('ui.database_informations') }}</b>
                  <ul>
                    <li>{{ __('ui.username') }}</li>
                    <li>{{ __('ui.password_2') }}</li>
                    <li>{{ __('ui.database_name_2') }}</li>
                    <li>{{ __('ui.database_host_2') }}</li>
                  </ul>
                </li>
                <li>
                  <b>{{ __('ui.mail_configuration') }}</b> - {{ __('ui.smtp_details_optional') }}
                </li>
                <li>
                  <b>{{ __('ui.envato_or_codecanyon_details') }}</b>
                  <ul>
                    <li><b>{{ __('ui.envato_purchase_code_2') }}</b> (<a href="https://help.market.envato.com/hc/en-us/articles/202822600-Where-Is-My-Purchase-Code-" target="_blank">{{ __('ui.where_is_my_purchase_code') }}</a>)</li>
                    <li>
                      <b>{{ __('ui.envato_username_2') }}</b> {{ __('ui.your_envato_username') }}
                    </li>
                  </ul>
                </li>
              </ol>

              @include('install.partials.i_service')

              @include('install.partials.e_license')
              
              <a href="{{route('install.checkServer')}}" class="btn btn-primary pull-right">{{ __('ui.i_agree_let_s_go') }}</a>
            </div>
          <!-- /.box-body -->
          </div>

        </div>

    </div>
</div>
@endsection
