@extends('layouts.app')

@section('title', __('ui.create_holiday'))

@section('content')
@include('hrm::partials.hrm_page_header', [
    'title' => __('ui.create_holiday'),
    'subtitle' => __('ui.add_a_company_holiday'),
    'actions' => '<a href="'.route('hrm.holidays.index').'" class="btn btn-default"><i class="fa fa-arrow-left"></i> '. __('ui.back_to_holidays') .'</a>'
])

<section class="content">
    <div class="box box-primary">
        <form method="POST" action="{{ route('hrm.holidays.store') }}">
            @csrf
            <div class="box-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>{{ __('ui.company') }}</label>
                            <select name="company_id" class="form-control" required>
                                <option value="">{{ __('ui.select_company') }}</option>
                                @foreach($companies as $company)
                                    <option value="{{ $company->id }}">{{ $company->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>{{ __('ui.title') }}</label>
                            <input type="text" name="title" class="form-control" value="{{ old('title') }}" required>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>{{ __('ui.start_date') }}</label>
                            <input type="date" name="start_date" class="form-control" value="{{ old('start_date') }}" required>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>{{ __('ui.end_date') }}</label>
                            <input type="date" name="end_date" class="form-control" value="{{ old('end_date') }}" required>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>{{ __('ui.description') }}</label>
                            <input type="text" name="description" class="form-control" value="{{ old('description') }}">
                        </div>
                    </div>
                </div>
            </div>
            <div class="box-footer text-right">
                <button class="btn btn-primary">{{ __('ui.save_holiday') }}</button>
            </div>
        </form>
    </div>
</section>
@endsection