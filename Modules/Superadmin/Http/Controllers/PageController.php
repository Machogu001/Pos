<?php

namespace Modules\Superadmin\Http\Controllers;

use App\Http\Controllers\Controller;

class PageController extends Controller
{
    public function showPage($slug)
    {
        return redirect()->route('home');
    }
}
