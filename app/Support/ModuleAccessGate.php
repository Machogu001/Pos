<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

class ModuleAccessGate
{
    public static function userCanAccess($user, array $exactPermissions = [], array $permissionPrefixes = []): bool
    {
        if (empty($user)) {
            return false;
        }

        foreach ($exactPermissions as $permission) {
            if ($user->can($permission)) {
                return true;
            }
        }

        if (! empty($permissionPrefixes)) {
            $permissionNames = $user->getAllPermissions()->pluck('name');
            foreach ($permissionPrefixes as $prefix) {
                if ($permissionNames->filter(fn ($permissionName) => str_starts_with($permissionName, $prefix))->isNotEmpty()) {
                    return true;
                }
            }
        }

        $tableNames = config('permission.table_names', []);
        $permissionsTable = $tableNames['permissions'] ?? 'permissions';
        $modelHasPermissionsTable = $tableNames['model_has_permissions'] ?? 'model_has_permissions';
        $modelHasRolesTable = $tableNames['model_has_roles'] ?? 'model_has_roles';
        $roleHasPermissionsTable = $tableNames['role_has_permissions'] ?? 'role_has_permissions';

        $permissionQuery = DB::table($permissionsTable)->where('guard_name', $user->guard_name ?? 'web');

        $permissionQuery->where(function ($query) use ($exactPermissions, $permissionPrefixes) {
            foreach ($exactPermissions as $permission) {
                $query->orWhere('name', $permission);
            }

            foreach ($permissionPrefixes as $prefix) {
                $query->orWhere('name', 'like', $prefix . '%');
            }
        });

        $permissionIds = $permissionQuery->pluck('id');

        if ($permissionIds->isEmpty()) {
            return false;
        }

        $modelType = $user->getMorphClass();
        $userId = $user->getKey();

        $hasDirectPermission = DB::table($modelHasPermissionsTable)
            ->where('model_type', $modelType)
            ->where('model_id', $userId)
            ->whereIn('permission_id', $permissionIds)
            ->exists();

        if ($hasDirectPermission) {
            return true;
        }

        return DB::table($modelHasRolesTable . ' as mhr')
            ->join($roleHasPermissionsTable . ' as rhp', 'mhr.role_id', '=', 'rhp.role_id')
            ->where('mhr.model_type', $modelType)
            ->where('mhr.model_id', $userId)
            ->whereIn('rhp.permission_id', $permissionIds)
            ->exists();
    }
}
