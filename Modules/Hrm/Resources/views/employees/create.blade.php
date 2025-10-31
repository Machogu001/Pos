@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Create Employee</h2>
    <form method="POST" action="{{ route('hrm.employees.store') }}">
        @csrf
        <div class="form-group">
            <label>First name</label>
            <input name="firstname" class="form-control" placeholder="First name" required />
        </div>
        <div class="form-group">
            <label>Last name</label>
            <input name="lastname" class="form-control" placeholder="Last name" />
        </div>
        <div class="form-group">
            <label>Gender</label>
            <select name="gender" class="form-control">
                <option value="Male">Male</option>
                <option value="Female">Female</option>
                <option value="Other">Other</option>
            </select>
        </div>
        <div class="form-group">
            <label>Company</label>
                <select name="company_id" id="company_id" class="form-control select2">
                    @foreach($companies as $c)
                        <option value="{{ $c->id }}">
                            @if($c->business_id)
                                <span class="badge badge-info">Business</span>
                            @endif
                            {{ $c->name }}
                        </option>
                    @endforeach
                </select>
        @include('hrm::partials.field_error', ['field' => 'company_id'])
        </div>
        <div class="form-group">
            <label>Department <small class="text-muted">(optional)</small></label>
            <select name="department_id" class="form-control">
                <option value="">-- Select Department (optional) --</option>
                @foreach($departments as $d)
                    <option value="{{ $d->id }}">{{ $d->department }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-group">
            <label>Designation <small class="text-muted">(optional)</small></label>
            <select name="designation_id" class="form-control">
                <option value="">-- Select Designation (optional) --</option>
                @foreach($designations as $d)
                    <option value="{{ $d->id }}">{{ $d->designation }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-group">
            <label>Office shift <small class="text-muted">(optional)</small></label>
            <select name="office_shift_id" class="form-control">
                <option value="">-- Select Office Shift (optional) --</option>
                @foreach($office_shifts as $os)
                    <option value="{{ $os->id }}">{{ $os->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-group">
            <label>Email</label>
            <input name="email" type="email" class="form-control" placeholder="user@example.com" />
        </div>
        <div class="form-group">
            <label>Phone</label>
            <input name="phone" class="form-control" placeholder="07xxxxxxxx" />
        </div>
        <button class="btn btn-primary">Save</button>
    </form>
</div>
@endsection

@push('scripts')
<script>
    // Debug AJAX POST for employees
    document.addEventListener('DOMContentLoaded', function(){
        var btn = document.createElement('button'); btn.type = 'button'; btn.className = 'btn btn-outline-primary mt-2'; btn.id = 'ajax-employees-debug'; btn.innerText = 'Send debug POST';
        var container = document.querySelector('.container'); container.appendChild(btn);
        var out = document.createElement('pre'); out.id = 'ajax-employees-result'; out.style.whiteSpace = 'pre-wrap'; container.appendChild(out);
        btn.addEventListener('click', function(){
            var form = document.querySelector('form'); var fd = new FormData(form);
            fetch("{{ route('hrm.employees.debug') }}", { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }, body: fd })
            .then(r=>r.json().catch(()=>r.text())).then(function(d){ out.textContent = JSON.stringify(d, null, 2); }).catch(function(e){ out.textContent = 'Error: '+e; });
        });
    });
</script>
@endpush
