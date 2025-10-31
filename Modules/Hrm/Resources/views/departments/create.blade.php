@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Create Department</h2>
    <form method="POST" action="{{ route('hrm.departments.store') }}">
        @csrf

        <div class="form-group mb-3">
            <label for="department">Department</label>
            <input type="text" name="department" id="department" class="form-control" value="{{ old('department') }}" placeholder="e.g. Sales, Support" required />
        </div>

        <div class="form-group mb-3">
            <label for="company_id">Company</label>
            <select name="company_id" id="company_id" class="form-control" required>
                @foreach($companies as $c)
                    @php
                        $isBusiness = isset($c->business_id) && $c->business_id == session('business.id');
                        $label = $isBusiness ? 'Business - ' . $c->name : $c->name;
                    @endphp
                    <option value="{{ $c->id }}" @if(old('company_id', isset($default_company_id) ? $default_company_id : null) == $c->id) selected @endif>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div class="form-group mb-3">
            <label for="department_head">Department Head (optional)</label>
            @if(isset($employees) && count($employees) > 0)
                <div class="d-flex gap-2">
                    <select name="department_head" id="department_head" class="form-control">
                        <option value="">-- None --</option>
                        @foreach($employees as $e)
                            @php $empLabel = $e->username ?? trim((($e->firstname ?? '') . ' ' . ($e->lastname ?? ''))); @endphp
                            <option value="{{ $e->id }}" @if(old('department_head') == $e->id) selected @endif>{{ $empLabel ?: 'Employee #'.$e->id }}</option>
                        @endforeach
                    </select>
                    <a href="{{ route('hrm.employees.create') }}" class="btn btn-outline-secondary" target="_blank" rel="noopener">Add Employee</a>
                </div>
            @else
                <div class="d-flex align-items-center gap-2">
                    <div class="text-muted">No employees available to select as head.</div>
                    <a href="{{ route('hrm.employees.create') }}" class="btn btn-primary" target="_blank" rel="noopener">Add Department Head</a>
                </div>
            @endif
            @error('department_head')
                <div class="text-danger small">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-group mb-3">
            <label for="description">Description (optional)</label>
            <textarea name="description" id="description" class="form-control">{{ old('description') }}</textarea>
        </div>

        @error('department')
            <div class="text-danger small">{{ $message }}</div>
        @enderror

        <button class="btn btn-primary">Save</button>
        <a href="{{ route('hrm.departments.index') }}" class="btn btn-secondary ms-2">Cancel</a>
    </form>
</div>
@endsection

    @push('scripts')
    <script>
        // Debug AJAX POST for departments
        (function(){
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'btn btn-outline-primary mt-2';
            btn.id = 'ajax-departments-debug';
            btn.innerText = 'Send debug POST';
            var container = document.querySelector('.container');
            container.appendChild(btn);
            var out = document.createElement('pre'); out.id = 'ajax-departments-result'; out.style.whiteSpace = 'pre-wrap'; container.appendChild(out);
            btn.addEventListener('click', function(){
                var form = document.querySelector('form');
                var fd = new FormData(form);
                fetch("{{ route('hrm.departments.debug') }}", {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    body: fd
                }).then(r=>r.json().catch(()=>r.text())).then(function(d){ out.textContent = JSON.stringify(d, null, 2); }).catch(function(e){ out.textContent = 'Error: '+e; });
            });
        })();
    </script>
    @endpush
