@extends('layouts.app')

@section('title', 'Create Employee')

@section('content')
@include('hrm::partials.hrm_page_header', [
    'title' => 'Create Employee',
    'subtitle' => 'Register staff with department, designation, shift, and leave entitlement details.',
    'actions' => '<a href="'.route('hrm.employees.index').'" class="btn btn-default"><i class="fa fa-arrow-left"></i> Back to Employees</a>'
])

<section class="content">
    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title">Employee Profile</h3>
        </div>
        <form method="POST" action="{{ route('hrm.employees.store') }}">
            @csrf
            @include('hrm::partials.hrm_form_toolbar')
            <div class="box-body">
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>First name</label>
                            <input name="firstname" class="form-control" placeholder="First name" required />
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Last name</label>
                            <input name="lastname" class="form-control" placeholder="Last name" />
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Gender</label>
                            <select name="gender" class="form-control">
                                <option value="male">Male</option>
                                <option value="female">Female</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Company</label>
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
                            <label>Email</label>
                            <input name="email" type="email" class="form-control" placeholder="user@example.com" />
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Department <small class="text-muted">optional</small></label>
                            <select name="department_id" class="form-control">
                                <option value="">-- Select Department (optional) --</option>
                                @foreach($departments as $d)
                                    <option value="{{ $d->id }}">{{ $d->department }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Designation <small class="text-muted">optional</small></label>
                            <select name="designation_id" class="form-control">
                                <option value="">-- Select Designation (optional) --</option>
                                @foreach($designations as $d)
                                    <option value="{{ $d->id }}">{{ $d->designation }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Office shift <small class="text-muted">optional</small></label>
                            <select name="office_shift_id" class="form-control">
                                <option value="">-- Select Office Shift (optional) --</option>
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
                            <label>Phone</label>
                            <input name="phone" class="form-control" placeholder="07xxxxxxxx" />
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Total annual leave (days)</label>
                            <input id="total_leave" name="total_leave" type="number" min="0" step="1" class="form-control" value="{{ old('total_leave', config('hrm.default_annual_leave', 21)) }}" />
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Remaining leave (days)</label>
                            <input id="remaining_leave" name="remaining_leave" type="number" min="0" step="1" class="form-control" value="{{ old('remaining_leave', config('hrm.default_annual_leave', 21)) }}" />
                        </div>
                    </div>
                </div>

                <p class="help-block">If remaining leave is left empty it will be initialized to the total entitlement.</p>
            </div>
            <div class="box-footer text-right">
                <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Save Employee</button>
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
                help.textContent = '{{ addslashes("If left empty the remaining leave will be initialized to the total entitlement.") }}';
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
            if (rem < 0) { remEl.value = 0; showRemainingMessage('Remaining cannot be negative', true); }
            if (rem > total) { remEl.value = total; showRemainingMessage('Remaining cannot exceed total; adjusted to total.', true); }
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
