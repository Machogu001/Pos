<?php

namespace Modules\Hrm\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Account;
use App\AccountTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use App\Business;
use App\Models\Company;
use App\Models\Employee;
use DB;
use App\AdminSetting;
use Illuminate\Support\Facades\Log;
use Modules\Hrm\Http\Controllers\Concerns\AuditsHrmActions;
use Modules\Hrm\Http\Requests\StorePayrollRequest;

class PayrollController extends Controller
{
    use AuditsHrmActions;

    protected function getAuthUser($request)
    {
        return $request->user('api') ?? $request->user() ?? auth()->user();
    }

    private function getPayrollPostingAccounts($adminSettings)
    {
        if (empty($adminSettings)) {
            return [null, null];
        }

        $expenseAccountId = $adminSettings->payroll_expense_account_id ?? null;
        $clearingAccountId = $adminSettings->payroll_clearing_account_id ?? null;

        if (empty($expenseAccountId) || empty($clearingAccountId)) {
            return [null, null];
        }

        $expenseAccount = Account::where('id', $expenseAccountId)->first();
        $clearingAccount = Account::where('id', $clearingAccountId)->first();

        if (empty($expenseAccount) || empty($clearingAccount)) {
            return [null, null];
        }

        return [$expenseAccount, $clearingAccount];
    }

    private function buildPayrollNote($payroll)
    {
        $parts = [
            'Payroll #'.$payroll->id,
            'Employee '.$payroll->employee_id,
            $payroll->period_start ?? null,
            $payroll->period_end ?? null,
        ];

        return implode(' | ', array_values(array_filter($parts)));
    }

    private function reversePayrollPosting($payroll)
    {
        if (empty($payroll)) {
            return;
        }

        if (! empty($payroll->debit_account_transaction_id)) {
            AccountTransaction::where('id', $payroll->debit_account_transaction_id)->delete();
        }

        if (! empty($payroll->credit_account_transaction_id)) {
            AccountTransaction::where('id', $payroll->credit_account_transaction_id)->delete();
        }

        DB::table('hrm_payrolls')->where('id', $payroll->id)->update([
            'posted_to_accounts' => 0,
            'posted_at' => null,
            'debit_account_transaction_id' => null,
            'credit_account_transaction_id' => null,
            'updated_at' => now(),
        ]);
    }

    private function postPayrollToAccounts($payroll, $adminSettings, $userId)
    {
        if (empty($payroll) || empty($adminSettings) || ! ($adminSettings->payroll_auto_post ?? true)) {
            return false;
        }

        if (! empty($payroll->posted_to_accounts)) {
            return true;
        }

        [$expenseAccount, $clearingAccount] = $this->getPayrollPostingAccounts($adminSettings);
        if (empty($expenseAccount) || empty($clearingAccount)) {
            Log::warning('Payroll account posting skipped: expense or clearing account not configured', [
                'payroll_id' => $payroll->id ?? null,
                'payroll_expense_account_id' => $adminSettings->payroll_expense_account_id ?? null,
                'payroll_clearing_account_id' => $adminSettings->payroll_clearing_account_id ?? null,
            ]);
            return false;
        }

        $amount = round((float) ($payroll->gross ?? 0), 2);
        if ($amount <= 0) {
            Log::warning('Payroll account posting skipped: gross pay is zero or negative', [
                'payroll_id' => $payroll->id ?? null,
                'gross' => $payroll->gross ?? null,
            ]);
            return false;
        }

        $note = $this->buildPayrollNote($payroll);

        $debitTransaction = AccountTransaction::createAccountTransaction([
            'amount' => $amount,
            'account_id' => $expenseAccount->id,
            'type' => 'debit',
            'sub_type' => 'payroll',
            'operation_date' => now(),
            'created_by' => $userId,
            'note' => $note.' | Salary expense',
        ]);

        $creditTransaction = AccountTransaction::createAccountTransaction([
            'amount' => $amount,
            'account_id' => $clearingAccount->id,
            'type' => 'credit',
            'sub_type' => 'payroll',
            'operation_date' => now(),
            'created_by' => $userId,
            'note' => $note.' | Payroll clearing',
        ]);

        DB::table('hrm_payrolls')->where('id', $payroll->id)->update([
            'posted_to_accounts' => 1,
            'posted_at' => now(),
            'debit_account_transaction_id' => $debitTransaction->id ?? null,
            'credit_account_transaction_id' => $creditTransaction->id ?? null,
            'updated_at' => now(),
        ]);

        return true;
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
            Log::warning('Payroll PAYE tax band parse failed; falling back to flat rate', [
                'error' => $e->getMessage(),
                'tax_bands_raw' => $adminSettings->payroll_tax_bands ?? null,
            ]);
        }

