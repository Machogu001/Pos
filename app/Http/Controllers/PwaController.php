<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class PwaController extends Controller
{
    public function markInstalled(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['success' => false], 401);
        }
        $user->pwa_installed_at = Carbon::now();
        $user->save();
        return response()->json(['success' => true]);
    }

    public function markDismissed(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['success' => false], 401);
        }
        $user->pwa_install_dismissed_at = Carbon::now();
        $user->save();
        return response()->json(['success' => true]);
    }
}
