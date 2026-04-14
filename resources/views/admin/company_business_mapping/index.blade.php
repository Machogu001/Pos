@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Company ↔ Business Mapping</h2>

    <table class="table">
        <thead>
            <tr>
                <th>Company</th>
                <th>Linked Business</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            @foreach($companies as $c)
                <tr>
                    <td>
                        {{ $c->name }}
                        @if(isset($c->business_id) && $c->business_id)
                            <span class="tw-inline-flex tw-items-center tw-ml-2 tw-bg-primary-100 tw-text-primary-800 tw-text-xs tw-font-medium tw-rounded tw-px-2.5 tw-py-0.5">Business</span>
                        @endif
                    </td>
                    <td>
                        <form method="POST" action="{{ url('admin/company-business-mapping/'.$c->id) }}">
                            @csrf
                            @method('POST')
                            <select name="business_id" class="form-control" style="display:inline-block; width:auto;">
                                <option value="">-- None --</option>
                                @foreach($businesses as $b)
                                    <option value="{{ $b->id }}" @if($c->business_id == $b->id) selected @endif>{{ $b->name }}</option>
                                @endforeach
                            </select>
                            <button class="btn btn-sm btn-primary ms-2">Save</button>
                        </form>
                    </td>
                    <td>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
