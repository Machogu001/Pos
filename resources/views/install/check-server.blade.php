@extends('layouts.install', ['no_header' => 1])
@section('title', __('ui.pos_installation_check_server'))

@section('content')
<div class="container">
    <div class="row">
        <h3 class="text-center">{{ config('app.name', 'POS') }} {{ __('ui.installation') }} <small>{{ __('ui.step_2_of_3') }}</small></h3>

        <div class="col-md-8 col-md-offset-2">
          <hr/>
          @include('install.partials.nav', ['active' => 'server'])

          <div class="box box-primary active" id="Installation">
            <!-- /.box-header -->
            <div class="box-body">
              <table class="table">
                <tr>
                  <td>{{ __('ui.php_7_1') }}</td>
                  <td>
                    @if($output['php'])
                      <i class="fa fa-check-circle-o text-success" aria-hidden="true"></i>
                    @else
                      <i class="fa fa-close text-danger" aria-hidden="true"></i>
                    @endif
                  </td>
                </tr>

                <tr>
                  <td>{{ __('ui.openssl_php_extension') }}</td>
                  <td>
                    @if($output['openssl'])
                      <i class="fa fa-check-circle-o text-success" aria-hidden="true"></i>
                    @else
                      <i class="fa fa-close text-danger" aria-hidden="true"></i>
                    @endif
                  </td>
                </tr>

                <tr>
                  <td>{{ __('ui.pdo_php_extension') }}</td>
                  <td>
                    @if($output['pdo'])
                      <i class="fa fa-check-circle-o text-success" aria-hidden="true"></i>
                    @else
                      <i class="fa fa-close text-danger" aria-hidden="true"></i>
                    @endif
                  </td>
                </tr>

                <tr>
                  <td>{{ __('ui.mbstring_php_extension') }}</td>
                  <td>
                    @if($output['mbstring'])
                      <i class="fa fa-check-circle-o text-success" aria-hidden="true"></i>
                    @else
                      <i class="fa fa-close text-danger" aria-hidden="true"></i>
                    @endif
                  </td>
                </tr>

                <tr>
                  <td>{{ __('ui.tokenizer_php_extension') }}</td>
                  <td>
                    @if($output['tokenizer'])
                      <i class="fa fa-check-circle-o text-success" aria-hidden="true"></i>
                    @else
                      <i class="fa fa-close text-danger" aria-hidden="true"></i>
                    @endif
                  </td>
                </tr>

                <tr>
                  <td>{{ __('ui.xml_php_extension') }}</td>
                  <td>
                    @if($output['xml'])
                      <i class="fa fa-check-circle-o text-success" aria-hidden="true"></i>
                    @else
                      <i class="fa fa-close text-danger" aria-hidden="true"></i>
                    @endif
                  </td>
                </tr>

                <tr>
                  <td>{{ __('ui.curl_php_extension') }}</td>
                  <td>
                    @if($output['curl'])
                      <i class="fa fa-check-circle-o text-success" aria-hidden="true"></i>
                    @else
                      <i class="fa fa-close text-danger" aria-hidden="true"></i>
                    @endif
                  </td>
                </tr>

                <tr>
                  <td>{{ __('ui.zip_php_extension') }}</td>
                  <td>
                    @if($output['zip'])
                      <i class="fa fa-check-circle-o text-success" aria-hidden="true"></i>
                    @else
                      <i class="fa fa-close text-danger" aria-hidden="true"></i>
                    @endif
                  </td>
                </tr>

                <tr>
                  <td>{{ __('ui.gd_php_extension') }}</td>
                  <td>
                    @if($output['gd'])
                      <i class="fa fa-check-circle-o text-success" aria-hidden="true"></i>
                    @else
                      <i class="fa fa-close text-danger" aria-hidden="true"></i>
                    @endif
                  </td>
                </tr>

                <tr>
                  <td colspan="2">&nbsp;</td>
                </tr>

                <tr>
                  <td><b>{{storage_path()}}</b> {{ __('ui.is_writable') }}</td>
                  <td>
                    @if($output['storage_writable'])
                      <i class="fa fa-check-circle-o text-success" aria-hidden="true"></i>
                    @else
                      <i class="fa fa-close text-danger" aria-hidden="true"></i>
                    @endif
                  </td>
                </tr>

                <tr>
                  <td><b>{{base_path('bootstrap/cache')}}</b> {{ __('ui.is_writable') }}</td>
                  <td>
                    @if($output['cache_writable'])
                      <i class="fa fa-check-circle-o text-success" aria-hidden="true"></i>
                    @else
                      <i class="fa fa-close text-danger" aria-hidden="true"></i>
                    @endif
                  </td>
                </tr>
                      
            </table>

              <br/>
              <a href="{{route('install.index')}}" class="btn btn-default pull-left">{{ __('ui.back') }}</a>

              <a @if($output['next']) href="{{route('install.details')}}" @endif class="btn btn-primary pull-right @if(!$output['next']) disabled-link @endif" @if(!$output['next']) disabled onclick="return false;" @endif>{{ __('ui.next') }}</a>
            </div>
          <!-- /.box-body -->
          </div>
        </div>

    </div>
</div>
@endsection