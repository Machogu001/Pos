@extends('layouts.app')

@section('title', 'Edit Payroll')

@section('content')
@include('hrm::partials.hrm_page_header', [
    'title' => 'Edit Payroll #'.$payroll->id,
    'subtitle' => 'Update payroll values before posting them to accounts.',
    'actions' => '<a href="'.route('hrm.payrolls.index').'" class="btn btn-default"><i class="fa fa-arrow-left"></i> Back to Payrolls</a>'
])

<section class="content">
    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title">Payroll Details</h3>
        </div>
        <form method="POST" action="{{ route('hrm.payrolls.update', $payroll->id) }}">
            @csrf
            @method('PUT')
            @include('hrm::partials.hrm_form_toolbar')

            <div class="box-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="company_id" class="form-label">Company</label>
                            <select name="company_id" id="company_id" class="form-control">
                                @foreach($companies as $c)
                                    <option value="{{ $c->id }}" {{ old('company_id', $payroll->company_id) == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="employee_id" class="form-label">Employee</label>
                            <select name="employee_id" id="employee_id" class="form-control">
                                @foreach($employees as $e)
                                    @php $label = $e->username ?? trim((($e->firstname ?? '') . ' ' . ($e->lastname ?? ''))); @endphp
                                    <option value="{{ $e->id }}" {{ old('employee_id', $payroll->employee_id) == $e->id ? 'selected' : '' }}>{{ $label ?: 'Employee #'.$e->id }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="period_start" class="form-label">Period Start</label>
                            <input type="date" name="period_start" id="period_start" class="form-control" value="{{ old('period_start', $payroll->period_start) }}" required />
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="period_end" class="form-label">Period End</label>
                            <input type="date" name="period_end" id="period_end" class="form-control" value="{{ old('period_end', $payroll->period_end) }}" required />
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="gross" class="form-label">Gross</label>
                            <input type="number" step="0.01" name="gross" id="gross" class="form-control" value="{{ old('gross', $payroll->gross) }}" required />
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="deductions" class="form-label">Deductions</label>
                            <input type="number" step="0.01" id="deductions" name="deductions" class="form-control" value="{{ old('deductions', $payroll->deductions) }}" />
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="net" class="form-label">Net</label>
                            <input type="number" step="0.01" id="net" name="net" class="form-control" value="{{ old('net', $payroll->net) }}" readonly />
                        </div>
                    </div>
                </div>

                <div class="box box-default">
                    <div class="box-header with-border">
                        <h3 class="box-title">Payroll Breakdown</h3>
                    </div>
                    <div class="box-body">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="basic_pay">Basic Pay</label>
                                    <input type="number" step="0.01" name="basic_pay" id="basic_pay" class="form-control" value="{{ old('basic_pay', $payroll->basic_pay ?? $payroll->gross) }}" />
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="nssf">NSSF</label>
                                    <input type="number" step="0.01" name="nssf" id="nssf" class="form-control" value="{{ old('nssf', $payroll->nssf ?? 0) }}" />
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="shif">SHIF</label>
                                    <input type="number" step="0.01" name="shif" id="shif" class="form-control" value="{{ old('shif', $payroll->shif ?? 0) }}" />
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="housing_levy">Housing Levy</label>
                                    <input type="number" step="0.01" name="housing_levy" id="housing_levy" class="form-control" value="{{ old('housing_levy', $payroll->housing_levy ?? 0) }}" />
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="taxable_pay">Taxable Pay</label>
                                    <input type="number" step="0.01" name="taxable_pay" id="taxable_pay" class="form-control" value="{{ old('taxable_pay', $payroll->taxable_pay ?? 0) }}" readonly />
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="income_tax">Income Tax</label>
                                    <input type="number" step="0.01" name="income_tax" id="income_tax" class="form-control" value="{{ old('income_tax', $payroll->income_tax ?? 0) }}" />
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="personal_relief">Personal Relief</label>
                                    <input type="number" step="0.01" name="personal_relief" id="personal_relief" class="form-control" value="{{ old('personal_relief', $payroll->personal_relief ?? 0) }}" />
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="paye">P.A.Y.E</label>
                                    <input type="number" step="0.01" name="paye" id="paye" class="form-control" value="{{ old('paye', $payroll->paye ?? 0) }}" readonly />
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="pay_after_tax">Pay After Tax</label>
                                    <input type="number" step="0.01" name="pay_after_tax" id="pay_after_tax" class="form-control" value="{{ old('pay_after_tax', $payroll->pay_after_tax ?? 0) }}" readonly />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="box-footer text-right">
                <a href="{{ route('hrm.payrolls.index') }}" class="btn btn-default">Cancel</a>
                <button class="btn btn-primary"><i class="fa fa-save"></i> Update Payroll</button>
            </div>
        </form>
    </div>
</section>
@endsection

@push('scripts')
<script>
    (function(){
        var adminDefaults = @json($adminSettings ?? null);
        var grossEl = document.querySelector('input[name="gross"]');
        var deductionsEl = document.getElementById('deductions');
        var netEl = document.getElementById('net');
        var basicEl = document.getElementById('basic_pay');
        var nssfEl = document.getElementById('nssf');
        var shifEl = document.getElementById('shif');
        var housingEl = document.getElementById('housing_levy');
        var taxableEl = document.getElementById('taxable_pay');
        var incomeTaxEl = document.getElementById('income_tax');
        var reliefEl = document.getElementById('personal_relief');
        var payeEl = document.getElementById('paye');
        var payAfterEl = document.getElementById('pay_after_tax');

        function computeTaxBands(taxable, bands){
            try {
                if (!bands || !Array.isArray(bands)) return null;
                var remaining = taxable;
                var tax = 0;
                var lower = 0;
                for (var i = 0; i < bands.length; i++) {
                    var band = bands[i];
                    var upper = (band.upper === null || band.upper === undefined) ? null : parseFloat(band.upper);
                    var rate = parseFloat(band.rate) || 0;
                    if (upper === null) {
                        tax += Math.max(0, remaining) * rate;
                        remaining = 0;
                        break;
                    }
                    var bandAmount = Math.max(0, Math.min(remaining, upper - lower));
                    if (bandAmount > 0) {
                        tax += bandAmount * rate;
                        remaining -= bandAmount;
                    }
                    lower = upper;
                    if (remaining <= 0) break;
                }
                return Math.round((tax + Number.EPSILON) * 100) / 100;
            } catch (e) {
                return null;
            }
        }

        function recalc(){
            var gross = parseFloat(grossEl && grossEl.value) || 0;
            var ded = parseFloat(deductionsEl && deductionsEl.value) || 0;
            var basic = parseFloat(basicEl && basicEl.value);
            if (isNaN(basic) || basic === 0) basic = gross;
            if (basicEl) basicEl.value = basic.toFixed(2);

            var nssfPercent = (adminDefaults && adminDefaults.payroll_nssf_percent) ? parseFloat(adminDefaults.payroll_nssf_percent) : 0;
            var shifPercent = (adminDefaults && adminDefaults.payroll_shif_percent) ? parseFloat(adminDefaults.payroll_shif_percent) : 0;
            var housingPercent = (adminDefaults && adminDefaults.payroll_housing_percent) ? parseFloat(adminDefaults.payroll_housing_percent) : 0;
            var taxPercent = (adminDefaults && adminDefaults.payroll_tax_percent) ? parseFloat(adminDefaults.payroll_tax_percent) : 0;
            var personalReliefDefault = (adminDefaults && adminDefaults.payroll_personal_relief) ? parseFloat(adminDefaults.payroll_personal_relief) : 0;

            var nssf = parseFloat(nssfEl && nssfEl.value);
            if (isNaN(nssf) || nssf === 0) nssf = +(basic * nssfPercent).toFixed(2);
            if (nssfEl) nssfEl.value = nssf.toFixed(2);

            var shif = parseFloat(shifEl && shifEl.value);
            if (isNaN(shif) || shif === 0) shif = +(basic * shifPercent).toFixed(2);
            if (shifEl) shifEl.value = shif.toFixed(2);

            var housing = parseFloat(housingEl && housingEl.value);
            if (isNaN(housing) || housing === 0) housing = +(basic * housingPercent).toFixed(2);
            if (housingEl) housingEl.value = housing.toFixed(2);

            var taxable = Math.max(0, basic - nssf - shif - housing);
            if (taxableEl) taxableEl.value = taxable.toFixed(2);

            var bands = null;
            try { bands = (adminDefaults && adminDefaults.payroll_tax_bands) ? JSON.parse(adminDefaults.payroll_tax_bands) : null; } catch (e) { bands = null; }

            var incomeTax = parseFloat(incomeTaxEl && incomeTaxEl.value);
            if (isNaN(incomeTax) || incomeTax === 0) {
                var bandTax = computeTaxBands(taxable, bands);
                incomeTax = bandTax !== null ? bandTax : +(taxable * taxPercent).toFixed(2);
            }
            if (incomeTaxEl) incomeTaxEl.value = incomeTax.toFixed(2);

            var relief = parseFloat(reliefEl && reliefEl.value);
            if (isNaN(relief) || relief === 0) relief = personalReliefDefault;
            if (reliefEl) reliefEl.value = parseFloat(relief).toFixed(2);

            var paye = Math.max(0, incomeTax - relief);
            if (payeEl) payeEl.value = paye.toFixed(2);

            var payAfter = Math.max(0, gross - paye);
            if (payAfterEl) payAfterEl.value = payAfter.toFixed(2);

            var net = Math.max(0, payAfter - ded);
            if (netEl) netEl.value = net.toFixed(2);
        }

        if(grossEl) grossEl.addEventListener('input', recalc);
        if(deductionsEl) deductionsEl.addEventListener('input', recalc);
        if(basicEl) basicEl.addEventListener('input', recalc);
        if(nssfEl) nssfEl.addEventListener('input', recalc);
        if(shifEl) shifEl.addEventListener('input', recalc);
        if(housingEl) housingEl.addEventListener('input', recalc);
        if(incomeTaxEl) incomeTaxEl.addEventListener('input', recalc);
        if(reliefEl) reliefEl.addEventListener('input', recalc);
        recalc();
    })();
</script>
@endpush

