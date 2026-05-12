@extends('layouts.app')

@section('title', __('ui.hrm_theme_settings'))

@section('content')
@include('hrm::partials.hrm_page_header', [
    'title' => __('ui.hrm_theme_settings'),
    'subtitle' => __('ui.choose_the_visual_style_for_all_hrm_pages'),
    'actions' => '<a href="'.route('hrm.settings.index').'" class="btn btn-default"><i class="fa fa-arrow-left"></i> '. __('ui.back_to_settings') .'</a>'
])

<section class="content">
    @if($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title">{{ __('ui.select_hrm_theme') }}</h3>
        </div>
        <form method="POST" action="{{ route('hrm.settings.theme.update') }}">
            @csrf
            <div class="box-body">
                <div class="form-group">
                    <label for="hrm_theme">{{ __('ui.theme') }}</label>
                    <select name="hrm_theme" id="hrm_theme" class="form-control" required>
                        <option value="classic" {{ old('hrm_theme', $currentTheme) === 'classic' ? 'selected' : '' }}>{{ __('ui.classic_warm_premium') }}</option>
                        <option value="corporate" {{ old('hrm_theme', $currentTheme) === 'corporate' ? 'selected' : '' }}>{{ __('ui.corporate_clean_cool') }}</option>
                        <option value="minimal" {{ old('hrm_theme', $currentTheme) === 'minimal' ? 'selected' : '' }}>{{ __('ui.minimal_light_soft_neutral') }}</option>
                    </select>
                    <small class="form-text text-muted">{{ __('ui.this_updates_dashboard_reports_settings_and_all_hrm_management_pages') }}</small>
                </div>

                <div class="row" style="margin-top:14px;">
                    <div class="col-md-4">
                        <div class="well" style="margin-bottom:0; border-color:#d8c49a; background:linear-gradient(135deg,#1f3f5b,#2f5d7c); color:#fff7e8;">
                            <strong>{{ __('ui.classic_preview') }}</strong>
                            <div style="margin-top:8px; font-size:12px; opacity:.95;">{{ __('ui.navy_bronze_accents_with_warmer_table_cards') }}</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="well" style="margin-bottom:0; border-color:#b9c7d8; background:linear-gradient(135deg,#1f3b57,#2a6f9f); color:#eef7ff;">
                            <strong>{{ __('ui.corporate_preview') }}</strong>
                            <div style="margin-top:8px; font-size:12px; opacity:.95;">{{ __('ui.cool_blue_slate_accents_with_cleaner_contrast') }}</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="well" style="margin-bottom:0; border-color:#d9dfe6; background:linear-gradient(135deg,#f5f7fa,#e9edf2); color:#2f3a45;">
                            <strong>{{ __('ui.minimal_light_preview') }}</strong>
                            <div style="margin-top:8px; font-size:12px; opacity:.95;">{{ __('ui.neutral_grays_with_subtle_blue_highlights_and_airy_spacing') }}</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="box-footer text-right">
                <button type="submit" class="btn btn-primary">{{ __('ui.save_theme') }}</button>
            </div>
        </form>
    </div>
</section>
@endsection
