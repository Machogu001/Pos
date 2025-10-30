@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Companies</h2>
    <a href="{{ route('hrm.companies.create') }}" class="btn btn-primary">Add Company</a>
    <hr />
    <p>Manage companies (these are used as the source for the Company dropdowns in HRM).</p>
</div>
@endsection
