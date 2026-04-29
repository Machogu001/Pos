<?php

namespace Modules\Repair\Http\Controllers;

use App\Http\Controllers\Controller;

class RepairController extends Controller
{
    public function index()
    {
        return redirect()->route('home');
    }

    public function printLabel($id)
    {
        return redirect()->route('home');
    }
}
