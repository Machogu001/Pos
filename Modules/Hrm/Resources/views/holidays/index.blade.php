@extends('layouts.app')

@section('title', __('ui.holidays'))

@section('content')
@include('hrm::partials.hrm_page_header', [
    'title' => __('ui.holidays'),
    'subtitle' => __('ui.manage_company_holiday_calendars'),
    'actions' => '<a href="'.route('hrm.holidays.create').'" class="btn btn-primary"><i class="fa fa-plus"></i> '. __('ui.create_holiday') .'</a>'
])

<section class="content">
    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title">{{ __('ui.holiday_register') }}</h3>
            <div class="box-tools pull-right">
                <span class="label label-info">{{ __('ui.total_holidays') }} {{ $totalRows ?? 0 }}</span>
            </div>
        </div>
        <div class="box-body table-responsive">
            <table class="table table-hover table-striped">
                <thead>
                    <tr>
                        <th>{{ __('ui.title') }}</th>
                        <th>{{ __('ui.company') }}</th>
                        <th>{{ __('ui.start_date') }}</th>
                        <th>{{ __('ui.end_date') }}</th>
                        <th>{{ __('ui.description') }}</th>
                        <th class="text-right">{{ __('ui.actions') }}</th>
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
                                <a href="{{ route('hrm.holidays.edit', $holiday['id']) }}" class="btn btn-sm btn-default">{{ __('ui.edit') }}</a>
                                <form action="{{ route('hrm.holidays.destroy', $holiday['id']) }}" method="POST" style="display:inline-block" data-hrm-confirm="{{ __('ui.delete_this_holiday') }}" data-hrm-confirm-title="{{ __('ui.delete_holiday') }}">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-danger">{{ __('ui.delete') }}</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted">{{ __('ui.no_holidays_found') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</section>
@endsection