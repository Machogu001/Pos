@extends('layouts.app')

@section('title', 'Companies')

@section('content')
@include('hrm::partials.hrm_page_header', [
    'title' => 'Companies',
    'subtitle' => 'Maintain the business records used across HRM forms, payroll, and reporting.',
    'actions' => (!empty($currentBusiness)
        ? '<form action="'.route('hrm.companies.store').'" method="POST" style="display:inline-block;margin-right:8px;">'
            .csrf_field().
            '<input type="hidden" name="use_business_details" value="1">'
                        .
            '<input type="hidden" name="source_business_id" value="'.$currentBusiness->id.'">'
                        .
            '<button type="submit" class="btn btn-default">Use Current Business ('.e($currentBusiness->name).')</button>'
          .'</form>'
        : '')
        .'<a href="'.route('hrm.companies.create').'" class="btn btn-primary"><i class="fa fa-plus"></i> Add Company</a>'
])

<section class="content">
    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title">Company Directory</h3>
            <div class="box-tools pull-right">
                <span class="label label-primary">{{ isset($companies) ? count($companies) : 0 }} shown</span>
            </div>
        </div>
        <div class="box-body no-padding">
            <div class="table-responsive">
                <table class="table table-hover table-striped mb-0">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Country</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($companies as $c)
                            <tr>
                                <td><strong>{{ $c->name }}</strong></td>
                                <td>{{ $c->email }}</td>
                                <td>{{ $c->phone }}</td>
                                <td>{{ $c->country }}</td>
                                <td class="text-end">
                                    <a href="{{ route('hrm.companies.edit', $c->id) }}" class="btn btn-sm btn-default">Edit</a>
                                    <form action="{{ route('hrm.companies.destroy', $c->id) }}" method="POST" style="display:inline-block" data-hrm-confirm="Delete this company?" data-hrm-confirm-title="Delete Company">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-danger">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted">
                                    <p style="margin-bottom: 0;">No companies found.</p>
                                    @if(!empty($currentBusiness))
                                        <form action="{{ route('hrm.companies.store') }}" method="POST" style="display:inline-block">
                                            @csrf
                                            <input type="hidden" name="use_business_details" value="1">
                                            <input type="hidden" name="source_business_id" value="{{ $currentBusiness->id }}">
                                            <button type="submit" class="btn btn-sm btn-default">
                                                Use Current Business ({{ $currentBusiness->name }})
                                            </button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @if(isset($totalRows) && $totalRows > (int)($perPage ?? 0) && isset($paginator))
        <div class="text-right">
            {{ $paginator->appends(request()->query())->links() }}
        </div>
    @endif
</section>
@endsection
