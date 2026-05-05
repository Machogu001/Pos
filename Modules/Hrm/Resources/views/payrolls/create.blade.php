@extends('layouts.app')

@section('title', 'Create Payroll')

@section('content')
@include('hrm::partials.hrm_page_header', [
    'title' => 'Create Payroll',
    'subtitle' => 'Process salary batches and keep posting status visible for accounting review.',
    'actions' => '<a href="'.route('hrm.payrolls.index').'" class="btn btn-default"><i class="fa fa-arrow-left"></i> Back to Payroll</a>'
])

<section class="content">
    <div class="box box-success">
        <div class="box-header with-border">
            <h3 class="box-title">Payroll Batch</h3>
        </div>
        <form method="POST" action="{{ route('hrm.payrolls.store') }}">
            @csrf
            @include('hrm::partials.hrm_form_toolbar')
            <div class="box-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Company</label>
                            <select name="company_id" id="company_id" class="form-control">
                                <option value="">-- Select Company --</option>
                                @foreach($companies as $c)
                                    @php
                                        $isBusiness = isset($c->business_id) && $c->business_id == session('business.id');
                                        $label = $isBusiness ? 'Business - ' . $c->name : $c->name;
                                    @endphp
                                    <option value="{{ $c->id }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Period Start</label>
                            <input type="date" name="period_start" class="form-control" required />
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Period End</label>
                            <input type="date" name="period_end" class="form-control" required />
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label>Employee(s)</label>
                    <div class="btn-toolbar" style="margin-bottom:10px; gap:8px;">
                        <button type="button" id="select-all-employees" class="btn btn-sm btn-default">Select all for company</button>
                        <button type="button" id="clear-employees" class="btn btn-sm btn-default">Clear selection</button>
                    </div>
                    <select name="employee_id[]" id="employee_id" class="form-control" multiple size="8">
                        @foreach($employees as $e)
                            <option value="{{ $e->id }}">{{ $e->username ?? $e->name }}</option>
                        @endforeach
                    </select>
                    <small class="form-text text-muted">Use Ctrl/Cmd+click to select multiple employees, or use the buttons above to select/clear.</small>
                </div>

                <hr />
                <div id="selectedPayrollRows"></div>
            </div>

            <div class="box-footer text-right">
                <button class="btn btn-success" id="createPayrollBtn"><i class="fa fa-save"></i> Create Payroll</button>
            </div>
        </form>
    </div>
</section>

@endsection

@section('javascript')
<script>
    (function(){
        var companySelect = document.getElementById('company_id');
        var employeeSelect = document.getElementById('employee_id');
        var selectAllBtn = document.getElementById('select-all-employees');
        var clearBtn = document.getElementById('clear-employees');

        function loadEmployeesForCompany(companyId, selectAll) {
            employeeSelect.innerHTML = '<option>Loading...</option>';
            fetch('{{ url("hrm/employees/by-company") }}?id=' + companyId)
                .then(r => r.json())
                .then(data => {
                    employeeSelect.innerHTML = '';
                    // controller may return an array or an object like { employees: [...] }
                    var list = (data && data.employees) ? data.employees : data || [];
                    (list || []).forEach(function(e){
                        var opt = document.createElement('option');
                        opt.value = e.id;
                        // prefer computed name, then username, then firstname+lastname
                        var label = e.name || e.username || ((e.firstname || '') + ' ' + (e.lastname || ''));
                        opt.text = (label || ('Employee #'+e.id)).trim();
                        // attach meta payload so we can set defaults (basic_salary etc.) later
                        try { opt.setAttribute('data-meta', JSON.stringify(e)); } catch(ex){}
                        if (selectAll) opt.selected = true;
                        employeeSelect.appendChild(opt);
                    });
            // rebuild rows for pre-selected options (may be defined later)
            if (typeof window.buildRowsFromSelect === 'function') {
                window.buildRowsFromSelect();
            } else {
                window._needsBuild = true;
            }
            }).catch(()=>{
                employeeSelect.innerHTML = '';
            });
        }

        companySelect.addEventListener('change', function() {
            var companyId = this.value;
            if (!companyId) { employeeSelect.innerHTML = ''; return; }
            loadEmployeesForCompany(companyId, false);
        });

        selectAllBtn.addEventListener('click', function(){
            var companyId = companySelect.value;
            if (!companyId) { window.hrmAlert('Please select a company first'); return; }
            loadEmployeesForCompany(companyId, true);
        });

        clearBtn.addEventListener('click', function(){
            Array.from(employeeSelect.options).forEach(function(o){ o.selected = false; });
            buildRowsFromSelect();
        });
    })();
