<?php

namespace Modules\Superadmin\Http\Controllers;

use App\Http\Controllers\Controller;

class PricingController extends Controller
{
    public function index()
    {
        return redirect()->route('home');
    }
}
