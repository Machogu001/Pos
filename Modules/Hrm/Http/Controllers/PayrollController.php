<?php

namespace Modules\Hrm\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use App\Business;
use App\Models\Company;
use App\Models\Employee;
use DB;

class PayrollController extends Controller
{
    protected function getAuthUser($request)
    {
        return $request->user('api') ?? $request->user() ?? auth()->user();
    }

    public function index(Request $request)
    {
        $this->authorizeForUser($this->getAuthUser($request), 'view', Employee::class);

        $payrolls = [];
        if (Schema::hasTable('hrm_payrolls')) {
            $payrolls = DB::table('hrm_payrolls')->orderBy('created_at', 'desc')->get();
        }

        if ($request->wantsJson()) {
            return response()->json(['payrolls' => $payrolls]);
        }

        return view('hrm::payrolls.index', compact('payrolls'));
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

        if ($request->wantsJson()) {
            return response()->json(['companies' => $companies, 'employees' => $employees]);
        }

        return view('hrm::payrolls.create', compact('companies', 'employees'));
    }

    public function store(Request $request)
    {
        $this->authorizeForUser($this->getAuthUser($request), 'create', Employee::class);

        $this->validate($request, [
            'company_id' => 'required',
            'employee_id' => 'required',
            'period_start' => 'required|date',
            'period_end' => 'required|date',
            'gross' => 'required|numeric|min:0',
        ]);

        $deductions = (float) $request->input('deductions', 0);
        $gross = (float) $request->input('gross');
        $net = max(0, $gross - $deductions);

        DB::table('hrm_payrolls')->insert([
            'company_id' => $request->input('company_id'),
            'employee_id' => $request->input('employee_id'),
            'period_start' => $request->input('period_start'),
            'period_end' => $request->input('period_end'),
            'gross' => $gross,
            'deductions' => $deductions,
            'net' => $net,
            'created_by' => auth()->id() ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if ($request->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect(url('/hrm/payrolls'))->with('success', 'Payroll created');
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
}
