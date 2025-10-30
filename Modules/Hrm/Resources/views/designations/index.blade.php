@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Designations</h2>
    <a href="{{ route('hrm.designations.create') }}" class="btn btn-primary">Add Designation</a>
    <hr />
</div>
@endsection
