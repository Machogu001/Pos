@extends('layouts.app')

@section('title', 'Create Holiday')

@section('content')
@include('hrm::partials.hrm_page_header', [
    'title' => 'Create Holiday',
    'subtitle' => 'Add a company holiday.',
    'actions' => '<a href="'.route('hrm.holidays.index').'" class="btn btn-default"><i class="fa fa-arrow-left"></i> Back to Holidays</a>'
])

<section class="content">
    <div class="box box-primary">
        <form method="POST" action="{{ route('hrm.holidays.store') }}">
            @csrf
            <div class="box-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Company</label>
                            <select name="company_id" class="form-control" required>
                                <option value="">-- Select Company --</option>
                                @foreach($companies as $company)
                                    <option value="{{ $company->id }}">{{ $company->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Title</label>
                            <input type="text" name="title" class="form-control" value="{{ old('title') }}" required>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Start Date</label>
                            <input type="date" name="start_date" class="form-control" value="{{ old('start_date') }}" required>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>End Date</label>
                            <input type="date" name="end_date" class="form-control" value="{{ old('end_date') }}" required>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Description</label>
                            <input type="text" name="description" class="form-control" value="{{ old('description') }}">
                        </div>
                    </div>
                </div>
            </div>
            <div class="box-footer text-right">
                <button class="btn btn-primary">Save Holiday</button>
            </div>
        </form>
    </div>
</section>
@endsection