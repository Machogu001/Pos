@extends('layouts.app')

@section('title', 'Create Office Shift')

@section('content')
@include('hrm::partials.hrm_page_header', [
    'title' => 'Create Office Shift',
    'subtitle' => 'Define shift hours and working patterns for a company.',
    'actions' => '<a href="'.route('hrm.office_shifts.index').'" class="btn btn-default"><i class="fa fa-arrow-left"></i> Back to Office Shifts</a>'
])

<section class="content">
    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title">Shift Details</h3>
        </div>
        <form action="{{ route('hrm.office_shifts.store') }}" method="POST">
            {{ csrf_field() }}
            @include('hrm::partials.hrm_form_toolbar')

            <div class="box-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="name">Name</label>
                            <input type="text" name="name" id="name" class="form-control" placeholder="e.g. Morning Shift" required />
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
                                    <option value="{{ $c->id }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <h4 class="tw-font-semibold">Times <small class="text-muted">optional — leave blank for day off</small></h4>
                <table class="table table-bordered table-condensed">
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
                        // Default all days to 08:00–17:00; admin can disable any day via "Off" checkbox
                        $defaults = [
                            'monday'    => ['08:00', '17:00'],
                            'tuesday'   => ['08:00', '17:00'],
                            'wednesday' => ['08:00', '17:00'],
                            'thursday'  => ['08:00', '17:00'],
                            'friday'    => ['08:00', '17:00'],
                            'saturday'  => ['08:00', '17:00'],
                            'sunday'    => ['08:00', '17:00'],
                        ];
                        @endphp
                        @foreach($days as $key => $label)
                        @php
                            $inVal  = old("{$key}_in",  $defaults[$key][0]);
                            $outVal = old("{$key}_out", $defaults[$key][1]);
                            $isOff  = ($inVal === '' && $outVal === '');
                        @endphp
                        <tr class="shift-row" data-day="{{ $key }}">
                            <td><strong>{{ $label }}</strong></td>
                            <td>
                                <input type="time" name="{{ $key }}_in" class="form-control shift-time-in"
                                       value="{{ $inVal }}" {{ $isOff ? 'disabled' : '' }} />
                            </td>
                            <td>
                                <input type="time" name="{{ $key }}_out" class="form-control shift-time-out"
                                       value="{{ $outVal }}" {{ $isOff ? 'disabled' : '' }} />
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

            <div class="box-footer text-right">
                <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Save Shift</button>
            </div>
        </form>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.shift-off-toggle').forEach(function (cb) {
        cb.addEventListener('change', function () {
            var day   = this.dataset.day;
            var row   = document.querySelector('.shift-row[data-day="' + day + '"]');
            var inEl  = row.querySelector('.shift-time-in');
            var outEl = row.querySelector('.shift-time-out');
            if (this.checked) {
                inEl.value    = '';
                outEl.value   = '';
                inEl.disabled  = true;
                outEl.disabled = true;
            } else {
                inEl.disabled  = false;
                outEl.disabled = false;
                 if (!inEl.value)  inEl.value  = '08:00';
                 if (!outEl.value) outEl.value = '17:00';
                 inEl.focus();
            }
        });
    });

    document.querySelector('form').addEventListener('submit', function () {
        document.querySelectorAll('.shift-time-in, .shift-time-out').forEach(function (el) {
            el.disabled = false;
        });
    });
});
</script>
@endsection

