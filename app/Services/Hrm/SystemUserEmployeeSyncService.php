<?php

namespace App\Services\Hrm;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class SystemUserEmployeeSyncService
{
    public function sync(?int $businessId = null): int
    {
        try {
            if (! Schema::hasTable('employees') || ! Schema::hasTable('users')) {
                return 0;
            }

            $employeeHasSystemUser = Schema::hasColumn('employees', 'system_user_id');
            $employeeHasIsSystem = Schema::hasColumn('employees', 'is_system_user');
            $employeeHasSyncDisabled = Schema::hasColumn('employees', 'sync_disabled');
            $employeeHasBusinessId = Schema::hasColumn('employees', 'business_id');
            $employeeHasCompanyId = Schema::hasColumn('employees', 'company_id');
            $employeeHasDeletedAt = Schema::hasColumn('employees', 'deleted_at');

            $usersHasBusinessId = Schema::hasColumn('users', 'business_id');
            $usersHasDeletedAt = Schema::hasColumn('users', 'deleted_at');
            $usersHasAllowLogin = Schema::hasColumn('users', 'allow_login');
            $usersHasStatus = Schema::hasColumn('users', 'status');
            $usersHasUserType = Schema::hasColumn('users', 'user_type');

            if (! $employeeHasSystemUser) {
                return 0;
            }

            $usersQuery = DB::table('users')
                ->select(['id', 'first_name', 'last_name', 'username', 'email', 'contact_no']);

            if ($usersHasDeletedAt) {
                $usersQuery->whereNull('deleted_at');
            }

            if ($usersHasAllowLogin) {
                $usersQuery->where('allow_login', 1);
            }

            if ($usersHasStatus) {
                $usersQuery->where('status', 'active');
            }

            if ($usersHasUserType) {
                $usersQuery->whereIn('user_type', ['admin', 'user']);
            }

            if ($businessId && $usersHasBusinessId) {
                $usersQuery->where('business_id', $businessId);
            }

            $users = $usersQuery->get();

            if ($users->isEmpty()) {
                return 0;
            }

            $companyId = null;
            if ($employeeHasCompanyId && Schema::hasTable('companies') && Schema::hasColumn('companies', 'business_id') && $businessId) {
                $companyId = DB::table('companies')->where('business_id', $businessId)->value('id');
            }

            $defaultLeave = (int) config('hrm.default_annual_leave', 21);
            $createdOrLinked = 0;

            foreach ($users as $user) {
                try {
                    $employee = DB::table('employees')->where('system_user_id', $user->id)->first();

                    if ($employee && $employeeHasSyncDisabled && (int) ($employee->sync_disabled ?? 0) === 1) {
                        continue;
                    }

                    if (! $employee) {
                        $employeeMatchQuery = DB::table('employees');

                        $employeeMatchQuery->where(function ($q) use ($user) {
                            if (! empty($user->email)) {
                                $q->orWhere('email', $user->email);
                            }
                            if (! empty($user->username)) {
                                $q->orWhere('username', $user->username);
                            }
                        });

                        if ($businessId && $employeeHasBusinessId) {
                            $employeeMatchQuery->where('business_id', $businessId);
                        }

                        if ($employeeHasDeletedAt) {
                            $employeeMatchQuery->orderByRaw('deleted_at is null desc');
                        }

                        $employee = $employeeMatchQuery->first();
                    }

                    if ($employee) {
                        $update = [];
                        if ($employeeHasSystemUser && empty($employee->system_user_id)) {
                            $update['system_user_id'] = $user->id;
                        }
                        if ($employeeHasIsSystem) {
                            $update['is_system_user'] = 1;
                        }
                        if ($employeeHasSyncDisabled) {
                            $update['sync_disabled'] = 0;
                        }
                        if ($employeeHasDeletedAt && ! empty($employee->deleted_at)) {
                            $update['deleted_at'] = null;
                        }
                        if (! empty($update)) {
                            $update['updated_at'] = now();
                            DB::table('employees')->where('id', $employee->id)->update($update);
                            $createdOrLinked++;
                        }
                        continue;
                    }

                    $payload = [
                        'firstname' => trim((string) ($user->first_name ?? 'System')) ?: 'System',
                        'lastname' => trim((string) ($user->last_name ?? 'User')) ?: 'User',
                        'username' => $user->username ?: trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')),
                        'email' => $user->email,
                        'phone' => $user->contact_no,
                        'joining_date' => now()->toDateString(),
                        'total_leave' => $defaultLeave,
                        'remaining_leave' => $defaultLeave,
                        'created_at' => now(),
                        'updated_at' => now(),
                        'system_user_id' => $user->id,
                    ];

                    if ($employeeHasIsSystem) {
                        $payload['is_system_user'] = 1;
                    }
                    if ($employeeHasSyncDisabled) {
                        $payload['sync_disabled'] = 0;
                    }
                    if ($businessId && $employeeHasBusinessId) {
                        $payload['business_id'] = $businessId;
                    }
                    if ($companyId && $employeeHasCompanyId) {
                        $payload['company_id'] = $companyId;
                    }

                    if (! empty($user->email)) {
                        $existingByEmail = DB::table('employees')->where('email', $user->email)->first();
                        if ($existingByEmail) {
                            $update = [
                                'system_user_id' => $user->id,
                                'updated_at' => now(),
                            ];

                            if ($employeeHasIsSystem) {
                                $update['is_system_user'] = 1;
                            }
                            if ($employeeHasSyncDisabled) {
                                $update['sync_disabled'] = 0;
                            }
                            if ($employeeHasDeletedAt && ! empty($existingByEmail->deleted_at)) {
                                $update['deleted_at'] = null;
                            }
                            if ($businessId && $employeeHasBusinessId && empty($existingByEmail->business_id)) {
                                $update['business_id'] = $businessId;
                            }
                            if ($companyId && $employeeHasCompanyId && empty($existingByEmail->company_id)) {
                                $update['company_id'] = $companyId;
                            }

                            DB::table('employees')->where('id', $existingByEmail->id)->update($update);
                            $createdOrLinked++;
                            continue;
                        }
                    }

                    DB::table('employees')->insert($payload);
                    $createdOrLinked++;
                } catch (\Throwable $userError) {
                    Log::warning('HRM system-user sync skipped one user', [
                        'business_id' => $businessId,
                        'user_id' => $user->id ?? null,
                        'email' => $user->email ?? null,
                        'message' => $userError->getMessage(),
                    ]);
                }
            }

            return $createdOrLinked;
        } catch (\Throwable $e) {
            Log::error('HRM system-user sync failed', [
                'business_id' => $businessId,
                'message' => $e->getMessage(),
            ]);

            return 0;
        }
    }
}
