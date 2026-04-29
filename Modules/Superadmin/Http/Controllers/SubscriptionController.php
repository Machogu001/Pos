<?php

namespace Modules\Superadmin\Http\Controllers;

use App\Http\Controllers\Controller;

class SubscriptionController extends Controller
{
    public function index()
    {
        return redirect()->route('home');
    }
}
