@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Create Office Shift</h2>
    <form action="/hrm/office_shifts" method="POST">
        {{ csrf_field() }}

        <div class="form-group">
            <label for="name">Name</label>
            <input type="text" name="name" id="name" class="form-control" placeholder="e.g. Morning Shift" required />
        </div>

        <div class="form-group">
            <label for="company_id">Company</label>
            <select name="company_id" id="company_id" class="form-control" required>
                @foreach($companies as $c)
                    @php
                        $isBusiness = isset($c->business_id) && $c->business_id == session('business.id');
                        $label = $isBusiness ? 'Business - ' . $c->name : $c->name;
                    @endphp
                    <option value="{{ $c->id }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>

    <h4>Times (optional)</h4>
        <div class="row">
            <div class="col-md-6">
                <label>Monday In</label>
                <input type="time" name="monday_in" class="form-control" />
            </div>
            <div class="col-md-6">
                <label>Monday Out</label>
                <input type="time" name="monday_out" class="form-control" />
            </div>
        </div>
        <!-- Reuse similar fields for other days if needed -->

        <button class="btn btn-success mt-3">Create</button>
    </form>
</div>
@endsection
