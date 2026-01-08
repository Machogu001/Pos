@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-12">
            <h1>{{ $title ?? 'HRM' }}</h1>
            <p>{{ $message ?? '' }}</p>
                <p>This is a minimal scaffolded HRM module index. Add controllers, views, routes and migrations under <code>Modules/Hrm</code>.</p>

                <div class="list-group" style="max-width:560px; margin-top:20px;">
                    <a href="{{ route('hrm.settings.leave.edit') }}" class="list-group-item">
                        HRM Settings: Default Annual Leave
                    </a>
                    <a href="{{ route('hrm.settings.modules.edit') }}" class="list-group-item">
                        HRM Settings: Enable/Disable Modules
                    </a>
                </div>
        </div>
    </div>
</div>
@endsection
