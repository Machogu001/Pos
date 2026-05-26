<?php

namespace App\Console\Commands;

use App\Business;
use App\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CheckPosHeaderHealth extends Command
{
    protected $signature = 'pos:health:header {--business-id= : Check only one business id}';

    protected $description = 'Validate POS header visibility prerequisites (module + permissions + role mappings)';

    public function handle(): int
    {
        $businessId = $this->option('business-id');
        $businesses = Business::query();

        if (!empty($businessId)) {
            $businesses->where('id', (int) $businessId);
        }

        $businesses = $businesses->get();

        if ($businesses->isEmpty()) {
            $this->warn('No businesses found. Nothing to validate.');

            return self::SUCCESS;
        }

        $hasFailures = false;

        foreach ($businesses as $business) {
            $this->line('');
            $this->info('Business #' . $business->id . ' - ' . $business->name);

            $enabledModules = $this->normalizeModules($business->enabled_modules);
            $isPosEnabled = in_array('pos_sale', $enabledModules, true);

            $this->line('  POS module enabled: ' . ($isPosEnabled ? 'YES' : 'NO'));
            $this->line('  Enabled modules: ' . implode(',', $enabledModules));

            if (!$isPosEnabled) {
                $this->line('  Skipping permission checks because pos_sale is disabled.');
                continue;
            }

            $permissionRows = DB::table('permissions')
                ->whereIn('name', ['sell.create', 'direct_sell.access'])
                ->where('guard_name', 'web')
                ->pluck('id', 'name');

            $hasSellCreatePermission = $permissionRows->has('sell.create');
            $hasDirectSellPermission = $permissionRows->has('direct_sell.access');

            $this->line('  permission sell.create exists: ' . ($hasSellCreatePermission ? 'YES' : 'NO'));
            $this->line('  permission direct_sell.access exists: ' . ($hasDirectSellPermission ? 'YES' : 'NO'));

            if (!$hasSellCreatePermission && !$hasDirectSellPermission) {
                $this->error('  FAIL: Neither sell.create nor direct_sell.access permission exists for guard web.');
                $hasFailures = true;
                continue;
            }

            $roleQuery = DB::table('roles')->where('guard_name', 'web');
            if (Schema::hasColumn('roles', 'business_id')) {
                $roleQuery->where('business_id', $business->id);
            } else {
                $roleQuery->where('name', 'like', '%#' . $business->id);
            }

            $roles = $roleQuery->get(['id', 'name']);

            if ($roles->isEmpty()) {
                $this->warn('  WARN: No roles found for this business.');
            }

            $roleIds = $roles->pluck('id');
            $eligiblePermissionIds = array_values(array_filter([
                $permissionRows->get('sell.create'),
                $permissionRows->get('direct_sell.access'),
            ]));

            $roleIdsWithPosCreate = [];
            if (!empty($eligiblePermissionIds) && $roleIds->isNotEmpty()) {
                $roleIdsWithPosCreate = DB::table('role_has_permissions')
                    ->whereIn('role_id', $roleIds)
                    ->whereIn('permission_id', $eligiblePermissionIds)
                    ->pluck('role_id')
                    ->unique()
                    ->values()
                    ->all();
            }

            $this->line('  Roles with POS create access: ' . count($roleIdsWithPosCreate));

            $users = User::query()
                ->where('business_id', $business->id)
                ->where('user_type', 'user')
                ->get();

            if ($users->isEmpty()) {
                $this->warn('  WARN: No staff users found for this business.');
                continue;
            }

            $usersWithHeaderAccess = 0;
            $usersWithoutHeaderAccess = [];

            foreach ($users as $user) {
                if ($user->hasAnyPermissionSafe(['sell.create', 'direct_sell.access'])) {
                    $usersWithHeaderAccess++;
                } else {
                    $usersWithoutHeaderAccess[] = $user->username ?: ('user#' . $user->id);
                }
            }

            $this->line('  Staff with POS header access: ' . $usersWithHeaderAccess . '/' . $users->count());

            if ($usersWithHeaderAccess === 0) {
                $this->error('  FAIL: No staff user can access POS header button.');
                $this->line('  Users without POS header access: ' . implode(', ', $usersWithoutHeaderAccess));
                $hasFailures = true;
            } elseif (!empty($usersWithoutHeaderAccess)) {
                $this->warn('  WARN: Some staff users cannot see POS header button: ' . implode(', ', $usersWithoutHeaderAccess));
            } else {
                $this->info('  PASS: POS header prerequisites look good.');
            }
        }

        $this->line('');
        if ($hasFailures) {
            $this->error('POS header health check finished with failures.');

            return self::FAILURE;
        }

        $this->info('POS header health check finished successfully.');

        return self::SUCCESS;
    }

    private function normalizeModules($modules): array
    {
        if (is_string($modules)) {
            $decoded = json_decode($modules, true);
            $modules = is_array($decoded) ? $decoded : [];
        }

        if (!is_array($modules)) {
            return [];
        }

        return array_values(array_unique(array_filter($modules, function ($module) {
            return is_string($module) && $module !== '';
        })));
    }
}
