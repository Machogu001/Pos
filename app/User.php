<?php

namespace App;

use DB;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Laravel\Passport\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\Relations\HasMany; // Add this import

class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes, HasRoles, HasApiTokens;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'otp_login_enabled' => 'boolean',
        'stock_alert_sms_notification_enabled' => 'boolean',
        'has_active_subscription' => 'boolean',
        'subscription_expires_at' => 'datetime',
        'last_login_at' => 'datetime',
    ];

    // change api guard to web
    protected $guard_name = 'web';

    /**
     * Dates casting for Carbon
     */
    protected $dates = [
        'subscription_end_date',
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = ['subscription_status', 'active_subscription'];

    /**
     * Relationships
     */
    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function contactAccess()
    {
        return $this->belongsToMany(Contact::class, 'user_contact_access');
    }

    public function documentsAndnote()
    {
        return $this->morphMany(DocumentAndNote::class, 'notable');
    }

    public function media()
    {
        return $this->morphOne(Media::class, 'model');
    }

    public function contact()
    {
        return $this->belongsTo(\Modules\Crm\Entities\CrmContact::class, 'crm_contact_id');
    }

    public function preferredLocation()
    {
        return $this->belongsTo(BusinessLocation::class, 'preferred_location_id');
    }

    public function locations()
    {
        return $this->business->locations();
    }

    /**
     * M-Pesa payments relationship
     */
    public function mpesaPayments(): HasMany
    {
        return $this->hasMany(MpesaPayment::class);
    }

    /**
     * Subscriptions relationships
     */
    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    public function activeSubscriptionRelation()
    {
        return $this->hasOne(Subscription::class)
                    ->where('status', 'active')
                    ->where('end_date', '>=', now());
    }

    public function getActiveSubscriptionAttribute()
    {
        return $this->subscriptions()
                    ->where('status', 'active')
                    ->where('end_date', '>=', now())
                    ->latest('end_date')
                    ->first();
    }

    /**
     * Check if user can perform transaction based on subscription
     */
    public function canPerformTransaction()
    {
        if (!$this->has_active_subscription) {
            return false;
        }
        
        $activeSubscription = $this->subscriptions()
            ->where('status', 'active')
            ->where('end_date', '>', now())
            ->first();
            
        return !is_null($activeSubscription);
    }

    /**
     * Get subscription status attribute
     */
    public function getSubscriptionStatusAttribute()
    {
        $subscription = $this->activeSubscription;
        
        if (!$subscription) {
            return 'inactive';
        }
        
        if ($subscription->end_date->diffInDays(now()) <= 7) {
            return 'expiring_soon';
        }
        
        return 'active';
    }

    /**
     * Helpers & scopes
     */
    public function scopeUser($query)
    {
        return $query->where('users.user_type', 'user');
    }

    public function isAdmin()
    {
        return $this->role === 'admin';
    }

    /**
     * Returns true if this user is the designated system-level superuser.
     * System superusers are listed in the ADMINISTRATOR_USERNAMES env var
     * and have role='admin' on the users table. They bypass subscription
     * checks and can access backup/module-management gates.
     */
    public function isSuperAdmin(): bool
    {
        // Primary check: role column is the authoritative DB marker for superuser
        if ($this->role === 'admin') {
            // Cross-verify with ADMINISTRATOR_USERNAMES to prevent privilege
            // escalation if role column is incorrectly set on a business admin.
            $administrator_list = config('constants.administrator_usernames');
            if (!empty($administrator_list)) {
                return in_array(
                    strtolower($this->username),
                    array_map('trim', explode(',', strtolower($administrator_list)))
                );
            }
            // If ADMINISTRATOR_USERNAMES not set, fall back to role column alone.
            return true;
        }
        return false;
    }

    public function hasActiveSubscription()
    {
        return $this->subscription_end_date
            ? Carbon::parse($this->subscription_end_date)->gte(now())
            : false;
    }

    public function getUserFullNameAttribute()
    {
        return "{$this->surname} {$this->first_name} {$this->last_name}";
    }

    public function getRoleNameAttribute()
    {
        $role_name_array = $this->getRoleNames();
        return !empty($role_name_array[0]) ? explode('#', $role_name_array[0])[0] : '';
    }

    public function getImageUrlAttribute()
    {
        return $this->media->display_url ?? 'https://ui-avatars.com/api/?name='.$this->first_name;
    }

    public function getPhoneAttribute()
    {
        return $this->contact_number ?? null;
    }

    /**
     * Permission check that falls back to role/permission pivots when cache is stale.
     */
    public function hasAnyPermissionSafe(array $permissionNames): bool
    {
        foreach ($permissionNames as $permissionName) {
            if ($this->can($permissionName)) {
                return true;
            }
        }

        $tableNames = config('permission.table_names', []);
        $permissionsTable = $tableNames['permissions'] ?? 'permissions';
        $modelHasPermissionsTable = $tableNames['model_has_permissions'] ?? 'model_has_permissions';
        $modelHasRolesTable = $tableNames['model_has_roles'] ?? 'model_has_roles';
        $roleHasPermissionsTable = $tableNames['role_has_permissions'] ?? 'role_has_permissions';

        $permissionIds = DB::table($permissionsTable)
            ->whereIn('name', $permissionNames)
            ->pluck('id');

        if ($permissionIds->isEmpty()) {
            return false;
        }

        $modelType = $this->getMorphClass();

        $hasDirectPermission = DB::table($modelHasPermissionsTable)
            ->where('model_type', $modelType)
            ->where('model_id', $this->id)
            ->whereIn('permission_id', $permissionIds)
            ->exists();

        if ($hasDirectPermission) {
            return true;
        }

        return DB::table($modelHasRolesTable . ' as mhr')
            ->join($roleHasPermissionsTable . ' as rhp', 'mhr.role_id', '=', 'rhp.role_id')
            ->where('mhr.model_type', $modelType)
            ->where('mhr.model_id', $this->id)
            ->whereIn('rhp.permission_id', $permissionIds)
            ->exists();
    }

    public function permitted_locations($business_id = null)
    {
        if ($this->can('access_all_locations')) {
            return 'all';
        }

        $business_id ??= auth()->check() ? auth()->user()->business_id : (session('business.id') ?? null);

        $permitted_locations = [];
        $all_locations = BusinessLocation::where('business_id', $business_id)->get();
        $permissions = $this->permissions->pluck('name')->all();

        foreach ($all_locations as $location) {
            if (in_array('location.' . $location->id, $permissions)) {
                $permitted_locations[] = $location->id;
            }
        }

        return $permitted_locations;
    }

    public static function can_access_this_location($location_id, $business_id = null)
    {
        $permitted_locations = auth()->user()->permitted_locations($business_id);

        return $permitted_locations === 'all' || in_array($location_id, $permitted_locations);
    }

    public function getDefaultLocation()
    {
        if ($this->preferred_location_id) {
            return BusinessLocation::find($this->preferred_location_id);
        }

        $permitted = $this->permitted_locations();
        if ($permitted !== 'all' && !empty($permitted)) {
            return BusinessLocation::find($permitted[0]);
        }

        return $this->business->locations()->first();
    }

    public function accessibleLocations()
    {
        $permitted = $this->permitted_locations();
        return $permitted === 'all'
            ? $this->business->locations()
            : $this->business->locations()->whereIn('id', $permitted);
    }

    /**
     * Check if a user has specific selected contacts assigned to them.
     * Used to decide whether to enforce contact-level access restrictions.
     */
    public static function isSelectedContacts($user_id): bool
    {
        $user = self::withCount('contactAccess')->find($user_id);

        return $user ? ($user->contact_access_count > 0) : false;
    }

    /**
     * Create user helper
     */
    public static function create_user($details)
    {
        return self::create([
            'surname' => $details['surname'],
            'first_name' => $details['first_name'],
            'last_name' => $details['last_name'],
            'username' => $details['username'],
            'email' => $details['email'],
            'password' => Hash::make($details['password']),
            'language' => $details['language'] ?? 'en',
        ]);
    }

    /**
     * Get list of users for dropdowns
     *
     * @param int|null $business_id
     * @param bool $show_all
     * @return array
     */
    public static function forDropdown($business_id = null, $show_all = false)
    {
        $query = User::select('id', 'surname', 'first_name', 'last_name', 'username');

        if ($business_id) {
            $query->where('business_id', $business_id);
        }

        $users = $query->get();

        $dropdown = $users->mapWithKeys(function ($user) {
            $full_name = trim($user->surname . ' ' . $user->first_name . ' ' . $user->last_name);
            return [
                $user->id => $full_name . ' (' . $user->username . ')'
            ];
        });

        if ($show_all) {
            return ["" => __("lang_v1.all")] + $dropdown->toArray();
        }

        return $dropdown->toArray();
    }

    /**
     * Backward compatibility: alias of forDropdown
     *
     * @param int|null $business_id
     * @param bool $show_all
     * @return array
     */
    public static function allUsersDropdown($business_id = null, $show_all = false)
    {
        return self::forDropdown($business_id, $show_all);
    }
    // In User model
public function payments()
{
    return $this->hasMany(Payment::class); // Adjust based on your payment model name
}
}
