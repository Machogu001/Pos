@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Create Company</h2>
    <form action="/hrm/companies" method="POST">
        {{ csrf_field() }}

        <div class="form-group">
            <label for="name">Name</label>
            <input type="text" name="name" id="name" class="form-control" placeholder="Company name" required />
        </div>

        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" name="email" id="email" class="form-control" placeholder="contact@company.com" />
        </div>

        <div class="form-group">
            <label for="phone">Phone</label>
            <input type="text" name="phone" id="phone" class="form-control" placeholder="07xxxxxxxx" />
        </div>

        <div class="form-group">
            <label for="country">Country</label>
            <input type="text" name="country" id="country" class="form-control" placeholder="Country name" />
        </div>

        <button class="btn btn-success">Create</button>
    </form>
</div>
@endsection
