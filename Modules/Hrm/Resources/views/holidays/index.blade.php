@extends('layouts.app')

@section('title', 'Holidays')

@section('content')
@include('hrm::partials.hrm_page_header', [
    'title' => 'Holidays',
    'subtitle' => 'Manage company holiday calendars.',
    'actions' => '<a href="'.route('hrm.holidays.create').'" class="btn btn-primary"><i class="fa fa-plus"></i> Create Holiday</a>'
])

<section class="content">
    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title">Holiday Register</h3>
            <div class="box-tools pull-right">
                <span class="label label-info">Total holidays: {{ $totalRows ?? 0 }}</span>
            </div>
        </div>
        <div class="box-body table-responsive">
            <table class="table table-hover table-striped">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Company</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                        <th>Description</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($holidays as $holiday)
                        <tr>
                            <td>{{ $holiday['title'] }}</td>
                            <td>{{ $holiday['company_name'] ?: '-' }}</td>
                            <td>{{ $holiday['start_date'] }}</td>
                            <td>{{ $holiday['end_date'] }}</td>
                            <td>{{ $holiday['description'] ?: '-' }}</td>
                            <td class="text-right">
                                <a href="{{ route('hrm.holidays.edit', $holiday['id']) }}" class="btn btn-sm btn-default">Edit</a>
                                <form action="{{ route('hrm.holidays.destroy', $holiday['id']) }}" method="POST" style="display:inline-block" data-hrm-confirm="Delete this holiday?" data-hrm-confirm-title="Delete Holiday">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted">No holidays found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</section>
@endsection