        // flat percent fallback: company override then admin setting
        $taxPercent = $companyModel->tax_percent ?? ($adminSettings->payroll_tax_percent ?? 0);
        return round($taxablePay * (float)$taxPercent, 2);
    }

    private function normalizePayrollValues(array $input, $companyModel = null, $adminSettings = null)
    {
        $hasExplicitValue = static function ($key) use ($input) {
            return array_key_exists($key, $input) && $input[$key] !== null && $input[$key] !== '';
        };

        $gross = round((float) ($input['gross'] ?? 0), 2);
        $deductions = round((float) ($input['deductions'] ?? 0), 2);
        $basicPay = round((float) ($input['basic_pay'] ?? $gross), 2);

        $nssf = $hasExplicitValue('nssf')
            ? round((float) $input['nssf'], 2)
            : round($basicPay * (float) ($companyModel->nssf_percent ?? ($adminSettings->payroll_nssf_percent ?? 0)), 2);

        $shif = $hasExplicitValue('shif')
            ? round((float) $input['shif'], 2)
            : round($basicPay * (float) ($companyModel->shif_percent ?? ($adminSettings->payroll_shif_percent ?? 0)), 2);

        $housingLevy = $hasExplicitValue('housing_levy')
            ? round((float) $input['housing_levy'], 2)
            : round($basicPay * (float) ($companyModel->housing_percent ?? ($adminSettings->payroll_housing_percent ?? 0)), 2);

        $taxablePay = $hasExplicitValue('taxable_pay')
            ? round((float) $input['taxable_pay'], 2)
            : round(max(0, $basicPay - $nssf - $shif - $housingLevy), 2);

        $incomeTax = $hasExplicitValue('income_tax')
            ? round((float) $input['income_tax'], 2)
            : $this->computeIncomeTax($taxablePay, $companyModel, $adminSettings);

        $personalRelief = $hasExplicitValue('personal_relief')
            ? round((float) $input['personal_relief'], 2)
            : round((float) ($companyModel->personal_relief ?? ($adminSettings->payroll_personal_relief ?? 0)), 2);

        $paye = $hasExplicitValue('paye')
            ? round((float) $input['paye'], 2)
            : round(max(0, $incomeTax - $personalRelief), 2);

        $payAfterTax = $hasExplicitValue('pay_after_tax')
            ? round((float) $input['pay_after_tax'], 2)
            : round(max(0, $gross - $paye), 2);

        $net = $hasExplicitValue('net')
            ? round((float) $input['net'], 2)
            : round(max(0, $payAfterTax - $deductions), 2);

        return [
            'basic_pay' => $basicPay,
            'gross' => $gross,
            'nssf' => $nssf,
            'shif' => $shif,
            'housing_levy' => $housingLevy,
            'taxable_pay' => $taxablePay,
            'income_tax' => $incomeTax,
            'personal_relief' => $personalRelief,
            'paye' => $paye,
            'pay_after_tax' => $payAfterTax,
            'deductions' => $deductions,
            'net' => $net,
        ];
    }

    private function findDuplicatePayroll($companyId, $employeeId, $periodStart, $periodEnd, $ignoreId = null)
    {
        $query = DB::table('hrm_payrolls')
            ->where('company_id', $companyId)
            ->where('employee_id', $employeeId)
            ->where('period_start', $periodStart)
            ->where('period_end', $periodEnd);

        if (! empty($ignoreId)) {
            $query->where('id', '!=', $ignoreId);
        }

        if (Schema::hasColumn('hrm_payrolls', 'deleted_at')) {
            $query->whereNull('deleted_at');
        }

        return $query->first();
    }

    public function index(Request $request)
    {
        // Canonical web entrypoint for payroll is the Essentials flow.
        if (! $request->wantsJson() && ! $request->expectsJson()) {
            $target = url('/hrm/payroll');
            if ($request->getQueryString()) {
                $target .= '?' . $request->getQueryString();
            }

            return redirect($target);
        }

        $user = $this->getAuthUser($request);
        if (!$user || (! $user->can('hrm.access') && ! $user->can('hrm.payrolls'))) {
            abort(403);
        }

        $payrolls = [];
        if (Schema::hasTable('hrm_payrolls')) {
            // join with employees and company/business to show names when employee_id column exists
            $companyTable = Schema::hasTable('business') ? 'business' : (Schema::hasTable('companies') ? 'companies' : null);

            $query = DB::table('hrm_payrolls as p');

            $hasEmployeeId = Schema::hasColumn('hrm_payrolls', 'employee_id');
            if ($hasEmployeeId && Schema::hasTable('employees')) {
                $query->leftJoin('employees as e', 'p.employee_id', '=', 'e.id');
            }

            if ($companyTable && Schema::hasTable($companyTable)) {
                $query->leftJoin($companyTable.' as c', 'p.company_id', '=', 'c.id');
            }

            // Select base payroll columns plus optional names
            $selects = ['p.*'];
            if ($hasEmployeeId && Schema::hasTable('employees')) {
                $selects[] = DB::raw("COALESCE(e.username, CONCAT_WS(' ', e.firstname, e.lastname)) as employee_name");
            }
            if ($companyTable && Schema::hasTable($companyTable)) {
                $selects[] = 'c.name as company_name';
            }
            $query->select($selects);

            // Multi-tenant scope — always restrict to current business
            $businessId = session('business.id');
            if ($businessId && Schema::hasColumn('hrm_payrolls', 'business_id')) {
                $query->where('p.business_id', $businessId);
            }

            // apply filters
            if ($request->filled('company_id')) {
                $query->where('p.company_id', $request->get('company_id'));
            }
            if ($hasEmployeeId && $request->filled('employee_id')) {
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
        $user = $this->getAuthUser($request);
        if (!$user || (! $user->can('hrm.access') && ! $user->can('hrm.payrolls'))) {
            abort(403);
        }

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

    public function store(StorePayrollRequest $request)
    {
        $user = $this->getAuthUser($request);
        if (!$user || (! $user->can('hrm.access') && ! $user->can('hrm.payrolls'))) {
            abort(403);
        }

        // Validation is handled by StorePayrollRequest (type-hinted below)
        $companyId   = $request->input('company_id');
        $periodStart = $request->input('period_start');
        $periodEnd   = $request->input('period_end');

        $employeeIds = $request->input('employee_id', []);

        // load defaults for calculations
        $adminSettings = null;
        if (Schema::hasTable('admin_settings')) {
            $adminSettings = AdminSetting::first();
        }
        $companyModel = Schema::hasTable('companies') ? Company::find($companyId) : null;

        $postingWarnings = [];
        $duplicateEmployees = [];

        foreach ($employeeIds as $eid) {
            if ($this->findDuplicatePayroll($companyId, $eid, $periodStart, $periodEnd)) {
                $employee = Schema::hasTable('employees') ? Employee::find($eid) : null;
                $duplicateEmployees[] = $employee->username ?? trim((($employee->firstname ?? '') . ' ' . ($employee->lastname ?? ''))) ?: ('Employee #'.$eid);
            }
        }

        if (! empty($duplicateEmployees)) {
            $message = 'Payroll already exists for: '.implode(', ', $duplicateEmployees).' in the selected period.';
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $message], 422);
            }

            return back()->withInput()->withErrors(['employee_id' => $message]);
        }

        DB::beginTransaction();
        try {
            foreach ($employeeIds as $eid) {
                $values = $this->normalizePayrollValues([
                    'gross' => $request->input('gross.'.$eid, 0),
                    'deductions' => $request->input('deductions.'.$eid, 0),
                    'basic_pay' => $request->input('basic_pay.'.$eid),
                    'nssf' => $request->input('nssf.'.$eid),
                    'shif' => $request->input('shif.'.$eid),
                    'housing_levy' => $request->input('housing_levy.'.$eid),
                    'taxable_pay' => $request->input('taxable_pay.'.$eid),
                    'income_tax' => $request->input('income_tax.'.$eid),
                    'personal_relief' => $request->input('personal_relief.'.$eid),
                    'paye' => $request->input('paye.'.$eid),
                    'pay_after_tax' => $request->input('pay_after_tax.'.$eid),
                    'net' => $request->input('net.'.$eid),
                ], $companyModel, $adminSettings);

                $payload = [
                    'company_id' => $companyId,
                    'employee_id' => $eid,
                    'period_start' => $periodStart,
                    'period_end' => $periodEnd,
                    'basic_pay' => $values['basic_pay'],
                    'gross' => $values['gross'],
                    'nssf' => $values['nssf'],
                    'shif' => $values['shif'],
                    'housing_levy' => $values['housing_levy'],
                    'taxable_pay' => $values['taxable_pay'],
                    'income_tax' => $values['income_tax'],
                    'personal_relief' => $values['personal_relief'],
                    'paye' => $values['paye'],
                    'pay_after_tax' => $values['pay_after_tax'],
                    'deductions' => $values['deductions'],
                    'net' => $values['net'],
                    'posted_to_accounts' => 0,
                    'posted_at' => null,
                    'debit_account_transaction_id' => null,
                    'credit_account_transaction_id' => null,
                    'created_by' => auth()->id() ?? null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                if (Schema::hasColumn('hrm_payrolls', 'business_id')) {
                    $payload['business_id'] = session('business.id');
                }

                $payrollId = DB::table('hrm_payrolls')->insertGetId($payload);

                $payroll = DB::table('hrm_payrolls')->where('id', $payrollId)->first();
                if (! $this->postPayrollToAccounts($payroll, $adminSettings, auth()->id() ?? null)) {
                    $postingWarnings[] = 'Payroll #'.$payrollId.' was saved but not posted to the chart of accounts because payroll account settings are incomplete.';
                }

                $this->logHrmAudit('hrm.payroll.created', [
                    'payroll_id' => $payrollId,
                    'company_id' => $companyId,
                    'employee_id' => $eid,
                    'period_start' => $periodStart,
                    'period_end' => $periodEnd,
                ]);
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('HRM payroll create failed', [
                'company_id' => $companyId,
                'employee_ids' => $employeeIds,
                'period_start' => $periodStart,
                'period_end' => $periodEnd,
                'exception' => $e->getMessage(),
            ]);

            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Unable to create payrolls right now. Please try again.'], 500);
            }
            return back()->withInput()->with('error', 'Unable to create payrolls right now. Please try again.');
        }

        if ($request->wantsJson()) {
            return response()->json(['success' => true]);
        }

        $redirect = redirect()->route('hrm.payrolls.index')->with('success', 'Created successfully');
        if (! empty($postingWarnings)) {
            $redirect->with('warning', implode(' ', $postingWarnings));
        }

        return $redirect;
    }

    public function show(Request $request, $id)
    {
        $user = $this->getAuthUser($request);
        if (!$user || (! $user->can('hrm.access') && ! $user->can('hrm.payrolls'))) {
            abort(403);
        }
        if (! Schema::hasTable('hrm_payrolls')) {
            abort(404);
        }

        $payroll = DB::table('hrm_payrolls')->where('id', $id)->first();

        // Enrich with employee and company details for the payslip view
        $employee = null;
        $company  = null;
        if ($payroll) {
            if (!empty($payroll->employee_id) && Schema::hasTable('employees')) {
                $employee = Employee::with(['office_shift', 'designation', 'department'])->find($payroll->employee_id);
            }
            if (!empty($payroll->company_id)) {
                if (Schema::hasTable('business')) {
                    $company = \App\Business::find($payroll->company_id);
                } elseif (Schema::hasTable('companies')) {
                    $company = \App\Models\Company::find($payroll->company_id);
                }
            }
        }

        if ($request->wantsJson()) {
            return response()->json(['payroll' => $payroll]);
        }

        return view('hrm::payrolls.show', compact('payroll', 'employee', 'company'));
    }

    public function edit(Request $request, $id)
    {
        $user = $this->getAuthUser($request);
        if (!$user || (! $user->can('hrm.access') && ! $user->can('hrm.payrolls'))) {
            abort(403);
        }
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

        $adminSettings = Schema::hasTable('admin_settings') ? AdminSetting::first() : null;

        return view('hrm::payrolls.edit', compact('payroll','companies','employees', 'adminSettings'));
    }

    public function update(Request $request, $id)
    {
        $user = $this->getAuthUser($request);
        if (!$user || (! $user->can('hrm.access') && ! $user->can('hrm.payrolls'))) {
            abort(403);
        }
        $this->validate($request, [
            'company_id' => 'required',
            'employee_id' => 'required',
            'period_start' => 'required|date',
            'period_end' => 'required|date',
            'gross' => 'required|numeric|min:0',
        ]);

        $deductions = (float) $request->input('deductions', 0);
        $gross = (float) $request->input('gross');

        $existingPayroll = DB::table('hrm_payrolls')->where('id', $id)->first();
        if (! $existingPayroll) {
            abort(404);
        }

        // load defaults
        $adminSettings = null;
        if (Schema::hasTable('admin_settings')) {
            $adminSettings = AdminSetting::first();
        }
        $companyModel = Schema::hasTable('companies') ? Company::find($request->input('company_id')) : null;

        $duplicatePayroll = $this->findDuplicatePayroll(
            $request->input('company_id'),
            $request->input('employee_id'),
            $request->input('period_start'),
            $request->input('period_end'),
            $id
        );

        if ($duplicatePayroll) {
            return back()->withInput()->withErrors([
                'employee_id' => 'Payroll already exists for the selected employee and period.',
            ]);
        }

        $values = $this->normalizePayrollValues([
            'gross' => $gross,
            'deductions' => $deductions,
            'basic_pay' => $request->input('basic_pay'),
            'nssf' => $request->input('nssf'),
            'shif' => $request->input('shif'),
            'housing_levy' => $request->input('housing_levy'),
            'taxable_pay' => $request->input('taxable_pay'),
            'income_tax' => $request->input('income_tax'),
            'personal_relief' => $request->input('personal_relief'),
            'paye' => $request->input('paye'),
            'pay_after_tax' => $request->input('pay_after_tax'),
            'net' => $request->input('net'),
        ], $companyModel, $adminSettings);

        DB::beginTransaction();
        try {
            if (! empty($existingPayroll->posted_to_accounts)) {
                $this->reversePayrollPosting($existingPayroll);
            }

            DB::table('hrm_payrolls')->where('id', $id)->update([
                'company_id' => $request->input('company_id'),
                'employee_id' => $request->input('employee_id'),
                'period_start' => $request->input('period_start'),
                'period_end' => $request->input('period_end'),
                'basic_pay' => $values['basic_pay'],
                'gross' => $values['gross'],
                'nssf' => $values['nssf'],
                'shif' => $values['shif'],
                'housing_levy' => $values['housing_levy'],
                'taxable_pay' => $values['taxable_pay'],
                'income_tax' => $values['income_tax'],
                'personal_relief' => $values['personal_relief'],
                'paye' => $values['paye'],
                'pay_after_tax' => $values['pay_after_tax'],
                'deductions' => $values['deductions'],
                'net' => $values['net'],
                'posted_to_accounts' => 0,
                'posted_at' => null,
                'debit_account_transaction_id' => null,
                'credit_account_transaction_id' => null,
                'updated_at' => now(),
            ]);

            $updatedPayroll = DB::table('hrm_payrolls')->where('id', $id)->first();
            $this->postPayrollToAccounts($updatedPayroll, $adminSettings, auth()->id() ?? null);

            $this->logHrmAudit('hrm.payroll.updated', [
                'payroll_id' => $id,
                'company_id' => $request->input('company_id'),
                'employee_id' => $request->input('employee_id'),
                'period_start' => $request->input('period_start'),
                'period_end' => $request->input('period_end'),
            ]);

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('HRM payroll update failed', [
                'payroll_id' => $id,
                'company_id' => $request->input('company_id'),
                'employee_id' => $request->input('employee_id'),
                'period_start' => $request->input('period_start'),
                'period_end' => $request->input('period_end'),
                'exception' => $e->getMessage(),
            ]);

            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Unable to update payroll right now. Please try again.'], 500);
            }

            return back()->withInput()->with('error', 'Unable to update payroll right now. Please try again.');
        }

        return redirect()->route('hrm.payrolls.index')->with('success', 'Updated successfully');
    }

    /**
     * P9 Annual Tax Certificate — aggregate all payrolls for an employee
     * across a tax year into the Kenya KRA P9 format.
     */
    public function p9(Request $request)
    {
        $user = $this->getAuthUser($request);
        if (!$user || (! $user->can('hrm.access') && ! $user->can('hrm.payrolls'))) {
            abort(403);
        }

        $employeeId = $request->get('employee_id');
        $year       = (int) $request->get('year', now()->year);

        // Load all employees for the selector
        $employees = Schema::hasTable('employees')
            ? Employee::where('deleted_at', null)->orderBy('firstname')->get(['id','firstname','lastname','username'])
            : collect([]);

        $rows      = collect(); // monthly rows
        $employee  = null;
        $company   = null;
        $annualTotals = [];

        if ($employeeId && Schema::hasTable('hrm_payrolls')) {
            $employee = Employee::with(['designation', 'department', 'office_shift'])->find($employeeId);

            $records = DB::table('hrm_payrolls')
                ->where('employee_id', $employeeId)
                ->whereYear('period_start', $year)
                ->orderBy('period_start')
                ->get();

            // Build one row per calendar month Jan–Dec
            $monthlyData = [];
            foreach ($records as $r) {
                $month = (int) \Carbon\Carbon::parse($r->period_start)->format('n');
                // If multiple payrolls in same month, sum them
                if (isset($monthlyData[$month])) {
                    foreach (['basic_pay','gross','nssf','shif','housing_levy','taxable_pay','income_tax','personal_relief','paye','pay_after_tax','deductions','net'] as $col) {
                        $monthlyData[$month][$col] += (float) ($r->$col ?? 0);
                    }
                } else {
                    $monthlyData[$month] = [
                        'month'           => $month,
                        'month_name'      => \Carbon\Carbon::create($year, $month, 1)->format('M'),
                        'basic_pay'       => (float) ($r->basic_pay ?? $r->gross ?? 0),
                        'gross'           => (float) ($r->gross ?? 0),
                        'nssf'            => (float) ($r->nssf ?? 0),
                        'shif'            => (float) ($r->shif ?? 0),
                        'housing_levy'    => (float) ($r->housing_levy ?? 0),
                        'taxable_pay'     => (float) ($r->taxable_pay ?? 0),
                        'income_tax'      => (float) ($r->income_tax ?? 0),
                        'personal_relief' => (float) ($r->personal_relief ?? 0),
                        'paye'            => (float) ($r->paye ?? 0),
                        'pay_after_tax'   => (float) ($r->pay_after_tax ?? 0),
                        'deductions'      => (float) ($r->deductions ?? 0),
                        'net'             => (float) ($r->net ?? 0),
                    ];
                }
                // Grab company from first record
                if (!$company && !empty($r->company_id)) {
                    if (Schema::hasTable('business')) {
                        $company = \App\Business::find($r->company_id);
                    } elseif (Schema::hasTable('companies')) {
                        $company = \App\Models\Company::find($r->company_id);
                    }
                }
            }

            // Fill all 12 months (empty = zero row)
            $cols = ['basic_pay','gross','nssf','shif','housing_levy','taxable_pay','income_tax','personal_relief','paye','pay_after_tax','deductions','net'];
            for ($m = 1; $m <= 12; $m++) {
                if (!isset($monthlyData[$m])) {
                    $row = ['month' => $m, 'month_name' => \Carbon\Carbon::create($year, $m, 1)->format('M')];
                    foreach ($cols as $c) { $row[$c] = 0; }
                    $monthlyData[$m] = $row;
                }
            }
            ksort($monthlyData);
            $rows = collect(array_values($monthlyData));

            // Annual totals
            $annualTotals = [];
            foreach ($cols as $c) {
                $annualTotals[$c] = $rows->sum($c);
            }
        }

        $years = range(now()->year, max(2020, now()->year - 5));

        if ($request->wantsJson()) {
            return response()->json([
                'rows' => $rows,
                'annual_totals' => $annualTotals,
            ]);
        }

        return view('hrm::payrolls.p9', compact(
            'employees', 'employee', 'company', 'rows', 'annualTotals', 'year', 'years', 'employeeId'
        ));
    }

    public function destroy(Request $request, $id)
    {
        $user = $this->getAuthUser($request);
        if (!$user || !$user->can('hrm.access')) {
            abort(403);
        }
        if (! Schema::hasTable('hrm_payrolls')) {
            abort(404);
        }

        $payroll = DB::table('hrm_payrolls')->where('id', $id)->first();
        if ($payroll && ! empty($payroll->posted_to_accounts)) {
            $this->reversePayrollPosting($payroll);
        }

        DB::table('hrm_payrolls')->where('id', $id)->delete();

        $this->logHrmAudit('hrm.payroll.deleted', [
            'payroll_id' => $id,
        ]);

        if ($request->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->route('hrm.payrolls.index')->with('success', 'Deleted successfully');
    }
}
