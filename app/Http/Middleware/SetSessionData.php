<?php

namespace App\Http\Middleware;

use App\Business;
use App\Utils\BusinessUtil;
use Closure;
use Illuminate\Support\Facades\Auth;

class SetSessionData
{
    /**
     * Checks if session data is set or not for a user. If data is not set then set it.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (! $request->session()->has('user') ||
            ! $request->session()->has('business') ||
            ! $request->session()->has('currency') ||
            ! $request->session()->has('financial_year')) {
            $business_util = new BusinessUtil;

            $user = Auth::user();
            $session_data = ['id' => $user->id,
                'surname' => $user->surname,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => $user->email,
                'business_id' => $user->business_id,
                'language' => $user->language,
            ];

            $request->session()->put('user', $session_data);

            // System superadmin with no assigned business — use the pinned
            // business (set via switcher) or fall back to the first registered one.
            if (empty($user->business_id) && $user->role === 'admin') {
                $pinnedId = session('superadmin_active_business_id');
                $business = ($pinnedId ? Business::find($pinnedId) : null) ?? Business::first();

                if (! $business) {
                    // No business at all — send to module management / create business
                    if (! $request->is('manage-modules*') &&
                        ! $request->is('business/create') &&
                        ! $request->is('logout')) {
                        return redirect()->route('manage-modules.index');
                    }
                    return $next($request);
                }

                // A business exists — let superadmin operate within it
                $currency = $business->currency;
                $currency_data = ['id' => $currency->id,
                    'code' => $currency->code,
                    'symbol' => $currency->symbol,
                    'thousand_separator' => $currency->thousand_separator,
                    'decimal_separator' => $currency->decimal_separator,
                ];

                $request->session()->put('business', $business);
                $request->session()->put('currency', $currency_data);

                $financial_year = $business_util->getCurrentFinancialYear($business->id);
                $request->session()->put('financial_year', $financial_year);

                return $next($request);
            }

            $business = Business::findOrFail($user->business_id);

            $currency = $business->currency;
            $currency_data = ['id' => $currency->id,
                'code' => $currency->code,
                'symbol' => $currency->symbol,
                'thousand_separator' => $currency->thousand_separator,
                'decimal_separator' => $currency->decimal_separator,
            ];

            $request->session()->put('business', $business);
            $request->session()->put('business.date_format', config('constants.default_date_format'));
            $request->session()->put('currency', $currency_data);

            //set current financial year to session
            $financial_year = $business_util->getCurrentFinancialYear($business->id);
            $request->session()->put('financial_year', $financial_year);
        }

        return $next($request);
    }
}
