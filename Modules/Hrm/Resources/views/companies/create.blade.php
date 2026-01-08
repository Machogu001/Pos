@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Create Company</h2>
    <form action="{{ route('hrm.companies.store') }}" method="POST">
        {{ csrf_field() }}
        @include('hrm::partials.hrm_form_toolbar')

        <div class="form-group">
            <label for="name">Name</label>
            <input type="text" name="name" id="name" class="form-control" placeholder="Company name" required />
        </div>

        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" name="email" id="email" class="form-control" placeholder="contact@company.com" />
        </div>

        <div class="form-group">
            <label for="phone">Phone</label>
            <input type="text" name="phone" id="phone" class="form-control" placeholder="07xxxxxxxx" />
        </div>

        <div class="form-group">
            <label for="country">Country</label>
            <input type="text" name="country" id="country" class="form-control" placeholder="Country name" />
        </div>

    
    </form>

    <hr />
    <h5>Debug: quick AJAX POST (does not replace normal form)</h5>
    <button id="ajax-debug-btn" class="btn btn-outline-primary">Send debug POST</button>
    <div id="ajax-debug-result" class="mt-2"></div>

    @push('scripts')
    <script>
        document.getElementById('ajax-debug-btn').addEventListener('click', function() {
            var form = document.querySelector('form');
            var fd = new FormData(form);
            // Use the debug named route
            fetch("{{ route('hrm.companies.debug') }}", {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body: fd
            }).then(function(r){ return r.json().catch(function(){ return r.text(); }); })
            .then(function(data){
                document.getElementById('ajax-debug-result').innerText = JSON.stringify(data, null, 2);
            }).catch(function(err){
                document.getElementById('ajax-debug-result').innerText = 'Error: ' + err;
            });
        });
    </script>
    @endpush
</div>
@endsection
