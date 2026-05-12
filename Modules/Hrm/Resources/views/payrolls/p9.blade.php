@php
    function p9Fmt($v){ return number_format((float)$v, 2); }
    $empName = $employee
        ? trim(($employee->firstname ?? '') . ' ' . ($employee->lastname ?? '')) ?: ($employee->username ?? __('ui.n_a'))
        : null;
    $companyName = $company->name ?? '-';
    $hasData = $rows->isNotEmpty() && collect($rows)->sum('gross') > 0;
@endphp

@extends('layouts.app')

@section('title', __('ui.p9_tax_certificate') . ($empName ? ' - ' . $empName : ''))

@section('css')
<style>
    @media print {
        .no-print { display: none !important; }
        body { background: #fff !important; font-size: 11px; }
        .content-wrapper, .main-sidebar, .main-header, footer { display: none !important; }
        .p9-wrap { max-width: 100% !important; }
    }

    .p9-wrap { max-width: 960px; margin: 0 auto; }

    .p9-header {
        background: linear-gradient(120deg, #1a3a5c 0%, #2563a8 100%);
        color: #fff;
        padding: 20px 28px;
        border-radius: 8px 8px 0 0;
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 0;
    }
    .p9-header .co-name { font-size: 18px; font-weight: 700; }
    .p9-header .p9-label { text-align: right; }
    .p9-header .p9-label strong { font-size: 20px; display: block; }
    .p9-header .p9-label span { font-size: 12px; opacity: 0.8; }

    .p9-meta {
        background: #f8fafc;
        border: 1px solid #d6e4f0;
        border-top: none;
        padding: 16px 28px;
        display: flex;
        gap: 40px;
        font-size: 13px;
    }
    .p9-meta dt { color: #6b7280; font-size: 11px; text-transform: uppercase; font-weight: 600; margin-bottom: 2px; }
    .p9-meta dd { font-weight: 700; color: #111; margin: 0 0 10px; }

    .p9-table-wrap {
        border: 1px solid #d6e4f0;
        border-top: none;
        overflow-x: auto;
        border-radius: 0 0 8px 8px;
    }

    .p9-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 12px;
        white-space: nowrap;
    }
    .p9-table th, .p9-table td {
        padding: 7px 10px;
        border: 1px solid #e5e7eb;
        text-align: right;
    }
    .p9-table th:first-child, .p9-table td:first-child { text-align: left; }
    .p9-table thead tr:first-child th {
        background: #1a3a5c;
        color: #fff;
        font-size: 11px;
        letter-spacing: 0.03em;
    }
    .p9-table thead tr:last-child th {
        background: #eaf1fb;
        color: #1f3b57;
        font-size: 10px;
    }
    .p9-table tbody tr:hover { background: #f0f7ff; }
    .p9-table tbody tr.empty-row td { color: #d1d5db; }
    .p9-table tfoot td {
        background: #1a3a5c;
        color: #fff;
        font-weight: 700;
    }

    .p9-note {
        font-size: 11px;
        color: #9ca3af;
        padding: 10px 20px;
        text-align: center;
        border-top: 1px solid #e5e7eb;
        margin-top: 4px;
    }
</style>
@endsection

@section('content')
@include('hrm::partials.hrm_page_header', [
    'title' => __('ui.p9_annual_tax_certificate'),
    'subtitle' => __('ui.employee_annual_income_paye_summary_kra_p9_form'),
    'actions' =>
        '<a href="'.route('hrm.payrolls.index').'" class="btn btn-default no-print"><i class="fa fa-arrow-left"></i> '. __('ui.payrolls') .'</a>
         ' . ($empName ? '<button onclick="window.print()" class="btn btn-primary no-print"><i class="fa fa-print"></i> '. __('ui.print_p9') .'</button>' : '')
])

<section class="content">

    {{-- Filter form --}}
    <div class="box box-default no-print">
        <div class="box-header with-border"><h3 class="box-title">{{ __('ui.select_employee_year') }}</h3></div>
        <div class="box-body">
            <form method="GET" action="{{ route('hrm.payrolls.p9') }}" class="form-inline" style="gap:12px; display:flex; flex-wrap:wrap; align-items:flex-end;">
                <div class="form-group" style="margin-right:12px;">
                    <label class="control-label" style="display:block; margin-bottom:4px;">{{ __('ui.employee') }}</label>
                    <select name="employee_id" class="form-control" required style="min-width:220px;">
                        <option value="">{{ __('ui.select_employee') }}</option>
                        @foreach($employees as $emp)
                        @php $eName = trim(($emp->firstname ?? '') . ' ' . ($emp->lastname ?? '')) ?: $emp->username; @endphp
                        <option value="{{ $emp->id }}" @selected($emp->id == $employeeId)>{{ $eName }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group" style="margin-right:12px;">
                    <label class="control-label" style="display:block; margin-bottom:4px;">{{ __('ui.tax_year') }}</label>
                    <select name="year" class="form-control">
                        @foreach($years as $y)
                        <option value="{{ $y }}" @selected($y == $year)>{{ $y }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="btn btn-primary"><i class="fa fa-search"></i> {{ __('ui.generate_p9') }}</button>
            </form>
        </div>
    </div>

    @if($employee)
    <div class="p9-wrap">

        {{-- P9 Header --}}
        <div class="p9-header">
            <div>
                <div class="co-name">{{ $companyName }}</div>
                <div style="font-size:12px; margin-top:4px; opacity:0.8;">{{ __('ui.annual_paye_tax_certificate') }}</div>
            </div>
            <div class="p9-label">
                <strong>{{ __('ui.p9_form') }}</strong>
                <span>{{ __('ui.tax_year_2') }} {{ $year }}</span>
            </div>
        </div>

        {{-- Employee meta --}}
        <div class="p9-meta">
            <dl>
                <dt>{{ __('ui.employee_name') }}</dt>
                <dd>{{ $empName }}</dd>
            </dl>
            <dl>
                <dt>{{ __('ui.employee_id') }}</dt>
                <dd>#{{ $employee->id }}</dd>
            </dl>
            <dl>
                <dt>{{ __('ui.designation') }}</dt>
                <dd>{{ optional($employee->designation)->name ?? '-' }}</dd>
            </dl>
            <dl>
                <dt>{{ __('ui.department') }}</dt>
                <dd>{{ optional($employee->department)->name ?? '-' }}</dd>
            </dl>
            <dl>
                <dt>{{ __('ui.joining_date') }}</dt>
                <dd>{{ $employee->joining_date ? \Carbon\Carbon::parse($employee->joining_date)->format('d M Y') : '-' }}</dd>
            </dl>
        </div>

        {{-- P9 Table --}}
        <div class="p9-table-wrap">
            @if($hasData)
            <table class="p9-table">
                <thead>
                    <tr>
                        <th rowspan="2">{{ __('ui.month') }}</th>
                        <th colspan="2">{{ __('ui.earnings_kes') }}</th>
                        <th colspan="3">{{ __('ui.deductions_kes') }}</th>
                        <th colspan="3">{{ __('ui.tax_computation_kes') }}</th>
                        <th rowspan="2">{{ __('ui.net_pay') }}</th>
                    </tr>
                    <tr>
                        <th>{{ __('ui.basic_pay') }}</th>
                        <th>{{ __('ui.gross_pay') }}</th>
                        <th>NSSF</th>
                        <th>SHIF</th>
                        <th>{{ __('ui.housing_levy_2') }}</th>
                        <th>{{ __('ui.taxable_pay') }}</th>
                        <th>{{ __('ui.income_tax') }}</th>
                        <th>P.A.Y.E</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($rows as $row)
                    @php $isEmpty = ($row['gross'] == 0); @endphp
                    <tr class="{{ $isEmpty ? 'empty-row' : '' }}">
                        <td>{{ $row['month_name'] }}</td>
                        <td>{{ $isEmpty ? '-' : p9Fmt($row['basic_pay']) }}</td>
                        <td>{{ $isEmpty ? '-' : p9Fmt($row['gross']) }}</td>
                        <td>{{ $isEmpty ? '-' : p9Fmt($row['nssf']) }}</td>
                        <td>{{ $isEmpty ? '-' : p9Fmt($row['shif']) }}</td>
                        <td>{{ $isEmpty ? '-' : p9Fmt($row['housing_levy']) }}</td>
                        <td>{{ $isEmpty ? '-' : p9Fmt($row['taxable_pay']) }}</td>
                        <td>{{ $isEmpty ? '-' : p9Fmt($row['income_tax']) }}</td>
                        <td>{{ $isEmpty ? '-' : p9Fmt($row['paye']) }}</td>
                        <td>{{ $isEmpty ? '-' : p9Fmt($row['net']) }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td><strong>{{ __('ui.totals_2') }}</strong></td>
                        <td>{{ p9Fmt($annualTotals['basic_pay'] ?? 0) }}</td>
                        <td>{{ p9Fmt($annualTotals['gross'] ?? 0) }}</td>
                        <td>{{ p9Fmt($annualTotals['nssf'] ?? 0) }}</td>
                        <td>{{ p9Fmt($annualTotals['shif'] ?? 0) }}</td>
                        <td>{{ p9Fmt($annualTotals['housing_levy'] ?? 0) }}</td>
                        <td>{{ p9Fmt($annualTotals['taxable_pay'] ?? 0) }}</td>
                        <td>{{ p9Fmt($annualTotals['income_tax'] ?? 0) }}</td>
                        <td>{{ p9Fmt($annualTotals['paye'] ?? 0) }}</td>
                        <td>{{ p9Fmt($annualTotals['net'] ?? 0) }}</td>
                    </tr>
                </tfoot>
            </table>
            @else
            <div style="padding:40px; text-align:center; color:#9ca3af;">
                <i class="fa fa-inbox fa-2x" style="display:block; margin-bottom:8px;"></i>
                {{ __('ui.no_payroll_records_found_for') }} <strong>{{ $empName }}</strong> {{ __('ui.in') }} {{ $year }}.
            </div>
            @endif
        </div>

        <div class="p9-note">
            {{ __('ui.this_p9_certificate_is_computer_generated_from_payroll_records') }} &middot;
            {{ $companyName }} &middot; {{ __('ui.generated') }} {{ now()->format('d M Y') }}
        </div>

    </div>
    @endif

</section>
@endsection
