@extends('layouts.app')

@section('title', __('ui.edit_holiday'))

@section('content')
@include('hrm::partials.hrm_page_header', [
    'title' => __('ui.edit_holiday'),
    'subtitle' => __('ui.update_a_company_holiday'),
    'actions' => '<a href="'.route('hrm.holidays.index').'" class="btn btn-default"><i class="fa fa-arrow-left"></i> '. __('ui.back_to_holidays') .'</a>'
])

<section class="content">
    <div class="box box-primary">
        <form method="POST" action="{{ route('hrm.holidays.update', $holiday->id) }}">
            @csrf
            @method('PUT')
            <div class="box-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>{{ __('ui.company') }}</label>
                            <select name="company_id" class="form-control" required>
                                @foreach($companies as $company)
                                    <option value="{{ $company->id }}" {{ old('company_id', $holiday->company_id) == $company->id ? 'selected' : '' }}>{{ $company->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>{{ __('ui.title') }}</label>
                            <input type="text" name="title" class="form-control" value="{{ old('title', $holiday->title) }}" required>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>{{ __('ui.start_date') }}</label>
                            <input type="date" name="start_date" class="form-control" value="{{ old('start_date', $holiday->start_date) }}" required>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>{{ __('ui.end_date') }}</label>
                            <input type="date" name="end_date" class="form-control" value="{{ old('end_date', $holiday->end_date) }}" required>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>{{ __('ui.description') }}</label>
                            <input type="text" name="description" class="form-control" value="{{ old('description', $holiday->description) }}">
                        </div>
                    </div>
                </div>
            </div>
            <div class="box-footer text-right">
                <button class="btn btn-primary">{{ __('ui.update_holiday') }}</button>
            </div>
        </form>
    </div>
</section>
@endsection