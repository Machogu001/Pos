<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Company;
use App\Business;

class CompanyBusinessMappingController extends Controller
{
    public function index()
    {
        // List companies and businesses
        $companies = Company::orderBy('id', 'desc')->get();
        $businesses = Business::orderBy('id', 'desc')->get(['id', 'name']);

        return view('admin.company_business_mapping.index', compact('companies', 'businesses'));
    }

    public function update(Request $request, Company $company)
    {
        $data = $request->validate([
            'business_id' => 'nullable|exists:business,id',
        ]);

        $company->business_id = $data['business_id'] ?? null;
        $company->save();

        return redirect()->back()->with('success', 'Mapping updated');
    }
}
