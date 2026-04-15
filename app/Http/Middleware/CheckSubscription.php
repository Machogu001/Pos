<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class CheckSubscription
{
    public function handle(Request $request, Closure $next)
    {
        // Check if subscription enforcement is enabled
        $settings = \App\AdminSetting::first();
        
        // If no settings exist yet (fresh install) or subscription not required, allow access
        if (!$settings || !($settings->subscription_required ?? false)) {
            return $next($request);
        }

        $user = Auth::user();
        
        if (!$user) {
            return $next($request);
        }
        
        // Allow access to subscription-related routes, auth routes, and admin routes
        if ($request->is('subscription/*') || 
            $request->is('payment/*') || 
            $request->is('logout') ||
            $request->is('login') ||
            $request->is('register') ||
            $request->is('admin/*') || // Exclude all admin routes
            $this->isSystemAdmin($user)) { // Check if user is system admin
            return $next($request);
        }
        
        // Check if user has active subscription
        if (!$this->hasActiveSubscription($user)) {
            return redirect()->route('subscription.plans')
                ->with('error', 'Your subscription has expired. Please renew to continue using the system.');
        }
        
        return $next($request);
    }
    
    private function hasActiveSubscription($user)
    {
        if (!$user) return false;
        
        // Check if user has an active subscription
        $activeSubscription = $user->subscriptions()
            ->where('status', 'active')
            ->where('end_date', '>', Carbon::now())
            ->first();
            
        return !is_null($activeSubscription);
    }
    
    /**
     * Check if user is a system administrator
     */
    private function isSystemAdmin($user)
    {
        // Only the designated system superuser bypasses subscription enforcement.
        // Business admins (Spatie Admin#business_id role) are NOT exempt —
        // they must hold an active subscription just like regular users.
        if (!$user) return false;

        // Use the dedicated isSuperAdmin() method which cross-checks both the
        // role='admin' DB column and the ADMINISTRATOR_USERNAMES config.
        if (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) {
            return true;
        }

        return false;
    }
}