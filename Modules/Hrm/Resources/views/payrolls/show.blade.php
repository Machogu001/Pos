@php
    function fmt($v){ return number_format((float)$v, 2); }
@endphp

@extends('layouts.app')

@section('content')
<div class="container">
    <h3>Payslip for {{ \Carbon\Carbon::parse($payroll->period_start)->format('F Y') }}</h3>

    <table class="table table-bordered" style="max-width:600px">
        <tbody>
            <tr>
                <th>BASIC PAY</th>
                <td class="text-right">{{ fmt($payroll->basic_pay ?? $payroll->gross ?? 0) }}</td>
            </tr>
            <tr>
                <th>NSSF</th>
                <td class="text-right">{{ fmt($payroll->nssf ?? 0) }}</td>
            </tr>
            <tr>
                <th>SHIF</th>
                <td class="text-right">{{ fmt($payroll->shif ?? 0) }}</td>
            </tr>
            <tr>
                <th>Housing Levy</th>
                <td class="text-right">{{ fmt($payroll->housing_levy ?? 0) }}</td>
            </tr>
            <tr>
                <th>TAXABLE PAY</th>
                <td class="text-right">{{ fmt($payroll->taxable_pay ?? 0) }}</td>
            </tr>
            <tr>
                <th>INCOME TAX</th>
                <td class="text-right">{{ fmt($payroll->income_tax ?? 0) }}</td>
            </tr>
            <tr>
                <th>Personal Relief</th>
                <td class="text-right">{{ fmt($payroll->personal_relief ?? 0) }}</td>
            </tr>
            <tr>
                <th>P.A.Y.E</th>
                <td class="text-right">{{ fmt($payroll->paye ?? 0) }}</td>
            </tr>
            <tr>
                <th>PAY AFTER TAX</th>
                <td class="text-right">{{ fmt($payroll->pay_after_tax ?? 0) }}</td>
            </tr>
            <tr>
                <th>NET PAY</th>
                <td class="text-right">{{ fmt($payroll->net ?? 0) }}</td>
            </tr>
        </tbody>
    </table>

    <a href="{{ route('hrm.payrolls.index') }}" class="btn btn-secondary">Back</a>
</div>
@endsection
