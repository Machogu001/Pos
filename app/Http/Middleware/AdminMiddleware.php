<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();
        
        if (!$user || !$user->isAdmin()) {
            return redirect()->route('home')
                ->with('error', 'You do not have permission to access this area.');
        }
        
        return $next($request);
    }
}