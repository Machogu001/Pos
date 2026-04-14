<?php

namespace Tests\Feature;

use App\User;
use App\Models\Attendance;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Holiday;
use App\Models\OfficeShift;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class HrmAttendanceHolidayFeatureTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware();
        $this->rebuildSchema();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_holiday_html_crud_flow_works_end_to_end(): void
    {
        $user = $this->createUserWithPermissions(['hrm.access', 'hrm.holidays']);
        $company = Company::create(['name' => 'Holiday Co']);

        $this->actingAs($user)
            ->withSession($this->hrmSession())
            ->get(route('hrm.holidays.create'))
            ->assertOk()
            ->assertSee('Create Holiday');

        $storeResponse = $this->actingAs($user)
            ->withSession($this->hrmSession())
            ->post(route('hrm.holidays.store'), [
                'company_id' => $company->id,
                'title' => 'Labour Day',
                'start_date' => '2026-05-01',
                'end_date' => '2026-05-01',
                'description' => 'Public holiday',
            ]);

        $storeResponse->assertRedirect(route('hrm.holidays.index'));

        $holiday = Holiday::query()->first();
        $this->assertNotNull($holiday);
        $this->assertSame('Labour Day', $holiday->title);

        $this->actingAs($user)
            ->withSession($this->hrmSession())
            ->get(route('hrm.holidays.index'))
            ->assertOk()
            ->assertSee('Holiday Register')
            ->assertSee('Labour Day');

        $this->actingAs($user)
            ->withSession($this->hrmSession())
            ->get(route('hrm.holidays.edit', $holiday->id))
            ->assertOk()
            ->assertSee('Edit Holiday');

        $updateResponse = $this->actingAs($user)
            ->withSession($this->hrmSession())
            ->put(route('hrm.holidays.update', $holiday->id), [
                'company_id' => $company->id,
                'title' => 'Labour Day Updated',
                'start_date' => '2026-05-01',
                'end_date' => '2026-05-02',
                'description' => 'Updated holiday window',
            ]);

        $updateResponse->assertRedirect(route('hrm.holidays.index'));

        $holiday->refresh();
        $this->assertSame('Labour Day Updated', $holiday->title);
        $this->assertSame('2026-05-02', (string) $holiday->end_date);

        $deleteResponse = $this->actingAs($user)
            ->withSession($this->hrmSession())
            ->delete(route('hrm.holidays.destroy', $holiday->id));

        $deleteResponse->assertRedirect(route('hrm.holidays.index'));
        $this->assertNotNull($holiday->fresh()->deleted_at);
    }

    public function test_attendance_html_flow_lists_and_updates_records(): void
    {
        $user = $this->createUserWithPermissions(['hrm.access', 'hrm.attendances']);
        $company = Company::create(['name' => 'Attendance Co']);
        $officeShift = OfficeShift::create([
            'name' => 'Main Shift',
            'company_id' => $company->id,
            'monday_in' => '09:00am',
            'monday_out' => '17:00pm',
            'tuesday_in' => '09:00am',
            'tuesday_out' => '17:00pm',
            'wednesday_in' => '09:00am',
            'wednesday_out' => '17:00pm',
            'thursday_in' => '09:00am',
            'thursday_out' => '17:00pm',
            'friday_in' => '09:00am',
            'friday_out' => '17:00pm',
            'saturday_in' => '09:00am',
            'saturday_out' => '13:00pm',
            'sunday_in' => '09:00am',
            'sunday_out' => '13:00pm',
        ]);
        $employee = Employee::create([
            'firstname' => 'Alex',
            'lastname' => 'Worker',
            'username' => 'alex.worker',
            'gender' => 'male',
            'company_id' => $company->id,
            'office_shift_id' => $officeShift->id,
        ]);

        $this->actingAs($user)
            ->withSession($this->hrmSession())
            ->get(route('hrm.attendances.create'))
            ->assertOk()
            ->assertSee('Create Attendance');

        $storeResponse = $this->actingAs($user)
            ->withSession($this->hrmSession())
            ->post(route('hrm.attendances.store'), [
                'company_id' => $company->id,
                'employee_id' => $employee->id,
                'date' => '2026-04-10',
                'clock_in' => '09:15',
                'clock_out' => '17:20',
            ]);

        $storeResponse->assertRedirect(route('hrm.attendances.index'));

        $attendance = Attendance::query()->first();
        $this->assertNotNull($attendance);
        $this->assertSame('alex.worker', $attendance->employee->username);
        $this->assertSame('08:05', $attendance->total_work);
        $this->assertSame('00:15', $attendance->late_time);
        $this->assertSame('00:20', $attendance->overtime);

        $this->actingAs($user)
            ->withSession($this->hrmSession())
            ->get(route('hrm.attendances.index'))
            ->assertOk()
            ->assertSee('Attendance Register')
            ->assertSee('alex.worker');

        $this->actingAs($user)
            ->withSession($this->hrmSession())
            ->get(route('hrm.attendances.edit', $attendance->id))
            ->assertOk()
            ->assertSee('Edit Attendance');

        $updateResponse = $this->actingAs($user)
            ->withSession($this->hrmSession())
            ->put(route('hrm.attendances.update', $attendance->id), [
                'company_id' => $company->id,
                'employee_id' => $employee->id,
                'date' => '2026-04-10',
                'clock_in' => '08:55',
                'clock_out' => '16:40',
            ]);

        $updateResponse->assertRedirect(route('hrm.attendances.index'));

        $attendance->refresh();
        $this->assertSame('07:45', $attendance->total_work);
        $this->assertSame('00:00', $attendance->late_time);
        $this->assertSame('00:20', $attendance->depart_early);
        $this->assertSame('00:00', $attendance->overtime);

        $deleteResponse = $this->actingAs($user)
            ->withSession($this->hrmSession())
            ->delete(route('hrm.attendances.destroy', $attendance->id));

        $deleteResponse->assertRedirect(route('hrm.attendances.index'));
        $this->assertNotNull($attendance->fresh()->deleted_at);
    }

    public function test_leave_index_search_uses_employee_company_and_department_when_leave_columns_are_missing(): void
    {
        $user = $this->createUserWithPermissions(['hrm.access', 'hrm.leaves']);
        $company = Company::create(['name' => 'TI50GM3ESC Logistics']);
        $departmentId = \DB::table('departments')->insertGetId([
            'company_id' => $company->id,
            'department' => 'Dispatch',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $leaveTypeId = \DB::table('leave_types')->insertGetId([
            'name' => 'Annual Leave',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $employee = Employee::create([
            'firstname' => 'Tina',
            'lastname' => 'Logistics',
            'username' => 'tina.logistics',
            'gender' => 'female',
            'company_id' => $company->id,
            'department_id' => $departmentId,
        ]);

        \DB::table('leaves')->insert([
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveTypeId,
            'start_date' => '2026-04-14',
            'end_date' => '2026-04-16',
            'days' => 3,
            'status' => 'approved',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($user)
            ->withSession($this->hrmSession())
            ->getJson(route('hrm.leaves.index', ['search' => 'TI50GM3ESC']));

        $response->assertOk()
            ->assertJsonPath('totalRows', 1)
            ->assertJsonPath('leaves.0.employee_name', 'tina.logistics')
            ->assertJsonPath('leaves.0.company_name', 'TI50GM3ESC Logistics')
            ->assertJsonPath('leaves.0.department_name', 'Dispatch');
    }

    private function rebuildSchema(): void
    {
        Schema::dropIfExists('leaves');
        Schema::dropIfExists('leave_types');
        Schema::dropIfExists('departments');
        Schema::dropIfExists('attendances');
        Schema::dropIfExists('holidays');
        Schema::dropIfExists('employees');
        Schema::dropIfExists('office_shifts');
        Schema::dropIfExists('companies');
        Schema::dropIfExists('model_has_permissions');
        Schema::dropIfExists('role_has_permissions');
        Schema::dropIfExists('model_has_roles');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('notifications');
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

        Schema::create('permissions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();
        });

        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
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

        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();
        });

        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->string('department');
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();
        });

        Schema::create('leave_types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();
        });

        Schema::create('office_shifts', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->string('monday_in')->nullable();
            $table->string('monday_out')->nullable();
            $table->string('tuesday_in')->nullable();
            $table->string('tuesday_out')->nullable();
            $table->string('wednesday_in')->nullable();
            $table->string('wednesday_out')->nullable();
            $table->string('thursday_in')->nullable();
            $table->string('thursday_out')->nullable();
            $table->string('friday_in')->nullable();
            $table->string('friday_out')->nullable();
            $table->string('saturday_in')->nullable();
            $table->string('saturday_out')->nullable();
            $table->string('sunday_in')->nullable();
            $table->string('sunday_out')->nullable();
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
            $table->unsignedBigInteger('department_id')->nullable();
            $table->unsignedBigInteger('office_shift_id')->nullable();
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();
        });

        Schema::create('leaves', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id');
            $table->unsignedBigInteger('leave_type_id')->nullable();
            $table->date('start_date');
            $table->date('end_date');
            $table->integer('days')->default(1);
            $table->text('reason')->nullable();
            $table->string('status')->default('pending');
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();
        });

        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('employee_id')->nullable();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->date('date')->nullable();
            $table->string('clock_in')->nullable();
            $table->string('clock_out')->nullable();
            $table->string('clock_in_ip')->nullable();
            $table->string('clock_out_ip')->nullable();
            $table->integer('clock_in_out')->default(0);
            $table->string('depart_early')->nullable();
            $table->string('late_time')->nullable();
            $table->string('overtime')->nullable();
            $table->string('total_work')->nullable();
            $table->string('total_rest')->nullable();
            $table->string('status')->nullable();
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();
        });

        Schema::create('holidays', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->string('title');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->text('description')->nullable();
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();
        });
    }

    private function createUserWithPermissions(array $permissions): User
    {
        \DB::table('business')->insert([
            'id' => 1,
            'name' => 'HRM Test Business',
            'enabled_modules' => json_encode(['hrm']),
            'common_settings' => json_encode([]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $user = User::create([
            'business_id' => 1,
            'surname' => 'HRM',
            'first_name' => 'Test',
            'last_name' => 'User',
            'username' => 'hrm-test-user-' . uniqid(),
            'email' => uniqid('hrm-', true) . '@example.com',
            'password' => Hash::make('secret'),
            'language' => 'en',
            'status' => 'active',
            'allow_login' => 1,
            'user_type' => 'user',
        ]);

        foreach ($permissions as $permissionName) {
            $permissionId = \DB::table('permissions')->insertGetId([
                'name' => $permissionName,
                'guard_name' => 'web',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            \DB::table('model_has_permissions')->insert([
                'permission_id' => $permissionId,
                'model_type' => User::class,
                'model_id' => $user->id,
            ]);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $user;
    }

    private function hrmSession(): array
    {
        return [
            'business' => [
                'id' => 1,
                'name' => 'HRM Test Business',
                'enabled_modules' => ['hrm'],
            ],
            'currency' => [
                'id' => 1,
                'code' => 'KES',
                'symbol' => 'KSh',
                'thousand_separator' => ',',
                'decimal_separator' => '.',
            ],
        ];
    }
}