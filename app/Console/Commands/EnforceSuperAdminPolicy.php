<?php

namespace App\Console\Commands;

use App\User;
use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class EnforceSuperAdminPolicy extends Command
{
    use ConfirmableTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admin:enforce-superuser-policy
                            {--force : Force the operation to run when in production}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Enforce system superuser policy: only ADMINISTRATOR_USERNAMES keep role=admin; others are demoted to user.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (! $this->confirmToProceed()) {
            return 1;
        }

        $administratorList = (string) config('constants.administrator_usernames', '');
        if (trim($administratorList) === '') {
            $this->error('ADMINISTRATOR_USERNAMES is empty. Nothing to enforce.');
            return 1;
        }

        $usernames = array_values(array_filter(array_map(function ($username) {
            return trim($username);
        }, explode(',', $administratorList))));

        if (empty($usernames)) {
            $this->error('No valid usernames found in ADMINISTRATOR_USERNAMES.');
            return 1;
        }

        $canonicalUsername = $usernames[0];
        $email = env('SUPERADMIN_EMAIL', 'admin@example.com');
        $password = env('SUPERADMIN_PASSWORD', 'admin123');

        $this->info('Enforcing superuser policy...');
        $this->line('Allowed superuser usernames: ' . implode(', ', $usernames));

        try {
            DB::beginTransaction();

            $superAdmin = User::withTrashed()->firstOrNew(['username' => $canonicalUsername]);
            $isNew = ! $superAdmin->exists;

            $superAdmin->fill([
                'surname' => 'Mr',
                'first_name' => $canonicalUsername,
                'last_name' => null,
                'email' => $superAdmin->exists ? $superAdmin->email : $email,
                'password' => $superAdmin->exists ? $superAdmin->password : Hash::make($password),
                'language' => 'en',
                'role' => 'admin',
                'user_type' => 'user',
            ]);

            if ($superAdmin->trashed()) {
                $superAdmin->restore();
            }

            $superAdmin->save();

            User::where('username', $canonicalUsername)->update(['role' => 'admin']);

            $usersToDemote = User::where('role', 'admin')
                ->whereNotIn('username', $usernames)
                ->pluck('username')
                ->filter()
                ->values();

            $demotedCount = User::where('role', 'admin')
                ->whereNotIn('username', $usernames)
                ->update(['role' => 'user']);

            if ($superAdmin->business_id) {
                $roleName = 'Admin#' . $superAdmin->business_id;

                if (! Role::where('name', $roleName)->exists()) {
                    Role::create([
                        'name' => $roleName,
                        'business_id' => $superAdmin->business_id,
                        'guard_name' => 'web',
                        'is_default' => 1,
                    ]);
                }

                if (! $superAdmin->hasRole($roleName)) {
                    $superAdmin->assignRole($roleName);
                }
            }

            DB::commit();

            $this->info(($isNew ? 'Created' : 'Verified') . " canonical superuser '{$canonicalUsername}'.");
            $this->info("Demoted {$demotedCount} non-approved admin user(s).");

            if ($usersToDemote->isNotEmpty()) {
                $this->line('Demoted usernames: ' . $usersToDemote->implode(', '));
            }

            $finalAdmins = User::where('role', 'admin')->pluck('username')->filter()->values();
            $this->line('Current users with role=admin: ' . ($finalAdmins->isEmpty() ? '(none)' : $finalAdmins->implode(', ')));

            return 0;
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error('Failed to enforce superuser policy: ' . $e->getMessage());
            return 1;
        }
    }
}
