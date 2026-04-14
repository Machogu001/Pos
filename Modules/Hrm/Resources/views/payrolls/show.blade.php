@php
    function fmtAmt($v){ return number_format((float)$v, 2); }
    $period = $payroll->period_start ? \Carbon\Carbon::parse($payroll->period_start)->format('F Y') : '-';
    $empName = $employee
        ? trim(($employee->firstname ?? '') . ' ' . ($employee->lastname ?? '')) ?: ($employee->username ?? 'N/A')
        : 'N/A';
    $companyName = $company->name ?? '-';
@endphp

@extends('layouts.app')

@section('title', 'Payslip – ' . $empName . ' – ' . $period)

@section('css')
<style>
    @media print {
        .no-print { display: none !important; }
        body { background: #fff !important; }
        .box { box-shadow: none !important; border: none !important; }
        .content-wrapper, .main-sidebar, .main-header, footer { display: none !important; }
    }

    .payslip-card {
        max-width: 780px;
        margin: 0 auto;
        background: #fff;
        border: 1px solid #d6e4f0;
        border-radius: 8px;
        overflow: hidden;
    }

    .payslip-header {
        background: linear-gradient(120deg, #1a3a5c 0%, #2563a8 100%);
        color: #fff;
        padding: 24px 28px;
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
    }

    .payslip-header .company-name { font-size: 20px; font-weight: 700; }
    .payslip-header .payslip-label {
        text-align: right;
        font-size: 13px;
        opacity: 0.85;
    }
    .payslip-header .payslip-label strong { font-size: 22px; display: block; opacity: 1; }

    .payslip-meta {
        display: flex;
        gap: 0;
        border-bottom: 1px solid #d6e4f0;
    }

    .payslip-meta-col {
        flex: 1;
        padding: 16px 20px;
        border-right: 1px solid #d6e4f0;
        font-size: 13px;
    }
    .payslip-meta-col:last-child { border-right: none; }
    .payslip-meta-col dt { color: #6b7280; font-weight: 600; margin-bottom: 2px; font-size: 11px; text-transform: uppercase; }
    .payslip-meta-col dd { color: #111; font-weight: 700; margin: 0 0 8px; }

    .payslip-table-wrap { padding: 20px; }
    .payslip-section-title {
        font-size: 12px;
        font-weight: 700;
        text-transform: uppercase;
        color: #2563a8;
        border-bottom: 2px solid #2563a8;
        padding-bottom: 4px;
        margin-bottom: 8px;
        margin-top: 16px;
    }

    .payslip-table { width: 100%; border-collapse: collapse; font-size: 13px; }
    .payslip-table th, .payslip-table td { padding: 7px 10px; border: 1px solid #e5e7eb; }
    .payslip-table thead th { background: #f3f7ff; color: #374151; font-weight: 700; }
    .payslip-table tfoot td { background: #f3f7ff; font-weight: 700; }
    .payslip-table .text-right { text-align: right; }

    .payslip-net-box {
        margin: 20px;
        background: linear-gradient(120deg, #e7f9ef 0%, #d1fae5 100%);
        border: 1px solid #6ee7b7;
        border-radius: 8px;
        padding: 16px 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .payslip-net-box .label { font-size: 14px; font-weight: 700; color: #065f46; }
    .payslip-net-box .amount { font-size: 26px; font-weight: 800; color: #065f46; }

    .payslip-footer-note {
        background: #f9fafb;
        border-top: 1px solid #e5e7eb;
        padding: 10px 20px;
        font-size: 11px;
        color: #9ca3af;
        text-align: center;
    }
</style>
@endsection

@section('content')
@include('hrm::partials.hrm_page_header', [
    'title' => 'Payslip',
    'subtitle' => $empName . ' · ' . $period,
    'actions' =>
        '<a href="'.route('hrm.payrolls.index').'" class="btn btn-default no-print"><i class="fa fa-arrow-left"></i> Back</a>
         <a href="'.route('hrm.payrolls.p9', ['employee_id' => $payroll->employee_id]).'" class="btn btn-info no-print"><i class="fa fa-file-text"></i> P9 Form</a>
         <button onclick="window.print()" class="btn btn-primary no-print"><i class="fa fa-print"></i> Print Payslip</button>'
])

<section class="content">
<div class="payslip-card">

    {{-- Header --}}
    <div class="payslip-header">
        <div>
            <div class="company-name">{{ $companyName }}</div>
            @if(!empty($company->address))
            <div style="font-size:12px; margin-top:4px; opacity:0.8;">{{ $company->address }}</div>
            @endif
        </div>
        <div class="payslip-label">
            <strong>PAYSLIP</strong>
            {{ $period }}
        </div>
    </div>

    {{-- Employee meta --}}
    <div class="payslip-meta">
        <div class="payslip-meta-col">
            <dl>
                <dt>Employee Name</dt>
                <dd>{{ $empName }}</dd>
                <dt>Employee ID</dt>
                <dd>#{{ $employee->id ?? $payroll->employee_id }}</dd>
            </dl>
        </div>
        <div class="payslip-meta-col">
            <dl>
                <dt>Designation</dt>
                <dd>{{ optional($employee->designation ?? null)->name ?? '-' }}</dd>
                <dt>Department</dt>
                <dd>{{ optional($employee->department ?? null)->name ?? '-' }}</dd>
            </dl>
        </div>
        <div class="payslip-meta-col">
            <dl>
                <dt>Office Shift</dt>
                <dd>{{ optional($employee->office_shift ?? null)->name ?? '-' }}</dd>
                <dt>Period</dt>
                <dd>
                    {{ $payroll->period_start ? \Carbon\Carbon::parse($payroll->period_start)->format('d M Y') : '-' }}
                    –
                    {{ $payroll->period_end ? \Carbon\Carbon::parse($payroll->period_end)->format('d M Y') : '-' }}
                </dd>
            </dl>
        </div>
    </div>

    {{-- Earnings & Deductions --}}
    <div class="payslip-table-wrap">

        <div class="payslip-section-title">Earnings</div>
        <table class="payslip-table">
            <thead>
                <tr>
                    <th>Description</th>
                    <th class="text-right">Amount (KES)</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Basic Pay</td>
                    <td class="text-right">{{ fmtAmt($payroll->basic_pay ?? $payroll->gross ?? 0) }}</td>
                </tr>
                <tr>
                    <td><strong>Gross Pay</strong></td>
                    <td class="text-right"><strong>{{ fmtAmt($payroll->gross ?? 0) }}</strong></td>
                </tr>
            </tbody>
        </table>

        <div class="payslip-section-title">Statutory Deductions</div>
        <table class="payslip-table">
            <thead>
                <tr>
                    <th>Description</th>
                    <th class="text-right">Amount (KES)</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>NSSF (National Social Security Fund)</td>
                    <td class="text-right">{{ fmtAmt($payroll->nssf ?? 0) }}</td>
                </tr>
                <tr>
                    <td>SHIF (Social Health Insurance Fund)</td>
                    <td class="text-right">{{ fmtAmt($payroll->shif ?? 0) }}</td>
                </tr>
                <tr>
                    <td>Housing Levy (Affordable Housing)</td>
                    <td class="text-right">{{ fmtAmt($payroll->housing_levy ?? 0) }}</td>
                </tr>
                <tr style="background:#fff8e6;">
                    <td>Taxable Pay</td>
                    <td class="text-right">{{ fmtAmt($payroll->taxable_pay ?? 0) }}</td>
                </tr>
                <tr>
                    <td>Income Tax</td>
                    <td class="text-right">{{ fmtAmt($payroll->income_tax ?? 0) }}</td>
                </tr>
                <tr>
                    <td>Personal Relief</td>
                    <td class="text-right">({{ fmtAmt($payroll->personal_relief ?? 0) }})</td>
                </tr>
                <tr>
                    <td><strong>P.A.Y.E</strong></td>
                    <td class="text-right"><strong>{{ fmtAmt($payroll->paye ?? 0) }}</strong></td>
                </tr>
            </tbody>
            <tfoot>
                <tr>
                    <td>Pay After Tax</td>
                    <td class="text-right">{{ fmtAmt($payroll->pay_after_tax ?? 0) }}</td>
                </tr>
            </tfoot>
        </table>

        @if(($payroll->deductions ?? 0) > 0)
        <div class="payslip-section-title">Other Deductions</div>
        <table class="payslip-table">
            <tbody>
                <tr>
                    <td>Other Deductions</td>
                    <td class="text-right">{{ fmtAmt($payroll->deductions ?? 0) }}</td>
                </tr>
            </tbody>
        </table>
        @endif

    </div>

    {{-- Net Pay Box --}}
    <div class="payslip-net-box">
        <div class="label"><i class="fa fa-check-circle"></i>&nbsp; NET PAY</div>
        <div class="amount">KES {{ fmtAmt($payroll->net ?? 0) }}</div>
    </div>

    <div class="payslip-footer-note">
        This is a computer-generated payslip. No signature is required. &middot; {{ $companyName }} &middot; {{ now()->format('d M Y') }}
    </div>
</div>
</section>
@endsection
