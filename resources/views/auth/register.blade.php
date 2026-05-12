@extends('layouts.auth')

@section('content')

<div class="row">

    <h1 class="page-header text-center">{{ config('app.name', 'ultimatePOS') }}</h2>
    
    <div class="col-md-8 col-md-offset-2">
        
        <div class="box box-solid">
            <div class="box-header with-border">
                <h3 class="box-title text-center">{{ __('ui.register_and_get_started_in_minutes') }}</h3>
            </div>

            {!! Form::open(['url' => {{ route('business.postRegister') }}]) !!}
            {!! Form::token(); !!}

                <!-- /.box-header -->
                <div class="box-body">
                    <div class="col-md-12">
                        <div class="form-group">
                            {!! Form::label('name', __('ui.business_name')) !!}
                            <div class="input-group">
                                <span class="input-group-addon">
                                    <i class="fa fa-suitcase"></i>
                                </span>
                                {!! Form::text('name', null, ['class' => 'form-control','placeholder' => __('ui.business_name_2')]); !!}
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                        {!! Form::label('start_date', __('ui.start_date_2')) !!}
                        <div class="input-group">
                            <span class="input-group-addon">
                                <i class="fa fa-calendar"></i>
                            </span>
                            {!! Form::text('start_date', null, ['class' => 'form-control start-date-picker','placeholder' => __('ui.start_date'), 'readonly']); !!}
                        </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                        {!! Form::label('currency', __('ui.currency')) !!}
                        <div class="input-group">
                            <span class="input-group-addon">
                                <i class="fas fa-money-bill-alt"></i>
                            </span>
                            {!! Form::select('currency', $currencies, '', ['class' => 'form-control','placeholder' => __('ui.select_currency')]); !!}
                        </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="form-group">
                        {!! Form::label('country', __('ui.country_2')) !!}
                        <div class="input-group">
                            <span class="input-group-addon">
                                <i class="fa fa-globe"></i>
                            </span>
                            {!! Form::text('country', null, ['class' => 'form-control','placeholder' => __('ui.country')]); !!}
                        </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="form-group">
                        {!! Form::label('state', __('ui.state_2')) !!}
                        <div class="input-group">
                            <span class="input-group-addon">
                                <i class="fa fa-map-marker"></i>
                            </span>
                            {!! Form::text('state', null, ['class' => 'form-control','placeholder' => __('ui.state')]); !!}
                        </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="form-group">
                        {!! Form::label('city', __('ui.city_2')) !!}
                        <div class="input-group">
                            <span class="input-group-addon">
                                <i class="fa fa-map-marker"></i>
                            </span>
                            {!! Form::text('city', null, ['class' => 'form-control','placeholder' => __('ui.city')]); !!}
                        </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                        {!! Form::label('zip_code', __('ui.zip_code_2')) !!}
                        <div class="input-group">
                            <span class="input-group-addon">
                                <i class="fa fa-map-marker"></i>
                            </span>
                            {!! Form::text('zip_code', null, ['class' => 'form-control','placeholder' => __('ui.zip_postal_code')]); !!}
                        </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                        {!! Form::label('landmark', __('ui.landmark_2')) !!}
                        <div class="input-group">
                            <span class="input-group-addon">
                                <i class="fa fa-map-marker"></i>
                            </span>
                            {!! Form::text('landmark', null, ['class' => 'form-control','placeholder' => __('ui.landmark')]); !!}
                        </div>
                        </div>
                    </div>

                    <div class="col-md-12">
                        <hr/>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                        {!! Form::label('tax_label_1', __('ui.tax_1_name')) !!}
                        <div class="input-group">
                            <span class="input-group-addon">
                                <i class="fa fa-info"></i>
                            </span>
                            {!! Form::text('tax_label_1', null, ['class' => 'form-control','placeholder' => __('ui.gst_vat_other')]); !!}
                        </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                        {!! Form::label('tax_number_1', __('ui.tax_1_no')) !!}
                        <div class="input-group">
                            <span class="input-group-addon">
                                <i class="fa fa-info"></i>
                            </span>
                            {!! Form::text('tax_number_1', null, ['class' => 'form-control',]); !!}
                        </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                        {!! Form::label('tax_label_2', __('ui.tax_2_name')) !!}
                        <div class="input-group">
                            <span class="input-group-addon">
                                <i class="fa fa-info"></i>
                            </span>
                            {!! Form::text('tax_label_2', null, ['class' => 'form-control','placeholder' => __('ui.gst_vat_other')]); !!}
                        </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                        {!! Form::label('tax_number_2', __('ui.tax_2_no')) !!}
                        <div class="input-group">
                            <span class="input-group-addon">
                                <i class="fa fa-info"></i>
                            </span>
                            {!! Form::text('tax_number_2', null, ['class' => 'form-control',]); !!}
                        </div>
                        </div>
                    </div>

                    <div class="col-md-12">
                        <hr/>
                    </div>

                    <!-- Owner Information -->
                    <div class="col-md-4">
                        <div class="form-group">
                        {!! Form::label('surname', __('ui.surname')) !!}
                        <div class="input-group">
                            <span class="input-group-addon">
                                <i class="fa fa-info"></i>
                            </span>
                            {!! Form::text('surname', null, ['class' => 'form-control','placeholder' => __('ui.surname_2')]); !!}
                        </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="form-group">
                        {!! Form::label('first_name', __('ui.first_name_2')) !!}
                        <div class="input-group">
                            <span class="input-group-addon">
                                <i class="fa fa-info"></i>
                            </span>
                            {!! Form::text('first_name', null, ['class' => 'form-control','placeholder' => __('ui.owner_name')]); !!}
                        </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="form-group">
                        {!! Form::label('last_name', __('ui.last_name_2')) !!}
                        <div class="input-group">
                            <span class="input-group-addon">
                                <i class="fa fa-info"></i>
                            </span>
                            {!! Form::text('last_name', null, ['class' => 'form-control','placeholder' => __('ui.owner_name')]); !!}
                        </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                        {!! Form::label('username', __('ui.username_2')) !!}
                        <div class="input-group">
                            <span class="input-group-addon">
                                <i class="fa fa-user"></i>
                            </span>
                            {!! Form::text('username', null, ['class' => 'form-control','placeholder' => __('ui.username_used_for_login')]); !!}
                        </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                        {!! Form::label('email', __('ui.email_2')) !!}
                        <div class="input-group">
                            <span class="input-group-addon">
                                <i class="fa fa-envelope"></i>
                            </span>
                            {!! Form::text('email', null, ['class' => 'form-control','placeholder' => '']); !!}
                        </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                        {!! Form::label('password', __('ui.password')) !!}
                        <div class="input-group">
                            <span class="input-group-addon">
                                <i class="fa fa-lock"></i>
                            </span>
                            {!! Form::password('password', ['class' => 'form-control','placeholder' => __('ui.login_password')]); !!}
                        </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                        {!! Form::label('confirm_password', __('ui.confirm_password')) !!}
                        <div class="input-group">
                            <span class="input-group-addon">
                                <i class="fa fa-lock"></i>
                            </span>
                            {!! Form::password('confirm_password', ['class' => 'form-control','placeholder' => __('ui.same_as_login_password')]); !!}
                        </div>
                        </div>
                    </div>

                </div>
                <!-- /.box-body -->
                
                <div class="box-footer">
                    <button type="button" class="btn btn-success pull-right">{{ __('ui.register') }}</button>
                </div>

            {!! Form::close() !!}
            
        </div>
          <!-- /.box -->
    </div>

</div>


@endsection