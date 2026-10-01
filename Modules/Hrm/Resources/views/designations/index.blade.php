@extends('layouts.app')

@section('title', __('ui.designation_register'))

@section('content')
@include('hrm::partials.hrm_page_header', [
    'title' => __('ui.designation_register'),
    'subtitle' => __('ui.maintain_job_titles_and_map_them_to_departments_and_companies'),
    'actions' => '<a href="'.route('hrm.designations.create').'" class="btn btn-primary"><i class="fa fa-plus"></i> '. __('ui.add_designation') .'</a>'
])

<section class="content">
    <div class="box box-primary">
        <div class="box-body no-padding">
            <div class="table-responsive">
                <table class="table table-hover table-striped mb-0">
                    <thead>
                        <tr>
                            <th>{{ __('ui.designation') }}</th>
                            <th>{{ __('ui.company') }}</th>
                            <th>{{ __('ui.department') }}</th>
                            <th class="text-end">{{ __('ui.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($designations_for_view ?? [] as $d)
                            <tr>
                                <td><strong>{{ $d['designation'] }}</strong></td>
                                <td>{{ $d['company_name'] }}</td>
                                <td>{{ $d['department_name'] }}</td>
                                <td class="text-end">
                                    <a href="{{ route('hrm.designations.edit', $d['id']) }}" class="btn btn-sm btn-default">{{ __('ui.edit') }}</a>
                                    <form action="{{ route('hrm.designations.destroy', $d['id']) }}" method="POST" style="display:inline-block">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-danger" data-hrm-confirm-submit="1" data-hrm-confirm="{{ __('ui.delete_this_designation') }}" data-hrm-confirm-title="{{ __('ui.delete_designation') }}">{{ __('ui.delete') }}</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted">{{ __('ui.no_designations_found') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>
@endsection
