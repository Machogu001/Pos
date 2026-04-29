<?php

namespace Modules\Repair\Http\Controllers;

use App\Http\Controllers\Controller;

class CustomerRepairStatusController extends Controller
{
    public function index()
    {
        return redirect()->route('home');
    }
}
