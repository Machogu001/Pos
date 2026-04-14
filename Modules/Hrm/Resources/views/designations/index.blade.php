@extends('layouts.app')

@section('title', 'Designations')

@section('content')
@include('hrm::partials.hrm_page_header', [
    'title' => 'Designations',
    'subtitle' => 'Maintain job titles and map them to departments and companies.',
    'actions' => '<a href="'.route('hrm.designations.create').'" class="btn btn-primary"><i class="fa fa-plus"></i> Add Designation</a>'
])

<section class="content">
    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title">Designation Register</h3>
        </div>
        <div class="box-body no-padding">
            <div class="table-responsive">
                <table class="table table-hover table-striped mb-0">
                    <thead>
                        <tr>
                            <th>Designation</th>
                            <th>Company</th>
                            <th>Department</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($designations_for_view ?? [] as $d)
                            <tr>
                                <td><strong>{{ $d['designation'] }}</strong></td>
                                <td>{{ $d['company_name'] }}</td>
                                <td>{{ $d['department_name'] }}</td>
                                <td class="text-end">
                                    <a href="{{ route('hrm.designations.edit', $d['id']) }}" class="btn btn-sm btn-default">Edit</a>
                                    <form action="{{ route('hrm.designations.destroy', $d['id']) }}" method="POST" style="display:inline-block">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-danger" data-hrm-confirm-submit="1" data-hrm-confirm="Delete this designation?" data-hrm-confirm-title="Delete Designation">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted">No designations found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>
@endsection
