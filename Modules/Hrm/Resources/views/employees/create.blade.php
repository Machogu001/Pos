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
                @if ($errors->any())
                    <div class="alert alert-danger mx-3">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if(!empty($existing_users) && $existing_users->count())
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="existing_user_id">{{ __('ui.system_user') }} <small class="text-muted">{{ __('ui.optional') }}</small></label>
                                <select name="existing_user_id" id="existing_user_id" class="form-control select2">
                                    <option value="">{{ __('ui.user') }}</option>
                                    @foreach($existing_users as $existingUser)
                                        <option value="{{ $existingUser['id'] }}" data-user='@json($existingUser)' @if((string) old('existing_user_id') === (string) $existingUser['id']) selected @endif>{{ $existingUser['label'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                @endif

                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="firstname">{{ __('ui.first_name') }}</label>
                            <input id="firstname" name="firstname" class="form-control" placeholder="{{ __('ui.first_name') }}" required value="{{ old('firstname') }}" />
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="lastname">{{ __('ui.last_name') }}</label>
                            <input id="lastname" name="lastname" class="form-control" placeholder="{{ __('ui.last_name') }}" value="{{ old('lastname') }}" />
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="gender">{{ __('ui.gender') }}</label>
                            <select name="gender" id="gender" class="form-control">
                                <option value="male" @if(old('gender', 'male') === 'male') selected @endif>{{ __('ui.male') }}</option>
                                <option value="female" @if(old('gender') === 'female') selected @endif>{{ __('ui.female') }}</option>
                                <option value="other" @if(old('gender') === 'other') selected @endif>{{ __('ui.other') }}</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="company_id">{{ __('ui.company') }}</label>
                            <select name="company_id" id="company_id" class="form-control select2">
                                @foreach($companies as $c)
                                    <option value="{{ $c->id }}" @if((string) old('company_id') === (string) $c->id) selected @endif>{{ $c->name }}</option>
                                @endforeach
                            </select>
                            @include('hrm::partials.field_error', ['field' => 'company_id'])
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="email">{{ __('ui.email') }}</label>
                            <input id="email" name="email" type="email" class="form-control" placeholder="{{ __('ui.user_example_com') }}" value="{{ old('email') }}" />
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="department_id">{{ __('ui.department') }} <small class="text-muted">{{ __('ui.optional') }}</small></label>
                            <select name="department_id" id="department_id" class="form-control">
                                <option value="">{{ __('ui.select_department_optional') }}</option>
                                @foreach($departments as $d)
                                    <option value="{{ $d->id }}" @if((string) old('department_id') === (string) $d->id) selected @endif>{{ $d->department }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="designation_id">{{ __('ui.designation') }} <small class="text-muted">{{ __('ui.optional') }}</small></label>
                            <select name="designation_id" id="designation_id" class="form-control">
                                <option value="">{{ __('ui.select_designation_optional') }}</option>
                                @foreach($designations as $d)
                                    <option value="{{ $d->id }}" @if((string) old('designation_id') === (string) $d->id) selected @endif>{{ $d->designation }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="office_shift_id">{{ __('ui.office_shift') }} <small class="text-muted">{{ __('ui.optional') }}</small></label>
                            <select name="office_shift_id" id="office_shift_id" class="form-control">
                                <option value="">{{ __('ui.select_office_shift_optional') }}</option>
                                @foreach($office_shifts as $os)
                                    <option value="{{ $os->id }}" @if((string) old('office_shift_id') === (string) $os->id) selected @endif>{{ $os->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="phone">{{ __('ui.phone') }}</label>
                            <input id="phone" name="phone" class="form-control" placeholder="{{ __('ui.07xxxxxxxx') }}" value="{{ old('phone') }}" />
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
    function initEmployeeCreateForm(){
        var existingUsers = @json(!empty($existing_users) ? $existing_users->keyBy('id') : []);
        var existingUserSelect = document.getElementById('existing_user_id');
        var firstNameInput = document.getElementById('firstname');
        var lastNameInput = document.getElementById('lastname');
        var emailInput = document.getElementById('email');
        var phoneInput = document.getElementById('phone');
        var genderInput = document.getElementById('gender');

        function setFieldValue(input, value) {
            if (!input) {
                return;
            }

            input.value = value || '';
            input.dispatchEvent(new Event('input', { bubbles: true }));
            input.dispatchEvent(new Event('change', { bubbles: true }));
        }

        function fillFromExistingUser() {
            if (!existingUserSelect || !existingUserSelect.value) {
                return;
            }

            var user = existingUsers[String(existingUserSelect.value)] || null;
            if (!user) {
                return;
            }

            setFieldValue(firstNameInput, user.firstname || '');
            setFieldValue(lastNameInput, user.lastname || '');
            setFieldValue(emailInput, user.email || '');
            setFieldValue(phoneInput, user.phone || '');
            if (genderInput && user.gender) {
                genderInput.value = user.gender;
                genderInput.dispatchEvent(new Event('change', { bubbles: true }));
            }
        }

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
        if (existingUserSelect) {
            existingUserSelect.addEventListener('change', fillFromExistingUser);
            if (window.jQuery) {
                window.jQuery(existingUserSelect).on('change select2:select', fillFromExistingUser);
            }
            fillFromExistingUser();
        }

        // Ensure bounds on normal submit
        var formEl = document.querySelector('form');
        if (formEl) {
            formEl.addEventListener('submit', function(e){
                enforceLeaveBounds();
                // allow submit; server-side will also validate
            });
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initEmployeeCreateForm, { once: true });
    } else {
        initEmployeeCreateForm();
    }
</script>
@endpush
