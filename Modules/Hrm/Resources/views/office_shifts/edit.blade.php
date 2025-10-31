@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Edit Office Shift</h2>
    <form action="{{ route('hrm.office_shifts.update', $office_shift->id) }}" method="POST">
        {{ csrf_field() }}
        {{ method_field('PUT') }}

        <div class="form-group">
            <label for="name">Name</label>
            <input type="text" name="name" id="name" class="form-control" value="{{ $office_shift->name }}" required />
        </div>

        <div class="form-group">
            <label for="company_id">Company</label>
            <select name="company_id" id="company_id" class="form-control" required>
                @foreach($companies as $c)
                    @php
                        $isBusiness = isset($c->business_id) && $c->business_id == session('business.id');
                        $label = $isBusiness ? 'Business - ' . $c->name : $c->name;
                    @endphp
                    <option value="{{ $c->id }}" @if($office_shift->company_id == $c->id) selected @endif>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <h4>Times (optional)</h4>
        <div class="row">
            <div class="col-md-6">
                <label>Monday In</label>
                <input type="time" name="monday_in" class="form-control" value="{{ $office_shift->monday_in ? date('H:i', strtotime($office_shift->monday_in)) : '' }}" />
            </div>
            <div class="col-md-6">
                <label>Monday Out</label>
                <input type="time" name="monday_out" class="form-control" value="{{ $office_shift->monday_out ? date('H:i', strtotime($office_shift->monday_out)) : '' }}" />
            </div>
        </div>
        <!-- Additional days can be added similarly -->

        <button class="btn btn-success mt-3">Update</button>
    </form>
</div>
@endsection
