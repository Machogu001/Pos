@extends('layouts.install', ['no_header' => 1])
@section('title', __('ui.welcome_pos_installation'))

@section('content')
<div class="container">
    <div class="row">
        <h3 class="text-center">{{ config('app.name', 'POS') }} {{ __('ui.installation') }} <small>{{ __('ui.completed') }}</small></h3>

        <div class="col-md-8 col-md-offset-2">
          @include('install.partials.nav', ['active' => 'success'])

          <div class="box box-primary active">
            <div class="box-body">
              <h3 class="@if($all_passed) text-success @else text-danger @endif">
                @if($all_passed)
                  {{ __('ui.great_your_application_is_successfully_installed') }}
                @else
                  {{ __('ui.installation_finished_with_validation_issues') }}
                @endif
              </h3>

              <p>{{ __('ui.installer_validation_summary') }}</p>
              <table class="table table-bordered table-striped">
                <thead>
                  <tr>
                    <th>{{ __('ui.check') }}</th>
                    <th>{{ __('ui.status') }}</th>
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <td>{{ __('ui.env_file_exists') }}</td>
                    <td>@if($checks['env_file']) <span class="text-success">{{ __('ui.pass') }}</span> @else <span class="text-danger">{{ __('ui.fail') }}</span> @endif</td>
                  </tr>
                  <tr>
                    <td>{{ __('ui.application_key_configured') }}</td>
                    <td>@if($checks['app_key']) <span class="text-success">{{ __('ui.pass') }}</span> @else <span class="text-danger">{{ __('ui.fail') }}</span> @endif</td>
                  </tr>
                  <tr>
                    <td>{{ __('ui.database_connection') }}</td>
                    <td>@if($checks['db_connection']) <span class="text-success">{{ __('ui.pass') }}</span> @else <span class="text-danger">{{ __('ui.fail') }}</span> @endif</td>
                  </tr>
                  <tr>
                    <td>{{ __('ui.users_table') }}</td>
                    <td>@if($checks['users_table']) <span class="text-success">{{ __('ui.pass') }}</span> @else <span class="text-danger">{{ __('ui.fail') }}</span> @endif</td>
                  </tr>
                  <tr>
                    <td>{{ __('ui.business_table') }}</td>
                    <td>@if($checks['business_table']) <span class="text-success">{{ __('ui.pass') }}</span> @else <span class="text-danger">{{ __('ui.fail') }}</span> @endif</td>
                  </tr>
                  <tr>
                    <td>{{ __('ui.admin_settings_table') }}</td>
                    <td>@if($checks['admin_settings_table']) <span class="text-success">{{ __('ui.pass') }}</span> @else <span class="text-danger">{{ __('ui.fail') }}</span> @endif</td>
                  </tr>
                  <tr>
                    <td>{{ __('ui.storage_writable') }}</td>
                    <td>@if($checks['storage_writable']) <span class="text-success">{{ __('ui.pass') }}</span> @else <span class="text-danger">{{ __('ui.fail') }}</span> @endif</td>
                  </tr>
                  <tr>
                    <td>{{ __('ui.bootstrap_cache_writable') }}</td>
                    <td>@if($checks['cache_writable']) <span class="text-success">{{ __('ui.pass') }}</span> @else <span class="text-danger">{{ __('ui.fail') }}</span> @endif</td>
                  </tr>
                </tbody>
              </table>

              @if($all_passed)
                <p class="text-success">
                  {{ __('ui.redirecting_you_to_login_in') }} <span id="redirect-seconds">8</span> {{ __('ui.seconds') }}
                </p>
                <a href="{{ $redirect_to }}" class="btn btn-primary">{{ __('ui.go_to_login') }}</a>
                <a href="{{ url('/') }}" class="btn btn-default">{{ __('ui.go_to_home') }}</a>

                <hr>
                <div class="panel panel-warning">
                  <div class="panel-heading"><strong>&#9888; {{ __('ui.server_setup_required_run_once_as_root_sudo') }}</strong></div>
                  <div class="panel-body">
                    <p>{{ __('ui.the_following_two_steps') }} <strong>{{ __('ui.cannot_be_done_by_the_web_installer') }}</strong> {{ __('ui.they_require_server_access_run_them_now_to_ensure_all_features_work_correctly') }}</p>

                    <pre style="background:#f5f5f5;padding:10px;">sudo bash {{ base_path('scripts/post_install_server_setup.sh') }}</pre>
                    <p>Runtime directories and existing nested cache files are repaired automatically during installation.
                      If ownership prevents repair, run the helper above as an administrator.
                      Pass the actual PHP worker username as its argument when it is not <code>www-data</code>.
                      The helper repairs ownership and runs the scheduler as that same account.</p>

                    <p><strong>{{ __('ui.1_register_the_laravel_scheduler_required_for_subscriptions_m_pesa_checks_reminders') }}</strong></p>
                    <pre style="background:#f5f5f5;padding:10px;"># Included in the helper script above.</pre>

                    <p><strong>{{ __('ui.2_enable_php_opcache_required_for_production_performance') }}</strong></p>
                    <pre style="background:#f5f5f5;padding:10px;"># Included in the helper script above.</pre>
                  </div>
                </div>
              @else
                <p class="text-danger">
                  {{ __('ui.please_fix_the_failed_checks_above_and_run_installation_again') }}
                </p>
                <a href="{{ route('install.details') }}" class="btn btn-primary">{{ __('ui.back_to_install_details') }}</a>
              @endif
            </div>
          </div>
        </div>
    </div>
</div>
@endsection

@section('javascript')
<script type="text/javascript">
  @if($all_passed)
    (function() {
      var seconds = 8;
      var el = document.getElementById('redirect-seconds');
      var timer = setInterval(function() {
        seconds -= 1;
        if (el) {
          el.textContent = seconds;
        }
        if (seconds <= 0) {
          clearInterval(timer);
          window.location.href = @json($redirect_to);
        }
      }, 1000);
    })();
  @endif
</script>
@endsection
