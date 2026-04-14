<?php

namespace Tests\Feature;

use App\AdminSetting;
use App\User;
use App\Models\Company;
use App\Models\Employee;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class HrmPayrollFeatureTest extends TestCase
{
    use WithFaker;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware();
        $this->rebuildSchema();
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Gate::define('hrm.access', fn ($user = null) => true);
        Gate::define('hrm.payrolls', fn ($user = null) => true);
    }

    public function test_store_calculates_payroll_components_when_breakdown_inputs_are_omitted(): void
    {
        $user = $this->createUser();
        $company = Company::create([
            'name' => 'Acme HR',
            'nssf_percent' => 0.06,
            'shif_percent' => 0.02,
            'housing_percent' => 0.03,
            'tax_percent' => 0.10,
            'personal_relief' => 1000,
        ]);
        $employee = Employee::create([
            'firstname' => 'Jane',
            'lastname' => 'Payroll',
            'username' => 'jane.payroll',
            'gender' => 'female',
            'company_id' => $company->id,
            'basic_salary' => 100000,
        ]);

        AdminSetting::create([
            'payroll_auto_post' => 0,
            'payroll_nssf_percent' => 0.01,
            'payroll_shif_percent' => 0.01,
            'payroll_housing_percent' => 0.01,
            'payroll_tax_percent' => 0.01,
            'payroll_personal_relief' => 50,
        ]);

        $response = $this->actingAs($user)->post(route('hrm.payrolls.store'), [
            'company_id' => $company->id,
            'employee_id' => [$employee->id],
            'period_start' => '2026-04-01',
            'period_end' => '2026-04-30',
            'gross' => [
                $employee->id => 100000,
            ],
            'basic_pay' => [
                $employee->id => 100000,
            ],
            'deductions' => [
                $employee->id => 5000,
            ],
        ]);

        $response->assertRedirect(route('hrm.payrolls.index'));

        $payroll = \DB::table('hrm_payrolls')->where('employee_id', $employee->id)->first();

        $this->assertNotNull($payroll);
        $this->assertSame('6000', (string) $payroll->nssf);
        $this->assertSame('2000', (string) $payroll->shif);
        $this->assertSame('3000', (string) $payroll->housing_levy);
        $this->assertSame('89000', (string) $payroll->taxable_pay);
        $this->assertSame('8900', (string) $payroll->income_tax);
        $this->assertSame('1000', (string) $payroll->personal_relief);
        $this->assertSame('7900', (string) $payroll->paye);
        $this->assertSame('92100', (string) $payroll->pay_after_tax);
        $this->assertSame('87100', (string) $payroll->net);
    }

    public function test_store_blocks_duplicate_payroll_for_same_employee_and_period(): void
    {
        $user = $this->createUser();
        $company = Company::create(['name' => 'Acme HR']);
        $employee = Employee::create([
            'firstname' => 'John',
            'lastname' => 'Duplicate',
            'username' => 'john.duplicate',
            'gender' => 'male',
            'company_id' => $company->id,
        ]);

        \DB::table('hrm_payrolls')->insert([
            'company_id' => $company->id,
            'employee_id' => $employee->id,
            'period_start' => '2026-04-01',
            'period_end' => '2026-04-30',
            'basic_pay' => 50000,
            'gross' => 50000,
            'deductions' => 0,
            'net' => 50000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->from(route('hrm.payrolls.create'))->actingAs($user)->post(route('hrm.payrolls.store'), [
            'company_id' => $company->id,
            'employee_id' => [$employee->id],
            'period_start' => '2026-04-01',
            'period_end' => '2026-04-30',
            'gross' => [
                $employee->id => 50000,
            ],
            'basic_pay' => [
                $employee->id => 50000,
            ],
            'deductions' => [
                $employee->id => 0,
            ],
        ]);

        $response->assertRedirect(route('hrm.payrolls.create'));
        $response->assertSessionHasErrors('employee_id');
        $this->assertSame(1, \DB::table('hrm_payrolls')->count());
    }

    public function test_update_uses_same_normalized_calculation_path_as_store(): void
    {
        $user = $this->createUser();
        $company = Company::create([
            'name' => 'Acme HR',
            'nssf_percent' => 0.05,
            'shif_percent' => 0.02,
            'housing_percent' => 0.01,
            'tax_percent' => 0.10,
            'personal_relief' => 1200,
        ]);
        $employee = Employee::create([
            'firstname' => 'Mary',
            'lastname' => 'Update',
            'username' => 'mary.update',
            'gender' => 'female',
            'company_id' => $company->id,
        ]);

        $payrollId = \DB::table('hrm_payrolls')->insertGetId([
            'company_id' => $company->id,
            'employee_id' => $employee->id,
            'period_start' => '2026-03-01',
            'period_end' => '2026-03-31',
            'basic_pay' => 40000,
            'gross' => 40000,
            'deductions' => 0,
            'net' => 40000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        AdminSetting::create([
            'payroll_auto_post' => 0,
            'payroll_nssf_percent' => 0.01,
            'payroll_shif_percent' => 0.01,
            'payroll_housing_percent' => 0.01,
            'payroll_tax_percent' => 0.01,
            'payroll_personal_relief' => 50,
        ]);

        $response = $this->from(route('hrm.payrolls.edit', $payrollId))->actingAs($user)->put(route('hrm.payrolls.update', $payrollId), [
            'company_id' => $company->id,
            'employee_id' => $employee->id,
            'period_start' => '2026-03-01',
            'period_end' => '2026-03-31',
            'gross' => 80000,
            'basic_pay' => 80000,
            'deductions' => 3000,
        ]);

        $response->assertRedirect(route('hrm.payrolls.index'));

        $payroll = \DB::table('hrm_payrolls')->where('id', $payrollId)->first();

        $this->assertSame('4000', (string) $payroll->nssf);
        $this->assertSame('1600', (string) $payroll->shif);
        $this->assertSame('800', (string) $payroll->housing_levy);
        $this->assertSame('73600', (string) $payroll->taxable_pay);
        $this->assertSame('7360', (string) $payroll->income_tax);
        $this->assertSame('1200', (string) $payroll->personal_relief);
        $this->assertSame('6160', (string) $payroll->paye);
        $this->assertSame('73840', (string) $payroll->pay_after_tax);
        $this->assertSame('70840', (string) $payroll->net);
    }

    private function rebuildSchema(): void
    {
        Schema::dropIfExists('hrm_payrolls');
        Schema::dropIfExists('employees');
        Schema::dropIfExists('companies');
        Schema::dropIfExists('admin_settings');
        Schema::dropIfExists('model_has_permissions');
        Schema::dropIfExists('role_has_permissions');
        Schema::dropIfExists('model_has_roles');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('users');
        Schema::dropIfExists('business');

        Schema::create('business', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->json('enabled_modules')->nullable();
            $table->json('common_settings')->nullable();
            $table->timestamps();
        });

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id')->nullable();
            $table->string('surname')->nullable();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('username')->unique();
            $table->string('email')->nullable();
            $table->string('password')->nullable();
            $table->string('language')->nullable();
            $table->string('status')->default('active');
            $table->boolean('allow_login')->default(true);
            $table->string('user_type')->default('user');
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->decimal('nssf_percent', 8, 5)->nullable();
            $table->decimal('shif_percent', 8, 5)->nullable();
            $table->decimal('housing_percent', 8, 5)->nullable();
            $table->decimal('tax_percent', 8, 5)->nullable();
            $table->decimal('personal_relief', 15, 2)->nullable();
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();
        });

        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('firstname')->nullable();
            $table->string('lastname')->nullable();
            $table->string('username')->nullable();
            $table->string('gender')->nullable();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->decimal('basic_salary', 15, 2)->nullable();
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();
        });

        Schema::create('admin_settings', function (Blueprint $table) {
            $table->id();
            $table->decimal('payroll_nssf_percent', 8, 5)->nullable();
            $table->decimal('payroll_shif_percent', 8, 5)->nullable();
            $table->decimal('payroll_housing_percent', 8, 5)->nullable();
            $table->decimal('payroll_tax_percent', 8, 5)->nullable();
            $table->decimal('payroll_personal_relief', 15, 2)->nullable();
            $table->boolean('payroll_auto_post')->default(false);
            $table->timestamps();
        });

        Schema::create('permissions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();
        });

        Schema::create('roles', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();
        });

        Schema::create('model_has_permissions', function (Blueprint $table) {
            $table->unsignedBigInteger('permission_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
        });

        Schema::create('model_has_roles', function (Blueprint $table) {
            $table->unsignedBigInteger('role_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
        });

        Schema::create('role_has_permissions', function (Blueprint $table) {
            $table->unsignedBigInteger('permission_id');
            $table->unsignedBigInteger('role_id');
        });

        Schema::create('hrm_payrolls', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('employee_id')->nullable();
            $table->date('period_start')->nullable();
            $table->date('period_end')->nullable();
            $table->decimal('basic_pay', 15, 2)->default(0);
            $table->decimal('gross', 15, 2)->default(0);
            $table->decimal('nssf', 15, 2)->default(0);
            $table->decimal('shif', 15, 2)->default(0);
            $table->decimal('housing_levy', 15, 2)->default(0);
            $table->decimal('taxable_pay', 15, 2)->default(0);
            $table->decimal('income_tax', 15, 2)->default(0);
            $table->decimal('personal_relief', 15, 2)->default(0);
            $table->decimal('paye', 15, 2)->default(0);
            $table->decimal('pay_after_tax', 15, 2)->default(0);
            $table->decimal('deductions', 15, 2)->default(0);
            $table->decimal('net', 15, 2)->default(0);
            $table->boolean('posted_to_accounts')->default(false);
            $table->dateTime('posted_at')->nullable();
            $table->unsignedBigInteger('debit_account_transaction_id')->nullable();
            $table->unsignedBigInteger('credit_account_transaction_id')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();
        });
    }

    private function createUser(): User
    {
        \DB::table('business')->insert([
            'id' => 1,
            'name' => 'Payroll Test Business',
            'enabled_modules' => json_encode(['hrm']),
            'common_settings' => json_encode([]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $user = User::create([
            'business_id' => 1,
            'surname' => 'Test',
            'first_name' => 'Payroll',
            'last_name' => 'User',
            'username' => 'payroll-test-user-'.$this->faker->unique()->numerify('###'),
            'email' => $this->faker->unique()->safeEmail(),
            'password' => Hash::make('secret'),
            'language' => 'en',
            'status' => 'active',
            'allow_login' => 1,
            'user_type' => 'user',
        ]);

        $permissionIds = [];
        foreach (['hrm.access', 'hrm.payrolls'] as $permissionName) {
            $permissionIds[] = \DB::table('permissions')->insertGetId([
                'name' => $permissionName,
                'guard_name' => 'web',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        foreach ($permissionIds as $permissionId) {
            \DB::table('model_has_permissions')->insert([
                'permission_id' => $permissionId,
                'model_type' => User::class,
                'model_id' => $user->id,
            ]);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $user;
    }
}