@extends('layouts.app')

@section('title', __('ui.create_employee'))

@section('content')
@include('hrm::partials.hrm_page_header', [
    'title' => __('ui.create_employee'),
    'subtitle' => __('ui.register_staff_with_department_designation_shift_and_leave_entitlement_details'),
    'actions' => '<a href="'.route('hrm.employees.index').'" class="btn btn-default"><i class="fa fa-arrow-left"></i> '. __('ui.back_to_employees') .'</a>'
])

<section class="content">
    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title">{{ __('ui.employee_profile') }}</h3>
        </div>
        <form method="POST" action="{{ route('hrm.employees.store') }}">
            @csrf
            @include('hrm::partials.hrm_form_toolbar')
            <div class="box-body">
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>{{ __('ui.first_name') }}</label>
                            <input name="firstname" class="form-control" placeholder="{{ __('ui.first_name') }}" required />
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>{{ __('ui.last_name') }}</label>
                            <input name="lastname" class="form-control" placeholder="{{ __('ui.last_name') }}" />
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>{{ __('ui.gender') }}</label>
                            <select name="gender" class="form-control">
                                <option value="male">{{ __('ui.male') }}</option>
                                <option value="female">{{ __('ui.female') }}</option>
                                <option value="other">{{ __('ui.other') }}</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>{{ __('ui.company') }}</label>
                            <select name="company_id" id="company_id" class="form-control select2">
                                @foreach($companies as $c)
                                    <option value="{{ $c->id }}">{{ $c->name }}</option>
                                @endforeach
                            </select>
                            @include('hrm::partials.field_error', ['field' => 'company_id'])
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>{{ __('ui.email') }}</label>
                            <input name="email" type="email" class="form-control" placeholder="{{ __('ui.user_example_com') }}" />
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>{{ __('ui.department') }} <small class="text-muted">{{ __('ui.optional') }}</small></label>
                            <select name="department_id" class="form-control">
                                <option value="">{{ __('ui.select_department_optional') }}</option>
                                @foreach($departments as $d)
                                    <option value="{{ $d->id }}">{{ $d->department }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>{{ __('ui.designation') }} <small class="text-muted">{{ __('ui.optional') }}</small></label>
                            <select name="designation_id" class="form-control">
                                <option value="">{{ __('ui.select_designation_optional') }}</option>
                                @foreach($designations as $d)
                                    <option value="{{ $d->id }}">{{ $d->designation }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>{{ __('ui.office_shift') }} <small class="text-muted">{{ __('ui.optional') }}</small></label>
                            <select name="office_shift_id" class="form-control">
                                <option value="">{{ __('ui.select_office_shift_optional') }}</option>
                                @foreach($office_shifts as $os)
                                    <option value="{{ $os->id }}">{{ $os->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>{{ __('ui.phone') }}</label>
                            <input name="phone" class="form-control" placeholder="{{ __('ui.07xxxxxxxx') }}" />
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>{{ __('ui.total_annual_leave_days') }}</label>
                            <input id="total_leave" name="total_leave" type="number" min="0" step="1" class="form-control" value="{{ old('total_leave', config('hrm.default_annual_leave', 21)) }}" />
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>{{ __('ui.remaining_leave_days') }}</label>
                            <input id="remaining_leave" name="remaining_leave" type="number" min="0" step="1" class="form-control" value="{{ old('remaining_leave', config('hrm.default_annual_leave', 21)) }}" />
                        </div>
                    </div>
                </div>

                <p id="remaining_help" class="help-block text-muted">{{ __('ui.if_remaining_leave_is_left_empty_it_will_be_initialized_to_the_total_entitlement') }}</p>
            </div>
            <div class="box-footer text-right">
                <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> {{ __('ui.save_employee') }}</button>
            </div>
        </form>
    </div>
</section>
@endsection

@push('scripts')
<script>
    // Client-side leave validation
    document.addEventListener('DOMContentLoaded', function(){
        // Helper: show brief message under remaining_help
        function showRemainingMessage(msg, isError){
            var help = document.getElementById('remaining_help');
            if (!help) return;
            help.textContent = msg;
            help.classList.toggle('text-danger', !!isError);
            help.classList.toggle('text-muted', !isError);
            setTimeout(function(){
                // restore default help text
                help.textContent = "{{ __('ui.if_remaining_leave_is_left_empty_it_will_be_initialized_to_the_total_entitlement') }}";
                help.classList.remove('text-danger');
                help.classList.add('text-muted');
            }, 3000);
        }

        function enforceLeaveBounds(){
            var totalEl = document.getElementById('total_leave');
            var remEl = document.getElementById('remaining_leave');
            if (!totalEl || !remEl) return;
            var total = parseInt(totalEl.value, 10);
            var rem = parseInt(remEl.value, 10);
            if (isNaN(total)) total = 0;
            if (isNaN(rem)) rem = 0;
            if (rem < 0) { remEl.value = 0; showRemainingMessage("{{ __('ui.remaining_cannot_be_negative') }}", true); }
            if (rem > total) { remEl.value = total; showRemainingMessage("{{ __('ui.remaining_cannot_exceed_total_adjusted_to_total') }}", true); }
        }

        // Attach listeners
        var totalInput = document.getElementById('total_leave');
        var remainingInput = document.getElementById('remaining_leave');
        if (totalInput) totalInput.addEventListener('input', enforceLeaveBounds);
        if (remainingInput) remainingInput.addEventListener('input', enforceLeaveBounds);

        // Ensure bounds on normal submit
        var formEl = document.querySelector('form');
        if (formEl) {
            formEl.addEventListener('submit', function(e){
                enforceLeaveBounds();
                // allow submit; server-side will also validate
            });
        }
    });
</script>
@endpush
