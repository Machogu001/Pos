<?php

namespace Modules\Hrm\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class HrmController extends Controller
{
    /**
     * Display a simple HRM dashboard/index
     */
    public function index(Request $request)
    {
        // Show a simple HRM employees page (blade).
        // Provide a small employees collection so the blade can render a table.
        $employees = collect([]);
        if (\Illuminate\Support\Facades\Schema::hasTable('employees')) {
            $employees = \App\Models\Employee::whereNull('deleted_at')->limit(50)->get();
        }

        return view('hrm::employees.index', compact('employees'));
    }
}
