@extends('layouts.app')

@section('title', 'Edit Holiday')

@section('content')
@include('hrm::partials.hrm_page_header', [
    'title' => 'Edit Holiday',
    'subtitle' => 'Update a company holiday.',
    'actions' => '<a href="'.route('hrm.holidays.index').'" class="btn btn-default"><i class="fa fa-arrow-left"></i> Back to Holidays</a>'
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
                            <label>Company</label>
                            <select name="company_id" class="form-control" required>
                                @foreach($companies as $company)
                                    <option value="{{ $company->id }}" {{ old('company_id', $holiday->company_id) == $company->id ? 'selected' : '' }}>{{ $company->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Title</label>
                            <input type="text" name="title" class="form-control" value="{{ old('title', $holiday->title) }}" required>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Start Date</label>
                            <input type="date" name="start_date" class="form-control" value="{{ old('start_date', $holiday->start_date) }}" required>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>End Date</label>
                            <input type="date" name="end_date" class="form-control" value="{{ old('end_date', $holiday->end_date) }}" required>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Description</label>
                            <input type="text" name="description" class="form-control" value="{{ old('description', $holiday->description) }}">
                        </div>
                    </div>
                </div>
            </div>
            <div class="box-footer text-right">
                <button class="btn btn-primary">Update Holiday</button>
            </div>
        </form>
    </div>
</section>
@endsection