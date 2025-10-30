@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-12">
            <h1>{{ $title ?? 'HRM' }}</h1>
            <p>{{ $message ?? '' }}</p>
            <p>This is a minimal scaffolded HRM module index. Add controllers, views, routes and migrations under <code>Modules/Hrm</code>.</p>
        </div>
    </div>
</div>
@endsection
