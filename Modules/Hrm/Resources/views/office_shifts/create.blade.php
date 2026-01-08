@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Create Office Shift</h2>
    <form action="{{ route('hrm.office_shifts.store') }}" method="POST">
        {{ csrf_field() }}
        @include('hrm::partials.hrm_form_toolbar')

        <div class="form-group">
            <label for="name">Name</label>
            <input type="text" name="name" id="name" class="form-control" placeholder="e.g. Morning Shift" required />
        </div>

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

    <h4>Times (optional)</h4>
        <div class="row">
            <div class="col-md-6">
                <label>Monday In</label>
                <input type="time" name="monday_in" class="form-control" />
            </div>
            <div class="col-md-6">
                <label>Monday Out</label>
                <input type="time" name="monday_out" class="form-control" />
            </div>
        </div>
        <!-- Reuse similar fields for other days if needed -->

        
    </form>
</div>
@endsection

@push('scripts')
<script>
    // Debug AJAX POST for office shifts
    (function(){
        var btn = document.createElement('button'); btn.type = 'button'; btn.className = 'btn btn-outline-primary mt-2'; btn.id = 'ajax-office-shifts-debug'; btn.innerText = 'Send debug POST';
        var container = document.querySelector('.container'); container.appendChild(btn);
        var out = document.createElement('pre'); out.id = 'ajax-office-shifts-result'; out.style.whiteSpace = 'pre-wrap'; container.appendChild(out);
        btn.addEventListener('click', function(){
            var form = document.querySelector('form'); var fd = new FormData(form);
            fetch("{{ route('hrm.office_shifts.debug') }}", { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }, body: fd })
            .then(r=>r.json().catch(()=>r.text())).then(function(d){ out.textContent = JSON.stringify(d, null, 2); }).catch(function(e){ out.textContent = 'Error: '+e; });
        });
    })();
</script>
@endpush
