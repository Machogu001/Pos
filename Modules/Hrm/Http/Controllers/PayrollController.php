<?php

namespace Modules\Hrm\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use App\Business;
use App\Models\Company;
use App\Models\Employee;
use DB;
use App\AdminSetting;

class PayrollController extends Controller
{
    protected function getAuthUser($request)
    {
        return $request->user('api') ?? $request->user() ?? auth()->user();
    }

    // Compute income tax using configured tax bands or fallback to flat percent
    private function computeIncomeTax(float $taxablePay, $companyModel = null, $adminSettings = null)
    {
        // If admin has payroll_tax_bands configured (JSON), use progressive bands.
        try {
            $bandsJson = $adminSettings->payroll_tax_bands ?? null;
            if ($bandsJson) {
                $bands = json_decode($bandsJson, true);
                if (is_array($bands) && count($bands) > 0) {
                    // bands should be array of ['upper' => number|null, 'rate' => fraction]
                    $remaining = $taxablePay;
                    $tax = 0.0;
                    $lower = 0.0;
                    foreach ($bands as $band) {
                        $upper = isset($band['upper']) && $band['upper'] !== null ? (float)$band['upper'] : null;
                        $rate = isset($band['rate']) ? (float)$band['rate'] : 0;
                        if ($upper === null) {
                            // last band: tax remaining at rate
                            $tax += max(0, $remaining) * $rate;
                            $remaining = 0;
                            break;
                        }
                        $bandAmount = max(0, min($remaining, $upper - $lower));
                        if ($bandAmount > 0) {
                            $tax += $bandAmount * $rate;
                            $remaining -= $bandAmount;
                        }
                        $lower = $upper;
                        if ($remaining <= 0) break;
                    }
                    return round($tax, 2);
                }
            }
        } catch (\Throwable $e) {
            // fall through to flat percent
        }

        // flat percent fallback: company override then admin setting
        $taxPercent = $companyModel->tax_percent ?? ($adminSettings->payroll_tax_percent ?? 0);
        return round($taxablePay * (float)$taxPercent, 2);
    }

    public function index(Request $request)
    {
        $this->authorizeForUser($this->getAuthUser($request), 'view', Employee::class);

        $payrolls = [];
        if (Schema::hasTable('hrm_payrolls')) {
            // join with employees and company/business to show names
            $companyTable = Schema::hasTable('business') ? 'business' : (Schema::hasTable('companies') ? 'companies' : null);

            $query = DB::table('hrm_payrolls as p')
                ->leftJoin('employees as e', 'p.employee_id', '=', 'e.id');

            if ($companyTable) {
                $query->leftJoin($companyTable.' as c', 'p.company_id', '=', 'c.id');
                $query->select('p.*', DB::raw("COALESCE(e.username, CONCAT_WS(' ', e.firstname, e.lastname)) as employee_name"), 'c.name as company_name');
            } else {
                $query->select('p.*', DB::raw("COALESCE(e.username, CONCAT_WS(' ', e.firstname, e.lastname)) as employee_name"));
            }

            // apply filters
            if ($request->filled('company_id')) {
                $query->where('p.company_id', $request->get('company_id'));
            }
            if ($request->filled('employee_id')) {
                $query->where('p.employee_id', $request->get('employee_id'));
            }

            $perPage = (int) $request->get('per_page', 15);
            $payrolls = $query->orderBy('p.created_at', 'desc')->paginate($perPage)->withQueryString();
        }

        // load companies and employees for filters
        if (Schema::hasTable('business')) {
            $companies = Business::orderBy('id', 'desc')->get(['id','name']);
        } else {
            $companies = Schema::hasTable('companies') ? Company::where('deleted_at', '=', null)->get(['id','name']) : collect([]);
        }

        $employees = Schema::hasTable('employees') ? Employee::where('deleted_at', null)->get(['id','username','firstname','lastname']) : collect([]);

        if ($request->wantsJson()) {
            return response()->json(['payrolls' => $payrolls]);
        }

        // Pass companies and employees so the index filters can render their dropdowns
        return view('hrm::payrolls.index', compact('payrolls', 'companies', 'employees'));
    }

