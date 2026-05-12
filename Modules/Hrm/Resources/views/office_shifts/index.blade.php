@extends('layouts.app')

@section('title', __('ui.office_shifts'))

@section('css')
<style>
    :root {
        --shift-head-bg: linear-gradient(120deg, #f3f7ff 0%, #eaf4ff 100%);
        --shift-border: #d6e4f0;
        --shift-text: #1f3b57;
        --shift-on-bg: #e7f9ef;
        --shift-on-text: #166534;
        --shift-on-border: #b9ebce;
        --shift-off-bg: #fff2f2;
        --shift-off-text: #9f1239;
        --shift-off-border: #fecaca;
        --shift-weekend-bg: #fff8e6;
    }

    .shift-schedule-wrap {
        background: #ffffff;
        border-radius: 8px;
        overflow: hidden;
    }

    .shift-table {
        margin-bottom: 0;
        border-collapse: separate;
        border-spacing: 0;
    }

    .shift-table thead th {
        background: var(--shift-head-bg);
        color: var(--shift-text);
        font-weight: 700;
        border-bottom: 1px solid var(--shift-border);
        white-space: nowrap;
    }

    .shift-table td,
    .shift-table th {
        vertical-align: middle !important;
    }

    .shift-table .weekend-head {
        background: #ffecc2;
    }

    .shift-table td.weekend-col {
        background-color: var(--shift-weekend-bg);
    }

    .shift-name {
        color: #0f2f4f;
        font-weight: 700;
    }

    .shift-company {
        color: #37506b;
        font-weight: 600;
    }

    .shift-chip {
        display: inline-block;
        padding: 5px 10px;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 700;
        border: 1px solid transparent;
        white-space: nowrap;
    }

    .shift-chip.on {
        background: var(--shift-on-bg);
        color: var(--shift-on-text);
        border-color: var(--shift-on-border);
    }

    .shift-chip.off {
        background: var(--shift-off-bg);
        color: var(--shift-off-text);
        border-color: var(--shift-off-border);
    }
</style>
@endsection

@section('content')
@include('hrm::partials.hrm_page_header', [
    'title' => __('ui.office_shifts'),
    'subtitle' => __('ui.define_working_hours_and_shift_patterns_for_each_company'),
    'actions' => '<a href="'.route('hrm.office_shifts.create').'" class="btn btn-primary"><i class="fa fa-plus"></i> '. __('ui.add_office_shift') .'</a>'
])

<section class="content">
    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title">{{ __('ui.shift_schedule') }}</h3>
        </div>
        <div class="box-body no-padding">
            <div class="table-responsive shift-schedule-wrap">
                <table class="table table-hover table-striped shift-table mb-0">
                    <thead>
                        <tr>
                            <th>{{ __('ui.name') }}</th>
                            <th>{{ __('ui.company') }}</th>
                            <th>{{ __('ui.mon') }}</th>
                            <th>{{ __('ui.tue') }}</th>
                            <th>{{ __('ui.wed') }}</th>
                            <th>{{ __('ui.thu') }}</th>
                            <th>{{ __('ui.fri') }}</th>
                            <th class="weekend-head">{{ __('ui.sat') }}</th>
                            <th class="weekend-head">{{ __('ui.sun') }}</th>
                            <th class="text-end">{{ __('ui.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($office_shifts_for_view ?? [] as $s)
                            @php
                                $fmtDay = function ($in, $out) {
                                    return [
                                        'label' => (!empty($in) && !empty($out)) ? $in.' - '.$out : __('ui.off_day'),
                                        'off' => empty($in) || empty($out),
                                    ];
                                };
                                $mon = $fmtDay($s['monday_in'] ?? null, $s['monday_out'] ?? null);
                                $tue = $fmtDay($s['tuesday_in'] ?? null, $s['tuesday_out'] ?? null);
                                $wed = $fmtDay($s['wednesday_in'] ?? null, $s['wednesday_out'] ?? null);
                                $thu = $fmtDay($s['thursday_in'] ?? null, $s['thursday_out'] ?? null);
                                $fri = $fmtDay($s['friday_in'] ?? null, $s['friday_out'] ?? null);
                                $sat = $fmtDay($s['saturday_in'] ?? null, $s['saturday_out'] ?? null);
                                $sun = $fmtDay($s['sunday_in'] ?? null, $s['sunday_out'] ?? null);
                            @endphp
                            <tr>
                                <td><span class="shift-name">{{ $s['name'] }}</span></td>
                                <td><span class="shift-company">{{ $s['company_name'] }}</span></td>
                                <td><span class="shift-chip {{ $mon['off'] ? 'off' : 'on' }}">{{ $mon['label'] }}</span></td>
                                <td><span class="shift-chip {{ $tue['off'] ? 'off' : 'on' }}">{{ $tue['label'] }}</span></td>
                                <td><span class="shift-chip {{ $wed['off'] ? 'off' : 'on' }}">{{ $wed['label'] }}</span></td>
                                <td><span class="shift-chip {{ $thu['off'] ? 'off' : 'on' }}">{{ $thu['label'] }}</span></td>
                                <td><span class="shift-chip {{ $fri['off'] ? 'off' : 'on' }}">{{ $fri['label'] }}</span></td>
                                <td class="weekend-col"><span class="shift-chip {{ $sat['off'] ? 'off' : 'on' }}">{{ $sat['label'] }}</span></td>
                                <td class="weekend-col"><span class="shift-chip {{ $sun['off'] ? 'off' : 'on' }}">{{ $sun['label'] }}</span></td>
                                <td class="text-end">
                                    <a href="{{ route('hrm.office_shifts.edit', $s['id']) }}" class="btn btn-sm btn-default">{{ __('ui.edit') }}</a>
                                    <form action="{{ route('hrm.office_shifts.destroy', $s['id']) }}" method="POST" style="display:inline-block">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-danger" data-hrm-confirm-submit="1" data-hrm-confirm="{{ __('ui.delete_this_office_shift') }}" data-hrm-confirm-title="{{ __('ui.delete_office_shift') }}">{{ __('ui.delete') }}</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="10" class="text-center text-muted">{{ __('ui.no_office_shifts_found') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>
@endsection
