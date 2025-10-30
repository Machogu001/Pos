@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Office Shifts</h2>
    <a href="{{ route('hrm.office_shifts.create') }}" class="btn btn-primary">Add Office Shift</a>
    <hr />
</div>
@endsection