    public function create(Request $request)
    {
        $this->authorizeForUser($this->getAuthUser($request), 'create', Employee::class);

        if (Schema::hasTable('business')) {
            $companies = Business::orderBy('id', 'desc')->get(['id','name']);
        } else {
            $companies = Schema::hasTable('companies') ? Company::where('deleted_at', '=', null)->get(['id','name']) : collect([]);
        }

    $employees = collect([]);

        if ($request->has('company_id')) {
            $company_id = $request->get('company_id');
            if (Schema::hasTable('employees')) {
                $employees = Employee::where('company_id', $company_id)->where('deleted_at', null)->get(['id','username']);
            }
        }

        // Pass admin payroll defaults to the view for client-side calculations
        $adminSettings = null;
        if (Schema::hasTable('admin_settings')) {
            $adminSettings = \App\AdminSetting::first();
        }

        if ($request->wantsJson()) {
            return response()->json(['companies' => $companies, 'employees' => $employees, 'admin_defaults' => $adminSettings]);
        }

        return view('hrm::payrolls.create', compact('companies', 'employees', 'adminSettings'));
    }

    public function store(Request $request)
    {
        $this->authorizeForUser($this->getAuthUser($request), 'create', Employee::class);

        // Expect employee_id[] (one or more) and per-employee gross/deductions inputs named gross[<id>], deductions[<id>]
        $this->validate($request, [
            'company_id' => 'required',
            'employee_id' => 'required|array|min:1',
            'period_start' => 'required|date',
            'period_end' => 'required|date',
        ]);

        $companyId = $request->input('company_id');
        $periodStart = $request->input('period_start');
        $periodEnd = $request->input('period_end');

        $employeeIds = $request->input('employee_id', []);

        // load defaults for calculations
        $adminSettings = null;
        if (Schema::hasTable('admin_settings')) {
            $adminSettings = AdminSetting::first();
        }
        $companyModel = Company::find($companyId);

        DB::beginTransaction();
        try {
            foreach ($employeeIds as $eid) {
                $deductions = (float) $request->input('deductions.'.$eid, $request->input('deductions', 0));
                $gross = (float) $request->input('gross.'.$eid, $request->input('gross', 0));

                // payroll breakdown fields (allow per-employee overrides)
                $basic_pay = (float) $request->input('basic_pay.'.$eid, $gross);
                $nssf = (float) $request->input('nssf.'.$eid, 0);
                $shif = (float) $request->input('shif.'.$eid, 0);
                $housing_levy = (float) $request->input('housing_levy.'.$eid, 0);
                    $taxable_pay = (float) $request->input('taxable_pay.'.$eid, max(0, $basic_pay - $nssf - $shif - $housing_levy));

                    // Calculate income tax using tax bands if configured, otherwise use flat percent
                    if ($request->has('income_tax.'.$eid)) {
                        $income_tax = (float) $request->input('income_tax.'.$eid);
                    } else {
                        $income_tax = $this->computeIncomeTax($taxable_pay, $companyModel, $adminSettings);
                    }
                $personal_relief = (float) $request->input('personal_relief.'.$eid, 0);
                $paye = (float) $request->input('paye.'.$eid, max(0, $income_tax - $personal_relief));
                $pay_after_tax = (float) $request->input('pay_after_tax.'.$eid, max(0, $gross - $paye));

                // Net defaults to pay after tax minus deductions if not explicitly provided
                $net = $request->has('net.'.$eid) ? (float) $request->input('net.'.$eid) : max(0, $pay_after_tax - $deductions);

                DB::table('hrm_payrolls')->insert([
                    'company_id' => $companyId,
                    'employee_id' => $eid,
                    'period_start' => $periodStart,
                    'period_end' => $periodEnd,
                    'basic_pay' => $basic_pay,
                    'gross' => $gross,
                    'nssf' => $nssf,
                    'shif' => $shif,
                    'housing_levy' => $housing_levy,
                    'taxable_pay' => $taxable_pay,
                    'income_tax' => $income_tax,
                    'personal_relief' => $personal_relief,
                    'paye' => $paye,
                    'pay_after_tax' => $pay_after_tax,
                    'deductions' => $deductions,
                    'net' => $net,
                    'created_by' => auth()->id() ?? null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
            }
            return back()->withInput()->with('error', 'Failed to create payrolls: ' . $e->getMessage());
        }

        if ($request->wantsJson()) {
            return response()->json(['success' => true]);
        }

    return redirect()->route('hrm.payrolls.index')->with('success', 'Payroll created');
    }

    public function show(Request $request, $id)
    {
        $this->authorizeForUser($this->getAuthUser($request), 'view', Employee::class);
        if (! Schema::hasTable('hrm_payrolls')) {
            abort(404);
        }

        $payroll = DB::table('hrm_payrolls')->where('id', $id)->first();

        if ($request->wantsJson()) {
            return response()->json(['payroll' => $payroll]);
        }

        return view('hrm::payrolls.show', compact('payroll'));
    }

    public function edit(Request $request, $id)
    {
        $this->authorizeForUser($this->getAuthUser($request), 'update', Employee::class);
        if (! Schema::hasTable('hrm_payrolls')) {
            abort(404);
        }

        $payroll = DB::table('hrm_payrolls')->where('id', $id)->first();
        if (! $payroll) {
            abort(404);
        }

        // load companies and employees similar to create
        if (Schema::hasTable('business')) {
            $companies = Business::orderBy('id', 'desc')->get(['id','name']);
        } else {
            $companies = Schema::hasTable('companies') ? Company::where('deleted_at', '=', null)->get(['id','name']) : collect([]);
        }

        $employees = collect([]);
        if (Schema::hasTable('employees')) {
            $employees = Employee::where('deleted_at', null)->get(['id','username','firstname','lastname']);
        }

        return view('hrm::payrolls.edit', compact('payroll','companies','employees'));
    }

    public function update(Request $request, $id)
    {
        $this->authorizeForUser($this->getAuthUser($request), 'update', Employee::class);
        $this->validate($request, [
            'company_id' => 'required',
            'employee_id' => 'required',
            'period_start' => 'required|date',
            'period_end' => 'required|date',
            'gross' => 'required|numeric|min:0',
        ]);

        $deductions = (float) $request->input('deductions', 0);
        $gross = (float) $request->input('gross');

        // load defaults
        $adminSettings = null;
        if (Schema::hasTable('admin_settings')) {
            $adminSettings = AdminSetting::first();
        }
        $companyModel = Company::find($request->input('company_id'));

        // accept and compute breakdown values if provided
        $basic_pay = (float) $request->input('basic_pay', $gross);
        $nssfPercent = $companyModel->nssf_percent ?? ($adminSettings->payroll_nssf_percent ?? 0);
        $shifPercent = $companyModel->shif_percent ?? ($adminSettings->payroll_shif_percent ?? 0);
        $housingPercent = $companyModel->housing_percent ?? ($adminSettings->payroll_housing_percent ?? 0);
        $taxPercent = $companyModel->tax_percent ?? ($adminSettings->payroll_tax_percent ?? 0);

        $nssf = $request->has('nssf') ? (float) $request->input('nssf') : round($basic_pay * (float)$nssfPercent, 2);
        $shif = $request->has('shif') ? (float) $request->input('shif') : round($basic_pay * (float)$shifPercent, 2);
        $housing_levy = $request->has('housing_levy') ? (float) $request->input('housing_levy') : round($basic_pay * (float)$housingPercent, 2);

        $taxable_pay = (float) $request->input('taxable_pay', max(0, $basic_pay - $nssf - $shif - $housing_levy));
        $income_tax = $request->has('income_tax') ? (float) $request->input('income_tax') : round($taxable_pay * (float)$taxPercent, 2);
        $personal_relief = $request->has('personal_relief') ? (float) $request->input('personal_relief') : ($companyModel->personal_relief ?? ($adminSettings->payroll_personal_relief ?? 0));
        $paye = (float) $request->input('paye', max(0, $income_tax - $personal_relief));
        $pay_after_tax = $request->has('pay_after_tax') ? (float) $request->input('pay_after_tax') : max(0, $gross - $paye);
        $net = $request->has('net') ? (float) $request->input('net') : max(0, $pay_after_tax - $deductions);

        DB::table('hrm_payrolls')->where('id', $id)->update([
            'company_id' => $request->input('company_id'),
            'employee_id' => $request->input('employee_id'),
            'period_start' => $request->input('period_start'),
            'period_end' => $request->input('period_end'),
            'basic_pay' => $basic_pay,
            'gross' => $gross,
            'nssf' => $nssf,
            'shif' => $shif,
            'housing_levy' => $housing_levy,
            'taxable_pay' => $taxable_pay,
            'income_tax' => $income_tax,
            'personal_relief' => $personal_relief,
            'paye' => $paye,
            'pay_after_tax' => $pay_after_tax,
            'deductions' => $deductions,
            'net' => $net,
            'updated_at' => now(),
        ]);

        return redirect()->route('hrm.payrolls.index')->with('success', 'Payroll updated');
    }

    public function destroy(Request $request, $id)
    {
        $this->authorizeForUser($this->getAuthUser($request), 'delete', Employee::class);
        if (! Schema::hasTable('hrm_payrolls')) {
            abort(404);
        }

        DB::table('hrm_payrolls')->where('id', $id)->delete();

        if ($request->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->route('hrm.payrolls.index')->with('success', 'Payroll deleted');
    }
}
