@extends('layouts.app')

@section('title', __('ui.payroll'))

@section('content')
@include('hrm::partials.hrm_page_header', [
    'title' => __('ui.payroll'),
    'subtitle' => __('ui.process_salaries_monitor_posting_status_and_review_payroll_history'),
    'actions' => '<a href="'.route('hrm.payrolls.create').'" class="btn btn-success"><i class="fa fa-plus"></i> '. __('ui.create_payroll') .'</a>'
])

<section class="content">
    <div class="box box-success">
        <div class="box-header with-border">
            <h2 class="box-title h3">{{ __('ui.payroll_register') }}</h2>
        </div>
        <div class="box-body">
            <form method="GET" class="form-inline" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap; margin-bottom: 15px;">
                <label for="payroll_company_id" class="sr-only">{{ __('ui.company') }}</label>
                <select id="payroll_company_id" name="company_id" class="form-control" aria-label="{{ __('ui.company_filter') }}">
                    <option value="">{{ __('ui.all_companies_2') }}</option>
                    @foreach($companies as $c)
                        <option value="{{ $c->id }}" {{ request('company_id') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                    @endforeach
                </select>
                <label for="payroll_employee_id" class="sr-only">{{ __('ui.employee') }}</label>
                <select id="payroll_employee_id" name="employee_id" class="form-control" aria-label="{{ __('ui.employee_filter') }}">
                    <option value="">{{ __('ui.all_employees') }}</option>
                    @foreach($employees as $e)
                        @php $label = $e->username ?? trim((($e->firstname ?? '') . ' ' . ($e->lastname ?? ''))); @endphp
                        <option value="{{ $e->id }}" {{ request('employee_id') == $e->id ? 'selected' : '' }}>{{ $label ?: 'Employee #'.$e->id }}</option>
                    @endforeach
                </select>
                <button class="btn btn-default" type="submit">{{ __('ui.filter') }}</button>
            </form>

            @if(isset($payrolls) && $payrolls->count())
                <div class="table-responsive">
                    <table class="table table-hover table-striped">
                        <thead>
                            <tr>
                                <th>{{ __('ui.payroll_no') }}</th>
                                <th>{{ __('ui.employee') }}</th>
                                <th>{{ __('ui.employer') }}</th>
                                <th>{{ __('ui.period') }}</th>
                                <th>{{ __('ui.gross') }}</th>
                                <th>{{ __('ui.deductions') }}</th>
                                <th>{{ __('ui.net') }}</th>
                                <th>{{ __('ui.posting') }}</th>
                                <th class="text-end">{{ __('ui.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($payrolls as $p)
                                <tr>
                                    <td><strong>{{ $p->id }}</strong></td>
                                    <td>{{ $p->employee_name ?? ($p->employee_id ?? '-') }}</td>
                                    <td>{{ $p->company_name ?? ($p->company_id ?? '-') }}</td>
                                    <td>{{ $p->period_start }} - {{ $p->period_end }}</td>
                                    <td>{{ number_format($p->gross, 2) }}</td>
                                    <td>{{ number_format($p->deductions, 2) }}</td>
                                    <td><strong>{{ number_format($p->net, 2) }}</strong></td>
                                    <td>
                                        @if(!empty($p->posted_to_accounts))
                                            <span class="label label-success">{{ __('ui.posted_to_accounts') }}</span>
                                        @else
                                            <span class="label label-warning">{{ __('ui.pending_posting') }}</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route('hrm.payrolls.show', $p->id) }}" class="btn btn-sm btn-info" title="{{ __('ui.view_payslip') }}"><i class="fa fa-file-text"></i> {{ __('ui.payslip') }}</a>
                                        <a href="{{ route('hrm.payrolls.p9', ['employee_id' => $p->employee_id, 'year' => \Carbon\Carbon::parse($p->period_start)->year]) }}" class="btn btn-sm btn-default" title="{{ __('ui.p9_tax_certificate') }}"><i class="fa fa-table"></i> {{ __('ui.p9') }}</a>
                                        <a href="{{ route('hrm.payrolls.edit', $p->id) }}" class="btn btn-sm btn-default"><i class="fa fa-pencil"></i> {{ __('ui.edit') }}</a>
                                        <form action="{{ route('hrm.payrolls.destroy', $p->id) }}" method="POST" style="display:inline-block" data-hrm-confirm="{{ __('ui.delete_this_payroll') }}" data-hrm-confirm-title="{{ __('ui.delete_payroll') }}">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-sm btn-danger"><i class="fa fa-trash"></i> {{ __('ui.delete') }}</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="alert alert-info mb-0">{{ __('ui.no_payrolls_yet') }}</div>
            @endif

            @if(isset($payrolls) && method_exists($payrolls, 'links'))
                <div class="text-right" style="margin-top: 15px;">{{ $payrolls->links() }}</div>
            @endif
        </div>
    </div>
</section>
@endsection
