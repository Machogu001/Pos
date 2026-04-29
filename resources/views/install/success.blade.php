@extends('layouts.install', ['no_header' => 1])
@section('title', 'Welcome - POS Installation')

@section('content')
<div class="container">
    <div class="row">
        <h3 class="text-center">{{ config('app.name', 'POS') }} Installation <small>Completed</small></h3>

        <div class="col-md-8 col-md-offset-2">
          @include('install.partials.nav', ['active' => 'success'])

          <div class="box box-primary active">
            <div class="box-body">
              <h3 class="@if($all_passed) text-success @else text-danger @endif">
                @if($all_passed)
                  Great! Your application is successfully installed.
                @else
                  Installation finished with validation issues.
                @endif
              </h3>

              <p>Installer validation summary:</p>
              <table class="table table-bordered table-striped">
                <thead>
                  <tr>
                    <th>Check</th>
                    <th>Status</th>
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <td>.env file exists</td>
                    <td>@if($checks['env_file']) <span class="text-success">PASS</span> @else <span class="text-danger">FAIL</span> @endif</td>
                  </tr>
                  <tr>
                    <td>Application key configured</td>
                    <td>@if($checks['app_key']) <span class="text-success">PASS</span> @else <span class="text-danger">FAIL</span> @endif</td>
                  </tr>
                  <tr>
                    <td>Database connection</td>
                    <td>@if($checks['db_connection']) <span class="text-success">PASS</span> @else <span class="text-danger">FAIL</span> @endif</td>
                  </tr>
                  <tr>
                    <td>users table</td>
                    <td>@if($checks['users_table']) <span class="text-success">PASS</span> @else <span class="text-danger">FAIL</span> @endif</td>
                  </tr>
                  <tr>
                    <td>business table</td>
                    <td>@if($checks['business_table']) <span class="text-success">PASS</span> @else <span class="text-danger">FAIL</span> @endif</td>
                  </tr>
                  <tr>
                    <td>admin_settings table</td>
                    <td>@if($checks['admin_settings_table']) <span class="text-success">PASS</span> @else <span class="text-danger">FAIL</span> @endif</td>
                  </tr>
                  <tr>
                    <td>storage writable</td>
                    <td>@if($checks['storage_writable']) <span class="text-success">PASS</span> @else <span class="text-danger">FAIL</span> @endif</td>
                  </tr>
                  <tr>
                    <td>bootstrap/cache writable</td>
                    <td>@if($checks['cache_writable']) <span class="text-success">PASS</span> @else <span class="text-danger">FAIL</span> @endif</td>
                  </tr>
                </tbody>
              </table>

              @if($all_passed)
                <p class="text-success">
                  Redirecting you to login in <span id="redirect-seconds">8</span> seconds...
                </p>
                <a href="{{ $redirect_to }}" class="btn btn-primary">Go to Login</a>
                <a href="{{ url('/') }}" class="btn btn-default">Go to Home</a>

                <hr>
                <div class="panel panel-warning">
                  <div class="panel-heading"><strong>&#9888; Server Setup Required (run once as root/sudo)</strong></div>
                  <div class="panel-body">
                    <p>The following two steps <strong>cannot be done by the web installer</strong> — they require server access. Run them now to ensure all features work correctly.</p>

                    <p><strong>1. Register the Laravel Scheduler (required for subscriptions, M-Pesa checks, reminders)</strong></p>
                    <pre style="background:#f5f5f5;padding:10px;">echo "* * * * * www-data /usr/bin/php {{ base_path() }}/artisan schedule:run >> {{ storage_path() }}/logs/scheduler.log 2>&1" | sudo tee /etc/cron.d/pos-scheduler
sudo chmod 644 /etc/cron.d/pos-scheduler</pre>

                    <p><strong>2. Enable PHP OPcache (required for production performance)</strong></p>
                    <pre style="background:#f5f5f5;padding:10px;">PHP_VER=$(php -r "echo PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION;")
sudo tee /etc/php/${PHP_VER}/fpm/conf.d/10-opcache.ini > /dev/null << 'EOF'
zend_extension=opcache.so
opcache.enable=1
opcache.enable_cli=0
opcache.memory_consumption=256
opcache.interned_strings_buffer=16
opcache.max_accelerated_files=20000
opcache.validate_timestamps=0
opcache.save_comments=1
opcache.jit=off
EOF
sudo systemctl reload php${PHP_VER}-fpm</pre>
                  </div>
                </div>
              @else
                <p class="text-danger">
                  Please fix the failed checks above and run installation again.
                </p>
                <a href="{{ route('install.details') }}" class="btn btn-primary">Back to Install Details</a>
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
