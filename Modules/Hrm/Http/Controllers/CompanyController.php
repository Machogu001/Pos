<?php

namespace Modules\Hrm\Http\Controllers;
use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use App\Models\Department;
use App\Models\Company;
use Carbon\Carbon;
use DB;
use Illuminate\Support\Facades\Schema;

class CompanyController extends Controller
{

    /**
     * Ensure Laravel Passport encryption keys exist.
     * If keys are missing and not provided via env, generate them.
     */
    protected function ensurePassportKeys()
    {
        $privateEnv = config('passport.private_key');
        $publicEnv = config('passport.public_key');

        $privateFile = storage_path('oauth-private.key');
        $publicFile = storage_path('oauth-public.key');

        $filesExist = file_exists($privateFile) && file_exists($publicFile);
        $envProvided = !empty($privateEnv) && !empty($publicEnv);

        if (!$filesExist && !$envProvided) {
            try {
                \Artisan::call('passport:keys');
            } catch (\Throwable $e) {
                // Swallow to avoid breaking request flow; logging will show if needed.
            }
        }
    }

    protected function getAuthUser($request)
    {
        // Make sure Passport keys are available before trying to resolve the user
        $this->ensurePassportKeys();
        return $request->user('api') ?? $request->user() ?? auth()->user();
    }

    //----------- GET ALL  company --------------\\

    public function index(Request $request)
    {
        $this->authorizeForUser($this->getAuthUser($request), 'view', Company::class);

        // If companies table does not exist yet, return empty view/response to avoid SQL errors
        if (!Schema::hasTable('companies')) {
            if ($request->expectsJson()) {
                return response()->json(['companies' => [], 'totalRows' => 0]);
            }
            // Table missing: render index with empty data to avoid view errors
            return view('hrm::companies.index', ['companies' => [], 'totalRows' => 0, 'perPage' => 0, 'pageStart' => 1]);
        }

        // How many items do you want to display.
        $perPage = $request->limit;
        $pageStart = \Request::get('page', 1);
        // Start displaying items from this number; only computed when perPage is numeric
        $offSet = 0;
        if (is_numeric($perPage) && intval($perPage) > 0) {
            $offSet = ($pageStart * intval($perPage)) - intval($perPage);
        }
        // sanitize order field and direction
        $order = $request->SortField;
        $dir = $request->SortType;
        if (!in_array(strtolower($dir ?? ''), ['asc', 'desc'])) {
            $dir = 'desc';
        }
        // provide a safe default if order is empty or invalid
        $allowed = ['id', 'name', 'created_at', 'updated_at', 'email', 'phone'];
        if (empty($order) || !in_array($order, $allowed)) {
            $order = 'id';
        }

        $companies = Company::where('deleted_at', '=', null)

        // Search With Multiple Param
            ->where(function ($query) use ($request) {
                return $query->when($request->filled('search'), function ($query) use ($request) {
                    return $query->where('name', 'LIKE', "%{$request->search}%")
                        ->orWhere('phone', 'LIKE', "%{$request->search}%")
                        ->orWhere('country', 'LIKE', "%{$request->search}%")
                        ->orWhere('email', 'LIKE', "%{$request->search}%");
                });
            });
        $totalRows = $companies->count();
        if ($perPage == "-1") {
            $perPage = $totalRows;
        }

        // Only apply offset/limit when perPage is a positive integer
        if (is_numeric($perPage) && intval($perPage) > 0) {
            $companies = $companies->offset($offSet)
                ->limit(intval($perPage))
                ->orderBy($order, $dir)
                ->get();
        } else {
            $companies = $companies->orderBy($order, $dir)->get();
        }

        if ($request->expectsJson()) {
            return response()->json([
                'companies' => $companies,
                'totalRows' => $totalRows,
            ]);
        }

    // Pass computed data to the view so the HTML listing can render companies
    return view('hrm::companies.index', compact('companies', 'totalRows', 'perPage', 'pageStart'));
    }

    //----------- Store new Company --------------\\

    public function store(Request $request)
    {
        $this->authorizeForUser($this->getAuthUser($request), 'create', Company::class);

        request()->validate([
            'name'      => 'required|string',
        ]);

        Company::create([
            'name'    => $request['name'],
            'email'   => $request['email'],
            'phone'   => $request['phone'],
            'country' => $request['country'],
            'nssf_percent' => $request->input('nssf_percent'),
            'shif_percent' => $request->input('shif_percent'),
            'housing_percent' => $request->input('housing_percent'),
            'tax_percent' => $request->input('tax_percent'),
            'personal_relief' => $request->input('personal_relief'),
        ]);

        if ($request->expectsJson()) {
            return response()->json(['success' => true]);
        }

    return redirect()->route('hrm.companies.index')->with('success', 'Company created');
    }

    //------------ function show -----------\\

    public function show($id){
        //
        
        }

    //----------- Show form to create Company --------------\\

    public function create(Request $request)
    {
        $this->authorizeForUser($this->getAuthUser($request), 'create', Company::class);

        // If companies table doesn't exist yet, just render the view (empty form)
        return view('hrm::companies.create');
    }

    //----------- Show form to edit Company --------------\\

    public function edit(Request $request, $id)
    {
        $this->authorizeForUser($this->getAuthUser($request), 'update', Company::class);

        // Attempt to find the company; if table missing or company not found, pass null to view
        $company = null;
        if (Schema::hasTable('companies')) {
            $company = Company::where('deleted_at', '=', null)->find($id);
        }

        return view('hrm::companies.edit', compact('company'));
    }

    //-----------Update Warehouse --------------\\

    public function update(Request $request, $id)
    {
        $this->authorizeForUser($this->getAuthUser($request), 'update', Company::class);

        request()->validate([
            'name'      => 'required|string',
        ]);

        Company::whereId($id)->update([
            'name'    => $request['name'],
            'email'   => $request['email'],
            'phone'   => $request['phone'],
            'country' => $request['country'],
            'nssf_percent' => $request->input('nssf_percent'),
            'shif_percent' => $request->input('shif_percent'),
            'housing_percent' => $request->input('housing_percent'),
            'tax_percent' => $request->input('tax_percent'),
            'personal_relief' => $request->input('personal_relief'),
        ]);

        if ($request->expectsJson()) {
            return response()->json(['success' => true]);
        }

    return redirect()->route('hrm.companies.index')->with('success', 'Company updated');
    }

    //----------- Delete  company --------------\\

    public function destroy(Request $request, $id)
    {
        $this->authorizeForUser($this->getAuthUser($request), 'delete', Company::class);

        Company::whereId($id)->update([
            'deleted_at' => Carbon::now(),
        ]);


        if ($request->expectsJson()) {
            return response()->json(['success' => true]);
        }

    return redirect()->route('hrm.companies.index')->with('success', 'Company deleted');
    }

    //-------------- Delete by selection  ---------------\\

    public function delete_by_selection(Request $request)
    {

        $this->authorizeForUser($this->getAuthUser($request), 'delete', Company::class);

        $selectedIds = $request->selectedIds;
        foreach ($selectedIds as $company_id) {
            Company::whereId($company_id)->update([
                'deleted_at' => Carbon::now(),
            ]);
        }

        return response()->json(['success' => true]);
    }

    //----------- GET ALL  Company --------------\\
    
    public function Get_all_Company()
    {
        $companies = Company::where('deleted_at', '=', null)
        ->orderBy('id', 'desc')
        ->get(['id','name']);

        return response()->json($companies);
    }

}
