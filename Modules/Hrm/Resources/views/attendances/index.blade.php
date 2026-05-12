@extends('layouts.app')

@section('title', __('ui.attendance'))

@section('content')
@include('hrm::partials.hrm_page_header', [
    'title' => __('ui.attendance'),
    'subtitle' => __('ui.track_employee_clock_in_and_clock_out_records'),
    'actions' => '<a href="'.route('hrm.attendances.create').'" class="btn btn-primary"><i class="fa fa-plus"></i> '. __('ui.create_attendance') .'</a>'
])

<section class="content">
    <div class="box box-primary">
        <div class="box-header with-border">
            <h2 class="box-title h3">{{ __('ui.attendance_register') }}</h2>
            <div class="box-tools pull-right">
                <span class="label label-info">{{ __('ui.total_records') }} {{ $totalRows ?? 0 }}</span>
            </div>
        </div>
        <div class="box-body table-responsive">
            <table class="table table-hover table-striped">
                <thead>
                    <tr>
                        <th>{{ __('ui.date') }}</th>
                        <th>{{ __('ui.employee') }}</th>
                        <th>{{ __('ui.company') }}</th>
                        <th>{{ __('ui.clock_in') }}</th>
                        <th>{{ __('ui.clock_out') }}</th>
                        <th>{{ __('ui.total_work') }}</th>
                        <th class="text-right">{{ __('ui.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($attendances as $attendance)
                        <tr>
                            <td>{{ !empty($attendance['date']) ? \Carbon\Carbon::parse($attendance['date'])->format('d m Y') : '-' }}</td>
                            <td>{{ $attendance['employee_username'] ?: '-' }}</td>
                            <td>{{ $attendance['company_name'] ?: '-' }}</td>
                            <td>{{ $attendance['clock_in'] ?: '-' }}</td>
                            <td>{{ $attendance['clock_out'] ?: '-' }}</td>
                            <td>{{ $attendance['total_work'] ?: '-' }}</td>
                            <td class="text-right">
                                <a href="{{ route('hrm.attendances.edit', $attendance['id']) }}" class="btn btn-sm btn-default">{{ __('ui.edit') }}</a>
                                <form action="{{ route('hrm.attendances.destroy', $attendance['id']) }}" method="POST" style="display:inline-block" data-hrm-confirm="{{ __('ui.delete_this_attendance') }}" data-hrm-confirm-title="{{ __('ui.delete_attendance') }}">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-danger">{{ __('ui.delete') }}</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted">{{ __('ui.no_attendance_records_found') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</section>
@endsection