</script>
<script>
    // admin defaults for client-side calculations
    var adminDefaults = @json($adminSettings ?? null);
</script>
<script>
    // Build rows for selected employees and wire up per-row live net calculation
    (function(){
        var employeeSelect = document.getElementById('employee_id');
        var rowsContainer = document.getElementById('selectedPayrollRows');

        function formatMoney(v){ return parseFloat(v||0).toFixed(2); }

        function createRow(empId, empLabel){
            if(document.getElementById('row-'+empId)) return;
            var div = document.createElement('div'); div.className = 'card mb-2'; div.id = 'row-'+empId;
            var body = document.createElement('div'); body.className = 'card-body';
            var html = `
                <input type="hidden" name="employee_id[]" value="${empId}" />
                <div class="row">
                    <div class="col-md-4"><strong>${empLabel}</strong></div>
                    <div class="col-md-2"><label class="form-label">Gross</label><input type="number" step="0.01" name="gross[${empId}]" class="form-control gross-input" value="0" required /></div>
                    <div class="col-md-2"><label class="form-label">Deductions</label><input type="number" step="0.01" name="deductions[${empId}]" class="form-control deductions-input" value="0" /></div>
                    <div class="col-md-2"><label class="form-label">Net</label><input type="number" step="0.01" name="net[${empId}]" class="form-control net-input" value="0.00" readonly /></div>
                    <div class="col-md-2 text-end">
                        <div class="btn-group-vertical btn-sm" role="group">
                            <button type="button" class="btn btn-sm btn-outline-secondary toggle-advanced" data-id="${empId}">Advanced</button>
                            <button type="button" class="btn btn-sm btn-outline-danger remove-row" data-id="${empId}">Remove</button>
                        </div>
                    </div>
                </div>

                <div class="advanced-panel mt-2 p-2 border rounded d-none" id="advanced-${empId}">
                    <div class="row g-2">
                        <div class="col-md-3">
                            <label class="form-label">Basic Pay</label>
                            <input type="number" step="0.01" name="basic_pay[${empId}]" class="form-control basic-pay-input" value="0" />
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">NSSF</label>
                            <input type="number" step="0.01" name="nssf[${empId}]" class="form-control nssf-input" value="0" />
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">SHIF</label>
                            <input type="number" step="0.01" name="shif[${empId}]" class="form-control shif-input" value="0" />
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Housing Levy</label>
                            <input type="number" step="0.01" name="housing_levy[${empId}]" class="form-control housing-input" value="0" />
                        </div>
                    </div>
                    <div class="row g-2 mt-2">
                        <div class="col-md-3">
                            <label class="form-label">Taxable Pay</label>
                            <input type="number" step="0.01" name="taxable_pay[${empId}]" class="form-control taxable-input" value="0" readonly />
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Income Tax</label>
                            <input type="number" step="0.01" name="income_tax[${empId}]" class="form-control income-tax-input" value="0" />
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Personal Relief</label>
                            <input type="number" step="0.01" name="personal_relief[${empId}]" class="form-control relief-input" value="0" />
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">P.A.Y.E</label>
                            <input type="number" step="0.01" name="paye[${empId}]" class="form-control paye-input" value="0" readonly />
                        </div>
                    </div>
                    <div class="row g-2 mt-2">
                        <div class="col-md-4">
                            <label class="form-label">Pay After Tax</label>
                            <input type="number" step="0.01" name="pay_after_tax[${empId}]" class="form-control pay-after-input" value="0" readonly />
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Notes</label>
                            <input type="text" name="notes[${empId}]" class="form-control" />
                        </div>
                    </div>
                </div>`;
            body.innerHTML = html; div.appendChild(body); rowsContainer.appendChild(div);

            var grossEl = div.querySelector('.gross-input');
            var dedEl = div.querySelector('.deductions-input');
            var netEl = div.querySelector('.net-input');
            var basicEl = div.querySelector('.basic-pay-input');
            var nssfEl = div.querySelector('.nssf-input');
            var shifEl = div.querySelector('.shif-input');
            var housingEl = div.querySelector('.housing-input');
            var taxableEl = div.querySelector('.taxable-input');
            var incomeTaxEl = div.querySelector('.income-tax-input');
            var reliefEl = div.querySelector('.relief-input');
            var payeEl = div.querySelector('.paye-input');
            var payAfterEl = div.querySelector('.pay-after-input');
            var advancedPanel = div.querySelector('#advanced-'+empId);

            function computeTaxBands(taxable, bands){
                try {
                    if (!bands || !Array.isArray(bands)) return null;
                    var remaining = taxable;
                    var tax = 0;
                    var lower = 0;
                    for(var i=0;i<bands.length;i++){
                        var band = bands[i];
                        var upper = (band.upper === null || band.upper === undefined) ? null : parseFloat(band.upper);
                        var rate = parseFloat(band.rate) || 0;
                        if (upper === null) { tax += Math.max(0, remaining) * rate; remaining = 0; break; }
                        var bandAmount = Math.max(0, Math.min(remaining, upper - lower));
                        if (bandAmount>0){ tax += bandAmount * rate; remaining -= bandAmount; }
                        lower = upper;
                        if (remaining <= 0) break;
                    }
                    return Math.round((tax + Number.EPSILON) * 100) / 100;
                } catch(e){ return null; }
            }

            function recalcAdvanced(){
                var gross = parseFloat(grossEl.value) || 0;
                var ded = parseFloat(dedEl.value) || 0;
                // default basic pay to gross if empty
                var basic = parseFloat(basicEl.value) || gross;
                basicEl.value = basic.toFixed(2);

                // get percents from adminDefaults if available
                var nssfPercent = (adminDefaults && adminDefaults.payroll_nssf_percent) ? parseFloat(adminDefaults.payroll_nssf_percent) : 0;
                var shifPercent = (adminDefaults && adminDefaults.payroll_shif_percent) ? parseFloat(adminDefaults.payroll_shif_percent) : 0;
                var housingPercent = (adminDefaults && adminDefaults.payroll_housing_percent) ? parseFloat(adminDefaults.payroll_housing_percent) : 0;
                var taxPercent = (adminDefaults && adminDefaults.payroll_tax_percent) ? parseFloat(adminDefaults.payroll_tax_percent) : 0;
                var personalReliefDefault = (adminDefaults && adminDefaults.payroll_personal_relief) ? parseFloat(adminDefaults.payroll_personal_relief) : 0;

                // allow manual override if field not zero
                var nssf = parseFloat(nssfEl.value);
                if (isNaN(nssf) || nssf === 0) nssf = +(basic * nssfPercent).toFixed(2);
                nssfEl.value = nssf.toFixed(2);

                var shif = parseFloat(shifEl.value);
                if (isNaN(shif) || shif === 0) shif = +(basic * shifPercent).toFixed(2);
                shifEl.value = shif.toFixed(2);

                var housing = parseFloat(housingEl.value);
                if (isNaN(housing) || housing === 0) housing = +(basic * housingPercent).toFixed(2);
                housingEl.value = housing.toFixed(2);

                var taxable = Math.max(0, basic - nssf - shif - housing);
                taxableEl.value = taxable.toFixed(2);

                // income tax using bands if present
                var incomeTax = parseFloat(incomeTaxEl.value);
                var bands = null;
                try { bands = (adminDefaults && adminDefaults.payroll_tax_bands) ? JSON.parse(adminDefaults.payroll_tax_bands) : null; } catch(e){ bands = null; }
                if (isNaN(incomeTax) || incomeTax === 0) {
                    var bandTax = computeTaxBands(taxable, bands);
                    if (bandTax !== null) incomeTax = bandTax; else incomeTax = +(taxable * taxPercent).toFixed(2);
                }
                incomeTaxEl.value = incomeTax.toFixed(2);

                var relief = parseFloat(reliefEl.value);
                if (isNaN(relief) || relief === 0) relief = personalReliefDefault;
                reliefEl.value = parseFloat(relief).toFixed(2);

                var paye = Math.max(0, incomeTax - relief);
                payeEl.value = paye.toFixed(2);

                var payAfter = Math.max(0, gross - paye);
                payAfterEl.value = payAfter.toFixed(2);

                // net = payAfter - deductions
                netEl.value = Math.max(0, payAfter - ded).toFixed(2);

                // update totals UI
                recalcTotals();
            }

            // initial recalc and event wiring
            grossEl.addEventListener('input', recalcAdvanced);
            dedEl.addEventListener('input', recalcAdvanced);
            basicEl.addEventListener('input', recalcAdvanced);
            nssfEl.addEventListener('input', recalcAdvanced);
            shifEl.addEventListener('input', recalcAdvanced);
            housingEl.addEventListener('input', recalcAdvanced);
            incomeTaxEl.addEventListener('input', recalcAdvanced);
            reliefEl.addEventListener('input', recalcAdvanced);
            recalcAdvanced();

            // toggle advanced panel
            var tbtn = div.querySelector('.toggle-advanced');
            if (tbtn){ tbtn.addEventListener('click', function(){ advancedPanel.classList.toggle('d-none'); }); }

            // initial validation & styling hook
            function validate(){
                var ok = !isNaN(parseFloat(grossEl.value)) && parseFloat(grossEl.value) >= 0;
                if(!ok){ grossEl.classList.add('is-invalid'); } else { grossEl.classList.remove('is-invalid'); }
                recalcAdvanced();
                recalcTotals();
                return ok;
            }
            grossEl.addEventListener('input', validate);

            div.querySelector('.remove-row').addEventListener('click', function(){
                div.remove();
                Array.from(employeeSelect.options).forEach(function(o){ if(o.value==empId) o.selected=false; });
                recalcTotals();
            });
        }

        function buildRowsFromSelect(){
            rowsContainer.innerHTML = '';
            var opts = Array.from(employeeSelect.options).filter(function(o){ return o.selected; });
            opts.forEach(function(o){ var label = o.textContent || o.innerText; createRow(o.value, label); });
            // after building rows, set default gross values if provided in option data
            opts.forEach(function(o){
                var id = o.value;
                try {
                    var data = JSON.parse(o.getAttribute('data-meta') || null);
                    if(data && data.basic_salary){
                        var input = document.querySelector('#row-'+id+' .gross-input'); if(input) input.value = parseFloat(data.basic_salary).toFixed(2);
                        var bp = document.querySelector('#row-'+id+' input[name="basic_pay['+id+']"]'); if(bp) bp.value = parseFloat(data.basic_salary).toFixed(2);
                        var ded = document.querySelector('#row-'+id+' .deductions-input'); if(ded) ded.value = parseFloat(data.default_deductions || 0).toFixed(2);
                        var ev = new Event('input',{bubbles:true}); input && input.dispatchEvent(ev);
                    }
                } catch(e){}
            });
            recalcTotals();
        }

        if(employeeSelect) employeeSelect.addEventListener('change', buildRowsFromSelect);

        var form = document.querySelector('form');
        form.addEventListener('submit', function(e){
            var has = rowsContainer.querySelectorAll('[id^="row-"]').length > 0;
            if(!has){ e.preventDefault(); window.hrmAlert('Please select at least one employee for payroll creation'); }
        });

    // expose for other scripts and handle deferred calls
    window.buildRowsFromSelect = buildRowsFromSelect;
    if (window._needsBuild) { buildRowsFromSelect(); window._needsBuild = false; }
    // create totals area after rows container so it's available
    var totalsDiv = document.createElement('div'); totalsDiv.className = 'mt-3'; totalsDiv.id = 'payrollTotals'; rowsContainer.parentNode.insertBefore(totalsDiv, rowsContainer.nextSibling);

    function recalcTotals(){
        var totalGross = 0, totalDeductions = 0, totalNet = 0; var invalid = false;
        document.querySelectorAll('.card[id^="row-"]').forEach(function(card){
            var g = parseFloat(card.querySelector('.gross-input').value) || 0;
            var d = parseFloat(card.querySelector('.deductions-input').value) || 0;
            var n = parseFloat(card.querySelector('.net-input').value) || 0;
            if(isNaN(g)) invalid = true;
            totalGross += g; totalDeductions += d; totalNet += n;
        });
        totalsDiv.innerHTML = `<div class="alert alert-light p-2">Totals — Gross: <strong>${totalGross.toFixed(2)}</strong>, Deductions: <strong>${totalDeductions.toFixed(2)}</strong>, Net: <strong>${totalNet.toFixed(2)}</strong></div>`;
        var createBtn = document.getElementById('createPayrollBtn');
        if(invalid || totalGross <= 0){ createBtn.disabled = true; createBtn.classList.add('disabled'); } else { createBtn.disabled = false; createBtn.classList.remove('disabled'); }
    }
    })();
</script>
@endsection

@push('scripts')
