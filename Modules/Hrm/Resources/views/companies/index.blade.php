@extends('layouts.app')

@section('title', __('lang_v1.companies'))

@section('content')
@include('hrm::partials.hrm_page_header', [
    'title' => __('lang_v1.companies'),
    'subtitle' => __('lang_v1.maintain_company_records'),
    'actions' => (!empty($currentBusiness)
        ? '<form action="'.route('hrm_admin.companies.store').'" method="POST" style="display:inline-block;margin-right:8px;">'
            .csrf_field().
            '<input type="hidden" name="use_business_details" value="1">'
                        .
            '<input type="hidden" name="source_business_id" value="'.$currentBusiness->id.'">'
                        .
            '<button type="submit" class="btn btn-default">'.e(__('lang_v1.use_current_business')).' ('.e($currentBusiness->name).')</button>'
          .'</form>'
        : '')
        .'<a href="'.route('hrm_admin.companies.create').'" class="btn btn-primary"><i class="fa fa-plus"></i> '.e(__('lang_v1.add_company')).'</a>'
])

<section class="content">
    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title">@lang('lang_v1.company_directory')</h3>
            <div class="box-tools pull-right">
                <span class="label label-primary">{{ __('lang_v1.shown_count', ['count' => isset($companies) ? count($companies) : 0]) }}</span>
            </div>
        </div>
        <div class="box-body no-padding">
            <div class="table-responsive">
                <table class="table table-hover table-striped mb-0">
                    <thead>
                        <tr>
                            <th>@lang('messages.name')</th>
                            <th>@lang('business.email')</th>
                            <th>@lang('contact.phone')</th>
                            <th>@lang('business.country')</th>
                            <th class="text-end">@lang('messages.actions')</th>
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
                                    <a href="{{ route('hrm_admin.companies.edit', $c->id) }}" class="btn btn-sm btn-default">@lang('messages.edit')</a>
                                    <form action="{{ route('hrm_admin.companies.destroy', $c->id) }}" method="POST" style="display:inline-block" data-hrm-confirm="{{ __('lang_v1.delete_company_confirm') }}" data-hrm-confirm-title="{{ __('lang_v1.delete_company') }}">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-danger">@lang('messages.delete')</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted">
                                    <p style="margin-bottom: 0;">@lang('lang_v1.no_companies_found')</p>
                                    @if(!empty($currentBusiness))
                                        <form action="{{ route('hrm_admin.companies.store') }}" method="POST" style="display:inline-block">
                                            @csrf
                                            <input type="hidden" name="use_business_details" value="1">
                                            <input type="hidden" name="source_business_id" value="{{ $currentBusiness->id }}">
                                            <button type="submit" class="btn btn-sm btn-default">
                                                {{ __('lang_v1.use_current_business') }} ({{ $currentBusiness->name }})
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
