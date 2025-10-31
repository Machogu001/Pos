@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Create Designation</h2>
    <form action="{{ route('hrm.designations.store') }}" method="POST">
        {{ csrf_field() }}

        <div class="form-group">
            <label for="designation">Designation</label>
            <input type="text" name="designation" id="designation" class="form-control" placeholder="e.g. Manager, Cashier" required />
            @include('hrm::partials.field_error', ['field' => 'designation'])
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

        <div class="form-group">
            <label for="department">Department</label>
            <select name="department" id="department" class="form-control" required>
                @foreach($departments as $d)
                    <option value="{{ $d->id }}">{{ $d->department }}</option>
                @endforeach
            </select>
            @include('hrm::partials.field_error', ['field' => 'department'])
        </div>

        <button class="btn btn-success">Create</button>
    </form>
</div>
@endsection

@push('scripts')
<script>
    // Debug AJAX POST for designations
    document.addEventListener('DOMContentLoaded', function(){
        var btn = document.createElement('button');
        btn.type = 'button'; btn.className = 'btn btn-outline-primary mt-2'; btn.id = 'ajax-designations-debug'; btn.innerText = 'Send debug POST';
        var container = document.querySelector('.container'); container.appendChild(btn);
        var out = document.createElement('pre'); out.id = 'ajax-designations-result'; out.style.whiteSpace = 'pre-wrap'; container.appendChild(out);
        btn.addEventListener('click', function(){
            var form = document.querySelector('form'); var fd = new FormData(form);
            fetch("{{ route('hrm.designations.debug') }}", {
                method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }, body: fd
            }).then(r=>r.json().catch(()=>r.text())).then(function(d){ out.textContent = JSON.stringify(d, null, 2); }).catch(function(e){ out.textContent = 'Error: '+e; });
        });
    });
</script>
@endpush
