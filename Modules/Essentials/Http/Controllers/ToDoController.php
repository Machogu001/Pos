<?php

namespace Modules\Essentials\Http\Controllers;

use App\Http\Controllers\Controller;

class ToDoController extends Controller
{
    public function index()
    {
        if (! auth()->user()->can('essentials.access')) {
            abort(403, 'Unauthorized action.');
        }
        return redirect()->route('home');
    }

    public function create()
    {
        if (request()->ajax()) {
            return response()->json(['html' => '<p class="text-center p-4">Essentials module not fully installed.</p>']);
        }
        return redirect()->route('home');
    }

    public function store()
    {
        return redirect()->route('home');
    }

    public function edit($id)
    {
        if (request()->ajax()) {
            return response()->json(['html' => '<p class="text-center p-4">Essentials module not fully installed.</p>']);
        }
        return redirect()->route('home');
    }

    public function update($id)
    {
        return redirect()->route('home');
    }

    public function destroy($id)
    {
        return redirect()->route('home');
    }
}
