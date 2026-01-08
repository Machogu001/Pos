@extends('layouts.app')

@section('title', __('HRM Module Settings'))

@section('content')
<section class="content-header">
    <h1>HRM Settings - Enable/Disable Modules</h1>
</section>

<section class="content">
    @if(session('status'))
        @php $status = session('status'); @endphp
        @if(!empty($status['success']))
            <div class="alert alert-success">{{ $status['msg'] ?? 'Settings updated successfully' }}</div>
        @else
            <div class="alert alert-danger">{{ $status['msg'] ?? 'Update failed' }}</div>
        @endif
    @endif

    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title">Toggle Modules</h3>
        </div>
        <form method="POST" action="{{ route('hrm.settings.modules.update') }}">
            @csrf
            <div class="box-body">
                <p class="help-block">Select which modules are enabled for this business. These control visibility/availability across menus and features.</p>

                <div class="row">
                    @foreach($moduleOptions as $key => $label)
                        <div class="col-md-4">
                            <div class="checkbox">
                                <label>
                                    <input type="checkbox" name="modules[]" value="{{ $key }}" {{ in_array($key, $enabled ?? []) ? 'checked' : '' }}>
                                    {{ $label }}
                                </label>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
            <div class="box-footer">
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</section>
@endsection
