<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        'App\\Model' => 'App\\Policies\\ModelPolicy',
        \App\Models\Employee::class => \App\Policies\EmployeePolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();
        
    Gate::before(function ($user, $ability) {
            if (in_array($ability, ['backup', 'superadmin',
                'manage_modules', ])) {
                $administrator_list = config('constants.administrator_usernames');

                if (in_array(strtolower($user->username), explode(',', strtolower($administrator_list)))) {
                    return true;
                }
            } else {
                if ($user->hasRole('Admin#'.$user->business_id)) {
                    return true;
                }
            }
        });

        // Register additional HRM policies if the models exist
        $policies = [
            \App\Models\Leave::class => \App\Policies\LeavePolicy::class,
            \App\Models\Payroll::class => \App\Policies\PayrollPolicy::class,
            \App\Models\Attendance::class => \App\Policies\AttendancePolicy::class,
            \App\Models\Holiday::class => \App\Policies\HolidayPolicy::class,
        ];

        foreach ($policies as $model => $policy) {
            try {
                if (class_exists($model) && class_exists($policy)) {
                    $this->policies[$model] = $policy;
                }
            } catch (\Exception $e) {
                // ignore
            }
        }
    }
}
