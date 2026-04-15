@extends('layouts.app')

@section('title', 'HRM Theme Settings')

@section('content')
@include('hrm::partials.hrm_page_header', [
    'title' => 'HRM Theme Settings',
    'subtitle' => 'Choose the visual style for all HRM pages.',
    'actions' => '<a href="'.route('hrm.settings.index').'" class="btn btn-default"><i class="fa fa-arrow-left"></i> Back to Settings</a>'
])

<section class="content">
    @if($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title">Select HRM Theme</h3>
        </div>
        <form method="POST" action="{{ route('hrm.settings.theme.update') }}">
            @csrf
            <div class="box-body">
                <div class="form-group">
                    <label for="hrm_theme">Theme</label>
                    <select name="hrm_theme" id="hrm_theme" class="form-control" required>
                        <option value="classic" {{ old('hrm_theme', $currentTheme) === 'classic' ? 'selected' : '' }}>Classic (Warm, premium)</option>
                        <option value="corporate" {{ old('hrm_theme', $currentTheme) === 'corporate' ? 'selected' : '' }}>Corporate (Clean, cool)</option>
                        <option value="minimal" {{ old('hrm_theme', $currentTheme) === 'minimal' ? 'selected' : '' }}>Minimal Light (Soft, neutral)</option>
                    </select>
                    <small class="form-text text-muted">This updates dashboard, reports, settings, and all HRM management pages.</small>
                </div>

                <div class="row" style="margin-top:14px;">
                    <div class="col-md-4">
                        <div class="well" style="margin-bottom:0; border-color:#d8c49a; background:linear-gradient(135deg,#1f3f5b,#2f5d7c); color:#fff7e8;">
                            <strong>Classic Preview</strong>
                            <div style="margin-top:8px; font-size:12px; opacity:.95;">Navy + bronze accents with warmer table/cards.</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="well" style="margin-bottom:0; border-color:#b9c7d8; background:linear-gradient(135deg,#1f3b57,#2a6f9f); color:#eef7ff;">
                            <strong>Corporate Preview</strong>
                            <div style="margin-top:8px; font-size:12px; opacity:.95;">Cool blue + slate accents with cleaner contrast.</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="well" style="margin-bottom:0; border-color:#d9dfe6; background:linear-gradient(135deg,#f5f7fa,#e9edf2); color:#2f3a45;">
                            <strong>Minimal Light Preview</strong>
                            <div style="margin-top:8px; font-size:12px; opacity:.95;">Neutral grays with subtle blue highlights and airy spacing.</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="box-footer text-right">
                <button type="submit" class="btn btn-primary">Save Theme</button>
            </div>
        </form>
    </div>
</section>
@endsection
