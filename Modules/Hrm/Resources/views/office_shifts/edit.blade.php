@extends('layouts.app')

@section('title', 'Edit Office Shift')

@section('css')
<style>
    :root {
        --shift-form-head: linear-gradient(120deg, #f3f7ff 0%, #eaf4ff 100%);
        --shift-form-border: #d6e4f0;
        --shift-day-text: #0f2f4f;
        --shift-weekend-bg: #fff8e6;
        --shift-off-row: #fff2f2;
        --shift-off-text: #9f1239;
    }

    .shift-edit-table {
        margin-bottom: 0;
    }

    .shift-edit-table thead th {
        background: var(--shift-form-head);
        border-bottom: 1px solid var(--shift-form-border);
        color: #1f3b57;
        font-weight: 700;
        white-space: nowrap;
    }

    .shift-edit-table td,
    .shift-edit-table th {
        vertical-align: middle !important;
    }

    .shift-day-name {
        color: var(--shift-day-text);
        font-weight: 700;
    }

    .shift-row.weekend-row td {
        background: var(--shift-weekend-bg);
    }

    .shift-row.off-row td {
        background: var(--shift-off-row);
    }

    .off-day-badge {
        display: inline-block;
        margin-left: 8px;
        padding: 2px 8px;
        border: 1px solid #fecaca;
        border-radius: 999px;
        background: #fff2f2;
        color: var(--shift-off-text);
        font-size: 11px;
        font-weight: 700;
    }
</style>
@endsection

@section('content')
@include('hrm::partials.hrm_page_header', [
    'title' => 'Edit Office Shift',
    'subtitle' => 'Update the shift name and working time setup.',
    'actions' => '<a href="'.route('hrm.office_shifts.index').'" class="btn btn-default"><i class="fa fa-arrow-left"></i> Back to Shifts</a>'
])

<section class="content">
    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title">Shift Details</h3>
        </div>
        <form action="{{ route('hrm.office_shifts.update', $office_shift->id) }}" method="POST">
            {{ csrf_field() }}
            {{ method_field('PUT') }}
            @include('hrm::partials.hrm_form_toolbar')

            <div class="box-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="name">Name</label>
                            <input type="text" name="name" id="name" class="form-control" value="{{ old('name', $office_shift->name) }}" required />
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="company_id">Company</label>
                            <select name="company_id" id="company_id" class="form-control" required>
                                @foreach($companies as $c)
                                    @php
                                        $isBusiness = isset($c->business_id) && $c->business_id == session('business.id');
                                        $label = $isBusiness ? 'Business - ' . $c->name : $c->name;
                                    @endphp
                                    <option value="{{ $c->id }}" @if(old('company_id', $office_shift->company_id) == $c->id) selected @endif>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <div class="box box-default" style="box-shadow:none; margin-bottom:0;">
                    <div class="box-header with-border">
                        <h3 class="box-title">Working Time Setup</h3>
                    </div>
                    <div class="box-body">
                        <p class="text-muted">Configure the shift times that apply to this office schedule.</p>
                        <table class="table table-bordered table-condensed shift-edit-table">
                            <thead>
                                <tr>
                                    <th style="width:120px;">Day</th>
                                    <th>Check In</th>
                                    <th>Check Out</th>
                                    <th class="text-center" style="width:80px;">Off</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                $days = [
                                    'monday'    => 'Monday',
                                    'tuesday'   => 'Tuesday',
                                    'wednesday' => 'Wednesday',
                                    'thursday'  => 'Thursday',
                                    'friday'    => 'Friday',
                                    'saturday'  => 'Saturday',
                                    'sunday'    => 'Sunday',
                                ];
                                @endphp
                                @foreach($days as $key => $label)
                                @php
                                    $inVal  = old("{$key}_in",  $office_shift->{"{$key}_in"}  ?? '');
                                    $outVal = old("{$key}_out", $office_shift->{"{$key}_out"} ?? '');
                                    $inTime  = $inVal  ? date('H:i', strtotime($inVal))  : '';
                                    $outTime = $outVal ? date('H:i', strtotime($outVal)) : '';
                                    $isOff   = ($inTime === '' && $outTime === '');
                                    $isWeekend = in_array($key, ['saturday', 'sunday']);
                                @endphp
                                <tr class="shift-row {{ $isWeekend ? 'weekend-row' : '' }} {{ $isOff ? 'off-row' : '' }}" data-day="{{ $key }}">
                                    <td>
                                        <span class="shift-day-name">{{ $label }}</span>
                                        <span class="off-day-badge" style="{{ $isOff ? '' : 'display:none;' }}">Off Day</span>
                                    </td>
                                    <td>
                                        <input type="time" name="{{ $key }}_in" class="form-control shift-time-in"
                                               value="{{ $inTime }}" {{ $isOff ? 'disabled' : '' }} />
                                    </td>
                                    <td>
                                        <input type="time" name="{{ $key }}_out" class="form-control shift-time-out"
                                               value="{{ $outTime }}" {{ $isOff ? 'disabled' : '' }} />
                                    </td>
                                    <td class="text-center">
                                        <input type="checkbox" class="shift-off-toggle"
                                               data-day="{{ $key }}"
                                               {{ $isOff ? 'checked' : '' }}
                                               title="Mark as day off" />
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="box-footer text-right">
                <a href="{{ route('hrm.office_shifts.index') }}" class="btn btn-default">Cancel</a>
                <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Update Shift</button>
            </div>
        </form>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.shift-off-toggle').forEach(function (cb) {
        cb.addEventListener('change', function () {
            var day  = this.dataset.day;
            var row  = document.querySelector('.shift-row[data-day="' + day + '"]');
            var inEl  = row.querySelector('.shift-time-in');
            var outEl = row.querySelector('.shift-time-out');
            if (this.checked) {
                inEl.value   = '';
                outEl.value  = '';
                inEl.disabled  = true;
                outEl.disabled = true;
                row.classList.add('off-row');
            } else {
                inEl.disabled  = false;
                outEl.disabled = false;
                 if (!inEl.value)  inEl.value  = '08:00';
                 if (!outEl.value) outEl.value = '17:00';
                 row.classList.remove('off-row');
                 inEl.focus();
            }

            var badge = row.querySelector('.off-day-badge');
            if (badge) {
                badge.style.display = this.checked ? '' : 'none';
            }
        });
    });

    // Re-enable disabled inputs before submit so values (empty) are submitted
    document.querySelector('form').addEventListener('submit', function () {
        document.querySelectorAll('.shift-time-in, .shift-time-out').forEach(function (el) {
            el.disabled = false;
        });
    });
});
</script>
@endsection
