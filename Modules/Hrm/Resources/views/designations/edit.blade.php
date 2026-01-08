@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Edit Designation</h2>
    <form action="{{ route('hrm.designations.update', $designation->id) }}" method="POST">
        {{ csrf_field() }}
        {{ method_field('PUT') }}
        @include('hrm::partials.hrm_form_toolbar')

        <div class="form-group">
            <label for="designation">Designation</label>
            <input type="text" name="designation" id="designation" class="form-control" value="{{ $designation->designation }}" required />
            @include('hrm::partials.field_error', ['field' => 'designation'])
        </div>

        <div class="form-group">
            <label for="company_id">Company</label>
            <select name="company_id" id="company_id" class="form-control" required>
                @foreach($companies as $c)
                    @php
                        $isBusiness = isset($c->business_id) && $c->business_id == session('business.id');
                        $label = $isBusiness ? 'Business - ' . $c->name : $c->name;
                    @endphp
                    <option value="{{ $c->id }}" @if($designation->company_id == $c->id) selected @endif>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div class="form-group">
            <label for="department">Department</label>
            <select name="department" id="department" class="form-control" required>
                @foreach($departments as $d)
                    <option value="{{ $d->id }}" @if($designation->department_id == $d->id) selected @endif>{{ $d->department }}</option>
                @endforeach
            </select>
            @include('hrm::partials.field_error', ['field' => 'department'])
        </div>

    </form>
</div>
@endsection
