<?php

namespace Database\Seeders;

use App\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

/**
 * SuperAdminSeeder
 *
 * Ensures the system-level superuser exists with the correct role configuration.
 *
 * Two-tier admin model:
 *   Tier 1 – System superuser:  role = 'admin' on users table
 *                                username listed in ADMINISTRATOR_USERNAMES env var
 *                                → bypasses subscription checks, can manage modules/backup
 *   Tier 2 – Business admin:    Spatie role Admin#<business_id>
 *                                → full rights within their own business only
 *                                → subject to subscription requirements
 *
 * Usage:
 *   php artisan db:seed --class=SuperAdminSeeder
 *
 * The username / email / password are read from environment variables so this
 * seeder is safe to commit.  Set the following in .env:
 *   ADMINISTRATOR_USERNAMES=Admin          # comma-separated if multiple
 *   SUPERADMIN_EMAIL=admin@example.com
 *   SUPERADMIN_PASSWORD=changeme
 */
class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $administratorList = config('constants.administrator_usernames', '');

        if (empty($administratorList)) {
            $this->command->warn(
                'ADMINISTRATOR_USERNAMES is not set in .env — skipping SuperAdminSeeder.'
            );
            return;
        }

        // Support a comma-separated list; seed the first entry as the canonical superuser.
        $usernames = array_map('trim', explode(',', $administratorList));
        $username  = $usernames[0];

        $email    = env('SUPERADMIN_EMAIL', 'admin@bremac.co.ke');
        $password = env('SUPERADMIN_PASSWORD', 'Admin@123');

        // ------------------------------------------------------------------
        // 1. Create or update the superuser record
        // ------------------------------------------------------------------
        /** @var User $superAdmin */
        $superAdmin = User::withTrashed()->firstOrNew(['username' => $username]);

        $isNew = ! $superAdmin->exists;

        $superAdmin->fill([
            'surname'    => 'Mr',
            'first_name' => $username,
            'last_name'  => null,
            'email'      => $email,
            'password'   => Hash::make($password),
            'language'   => 'en',
            'role'       => 'admin',   // Tier-1: system superuser marker
            'user_type'  => 'user',
        ]);

        // Restore if soft-deleted
        if ($superAdmin->trashed()) {
            $superAdmin->restore();
        }

        $superAdmin->save();

        // ------------------------------------------------------------------
        // 2. Ensure role='admin' is set (guards against accidental reset)
        // ------------------------------------------------------------------
        User::where('username', $username)->update(['role' => 'admin']);

        // ------------------------------------------------------------------
        // 3. Strip role='admin' from any non-superuser accounts
        //    Business admins are identified solely by their Spatie Admin# role;
        //    they should NOT have role='admin' on the users table.
        // ------------------------------------------------------------------
        User::where('role', 'admin')
            ->whereNotIn('username', $usernames)
            ->update(['role' => 'user']);

        // ------------------------------------------------------------------
        // 4. Assign the Spatie Admin#<business_id> role if the superuser
        //    owns a business (so they also get full business-level perms).
        // ------------------------------------------------------------------
        if ($superAdmin->business_id) {
            $roleName = 'Admin#' . $superAdmin->business_id;

            if (! Role::where('name', $roleName)->exists()) {
                Role::create([
                    'name'        => $roleName,
                    'business_id' => $superAdmin->business_id,
                    'guard_name'  => 'web',
                    'is_default'  => 1,
                ]);
            }

            if (! $superAdmin->hasRole($roleName)) {
                $superAdmin->assignRole($roleName);
            }
        }

        $action = $isNew ? 'Created' : 'Verified';
        $this->command->info(
            "{$action} system superuser '{$username}' (role=admin, ADMINISTRATOR_USERNAMES='{$administratorList}')."
        );
    }
}
